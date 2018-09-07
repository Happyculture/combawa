#!/bin/bash

# Return error codes if they happen.
set -e

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

source $SCRIPTS_PATH/colors.sh
source $SCRIPTS_PATH/functions.sh
source $SCRIPTS_PATH/app_settings.sh

# App variables.
WEBROOT="$SCRIPTS_PATH/../web"
APP_ROOT="$WEBROOT/.."
CONFIG_DIR="$SCRIPTS_PATH/../../../../config"

source $SCRIPTS_PATH/prerequisites.sh

WEBROOT="$SCRIPTS_PATH/../web"
APP_ROOT="$WEBROOT/.."

# Override default settings per project.
if [ ! -f "$SCRIPTS_PATH/settings.sh" ]; then
  echo "settings.sh file not found. Copy and rename the example.settings.sh file."
  exit 1
fi
source $SCRIPTS_PATH/settings.sh

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
        if [ ! $2 ]; then
          echo "URI parameter can not be empty."
          exit 1
        fi
        shift
        ;;
      -h|--help)
        usage
        shift
        ;;
      -f|--fetch-db-dump)
	echo "Testing connection with remote SSH server from which the dump will be retrieved."
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
        echo "[Offline] The build is processed in offline mode."
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

# Check if composer is installed in the system.
hash composer 2>/dev/null || { echo >&2 "Composer is required by this script. You can find it on https://getcomposer.org."; exit 1; }

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
  COMPOSER_SETTINGS=""
  if [ "$ENV" != "dev" ] ; then
    COMPOSER_SETTINGS="$COMPOSER_SETTINGS --no-interaction"
  fi
  if [ "$ENV" == "prod" ] ; then
    COMPOSER_SETTINGS="$COMPOSER_SETTINGS --no-dev"
  fi
  composer install $COMPOSER_SETTINGS
fi

# Make drush a variable to use the one shipped with the repository.
DRUSH="$APP_ROOT/vendor/bin/drush -y --root=$WEBROOT"
if [ $URI ]; then
  DRUSH="$DRUSH --uri=$URI"
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
  if [ "$(ls -l $WEBROOT/../dumps/*.sql.gz | wc -l)" -gt 10 ]; then
    ls -tp $WEBROOT/../dumps/*.sql.gz | grep -v '/$' | tail -n +10 | tr '\n' '\0' | xargs -0 rm --
  fi
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
