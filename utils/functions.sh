#!/usr/bin/env bash

########## FUNCTION ##############

# Display messages in specific colors.
# Arg1: Message string.
# Arg2: Message color (see colors.sh for available colors).
message_color()
{
  _MESSAGE="$1"
  _COLOR="$2"
  echo -e "${_COLOR}${_MESSAGE}${NC}"
}

# Displays a step message.
message_step()
{
  _MESSAGE="$1"
  message_color "${_MESSAGE}" "${BLUE}"
}

# Displays an action message.
message_action()
{
  _MESSAGE="$1"
  message_color "${_MESSAGE}" "${YELLOW}"
}

# Displays a confirmation message.
message_confirm()
{
  _MESSAGE="$1"
  message_color "${_MESSAGE}" "${GREEN}"
  echo -e ""
}

# Displays a warning message.
message_warning()
{
  _MESSAGE="$1"
  message_color "${_MESSAGE}" "${ORANGE}"
}

# Displays an error message.
message_error()
{
  _MESSAGE="$1"
  message_color "${_MESSAGE}" "${RED}"
}

# Displays an error message and exits.
message_fatal()
{
  message_error "$1"
  return -1
}

# Display a variable override.
# Arg 1 is the source value.
# Arg 2 is the new value.
message_override()
{
  _VAL_SOURCE="$1"
  _VAL_OVERRIDE="$2"
  echo -e "From ${LIGHT_RED}${_VAL_SOURCE}${NC} to ${LIGHT_GREEN}${_VAL_OVERRIDE}${NC}"
}

# Useful to separate build steps.
section_separator()
{
  echo -e ""
  echo -e "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~"
  echo -e ""
}

# Help function.
usage()
{
  bold=$(tput bold)
  normal=$(tput sgr0)

  echo -e "Usage:"
  echo -e "Long version: ./build.sh --env dev --mode install --backup 1 --fetch-db-dump"
  echo -e "Short version: ./build.sh -e dev -m install -b 1 -u http://hc.fun -f"
  echo ''
  echo -e "Available arguments are:"
  echo -e "${bold}\t--env, -e: Environment to build.${normal}"
  echo -e '\t\tAllowed values are: dev, testing, prod'
  echo -e '\t\tDefault value: prod'
  echo ''
  echo -e "${bold}\t--mode, -m: Build mode${normal}"
  echo -e '\t\tAllowed values are: install, update'
  echo -e '\t\tDefault value: update'
  echo ''
  echo -e '\tIn addition to the build mode you may want to use those options to control the steps to execute:'
  echo -e "${bold}\t--no-predeploy:${normal} Do not process predeploy actions."
  echo -e "${bold}\t--no-postdeploy:${normal} Do not process postdeploy actions."
  echo -e "${bold}\t--only-predeploy:${normal} Only process predeploy actions."
  echo -e "${bold}\t--only-postdeploy:${normal} Only process postdeploy actions."
  echo ''
  echo -e "${bold}\t--yes, -y: Bypass confirmation step.${normal}"
  echo ''
  echo -e "${bold}\t--backup, -b: Generates a backup before building the project.${normal}"
  echo -e '\t\tAllowed values are: 0: does not generate a backup, 1: generates a backup.'
  echo -e '\t\tDefault value: 1'
  echo ''
  echo -e "${bold}\t--fetch-db-dump, -f: Fetch a fresh DB dump from the production site.${normal}"
  echo -e '\t\tUsed when the reference dump should be updated.'
  echo ''
  echo -e "${bold}\t--reimport, -r: Reimport the site from the reference dump.${normal}"
  echo -e '\t\tAllowed values are: 0: does not reimport the reference dump, 1: reimports the ref dump (drop and inject).'
  echo -e '\t\tDefault value: 0'
  echo ''
  echo -e "${bold}\t--stop-after-reimport: Utilitary flag to stop building after reimporting the DB.${normal}"
  echo -e '\t\tThis option is useful if you want to fetch your remote DB, import it and version its config.'
  exit
}

# Notify function.
notify()
{
  if hash notify-send 2>/dev/null; then
    notify-send "$1"
  fi

  message_confirm "$1"
}

# Notify error.
notify_error()
{
  _MESSAGE="$1"
  if hash notify-send 2>/dev/null; then
    notify-send "$1"
  fi
  message_error "$_MESSAGE"
  # Test if we have a suggestion message and display it if so.
  # Bash is weird, we must test if the second argument is empty (the opposite
  # test doesn't exist). It means that when we enter in the if, we don't want
  # to do anything.
  if [ -z ${2+x} ]; then
    # We need to use ":" to do nothing (an empty string generates a syntax
    # error)
    :
  else
    message_warning "$2"
  fi
  
  exit -1
}

# Notify fatal.
notify_fatal()
{

  _MESSAGE="$1"
  if hash notify-send 2>/dev/null; then
    notify-send "$1"
  fi
  message_error "$_MESSAGE"
  # Test if we have a suggestion message and display it if so.
  # Bash is weird, we must test if the second argument is empty (the opposite
  # test doesn't exist). It means that when we enter in the if, we don't want
  # to do anything.
  if [ -z ${2+x} ]; then
    # We need to use ":" to do nothing (an empty string generates a syntax
    # error)
    :
  else
    message_warning "$2"
  fi
  exit -10
}

# Generates a backup of the current DB
backup_db()
{
  message_step "Generating the backup DB dump:"
  # Store a security backup in case the update doesn't go right.
  DUMP_NAME="update-backup-script-$(date +%Y%m%d%H%M%S).sql";
  DUMP_PATH="$WEBROOT/../dumps/$DUMP_NAME"
  mkdir -p "$WEBROOT/../dumps/"
  message_action "Dump generation in progress..."
  $DRUSH sql-dump --structure-tables-list=cache_* --result-file=$DUMP_PATH --gzip
  # Remove older backups but keep the 10 youngest ones.
  if [[ $(ls -l $WEBROOT/../dumps/*.sql.gz | wc -l) -gt 10 ]]; then
    message_action "Cleaning up oldest backup dumps..."
    ls -tp $WEBROOT/../dumps/*.sql.gz | grep -v '/$' | tail -n +10 | tr '\n' '\0' | xargs -0 rm --
    message_confirm "Cleanup done!"
  fi
  message_confirm "Backup dump generated!"
}

# Download dump function.
download_dump()
{
  message_step "Updating the reference dump:"
  # Create the dumps dir if necessary.
  $(mkdir -p $(dirname -- "$COMBAWA_ROOT/$COMBAWA_DB_DUMP_PATH"))

  case $COMBAWA_DB_FETCH_METHOD in
    "cp" )
      message_action "Copying reference dump with 'cp'."
      cp $COMBAWA_DB_FETCH_PATH_SOURCE $COMBAWA_ROOT/$COMBAWA_DB_DUMP_PATH
      message_confirm "Reference dump update... OK!"
      ;;
    "scp" )
      message_action "Copying reference dump with 'scp'."
      scp -P $COMBAWA_DB_FETCH_SCP_PORT $COMBAWA_DB_FETCH_SCP_USER@$COMBAWA_DB_FETCH_SCP_SERVER:$COMBAWA_DB_FETCH_PATH_SOURCE $COMBAWA_ROOT/$COMBAWA_DB_DUMP_PATH
      message_confirm "Reference dump update... OK!"
      ;;
  esac
  if [[ $? != 0 ]]; then
    message_fatal "Error when retrieving the reference dump file. Are your paths valid? Access opened?"
  fi
}

# Load dump function.
load_dump()
{
  if [ -f "$COMBAWA_ROOT/$COMBAWA_DB_DUMP_PATH" ]; then
    message_step "Let's import the reference dump:"
    echo -e ""
    $DRUSH sql-drop -y;
    message_confirm "DB drop... OK!"
    echo -e ""

    message_step "Importing the new DB..."
    message_action "Decompressing file..."
    _DUMP_PATH_GZ="$COMBAWA_ROOT/$COMBAWA_DUMP_FILE_NAME"
    _DUMP_PATH="${_DUMP_PATH_GZ%.*}"
    gzip -dkf $_DUMP_PATH_GZ
    message_confirm "Done!"
    message_action "DB Import in progress..."
    if hash pv 2>/dev/null; then
      pv --progress -tea "$_DUMP_PATH" | $DRUSH sqlc
    else
      $DRUSH sqlc < "$_DUMP_PATH"
    fi
    message_confirm "Done!"
    message_action "Removing temporary sql file..."
    rm -f $_DUMP_PATH
    message_confirm "Done!"
    message_confirm "Reimporting the reference dump... OK!"
    echo -e ""
  else
    message_fatal "Database reference dump $COMBAWA_ROOT/$COMBAWA_DB_DUMP_PATH not found."
  fi
}
