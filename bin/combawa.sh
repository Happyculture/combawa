#!/bin/bash

# Catch all errors.
set -euo pipefail

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
  message_error "There is no settings file at the moment or its not readable."
  message_warning "You should run the following command to initialize it: 'drupal combawa:generate-project'."
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
set +u
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

            message_action "Environment overriden:"
            message_override "$SOURCE_ENV" "$COMBAWA_ENV"
            ;;
          *)
            notify_error "Unknown environment: $2. Please check your name."
        esac
        shift
        ;;
      -m|--mode)
        SOURCE_BUILD_MODE=$COMBAWA_BUILD_MODE
        COMBAWA_BUILD_MODE="$2"

        if [ $2 != "install" ] && [ $2 != "update" ] && [ $2 != "pull" ] ; then
          notify_error "Invalid build mode."
        fi;
        message_action "Build mode overriden:"
        message_override "$SOURCE_BUILD_MODE" "$COMBAWA_BUILD_MODE"
        shift
        ;;
      -b|--backup)
        SOURCE_BACKUP_BASE=$COMBAWA_BACKUP_BASE
        COMBAWA_BACKUP_BASE="$2"

        if [ $2 != "0" ] && [ $2 != "1" ] ; then
          notify_error "Invalid backup flag." "Only 0 or 1 is valid."
        fi

        message_action "Backup base overriden:"
        message_override "$SOURCE_BACKUP_BASE" "$COMBAWA_BACKUP_BASE"
        shift
        ;;
      -u|--uri)
        SOURCE_URI=$COMBAWA_WEBSITE_URI
        COMBAWA_WEBSITE_URI="$2"

        if [ ! $2 ]; then
          notify_error "URI parameter can not be empty."
        fi

        message_action "URI overriden:"
        message_override "$SOURCE_URI" "$COMBAWA_WEBSITE_URI"
        shift
        ;;
      -h|--help)
        usage
        shift
        ;;
      -f|--fetch-db-dump)
        SOURCE_FETCH=$COMBAWA_FETCH_DB_DUMP
        COMBAWA_FETCH_DB_DUMP="$2"

        message_action "Fetch DB dump from prod overriden:"
        message_override "$SOURCE_FETCH" "$COMBAWA_FETCH_DB_DUMP"
        echo -e ""

        if [ "$COMBAWA_FETCH_DB_DUMP" == "1" ] ; then
          if [[ ! -z "$COMBAWA_SSH_CONFIG_NAME" ]]; then
            message_step "Testing connection with remote SSH server from which the dump will be retrieved:"
            ssh -q $COMBAWA_SSH_CONFIG_NAME echo > /dev/null
            if [ "$?" != "0" ] ; then
              notify_error "Impossible to connect to the production server." "Check your SSH config file. Should you connect through a VPN?"
            else
              message_confirm "SSH connection OK."
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
set -u

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

message_step "Build options summary:"
echo -e ""
if hash column 2>/dev/null; then
  echo -e "$USAGE" | column -s $'\t' -t
else
  echo -e "$USAGE"
fi

#################################
section_separator
#################################

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
    message_error "The install.sh generated an error. Check the logs."
    exit $?
  fi
  message_confirm "Install... OK!"
  echo -e ""
elif [ "$COMBAWA_BUILD_MODE" == "pull" ]; then
  echo "Start the local update..."
  source $APP_SCRIPTS_DIR/pull.sh
  if [[ $? != 0 ]]; then
    message_error "The pull.sh generated an error. Check the logs."
    exit $?
  fi
  message_confirm "Pull... OK!"
  echo -e ""
elif [ "$COMBAWA_BUILD_MODE" == "update" ]; then
  echo "Start the update..."
  source $APP_SCRIPTS_DIR/update.sh
  if [[ $? != 0 ]]; then
    message_error "The update.sh generated an error. Check the logs."
    exit $?
  fi
  message_confirm "Update... OK!"
  echo -e ""
fi

# Run the potential actions to do post deployment.
source $APP_SCRIPTS_DIR/postdeploy_actions.sh

# Send a notification to inform that the build is done.
notify "The build is completed."
