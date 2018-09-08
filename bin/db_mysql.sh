#!/usr/bin/env bash

# Make drush a variable to use the one shipped with the repository.
DRUSH="$APP_ROOT/vendor/bin/drush -y --root=$WEBROOT"
if [ $WEBSITE_URI ]; then
  DRUSH="$DRUSH --uri=$WEBSITE_URI"
fi

echo "$DRUSH sql:connect"
echo "AAAA"
exit

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
