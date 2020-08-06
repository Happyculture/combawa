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
  echo -e "Long version: ./build.sh --env dev --mode install --backup 1 --uri http://hc.fun --fetch-db-dump"
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
  echo -e "${bold}\t--backup, -b: Generates a backup before building the project.${normal}"
  echo -e '\t\tAllowed values are: 0: does not generate a backup, 1: generates a backup.'
  echo -e '\t\tDefault value: 1'
  echo ''
  echo -e "${bold}\t--uri, -u: Local URL of your project${normal}"
  echo -e '\t\tUsed when the final drush uli command is runned.'
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
  notify "$_MESSAGE"
}

# Download dump function.
download_dump()
{
  message_step "Updating the reference dump:"
  if [ -z "$COMBAWA_DB_FETCH_CNX_STRING" ]; then
    message_action "Copying locally the dump file..."
    cp $COMBAWA_PROD_DB_DUMP_PATH "$COMBAWA_ROOT/$COMBAWA_DUMP_FILE_NAME.gz"
    message_confirm "Done!"
  else
    message_action "Fetching the dump from remote source..."
    scp $COMBAWA_DB_FETCH_CNX_STRING:$COMBAWA_PROD_DB_DUMP_PATH "$COMBAWA_ROOT/$COMBAWA_DUMP_FILE_NAME.gz"
    message_confirm "Done!"
  fi
  if [[ $? != 0 ]]; then
    message_error "Impossible to retrieve the dump file. Verify the file name."
    exit 1
  fi
}

# Load dump function.
load_dump()
{
  if [ -f "$COMBAWA_ROOT/$COMBAWA_DUMP_FILE_NAME.gz" ]; then
    message_step "Let's import the reference dump:"
    echo -e ""
    $DRUSH sql-drop -y;
    message_confirm "DB drop... OK!"
    echo -e ""

    message_step "Importing the new DB..."
    message_action "Decompressing file..."
    gzip -dkf $COMBAWA_ROOT/$COMBAWA_DUMP_FILE_NAME.gz
    message_confirm "Done!"
    if hash pv 2>/dev/null; then
      pv --progress --name 'DB Import in progress' -tea "$COMBAWA_ROOT/$COMBAWA_DUMP_FILE_NAME" | $DRUSH sqlc
    else
      message_action "DB Import in progress..."
      $DRUSH sqlc < "$COMBAWA_ROOT/$COMBAWA_DUMP_FILE_NAME"
    fi
    message_confirm "Done!"
    message_action "Removing tempory sql file..."
    rm -f $COMBAWA_ROOT/$COMBAWA_DUMP_FILE_NAME
    message_confirm "Done!"
    message_confirm "Reimporting the reference dump... OK!"
    echo -e ""
  else
    message_error "Database reference dump $COMBAWA_ROOT/$COMBAWA_DUMP_FILE_NAME.gz not found."
    exit 1;
  fi
}
