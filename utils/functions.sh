#!/usr/bin/env bash

########## FUNCTION ##############
# Help function.
usage() {
  bold=$(tput bold)
  normal=$(tput sgr0)

  echo -e "Usage:"
  echo -e "Long version: ./build.sh --env dev --mode install --backup 1 --uri http://hc.fun --fetch-db-dump"
  echo -e "Short version: ./build.sh -e dev -m install -b 1 -u http://hc.fun -f"
  echo ''
  echo -e "Available arguments are:"
  echo -e "${bold}\t--env, -e: Environment to build.${normal}"
  echo -e '\t\tAllowed values are: dev, recette, preprod, prod'
  echo -e '\t\tDefault value: prod'
  echo ''
  echo -e "${bold}\t--mode, -m: Build mode${normal}"
  echo -e '\t\tAllowed values are: install, update, pull'
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
  exit
}

# Notify function.
notify() {
  if hash notify-send 2>/dev/null; then
    notify-send "$1"
  fi
  exit
}

# Load dump function.
load_dump() {
  if [ -f "$APP_ROOT/$COMBAWA_DUMP_FILE_NAME.gz" ]; then
    $DRUSH sql-drop -y;
    echo -e "${GREEN}DB drop... OK!${NC}"
    echo -e ""

    echo -e "${BLUE}Importing the new DB...${NC}"
    echo -e "${YELLOW}Decompressing file...${NC}"
    gzip -dkf $APP_ROOT/$COMBAWA_DUMP_FILE_NAME.gz
    echo -e "${GREEN}Done!${NC}"
    if hash pv 2>/dev/null; then
      pv --progress --name 'DB Import in progress' -tea "$APP_ROOT/$COMBAWA_DUMP_FILE_NAME" | $DRUSH sqlc
    else
      echo -e "${YELLOW}DB Import in progress...${NC}"
      $DRUSH sqlc < "$APP_ROOT/$COMBAWA_DUMP_FILE_NAME"
    fi
    echo -e "${GREEN}Done!${NC}"
    echo -e "${YELLOW}Removing tempory sql file...${NC}"
    rm -f $APP_ROOT/$COMBAWA_DUMP_FILE_NAME
    echo -e "${GREEN}Done!${NC}"
    echo -e "${GREEN}DB import... OK!${NC}"
    echo -e ""
  else
    echo "${RED}Database reference dump $APP_ROOT/$COMBAWA_DUMP_FILE_NAME.gz not found.${NC}"
    exit 1;
  fi
}
