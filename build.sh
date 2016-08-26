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

########## FUNCTION ##############
# Help function.
usage() {
  bold=$(tput bold)
  normal=$(tput sgr0)

  echo 'Usage:'
  echo 'Long version: ./build.sh --env dev --mode install --backup 1 --uri http://hc.fun'
  echo 'Short version: ./build.sh -e dev -m install -b 1 -u http://hc.fun'
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

# Actions to run before the main and shared deployment actions.
# It can be useful to backup, import databases or doing something similar.
predeploy_actions() {
  case $1 in
    dev)
      # $DRUSH sql-drop -y;
      # $DRUSH sqlc < "$WEBROOT/../reference_dump.sql";
      ;;
    recette|preprod)
      # $DRUSH sql-drop -y;
      # $DRUSH sqlc < "$WEBROOT/../reference_dump.sql";
      ;;
    prod)
      ;;
    *)
      echo "Unknown environment: $2. Please check your name."
      exit 1;
  esac
}

# Action to run after the main and shared deployment actions.
# It can be useful to enable specific modules for instance.
postdeploy_actions() {
  case $1 in
    dev)
      # Examples:
      # Compile CSS for development.
      # (
      #   cd $WEBROOT/sites/all/themes/custom_theme/;
      #   compass compile --force -e production
      # )

      # Turn off the aggregation to avoid to turn crazy.
      # $DRUSH vset preprocess_css 0;
      # $DRUSH vset preprocess_js 0;
      # Enable UIs.
      # $DRUSH en -y devel field_ui diff views_ui;
      # Fetch missing images from the remote server.
      # $DRUSH en -y stage_file_proxy
      # $DRUSH vset stage_file_proxy_origin "https://www.example.org"
      # Connect.
      # $DRUSH uli
      ;;
    recette|preprod)
      # Examples:
      # Compile CSS for development.
      # (
      #   cd $WEBROOT/sites/all/themes/custom_theme/;
      #   compass compile --force -e production
      # )

      # Disable dev modules.
      # $DRUSH dis -y devel field_ui diff views_ui
      # Fetch missing images from the remote server.
      # $DRUSH en -y stage_file_proxy
      # $DRUSH vset stage_file_proxy_origin "https://www.example.org"
      ;;
    prod)
      ;;
    *)
      echo "Unknown environment: $2. Please check your name."
      exit 1;
  esac
}

# Working directory.
RESULT=$(currentscriptpath)
WEBROOT="$RESULT/../www"

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
      --) # End of all options
        shift
        ;;
      *) # No more options
        ;;
    esac
    shift
  fi
done

# Check that we have what we need to build.
if [ $BUILD_MODE == "install" ]; then
  if [ ! -f "$WEBROOT/../scripts/install.sh" ]; then
    echo "The install.sh file is not readable and can not be processed."
    exit 1
  fi
elif [ $BUILD_MODE == "update" ]; then
  if [ ! -f "$WEBROOT/../scripts/update.sh" ]; then
    echo "The update.sh file is not readable and can not be processed."
    exit 1
  fi
else
  echo "Unknown build mode."
  exit 1
fi

# Make drush a variable to use the one shipped with the repository.
DRUSH="drush -y --root=$WEBROOT --uri=$URI"

# Show the build config.
echo "------"
echo "[Environment built] $ENV"
echo "[Build mode] $BUILD_MODE"
echo "[Generate a backup] $BACKUP_BASE"
echo "[Environment URI] $URI"
echo "------"

# Run the potential actions to do pre deployment.
predeploy_actions $ENV

if [ $BACKUP_BASE == 1 ] ; then
# TODO:
# - Limit the backups existing in the dump dir to 10.
# --
# Store a security backup in case the update doesn't go right.
DUMP_NAME="update-backup-script-$(date +%Y%m%d%H%M%S).sql";
DUMP_PATH="$WEBROOT/../dumps/$DUMP_NAME"
mkdir -p "$WEBROOT/../dumps/"
$DRUSH sql-dump > $DUMP_PATH;
tar -czf "$DUMP_PATH.tar.gz" $DUMP_PATH;
rm $DUMP_PATH;
fi

# Run the build content.
if [ $BUILD_MODE == "install" ]; then
  echo "Start the installation..."
  $WEBROOT/../scripts/install.sh
  if [[ $? != 0 ]]; then
    echo "The install.sh generated an error. Check the logs."
    exit $?
  fi
elif [ $BUILD_MODE == "update" ]; then
  echo "Start the update..."
  $WEBROOT/../scripts/update.sh
  if [[ $? != 0 ]]; then
    echo "The update.sh generated an error. Check the logs."
    exit $?
  fi
fi

# Run the potential actions to do post deployment.
postdeploy_actions $ENV

# Send a notification to inform that the build is done.
if hash notify-send 2>/dev/null; then
  notify-send  "The build is completed."
fi

