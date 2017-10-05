#!/bin/bash

usage() { 
    echo "USAGE:" 1>&2
    echo "This script provisions a Cloud SQL instance and a Google Compute Engine instance from scratch, or"
    echo "redeploys code and data to existing servers. All options are required." 1>&2
    echo "Usage details: $0 -a <GCE admin username> -e <GCE instance name> -s <Cloud SQL instance name> -u <db admin username> -p <admin password>" 1>&2
    echo ""
    echo "For example, $0 -a gceuser -e myvm -s mydbserver -u craftuser -p supersecurepassword" 1>&2
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
    mkdir xfr
    mkdir craftfiles


    cp bashrc.template bashrc
    echo "Creating fresh bashrc file" 1>&2

    # copy craft and config files into xfr/:
    echo "Copying content and config files into the xfr folder" 1>&2
    cp -r ../public craftfiles/
    cp -r ../craft craftfiles/
    rm -r craftfiles/craft/storage
    zip -qr xfr/craftfiles.zip craftfiles/
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
        gcloud compute instances create $gce_name \
            --machine-type=n1-standard-4 \
            --image-family=ubuntu-1604-lts \
            --image-project=ubuntu-os-cloud \
            --zone us-west1-b

        # explicitly allow HTTP and HTTPS access
        gcloud compute instances add-tags $gce_name --tags http-server,https-server

        gce_ip=$(gcloud compute instances list --filter="NAME:$gce_name"  --format='value(networkInterfaces[0].accessConfigs[0].natIP)')
        echo "Reserving a static IP for $gce_name" 1>&2
        gcloud compute addresses create $gce_name-staticip --addresses $gce_ip --region us-west1

        #this a new install, so you'll need the long form of install.sh
        echo "using the long form of the install script"
        cp install-newdeploy.sh xfr/install.sh

    else
        #this a redeploy, so you'll need the short form of install.sh
        echo "using the short form of the install script"
        cp install-redeploy.sh xfr/install.sh

        # get its ip address
        gce_ip=$(gcloud compute instances list --filter="NAME:$gce_name"  --format='value(networkInterfaces[0].accessConfigs[0].natIP)')

        # is it a static ip yet? if not, promote it
        isStaticIp=$(gcloud compute addresses list --filter="ADDRESS:$gce_ip")
        if [ "$isStaticIp" == "" ]; then
            echo "promoting IP address from ephemeral to static"
            gcloud compute addresses create $gce_name-staticip --addresses $gce_ip --region us-west1
        fi

    fi

    # give your new install script the proper perms
    chmod 700 xfr/install.sh


    # spin up a new gcloud sql instance, if it doesn't already exist. keep 'authorized-networks' at the end of this command!
    gsql_exists=$(gcloud sql instances list --filter="NAME:$sql_instance_name")
    if [[ $gsql_exists != NAME* ]]; then
        echo "Creating a new MySQL 5.7 server instance called '$sql_instance_name' - grab a cup of coffee, this will probably take a while." 1>&2
        gcloud sql instances create $sql_instance_name \
            --database-flags=sql_mode=TRADITIONAL \
            --database-version="MYSQL_5_7" \
            --region="us-west1" \
            --gce-zone="us-west1-b" \
            --authorized-networks=$gce_ip


    else
        echo "Using existing database server $sql_instance_name"
        echo "Backing up database. You can find backups in the GCP Console."
        gcloud sql backups create --instance=$sql_instance_name --description="Backing up for Craft redeploy"

    fi

    # regardless get its IP address
    sql_ip=$(gcloud sql instances list --filter="NAME:$sql_instance_name" --format='value(ipAddresses[0].ipAddress)')


    # set up the craft user
     gsqluser_exists=$(gcloud sql users list --instance=$sql_instance_name --filter="NAME:$sql_username AND HOST:$gce_ip")
    if [[ $gsqluser_exists != NAME* ]]; then
        echo "Updating password for $sql_username at $gce_ip on $sql_instance_name" 1>&2
#        gcloud sql users set-password $sql_username $gce_ip --instance=$sql_instance_name --password=$sql_password
    else
        echo "Creating new db user $sql_username at $gce_ip on $sql_instance_name" 1>&2
    fi
        gcloud sql users create $sql_username $gce_ip --instance=$sql_instance_name --password=$sql_password


    # update the bashrc file with the new env vars
    sed -i '' "s/GCLOUDDBIP/$sql_ip/g" bashrc
    sed -i '' "s/GCLOUDDBUSERNAME/$sql_username/g" bashrc
    sed -i '' "s/GCLOUDDBPASSWORD/$sql_password/g" bashrc
    sed -i '' "s/GCLOUDDBNAME/$sql_instance_name/g" bashrc



    # copy the deploy files up to your new GCE
    gcloud compute scp xfr $gce_username@$gce_name:~/ --recurse
    gcloud compute scp bashrc $gce_username@$gce_name:~/.bashrc


    # clean up
    rm -r craftfiles
    rm craft_db.sql
    rm -r xfr
    rm bashrc

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
