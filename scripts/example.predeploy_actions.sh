#!/bin/bash
# Actions to run before the main and shared deployment actions.
# It can be useful to backup, import databases or doing something similar.

# Return error codes if they happen.
set -e

if [ $FETCH_DB_DUMP == 1 ] ; then
  echo "Updating the reference dump."
  # Do the magic that connects to the server and retrieves the SQL dump.
  # $ scp $SSH_NAME:$PROD_DB_DUMP_PATH "$APP_ROOT/$DUMP_FILE_NAME.gz"
  # if [[ $? != 0 ]]; then
  #   echo "Impossible to retrieve the dump file. Verify the file name."
  #   exit 1
  # fi
fi

case $ENV in
  dev)
    # gzip -d -k "$APP_ROOT/$DUMP_FILE_NAME.gz";
    # $DRUSH sql-drop -y;
    # $DRUSH sqlc < "$APP_ROOT/$DUMP_FILE_NAME";
    # rm -f "$APP_ROOT/$DUMP_FILE_NAME";
    ;;
  recette|preprod)
    # gzip -d -k "$APP_ROOT/$DUMP_FILE_NAME.gz";
    # $DRUSH sql-drop -y;
    # $DRUSH sqlc < "$APP_ROOT/$DUMP_FILE_NAME";
    # rm -f "$APP_ROOT/$DUMP_FILE_NAME";
    ;;
  prod)
    ;;
  *)
    echo "Unknown environment: $ENV. Please check your name."
    exit 1;
esac
