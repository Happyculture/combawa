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
# $FETCH_DB_DUMP: Flag to retrieve a DB dump from the production server.

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

