#!/bin/bash

# Return error codes if they happen.
set -e

########## DEFAULT VARIABLES ##############
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

# Has to run offline.
OFFLINE=0

########## FUNCTION ##############
# Help function.
usage() {
  bold=$(tput bold)
  normal=$(tput sgr0)

  echo 'Usage:'
  echo 'Long version: ./build.sh --env dev --mode install --backup 1 --uri http://hc.fun --fetch-db-dump'
  echo 'Short version: ./build.sh -e dev -m install -b 1 -u http://hc.fun -f'
  echo ''
  echo -e "Available arguments are:"
  echo -e "${bold}\t--env, -e: Environment to build.${normal}"
  echo -e '\t\tAllowed values are: dev, recette, preprod, prod'
  echo -e '\t\tDefault value: prod'
  echo ''
  echo -e "${bold}\t--mode, -m: Build mode${normal}"
  echo -e '\t\tAllowed values are: install, update'
  echo -e '\t\tDefault value: update'
  echo ''
  echo -e "${bold}\t--backup, -e: Generates a backup before building the project.${normal}"
  echo -e '\t\tAllowed values are: 0: does not generate a backup, 1: generates a backup.'
  echo -e '\t\tDefault value: 1'
  echo ''
  echo -e "${bold}\t--uri, -u: Local URL of your project${normal}"
  echo -e '\t\tUsed when the final drush uli command is runned.'
  echo ''
  echo -e "${bold}\t--fetch-db-dump, -f: Fetch a fresh DB dump from the production site.${normal}"
  echo -e '\t\tUsed when the reference dump should be updated.'
  echo ''
  echo -e "${bold}\t--offline, -o: Run offline to avoid trying to make remote connections.${normal}"
  echo -e '\t\tAllowed values are: 0: make remote connections, 1: avoid remote connections.'
  echo -e '\t\tDefault value: 0'
  exit
}

# Working directory.
# Helper to let you run the install script from anywhere.
currentscriptpath () {
  SOURCE="${BASH_SOURCE[0]}"
  # resolve $SOURCE until the file is no longer a symlink
  while [ -h "$SOURCE" ]; do

    DIR="$( cd -P "$( dirname "$SOURCE" )" && pwd )"
    SOURCE="$(readlink "$SOURCE")"
    # if $SOURCE was a relative symlink, we need to resolve it relative to the path where the symlink file was located
    [[ $SOURCE != /* ]] && SOURCE="$DIR/$SOURCE"
  done
  DIR="$( cd -P "$( dirname "$SOURCE" )" && pwd )"
  echo $DIR
}

# Working directory.
SCRIPTS_PATH=$(currentscriptpath)
WEBROOT="$SCRIPTS_PATH/../web"
APP_ROOT="$WEBROOT/.."

# Set the arguments value.
while [[ $1 ]]
do
  key="$1"
  if [ -z "$key" ]; then
    shift
  else
    case $key in
      -e | --env)
        case $2 in
          dev|recette|preprod|prod)
            ENV="$2"
            ;;
          *)
            echo "Unknown environment: $2. Please check your name."
            exit 1
        esac
        shift
        ;;
      -m|--mode)
        BUILD_MODE="$2"
        if [ $2 != "install" ] && [ $2 != "update" ] ; then
	  echo "Invalid build mode."
	  exit 1
	fi;
        echo "[Build mode] $2"
        shift
        ;;
      -b|--backup)
        BACKUP_BASE="$2"
        shift
        ;;
      -u|--uri)
        URI="$2"
        shift
        ;;
      -h|--help)
        usage
        shift
        ;;
      -f|--fetch-db-dump)
        ssh -q $SSH_CONFIG_NAME exit
        if [[ $? != 0 ]]; then
          echo "Impossible to connect to the production server."
          echo "Check your SSH config file. Should you connect through a VPN?"
          exit 1
        fi
        echo "[Retrieve DB from prod] Yes."
        FETCH_DB_DUMP=1
        shift
        ;;
      -o|--offline)
        OFFLINE="$2"
        shift
        ;;
      --) # End of all options
        shift
        ;;
      *) # No more options
        ;;
    esac
    shift
  fi
done

# Make drush a variable to use the one shipped with the repository.
DRUSH="drush -y --root=$WEBROOT --uri=$URI"

# Check that we have what we need to build.
if [ ! -f "$SCRIPTS_PATH/../composer.json" ]; then
  echo "Your repository is missing a composer.json file."
  exit 1
elif [ ! -f "$SCRIPTS_PATH/predeploy_actions.sh" ]; then
  echo "The predeploy_actions.sh file is not readable and can not be processed."
  exit 1
elif [ ! -f "$SCRIPTS_PATH/postdeploy_actions.sh" ]; then
  echo "The postdeploy_actions.sh file is not readable and can not be processed."
  exit 1
fi

# Preliminary verification to avoid running actions
# if the requiprements are not met.
if [ $BUILD_MODE == "install" ]; then
  if [ ! -f "$SCRIPTS_PATH/install.sh" ]; then
    echo "The install.sh file is not readable and can not be processed."
    exit 1
  fi
elif [ $BUILD_MODE == "update" ]; then
  if [ ! -f "$SCRIPTS_PATH/update.sh" ]; then
    echo "The update.sh file is not readable and can not be processed."
    exit 1
  fi
else
  echo "Unknown build mode."
  exit 1
fi

# Show the build config.
echo "------"
echo "[Environment built] $ENV"
echo "[Build mode] $BUILD_MODE"
echo "[Generate a backup] $BACKUP_BASE"
echo "[Environment URI] $URI"
echo "[Retrieve DB from prod] $FETCH_DB_DUMP"
echo "[Run offline] $OFFLINE"
echo "------"

echo "Composer install"
cd $SCRIPTS_PATH/../
if [ $OFFLINE == 0 ] ; then
  if [ "$ENV" == "production" ] ; then
    composer install --optimize-autoloader --no-dev
  else
    composer install --optimize-autoloader
  fi
fi

# Stop the build if the DB connection is not set.
set +e
$DRUSH sql-connect
if [[ $? != 0 ]]; then
  echo "DB connection impossible."
  echo "Please check that your MySQL connection is correcty set."
  exit 1
fi
set -e

if [ $BACKUP_BASE == 1 ] ; then
  # Store a security backup in case the update doesn't go right.
  DUMP_NAME="update-backup-script-$(date +%Y%m%d%H%M%S).sql";
  DUMP_PATH="$WEBROOT/../dumps/$DUMP_NAME"
  mkdir -p "$WEBROOT/../dumps/"
  $DRUSH sql-dump --result-file=$DUMP_PATH --gzip
  # Remove older backups but keep the 10 youngest ones.
  ls -tp "$WEBROOT/../dumps/*.sql.gz" | grep -v '/$' | tail -n +10 | tr '\n' '\0' | xargs -0 rm --
fi

# Run the potential actions to do pre deployment.
source $SCRIPTS_PATH/predeploy_actions.sh

# Run the build content.
if [ $BUILD_MODE == "install" ]; then
  echo "Start the installation..."
  source $SCRIPTS_PATH/install.sh
  if [[ $? != 0 ]]; then
    echo "The install.sh generated an error. Check the logs."
    exit $?
  fi
elif [ $BUILD_MODE == "update" ]; then
  echo "Start the update..."
  source $SCRIPTS_PATH/update.sh
  if [[ $? != 0 ]]; then
    echo "The update.sh generated an error. Check the logs."
    exit $?
  fi
fi

# Run the potential actions to do post deployment.
source $SCRIPTS_PATH/postdeploy_actions.sh

# Send a notification to inform that the build is done.
if hash notify-send 2>/dev/null; then
  notify-send  "The build is completed."
fi
