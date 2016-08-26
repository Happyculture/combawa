#!/bin/bash
# Action to run after the main and shared deployment actions.
# It can be useful to enable specific modules for instance.

# Available variables are:
# $DRUSH: Executable to Drush with -y --root arguments specified.
# $WEBROOT: Path of the repo root path.
# $BUILD_MODE: Action to do, either install or update.
# $ENV: Environnement on which the build is done.
# $BACKUP_BASE: Flag if the backup of the database must be generated.
# $URI: URI of the site you build.
DRUSH=$1
WEBROOT=$2
BUILD_MODE=$3
ENV=$4
BACKUP_BASE=$5
URI=$6

# Return error codes if they happen.
set -e

case $ENV in
  dev)
    # Examples:
    # Compile CSS for development.
    # (
    #   cd $WEBROOT/sites/all/themes/custom_theme/;
    #   compass compile --force -e production
    # )

    # Turn off the aggregation to avoid to turn crazy.
    # $DRUSH vset preprocess_css 0;
    # $DRUSH vset preprocess_js 0;
    # Enable UIs.
    # $DRUSH en -y devel field_ui diff views_ui;
    # Fetch missing images from the remote server.
    # $DRUSH en -y stage_file_proxy
    # $DRUSH vset stage_file_proxy_origin "https://www.example.org"
    # Connect.
    # $DRUSH uli
    ;;
  recette|preprod)
    # Examples:
    # Compile CSS for development.
    # (
    #   cd $WEBROOT/sites/all/themes/custom_theme/;
    #   compass compile --force -e production
    # )

    # Disable dev modules.
    # $DRUSH dis -y devel field_ui diff views_ui
    # Fetch missing images from the remote server.
    # $DRUSH en -y stage_file_proxy
    # $DRUSH vset stage_file_proxy_origin "https://www.example.org"
    ;;
  prod)
    ;;
  *)
    echo "Unknown environment: $ENV. Please check your name."
    exit 1;
esac

