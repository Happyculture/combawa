#!/usr/bin/env bash

echo -e ""
echo -e "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~"
echo -e ""

# Check config directory existance.
echo -e ""
echo -e "${BLUE}Verifying config directory setup.${NC}"
if [ ! -d "$CONFIG_DIR" ]; then
  echo -e ""
  echo -e "${ORANGE}Your <app>/config directory does not exist yet.${NC}"
  while true; do
    echo ''
    read -p "Would you like to create it? [y/N/exit] " yn
    case $yn in
        [Yy]* )
          mkdir $CONFIG_DIR
          echo -e ""
          echo -e "${LIGHT_GREEN}Config directory $CONFIG_DIR created.${NC}"
          break;;
        [Nn]* )
          echo -e "${YELLOW}Config directory not created.${NC}"
          exit;;
        "exit"|"q" ) exit;;
        * ) echo -e "${ORANGE}Please answer yes or no.${NC}";;
    esac
  done
fi
echo -e ""
echo -e "${GREEN}Config directory... OK!${NC}"

echo -e ""
echo -e "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~"
echo -e ""

# Load the config file.
echo -e "${BLUE}Verifying environment config settings.${NC}"
if [ ! -f "$CONFIG_DIR/env_config.conf" ]; then
  echo -e ""
  echo -e "${ORANGE}There is no <app>/config/env_config.conf config file for the environment.${NC}"
  while true; do
    echo ''
    read -p "Would you like to create it? [y/N] " yn
    case $yn in
      [Yy]* )
        while true; do
          echo -e ""
          echo -e "Which environment are you building?"
          echo -e ""
          echo -e "\t1) Dev"
          echo -e "\t2) Recette"
          echo -e "\t3) Preprod"
          echo -e "\t4) Prod"
          read -p "Environment: " env_name
          case $env_name in
            "exit"|"q" ) exit;;
            1|2|3|4)
              if [ $env_name -eq 1 ]; then
                ENV_NAME=dev
                elif [ $env_name -eq 2 ]; then
                ENV_NAME=recette
                elif [ $env_name -eq 3 ]; then
                ENV_NAME=preprod
                elif [ $env_name -eq 4 ]; then
                ENV_NAME=prod
              fi
              break;
            ;;
            * )
              echo -e ""
              echo -e "${ORANGE}Please select one of the listed options.${NC}"
              ;;
            esac
        done
        while true; do
          echo -e ""
          echo -e "Which mode should be used?"
          echo -e ""
          echo -e "\t1) Install"
          echo -e "\t2) Update"
          read -p "Mode: " build_mode
          case $build_mode in
            "exit"|"q" ) exit;;
            1|2)
              if [ $env_name -eq 1 ]; then
                BUILD_MODE=install
                elif [ $env_name -eq 2 ]; then
                BUILD_MODE=update
              fi
              break;
            ;;
            * )
              echo -e ""
              echo -e "${ORANGE}Please select one of the listed options.${NC}"
              ;;
            esac
        done
        # Export the config in a file.
        export BUILD_MODE ENV_NAME
        TEMPLATE=$(<$TEMPLATES_DIR/env_config.conf)
        echo "$TEMPLATE" | envsubst > $CONFIG_DIR/env_config.conf

        break;;
      [Nn]* )
        echo -e "${YELLOW}Config file not created.${NC}"
        exit;;
      "exit"|"q" ) exit;;
      * )
        echo -e ""
        echo -e "${ORANGE}Please answer yes or no.${NC}"
        ;;
    esac
  done
fi

echo -e ""
echo "Loading config settings..." >&2
source $CONFIG_DIR/env_config.conf
if [ -r $CONFIG_DIR/.env_config.conf ]; then
  echo -e ""
  echo -e "${ORANGE}An overriden settings file exists and is loaded.${NC}"
  source $CONFIG_DIR/.env_config.conf
fi
echo -e ""
echo -e "${GREEN}Config settings... Loaded!${NC}"

echo -e ""
echo -e "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~"
echo -e ""


# Check config directory existance.
echo -e ""
echo -e "${BLUE}Verifying scripts directory setup.${NC}"
if [ ! -d "$APP_SCRIPTS_DIR" ]; then
  echo -e ""
  echo -e "${ORANGE}Your <app>/scripts directory does not exist yet.${NC}"
  while true; do
    echo ''
    read -p "Would you like to create it? [y/N/exit] " yn
    case $yn in
        [Yy]* )
          mkdir $APP_SCRIPTS_DIR
          echo -e ""
          echo -e "${LIGHT_GREEN}Scripts directory $APP_SCRIPTS_DIR created.${NC}"
          break;;
        [Nn]* )
          echo -e "${YELLOW}Scripts directory not created.${NC}"
          exit;;
        "exit"|"q" ) exit;;
        * ) echo -e "${ORANGE}Please answer yes or no.${NC}";;
    esac
  done
fi
echo -e ""
echo -e "${GREEN}Scripts directory... OK!${NC}"

echo -e ""
echo -e "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~"
echo -e ""

# Check predeploy action script.
echo -e ""
echo -e "${BLUE}Verifying predeploy action script.${NC}"
if [ ! -f "$APP_SCRIPTS_DIR/predeploy_actions.sh" ]; then
  echo -e ""
  echo -e "${ORANGE}There is no predeploy actions script at the moment or its not readable.${NC}"
  while true; do
    echo ''
    read -p "Would you like to start with a template? [y/N/exit] " yn
    case $yn in
        [Yy]* )
          cp $TEMPLATES_DIR/predeploy_actions.sh $APP_SCRIPTS_DIR/predeploy_actions.sh
          echo -e ""
          echo -e "${LIGHT_GREEN}Predeploy actions template added: $APP_SCRIPTS_DIR/predeploy_actions.sh${NC}"
          break;;
        [Nn]* )
          echo -e "${YELLOW}No predeploy actions script has been added. Please note that this file is required in order to be able to build a project.${NC}"
          exit;;
        "exit"|"q" ) exit;;
        * ) echo -e "${ORANGE}Please answer yes or no.${NC}";;
    esac
  done
fi
echo -e ""
echo -e "${GREEN}Predeploy actions script... OK!${NC}"

echo -e ""
echo -e "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~"
echo -e ""

# Check postdeploy actions script.
echo -e ""
echo -e "${BLUE}Verifying postdeploy action script.${NC}"
if [ ! -f "$APP_SCRIPTS_DIR/postdeploy_actions.sh" ]; then
  echo -e ""
  echo -e "${ORANGE}There is no postdeploy actions script at the moment or its not readable.${NC}"
  while true; do
    echo ''
    read -p "Would you like to start with a template? [y/N/exit] " yn
    case $yn in
        [Yy]* )
          cp $TEMPLATES_DIR/postdeploy_actions.sh $APP_SCRIPTS_DIR/postdeploy_actions.sh
          echo -e ""
          echo -e "${LIGHT_GREEN}Postdeploy actions template added: $APP_SCRIPTS_DIR/postdeploy_actions.sh${NC}"
          break;;
        [Nn]* )
          echo -e "${YELLOW}No postdeploy actions script has been added. Please note that this file is required in order to be able to build a project.${NC}"
          exit;;
        "exit"|"q" ) exit;;
        * ) echo -e "${ORANGE}Please answer yes or no.${NC}";;
    esac
  done
fi
echo -e ""
echo -e "${GREEN}Postdeploy actions script... OK!${NC}"

echo -e ""
echo -e "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~"
echo -e ""

# Preliminary verification to avoid running actions
# if the requiprements are not met.
case $BUILD_MODE in
  "install" )
    echo -e ""
    echo -e "${BLUE}Verifying install.sh action script.${NC}"
    if [ ! -f "$APP_SCRIPTS_DIR/install.sh" ]; then
      echo -e ""
      echo -e "${ORANGE}There is no <app>/scripts/install.sh script at the moment or its not readable.${NC}"
      while true; do
        echo ''
        read -p "Would you like to start with a template? [y/N/exit] " yn
        case $yn in
            [Yy]* )
              cp $TEMPLATES_DIR/install.sh $APP_SCRIPTS_DIR/install.sh
              echo -e ""
              echo -e "${LIGHT_GREEN}Install.sh template added: $APP_SCRIPTS_DIR/install.sh${NC}"
              break;;
            [Nn]* )
              echo -e "${YELLOW}No install.sh script has been added. Please note that this file is required in order to be able to build a project.${NC}"
              exit;;
            "exit"|"q" ) exit;;
            * ) echo -e "${ORANGE}Please answer yes or no.${NC}";;
        esac
      done
    fi
    echo -e ""
    echo -e "${GREEN}Install.sh check... OK!${NC}"
    ;;
  "update" )
    echo -e ""
    echo -e "${BLUE}Verifying update.sh action script.${NC}"
    if [ ! -f "$APP_SCRIPTS_DIR/update.sh" ]; then
      echo -e ""
      echo -e "${ORANGE}There is no <app>/scripts/update.sh script at the moment or its not readable.${NC}"
      while true; do
        echo ''
        read -p "Would you like to start with a template? [y/N/exit] " yn
        case $yn in
            [Yy]* )
              cp $TEMPLATES_DIR/update.sh $APP_SCRIPTS_DIR/update.sh
              echo -e ""
              echo -e "${LIGHT_GREEN}Update.sh template added: $APP_SCRIPTS_DIR/update.sh${NC}"
              break;;
            [Nn]* )
              echo -e "${YELLOW}No update.sh script has been added. Please note that this file is required in order to be able to build a project.${NC}"
              exit;;
            "exit"|"q" ) exit;;
            * ) echo -e "${ORANGE}Please answer yes or no.${NC}";;
        esac
      done
    fi
    echo -e ""
    echo -e "${GREEN}Update.sh check... OK!${NC}"
    ;;
    * )
      echo -e "${RED}Build mode unknown.${NC}"
      exit -1
      ;;
esac

echo -e ""
echo -e "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~"
echo -e ""

# Make drush a variable to use the one shipped with the repository.
DRUSH="$APP_ROOT/vendor/bin/drush -y --root=$WEBROOT"
if [ $WEBSITE_URI ]; then
  DRUSH="$DRUSH --uri=$WEBSITE_URI"
fi

# Test DB connection.
echo -e ""
echo -e "${BLUE}Verifying database connectivity.${NC}"
set +e
$DRUSH sql-connect
if [[ $? != 0 ]]; then
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
        echo -e "DB name: $DB_NAME${NC}"

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
fi
set -e
echo -e ""
echo -e "${GREEN}DB connection... OK!${NC}"

echo -e ""
echo -e "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~"
echo -e ""
