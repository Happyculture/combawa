#!/usr/bin/env bash

##### Per project settings.
# All these settings can still be overridden by the command line options.
# All variables defined here will be available in the other scripts.

# Installation profile and custom theme.
INSTALL_PROFILE="config_installer"
CUSTOM_THEME="bartik"

# Default environment is prod.
ENV="prod";

# Default build mode. Can be install or update.
BUILD_MODE="update";

# Backup base before build.
BACKUP_BASE=1;

# Default URI.
URI="https://example.org/"

# Default action to retrieve a DB dump from the production.
FETCH_DB_DUMP=0

# Map this to your ~/.ssh/config file.
# /!\ Your user must have a direct SSH access allowed to the server.
# /!\ The production dumps are supposed to be retrieved by our Jenkins build
# https://ci.happyculture.coop/job/Dumps/view/Dumps/
#
# If your dump isn't generated here. Add it.
# Howto: https://projets.happyculture.coop/projects/infra/wiki/Dumps_prod
#
# Example of config entry.
# Host ssh_hc_ci
#   HostName ci.happyculture.coop
#   Port 2222
#   User happyculture
#   (Optional) IdentityFile ~/.ssh/id_rsa
#
# More info: http://nerderati.com/2011/03/17/simplify-your-life-with-an-ssh-config-file/
SSH_CONFIG_NAME="ssh_hc_ci"

# Path on the prod server where the dump is stored.
PROD_DB_DUMP_PATH="/home/jenkins/dumps/project_prod_daily.sql.gz"

# Name of the reference dump name in the repo.
DUMP_FILE_NAME="reference_dump.sql"

##### Project executables.

NPM=npm