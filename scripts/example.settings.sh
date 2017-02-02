#!/usr/bin/env bash

##### Per project settings.
# All these settings can still be overridden by the command line options.
# All variables defined here will be available in the other scripts.

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

# Features bundle to revert.
APP_BUNDLE="hc"

# Map this to your ~/.ssh/config file.
# /!\ Your user must have a direct SSH access allowed to the prod server.
# /!\ You probably should have retrieved the Jenkins SSH keys to connect to the
# prod server.
#
# Example of config entry.
# Host ssh_hc_prod
#   HostName happyculture.coop
#   IdentityFile ~/.ssh/happyculture_bot
#   Port 2222
#   User happyculture
#
# More info: http://nerderati.com/2011/03/17/simplify-your-life-with-an-ssh-config-file/
SSH_CONFIG_NAME="ssh_hc_prod"

# Path on the prod server where the dump is stored.
PROD_DB_DUMP_PATH="/home/avise/sqldump/avise_prod_daily.sql.gz"

# Name of the reference dump name in the repo.
DUMP_FILE_NAME="reference_dump.sql"

##### Project executables.

BUNDLE=/usr/local/rvm/wrappers/default/bundle
if [ ! -f /usr/local/rvm/wrappers/default/bundle ]; then
  BUNDLE=`which bundle`
fi
