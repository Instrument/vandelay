#!/bin/bash

usage() { 
    echo "Uh oh!" 1>&2
    echo "This script provisions a Cloud SQL instance and a Google Compute Engine instance from scratch. All options are required." 1>&2
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

    mkdir -p xfr


    cp bashrc.template bashrc
    echo "Creating fresh bashrc file" 1>&2

    # copy craft and config files into xfr/:
    echo "Copying content and config files into the xfr folder" 1>&2
    cp -r ../public xfr/craftfiles/
    cp -r ../craft xfr/craftfiles/
    cp ../data/snapchat-craft*.sql xfr/craft_db.sql
    cp conf_files/* xfr/
    cp README.md xfr/
    cp install-master.sh xfr/install.sh
    chmod 700 xfr/install.sh

    # create a new Ubunutu server
    echo "Creating a new Ubuntu server instance called '$gce_name'" 1>&2
    gcloud compute instances create $gce_name --image-family=ubuntu-1604-lts --image-project=ubuntu-os-cloud --tags=http-server,https-server --zone us-west1-b

    # ...and get its IP address
    gce_ip=$(gcloud compute instances list --filter="NAME:$gce_name"  --format='value(networkInterfaces[0].accessConfigs[0].natIP)')

    echo "The IP address of your new GCE instance ($gce_name) is: $gce_ip" 1>&2

    # spin up a new gcloud sql instance. keep auth network at the end
    echo "Creating a new MySQL 5.7 server instance called '$sql_instance_name' - grab a cup of coffee, this will probably take a while." 1>&2
    gcloud sql instances create $sql_instance_name \
        --database-flags=sql_mode=TRADITIONAL \
        --database-version="MYSQL_5_7" \
        --region="us-west1" \
        --gce-zone="us-west1-b" \
        --authorized-networks=$gce_ip

    # get its IP address
    sql_ip=$(gcloud sql instances list --filter="NAME:$sql_instance_name" --format='value(ipAddresses[0].ipAddress)')
    export GCLOUD_DB_IP=$sql_ip
    echo "Your new Cloud SQL server IP address is: $sql_ip" 1>&2
    
    # set up the craft user
    echo "Creating new db user $sql_username on $sql_instance_name" 1>&2
    gcloud sql users create $sql_username $gce_ip --instance=$sql_instance_name --password=$sql_password

    # update the bashrc file with the new env vars
    sed -i '' "s/GCLOUDDBIP/$sql_ip/g" bashrc
    sed -i '' "s/GCLOUDDBNAME/craftcms/g" bashrc
    sed -i '' "s/GCLOUDDBUSERNAME/$sql_username/g" bashrc
    sed -i '' "s/GCLOUDDBPASSWORD/$sql_password/g" bashrc

    # sed -i the craft_db.sql to use InnoDB
    echo "Updating DB dump file in xfr folder --NOTE THAT THIS IS OPTIMIZED FOR OSX SED SYNTAX--" 1>&2
    sed -i '' "s/MyISAM/InnoDB/g" xfr/craft_db.sql

    
    # copy the deploy files up to your new GCE
    gcloud compute scp xfr $gce_username@$gce_name:~/ --recurse
    gcloud compute scp bashrc $gce_username@$gce_name:~/.bashrc

    # give further instructions

    echo ".........DONE........" 1>&2
    echo "!!!!!! IMPORTANT INFO BELOW !!!!!" 1>&2
    echo "Web server IP address ($gce_name): $gce_ip " 1>&2
    echo "Cloud SQL Instance IP address: $sql_ip" 1>&2
    echo "" 1>&2
    echo "Okay. Good job. Now SSH into that bad boy with: gcloud compute ssh $gce_username@$gce_name" 1>&2
    echo "Once you're in there, type: cd ~/xfr/; sudo -E ./install.sh" 1>&2

fi
