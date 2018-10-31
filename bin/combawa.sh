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

# Binary variables.
SCRIPTS_PATH=$(currentscriptpath)
UTILS_DIR="$SCRIPTS_PATH/../utils"
TEMPLATES_DIR="$SCRIPTS_PATH/../templates"

# App variables.
APP_ROOT="$SCRIPTS_PATH/../../../.."
WEBROOT="$APP_ROOT/web"
CONFIG_DIR="$APP_ROOT/config"
APP_SCRIPTS_DIR="$APP_ROOT/scripts"

source $UTILS_DIR/colors.sh
source $UTILS_DIR/functions.sh

echo -e ""
echo -e "${LIGHT_PURPLE}"
echo -e "                 .                 "
echo -e "             /\ /l                 "
echo -e "            ((.Y(!                 "
echo -e "             \ |/          COMBAWA, PLEASED TO SERVE!"
echo -e "             /  6~6,       Let's build this project."
echo -e "             \ _    +-.            "
echo -e "              \`-=--^-'            "
echo -e "               \ \                 "
echo -e "              _/  \\"
echo -e "             (  .  Y"
echo -e "            /\"\ \`--^--v--."
echo -e "           / _ \`--\"T~\/~\/"
echo -e "          / \" ~\.  !"
echo -e "    _    Y      Y./'"
echo -e "   Y^|   |      |~~7"
echo -e "   | l   |     / ./'"
echo -e "   | \`L  | Y .^/~T"
echo -e "   |  l  ! | |/| |          -Row"
echo -e "   | .\`\/' | Y | !"
echo -e "   l  \"~   j l j_L______"
echo -e "    \,____{ __\"~ __ ,\_,\_"
echo -e "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~${NC}"
echo -e ""

# Check project settings are set.
if [ ! -f "$APP_SCRIPTS_DIR/settings.sh" ]; then
  echo -e "${RED}There is no settings file at the moment or its not readable.${NC}"
  echo -e "${ORANGE}You should run the following command to initialize it: 'drupal combawa:generate-project'.${NC}"
  exit -1
fi

# Build variables and their overrides.
source $APP_SCRIPTS_DIR/settings.sh
if [ -f "$APP_ROOT/.env" ]; then
  source $APP_ROOT/.env
fi

# Make drush a variable to use the one shipped with the repository.
DRUSH="$APP_ROOT/vendor/bin/drush -y --root=$WEBROOT"
if [ $COMBAWA_WEBSITE_URI ]; then
  DRUSH="$DRUSH --uri=$COMBAWA_WEBSITE_URI"
fi

source $UTILS_DIR/prerequisites.sh

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
            COMBAWA_ENV="$2"
            ;;
          *)
            echo "Unknown environment: $2. Please check your name."
            exit 1
        esac
        shift
        ;;
      -m|--mode)
        COMBAWA_BUILD_MODE="$2"
        if [ $2 != "install" ] && [ $2 != "update" ] ; then
          echo "Invalid build mode."
          exit 1
        fi;
        echo "[Build mode] $2"
        shift
        ;;
      -b|--backup)
        COMBAWA_BACKUP_BASE="$2"
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
        ssh -q $COMBAWA_SSH_CONFIG_NAME exit
        if [[ $? != 0 ]]; then
          echo "Impossible to connect to the production server."
          echo "Check your SSH config file. Should you connect through a VPN?"
          exit 1
        fi
        echo "[Retrieve DB from prod] Yes."
        COMBAWA_FETCH_DB_DUMP=1
        shift
        ;;
      -o|--offline)
        echo "[Offline] The build is processed in offline mode."
        COMBAWA_OFFLINE="$2"
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

# Show the build config.
echo "------"
echo "[Environment built] $COMBAWA_ENV"
echo "[Build mode] $COMBAWA_BUILD_MODE"
echo "[Generate a backup] $COMBAWA_BACKUP_BASE"
echo "[Environment URI] $COMBAWA_WEBSITE_URI"
echo "[Retrieve DB from prod] $COMBAWA_FETCH_DB_DUMP"
echo "[Run offline] $COMBAWA_OFFLINE"
echo "------"

if [ $COMBAWA_BACKUP_BASE == 1 ] ; then
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
source $APP_SCRIPTS_DIR/predeploy_actions.sh

# Run the build content.
if [ $COMBAWA_BUILD_MODE == "install" ]; then
  echo "Start the installation..."
  source $APP_SCRIPTS_DIR/install.sh
  if [[ $? != 0 ]]; then
    echo "The install.sh generated an error. Check the logs."
    exit $?
  fi
elif [ $COMBAWA_BUILD_MODE == "update" ]; then
  echo "Start the update..."
  source $APP_SCRIPTS_DIR/update.sh
  if [[ $? != 0 ]]; then
    echo "The update.sh generated an error. Check the logs."
    exit $?
  fi
fi

# Run the potential actions to do post deployment.
source $APP_SCRIPTS_DIR/postdeploy_actions.sh

# Send a notification to inform that the build is done.
if hash notify-send 2>/dev/null; then
  notify-send  "The build is completed."
fi
