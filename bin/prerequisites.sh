#!/usr/bin/env bash

# Check config directory existance.
echo -e ""
echo -e "${BLUE}Verifying config directory setup.${NC}"
echo -e ""
if [ ! -d "$CONFIG_DIR" ]; then
  echo -e "${ORANGE}Your config directory does not exist yet.${NC}"
  while true; do
    echo ''
    echo "Would you like to create it?"
    read -p "$CONFIG_DIR [y/N] " yn
    case $yn in
        [Yy]* )
          mkdir $CONFIG_DIR
          echo -e "${LIGHT_GREEN}Config directory created.${NC}"
          break;;
        [Nn]* )
          echo -e "${LIGHT_CYAN}Config directory not created.${NC}"
          exit;;
        "" ) exit;;
        * ) echo -e "${ORANGE}Please answer yes or no.${NC}";;
    esac
  done
  else
    echo -e "${GREEN}Config directory... OK!${NC}"
fi

echo -e ""
echo -e "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~"
echo -e ""

# Override default settings per project.
echo -e "${BLUE}Verifying environment config settings.${NC}"
echo -e ""
if [ ! -f "$CONFIG_DIR/config_$ENV.cfg" ]; then
  echo -e "${ORANGE}There is no config file for the $ENV environment.${NC}"
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
            ""|"q" ) exit;;
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
            ""|"q" ) exit;;
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
        break;;
      [Nn]* )
        echo -e "${LIGHT_CYAN}Config directory not created.${NC}"
        exit;;
      "" ) exit;;
      * )
        echo -e ""
        echo -e "${ORANGE}Please answer yes or no.${NC}"
        ;;
    esac
  done
  exit 1
  else
    echo -e "${GREEN}Config $ENV settings... OK!${NC}"
fi
source $CONFIG_DIR/config_$ENV.cfg

