#!/bin/bash
# Actions to run before the main and shared deployment actions.
# It can be useful to backup, import databases or doing something similar.

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
    # $DRUSH sql-drop -y;
    # $DRUSH sqlc < "$WEBROOT/../reference_dump.sql";
    ;;
  recette|preprod)
    # $DRUSH sql-drop -y;
    # $DRUSH sqlc < "$WEBROOT/../reference_dump.sql";
    ;;
  prod)
    ;;
  *)
    echo "Unknown environment: $ENV. Please check your name."
    exit 1;
esac

