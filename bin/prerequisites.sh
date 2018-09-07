#!/usr/bin/env bash

# Check config directory existance.
echo -e ""
echo -e "${BLUE}Verifying config directory setup.${NC}"
echo -e ""
if [ ! -d "$CONFIG_DIR" ]; then
  echo -e "${ORANGE}Your config directory does not exist yet.${NC}"
  while true; do
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
