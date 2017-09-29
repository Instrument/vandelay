#!/bin/bash

usage() { 
    echo "USAGE:" 1>&2
    echo "This script provisions a Cloud SQL instance and a Google Compute Engine instance from scratch, or"
    echo "redeploys code and data to existing servers. All options are required." 1>&2
    echo "Usage details: $0 -a <GCE admin username> -e <GCE instance name> -s <Cloud SQL instance name> -u <db admin username> -p <admin password>" 1>&2
    echo ""
    echo "For example, $0 -a gceuser -e myubuntu -s mydbserver -u craftuser -p supersecurepassword" 1>&2
    echo ""; exit 0
}


while getopts ":a:e:s:u:p:" o; do
        case "${o}" in
        a)
            gce_username=${OPTARG}
            [[ -n "$gce_username" ]] || usage
            ;;
        e)
            gce_name=${OPTARG}
            [[ -n "$gce_name" ]] || usage
            ;;
        s)
            sql_instance_name=${OPTARG}
            [[ -n "$sql_instance_name" ]] || usage
            ;;
        u)
            sql_username=${OPTARG}
            [[ -n "$sql_username" ]] || usage
            ;;
        p)
            sql_password=${OPTARG}
            [[ -n "$sql_password" ]] || usage
            ;;
        *)
            usage
            ;;
    esac
done
shift $((OPTIND-1))


if [ -z "$gce_username" ] || [ -z "$gce_name" ] || [ -z "$sql_instance_name" ] || [ -z "$sql_username" ] || [ -z "$sql_password" ]; then
    usage
else

    # collect your files

    rm -r xfr
    rm -r craftfiles

    mkdir xfr
    mkdir craftfiles


    cp bashrc.template bashrc
    echo "Creating fresh bashrc file" 1>&2

    # copy craft and config files into xfr/:
    echo "Copying content and config files into the xfr folder" 1>&2
    cp -r ../public craftfiles/
    cp -r ../craft craftfiles/
    rm -r craftfiles/craft/storage
    zip -r xfr/craftfiles.zip craftfiles/
    cp conf_files/* xfr/
    cp README.md xfr/


    echo "Copying most recently modded SQL dump into the xfr folder" 1>&2
    newSQL=$(ls -t ../data/snapchat-craft*.sql | head -n1)
    cp $newSQL ./craft_db.sql
    # sed -i the craft_db.sql to use InnoDB
    sed -i '' "s/MyISAM/InnoDB/g" ./craft_db.sql
    zip xfr/craft_db.zip ./craft_db.sql



    # create a new Ubunutu server, if it doesn't already exist
    gce_exists=$(gcloud compute instances list --filter="NAME:$gce_name")

    if [ "$gce_exists" == "" ]; then
        echo "Creating a new Ubuntu server instance called '$gce_name'" 1>&2
        gcloud compute instances create $gce_name --image-family=ubuntu-1604-lts \
            --image-project=ubuntu-os-cloud \
            --tags=http-server,https-server \
            --zone us-west1-b

        #this a new install, so you'll need the long form of install.sh
        echo "using the long form of the install script"
        cp install-newdeploy.sh xfr/install.sh
    else
        #this a redeploy, so you'll need the short form of install.sh
        echo "using the short form of the install script"
        cp install-redeploy.sh xfr/install.sh
    fi


    # ...regardless, get its IP address
    gce_ip=$(gcloud compute instances list --filter="NAME:$gce_name"  --format='value(networkInterfaces[0].accessConfigs[0].natIP)')

    # give your new install script the proper perms
    chmod 700 xfr/install.sh


    # spin up a new gcloud sql instance, if it doesn't already exist. keep 'authorized-networks' at the end of this command!
    gsql_exists=$(gcloud sql instances list --filter="NAME:sql_instance_name") | sed 's/ //g'

    if [ "$gsql_exists" == "" ] || [ "$gsql_exists" == "Listed 0 items.\n" ]; then
        echo "Creating a new MySQL 5.7 server instance called '$sql_instance_name' - grab a cup of coffee, this will probably take a while." 1>&2
        gcloud sql instances create $sql_instance_name \
            --database-flags=sql_mode=TRADITIONAL \
            --database-version="MYSQL_5_7" \
            --region="us-west1" \
            --gce-zone="us-west1-b" \
            --authorized-networks=$gce_ip

        # set up the craft user
        echo "Creating new db user $sql_username on $sql_instance_name" 1>&2
        gcloud sql users create $sql_username $gce_ip --instance=$sql_instance_name --password=$sql_password
    fi

    # regardless get its IP address
    sql_ip=$(gcloud sql instances list --filter="NAME:$sql_instance_name" --format='value(ipAddresses[0].ipAddress)')


    # update the bashrc file with the new env vars
    sed -i '' "s/GCLOUDDBIP/$sql_ip/g" bashrc
    sed -i '' "s/GCLOUDDBUSERNAME/$sql_username/g" bashrc
    sed -i '' "s/GCLOUDDBPASSWORD/$sql_password/g" bashrc


    
    # copy the deploy files up to your new GCE
    gcloud compute scp xfr $gce_username@$gce_name:~/ --recurse
    gcloud compute scp bashrc $gce_username@$gce_name:~/.bashrc

    # give further instructions

    echo ".........DONE. PLEASE MAKE A NOTE OF THIS INFO:" 1>&2
    sleep 1
    echo "Web server IP address ($gce_name): $gce_ip " 1>&2
    echo "Cloud SQL Instance IP address: $sql_ip" 1>&2
    sleep .5
    echo "" 1>&2
    echo "Okay. Good job. Now SSH into that bad boy with: gcloud compute ssh $gce_username@$gce_name" 1>&2
    echo "Once you're in there, type: cd ~/xfr/; sudo -E ./install.sh" 1>&2

fi
