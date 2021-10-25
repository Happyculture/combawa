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

# App variables.
COMBAWA_ROOT="$SCRIPTS_PATH/../../../.."
WEBROOT="$COMBAWA_ROOT/web"
CONFIG_DIR="$COMBAWA_ROOT/config"
COMBAWA_SCRIPTS_DIR="$COMBAWA_ROOT/scripts/combawa"

# State variables.
_COMBAWA_ONLY_PREDEPLOY=0
_COMBAWA_ONLY_POSTDEPLOY=0
_COMBAWA_NO_PREDEPLOY=0
_COMBAWA_NO_POSTDEPLOY=0
_COMBAWA_REIMPORT_FORCE_EXIT=0

# Compute steps to run. By default, every steps are run.
_COMBAWA_RUN_PREDEPLOY=1
_COMBAWA_RUN_MAIN_BUILD_STEP=1
_COMBAWA_RUN_POSTDEPLOY=1

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
if [[ "" == "$(composer config extra.combawa.build_mode)" ]]; then
  message_error "Base settings has not been defined yet."
  message_warning "You should run the following command to initialize it: 'drupal combawa:initialize-build-scripts'."
  exit -1
fi

# Set default variables.
COMBAWA_BUILD_MODE=`composer config extra.combawa.build_mode`
COMBAWA_BUILD_ENV="prod"
COMBAWA_DB_BACKUP_FLAG=1
COMBAWA_REIMPORT_REF_DUMP_FLAG=0
COMBAWA_DB_FETCH_FLAG=0
# Load local overrides.
if [ -f "$COMBAWA_ROOT/.env" ]; then
  source $COMBAWA_ROOT/.env
fi

# Make drush a variable to use the one shipped with the repository.
DRUSH="$COMBAWA_ROOT/vendor/bin/drush -y"

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
          dev|testing|prod)
            SOURCE_BUILD_ENV=$COMBAWA_BUILD_ENV
            COMBAWA_BUILD_ENV="$2"

            message_action "Environment overriden:"
            message_override "$SOURCE_BUILD_ENV" "$COMBAWA_BUILD_ENV"
            ;;
          *)
            notify_error "Unknown environment: $2. Please check your name."
        esac
        shift
        ;;
      -m|--mode)
        SOURCE_BUILD_MODE=$COMBAWA_BUILD_MODE
        COMBAWA_BUILD_MODE="$2"

        if [ $2 != "install" ] && [ $2 != "update" ] ; then
          notify_error "Invalid build mode."
        fi;
        message_action "Build mode overriden:"
        message_override "$SOURCE_BUILD_MODE" "$COMBAWA_BUILD_MODE"
        shift
        ;;
      -b|--backup)
        SOURCE_DB_BACKUP_FLAG=$COMBAWA_DB_BACKUP_FLAG
        COMBAWA_DB_BACKUP_FLAG="$2"

        if [ $2 != "0" ] && [ $2 != "1" ] ; then
          notify_error "Invalid backup flag." "Only 0 or 1 is valid."
        fi

        message_action "Backup base overriden:"
        message_override "$SOURCE_DB_BACKUP_FLAG" "$COMBAWA_DB_BACKUP_FLAG"
        shift
        ;;
      -r|--reimport)
        SOURCE_REIMPORT=$COMBAWA_REIMPORT_REF_DUMP_FLAG
        COMBAWA_REIMPORT_REF_DUMP_FLAG="$2"

        if [ $2 != "0" ] && [ $2 != "1" ] ; then
          notify_error "Invalid reimport flag." "Only 0 or 1 is valid."
        fi

        message_action "Reimport reference dump flag overriden:"
        message_override "$SOURCE_REIMPORT" "$COMBAWA_REIMPORT_REF_DUMP_FLAG"
        shift
        ;;
      -h|--help)
        usage
        shift
        ;;
      -f|--fetch-db-dump)
        SOURCE_DB_FETCH_FLAG=$COMBAWA_DB_FETCH_FLAG
        COMBAWA_DB_FETCH_FLAG="$2"

        message_action "Fetch DB dump from prod overriden:"
        message_override "$SOURCE_DB_FETCH_FLAG" "$COMBAWA_DB_FETCH_FLAG"
        echo -e ""

        if [ "$COMBAWA_DB_FETCH_FLAG" == "1" ] ; then
          if [[ ! -z "$COMBAWA_DB_FETCH_CNX_STRING" ]]; then
            message_step "Testing connection with remote SSH server from which the dump will be retrieved:"
            ssh -q $COMBAWA_DB_FETCH_CNX_STRING echo > /dev/null
            if [ "$?" != "0" ] ; then
              notify_error "Impossible to connect to the production server." "Check your SSH config file. Should you connect through a VPN?"
            else
              message_confirm "SSH connection OK."
            fi
          fi
        fi
        shift
        ;;
      --only-predeploy)
        _COMBAWA_ONLY_PREDEPLOY=1
        message_action "Only predeploy actions will be run."
        ;;
      --only-postdeploy)
        _COMBAWA_ONLY_POSTDEPLOY=1
        message_action "Only postdeploy actions will be run."
        ;;
      --no-predeploy)
        _COMBAWA_RUN_PREDEPLOY=0
        message_action "Predeploy actions will not be run."
        ;;
      --no-postdeploy)
        _COMBAWA_RUN_POSTDEPLOY=0
        message_action "Postdeploy actions will not be run."
        ;;
      --stop-after-reimport)
        _COMBAWA_REIMPORT_FORCE_EXIT=1
        message_action "Postdeploy actions will not be run."
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

# Compute steps to run. By default, every steps are run.
if [ "$_COMBAWA_ONLY_PREDEPLOY" == 1 ]; then
  _COMBAWA_RUN_MAIN_BUILD_STEP=0
  _COMBAWA_RUN_POSTDEPLOY=0
fi
if [ "$_COMBAWA_ONLY_POSTDEPLOY" == 1 ]; then
  _COMBAWA_RUN_PREDEPLOY=0
  _COMBAWA_RUN_MAIN_BUILD_STEP=0
fi

source $UTILS_DIR/prerequisites.sh

# Show the build config.
USAGE=$(cat <<-END
Environment built:\t${LIGHT_CYAN}$COMBAWA_BUILD_ENV${NC}
Build mode:\t${LIGHT_CYAN}$COMBAWA_BUILD_MODE${NC}
Generate a backup:\t${LIGHT_CYAN}$COMBAWA_DB_BACKUP_FLAG${NC}
Environment URI:\t${LIGHT_CYAN}${DRUSH_OPTIONS_URI:-undefined}${NC}
Retrieve DB from prod:\t${LIGHT_CYAN}$COMBAWA_DB_FETCH_FLAG${NC}
Reimport site:\t${LIGHT_CYAN}$COMBAWA_REIMPORT_REF_DUMP_FLAG${NC}
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
if [ "$COMBAWA_DB_BACKUP_FLAG" == "1" ] ; then
  backup_db
fi

# Download the reference dump file.
if [ "$COMBAWA_DB_FETCH_FLAG" == "1" ] ; then
  download_dump
fi

# Reimport the SQL reference dump.
if [ "$COMBAWA_REIMPORT_REF_DUMP_FLAG" == "1" ] ; then
  load_dump
fi

# Exit if the force exit flag has been raised. It's interesting to check if the prod dump doesn't have config to export.
if [ "$_COMBAWA_REIMPORT_FORCE_EXIT" == 1 ]; then
  if [ "$COMBAWA_REIMPORT_REF_DUMP_FLAG" == "1" ] ; then
    message_action "Exiting now that the reference dump has been reimported and the force exit flag has been raised!"
    else
      message_action "Build stopped as requested but no dump has been reimported. Didn't you forget to add the --reimport flag? ;-)."
  fi
  exit
fi

if [ "$_COMBAWA_RUN_PREDEPLOY" == 1 ]; then
  message_step "Running predeploy actions."
  # Return error codes if they happen.
  set -xe
  # Run the potential actions to do pre deployment.
  source $COMBAWA_SCRIPTS_DIR/predeploy_actions.sh
  set +xe

  message_confirm "Predeploy actions... Done!"

  if [ "$_COMBAWA_ONLY_PREDEPLOY" == "1" ] ; then
    message_action "Exiting now that predeploy actions have been run!"
  fi
fi

if [ "$_COMBAWA_RUN_MAIN_BUILD_STEP" == 1 ]; then
  # Run the build content.
  if [ "$COMBAWA_BUILD_MODE" == "install" ]; then
    message_step "Running install."
    # Return error codes if they happen.
    set -xe
    source $COMBAWA_SCRIPTS_DIR/install.sh
    set +xe
    if [[ $? != 0 ]]; then
      message_error "The install.sh generated an error. Check the logs."
      exit $?
    fi
    message_confirm "Install... OK!"
  elif [ "$COMBAWA_BUILD_MODE" == "update" ]; then
    message_step "Running update."
    # Return error codes if they happen.
    set -xe
    source $COMBAWA_SCRIPTS_DIR/update.sh
    set +xe
    if [[ $? != 0 ]]; then
      message_error "The update.sh generated an error. Check the logs."
      exit $?
    fi
    message_confirm "Update... OK!"
  fi
fi

if [ "$_COMBAWA_RUN_POSTDEPLOY" == 1 ]; then
  message_step "Running postdeploy actions."
  # Run the potential actions to do post deployment.
  # Return error codes if they happen.
  set -xe
  source $COMBAWA_SCRIPTS_DIR/postdeploy_actions.sh
  set +xe

  message_confirm "Postdeploy actions... Done!"

  if [ "$_COMBAWA_ONLY_POSTDEPLOY" == "1" ] ; then
    message_action "Exiting now that postdeploy actions have been run!"
  fi
fi

# Send a notification to inform that the build is done.
notify "The build is completed."
