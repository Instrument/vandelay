# gce_craftcms
This currently runs a Bash script to install a CraftCMS instance on top of a LAMP stack on Ubuntu 16.04 (Xenial). The  hope is to get it into a Dockerfile at some point, but Docker and GCE aren't playing well together at the moment.

## requirements
This install assumes that you have: 

- created a project on Google Cloud Platform
- installed and updated the Cloud SDK 
- authenticated via SSH key on the platform.

### Google Cloud Storage settings:
- WIP

## installation ##
1. Log in to your GCE with `gcloud compute ssh your_username@gce_instance_name`
1. Type `cd deploy/`
1. Type `sudo -E ./install.sh`
1. It will install Apache, MySQL, PHP 7.1, and all of their dependencies, as well as the Craft CMS database and file structure.
