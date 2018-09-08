#!/usr/bin/env bash

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
echo -e "             /  6~6,       Checking prerequisites."
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

# @TODO: Load settings from conf file.

# Make drush a variable to use the one shipped with the repository.
DRUSH="$APP_ROOT/vendor/bin/drush -y --root=$WEBROOT"
if [ $WEBSITE_URI ]; then
  DRUSH="$DRUSH --uri=$WEBSITE_URI"
fi

echo $DRUSH

# Test DB connection.
echo -e ""
echo -e "${BLUE}Verifying database connectivity.${NC}"
{
  $DRUSH sql-connect
} &> /dev/null || { # catch
  echo -e ""
  echo -e "${ORANGE}The connection to the database is impossible.${NC}"
  while true; do
    echo ''
    read -p "Would you like to start to try to create a new database? [y/N/exit] " yn
    case $yn in
      [Yy]* )
        echo -e ""
        echo -e "${ORANGE}OK, let's collect your DB credentials in order to create it."
        echo -e "For the moment, only MySQL is supported.${NC}"
        echo -e ""

        read -p "What is your DB server name? [Default: localhost] " DB_SERVER_NAME
        if [ -z "$DB_SERVER_NAME" ]; then
          DB_SERVER_NAME="localhost"
        fi

        read -p "What is your DB username? [Default: root] " DB_SERVER_LOGIN
        if [ -z "$DB_SERVER_LOGIN" ]; then
          DB_SERVER_LOGIN="root"
        fi
        read -p "What is your DB password? [Default: none] " DB_SERVER_PWD
        if [ -z "$DB_SERVER_PWD" ]; then
          OUTPUT_DB_SERVER_PWD="none"
          else
          OUTPUT_DB_SERVER_PWD="The password that you correctly typed twice :)."
        fi
        while true; do
          read -p "What is your DB name? " DB_NAME
          if [ -z "$DB_NAME" ]; then
            echo -e "${ORANGE}The DB name can not be empty.${NC}"
            echo -e ""
            else
              break
          fi
        done
        echo ""
        echo -e "${LIGHT_CYAN}[DB credentials]"
        echo -e "DB Server: $DB_SERVER_NAME"
        echo -e "DB Login: $DB_SERVER_LOGIN"
        echo -e "DB Password: $OUTPUT_DB_SERVER_PWD"
        echo -e "DB name (Do not use "-" in name): $DB_NAME${NC}"

        while true; do
          echo ''
          read -p "Are those credentials correct? [y/N/exit] " yn
          case $yn in
            [Yy]* )
              # @TODO: Write the credentials back into the settings.local.php.
              echo "DB_SERVER_NAME=\"$DB_SERVER_NAME\"" > /tmp/combaya-sql.conf
              echo "DB_SERVER_LOGIN=\"$DB_SERVER_LOGIN\"" >> /tmp/combaya-sql.conf
              echo "DB_SERVER_PWD=\"$DB_SERVER_PWD\"" >> /tmp/combaya-sql.conf
              echo "DB_NAME=\"$DB_NAME\"" >> /tmp/combaya-sql.conf

              if [ -z "$DB_SERVER_PWD" ]; then
                mysql -h $DB_SERVER_NAME -u $DB_SERVER_LOGIN <<MY_QUERY
CREATE DATABASE $DB_NAME;
MY_QUERY
                else
                mysql -h $DB_SERVER_NAME -u $DB_SERVER_LOGIN -p$DB_SERVER_PWD <<MY_QUERY
CREATE DATABASE $DB_NAME;
MY_QUERY
              fi

              if [[ $? != 0 ]]; then
                echo -e "${RED}The MySQL access that you provided is not valid and the DB has not been created.${NC}"
                exit 1;
              fi

              echo -e ""
              echo -e "${LIGHT_GREEN}Database $DB_NAME successfully created.${NC}"
              break;;
            [Nn]* )
              echo -e "${YELLOW}No database has been created. Please note that a DB is required in order to be able to build a project.${NC}"
              exit;;
            "exit"|"q" ) exit;;
            * ) echo -e "${ORANGE}Please answer yes or no.${NC}";;
          esac
        done
        break;;
      [Nn]* )
        echo -e "${YELLOW}No database has been created. Please note that a DB is required in order to be able to build a project.${NC}"
        exit;;
      "exit"|"q" ) exit;;
      * ) echo -e "${ORANGE}Please answer yes or no.${NC}";;
    esac
  done
}

#$DRUSH sql-connect
#if [[ $? != 0 ]]; then

#fi
#set -e
echo -e ""
echo -e "${GREEN}DB connection... OK!${NC}"

echo -e ""
echo -e "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~"
echo -e ""
