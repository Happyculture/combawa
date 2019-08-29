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
            SOURCE_ENV=$COMBAWA_ENV
            COMBAWA_ENV="$2"

            echo -e "${YELLOW}Environment overriden:${NC}"
            echo -e "From ${LIGHT_RED}$SOURCE_ENV${NC} to ${LIGHT_GREEN}$2${NC}"
            ;;
          *)
            COMBAWA_MESSAGE="Unknown environment: $2. Please check your name."
            echo -e "${RED}$COMBAWA_MESSAGE${NC}"
            notify "$COMBAWA_MESSAGE"
            exit 1
        esac
        shift
        ;;
      -m|--mode)
        SOURCE_BUILD_MODE=$COMBAWA_BUILD_MODE
        COMBAWA_BUILD_MODE="$2"

        if [ $2 != "install" ] && [ $2 != "update" ] && [ $2 != "pull" ] ; then
          COMBAWA_MESSAGE="Invalid build mode."
          echo -e "${RED}$COMBAWA_MESSAGE${NC}"
          notify "$COMBAWA_MESSAGE"
          exit 1
        fi;
        echo -e "${YELLOW}Build mode overriden:${NC}"
        echo -e "From ${LIGHT_RED}$SOURCE_BUILD_MODE${NC} to ${LIGHT_GREEN}$2${NC}"
        shift
        ;;
      -b|--backup)
        SOURCE_BACKUP_BASE=$COMBAWA_BACKUP_BASE
        COMBAWA_BACKUP_BASE="$2"

        if [ $2 != "0" ] && [ $2 != "1" ] ; then
          COMBAWA_MESSAGE="Invalid backup flag."
          echo -e "${RED}$COMBAWA_MESSAGE${NC}"
          echo -e "${ORANGE}Only 0 or 1 is valid.${NC}"
          notify "$COMBAWA_MESSAGE"
          exit 1
        fi

        echo -e "${YELLOW}Backup base overriden:${NC}"
        echo -e "From ${LIGHT_RED}$SOURCE_BACKUP_BASE${NC} to ${LIGHT_GREEN}$2${NC}"
        shift
        ;;
      -u|--uri)
        SOURCE_URI=$COMBAWA_WEBSITE_URI
        COMBAWA_WEBSITE_URI="$2"

        if [ ! $2 ]; then
          COMBAWA_MESSAGE="URI parameter can not be empty."
          echo -e "${RED}$COMBAWA_MESSAGE${NC}"
          notify "$COMBAWA_MESSAGE"
          exit 1
        fi

        echo -e "${YELLOW}URI overriden:${NC}"
        echo -e "From ${LIGHT_RED}$SOURCE_URI${NC} to ${LIGHT_GREEN}$2${NC}"
        shift
        ;;
      -h|--help)
        usage
        shift
        ;;
      -f|--fetch-db-dump)
        SOURCE_FETCH=$COMBAWA_FETCH_DB_DUMP
        COMBAWA_FETCH_DB_DUMP="$2"

        echo -e "${YELLOW}Fetch DB dump from prod overriden:${NC}"
        echo -e "From ${LIGHT_RED}$SOURCE_FETCH${NC} to ${LIGHT_GREEN}$2${NC}"
        echo -e ""

        if [ "$COMBAWA_FETCH_DB_DUMP" == "1" ] ; then
          if [[ ! -z "$COMBAWA_SSH_CONFIG_NAME" ]]; then
            echo -e "${BLUE}Testing connection with remote SSH server from which the dump will be retrieved:${NC}"
            ssh -q $COMBAWA_SSH_CONFIG_NAME echo > /dev/null
            if [ "$?" != "0" ] ; then
              COMBAWA_MESSAGE="Impossible to connect to the production server."
              echo -e "${RED}$COMBAWA_MESSAGE${NC}"
              echo -e "${ORANGE}Check your SSH config file. Should you connect through a VPN?${NC}"
              notify "$COMBAWA_MESSAGE"
              exit 1
            else
              echo -e "${GREEN}SSH connection OK.${NC}"
            fi
          fi
        fi
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

source $UTILS_DIR/prerequisites.sh

# Show the build config.
USAGE=$(cat <<-END
Environment built:\t${LIGHT_CYAN}$COMBAWA_ENV${NC}
Build mode:\t${LIGHT_CYAN}$COMBAWA_BUILD_MODE${NC}
Generate a backup:\t${LIGHT_CYAN}$COMBAWA_BACKUP_BASE${NC}
Environment URI:\t${LIGHT_CYAN}$COMBAWA_WEBSITE_URI${NC}
Retrieve DB from prod:\t${LIGHT_CYAN}$COMBAWA_FETCH_DB_DUMP${NC}
END
)

echo -e "${BLUE}Build options summary:${NC}"
echo -e ""
if hash column 2>/dev/null; then
  echo -e "$USAGE" | column -s $'\t' -t
else
  echo -e "$USAGE"
fi
echo -e ""
echo -e "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~"
echo -e ""

if [ "$COMBAWA_BACKUP_BASE" == "1" ] ; then
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
if [ "$COMBAWA_BUILD_MODE" == "install" ]; then
  echo "Start the installation..."
  source $APP_SCRIPTS_DIR/install.sh
  if [[ $? != 0 ]]; then
    echo "The install.sh generated an error. Check the logs."
    exit $?
  fi
elif [ "$COMBAWA_BUILD_MODE" == "pull" ]; then
  echo "Start the local update..."
  source $APP_SCRIPTS_DIR/pull.sh
  if [[ $? != 0 ]]; then
    echo "The pull.sh generated an error. Check the logs."
    exit $?
  fi
elif [ "$COMBAWA_BUILD_MODE" == "update" ]; then
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
notify "The build is completed."
