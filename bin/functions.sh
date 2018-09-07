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
  echo -e '\t\tAllowed values are: install, update'
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
  echo ''
  echo -e "${bold}\t--offline, -o: Run offline to avoid trying to make remote connections.${normal}"
  echo -e '\t\tAllowed values are: 0: make remote connections, 1: avoid remote connections.'
  echo -e '\t\tDefault value: 0'
  exit
}
