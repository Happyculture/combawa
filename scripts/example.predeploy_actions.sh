#!/bin/bash
# Actions to run before the main and shared deployment actions.
# It can be useful to backup, import databases or doing something similar.

# Return error codes if they happen.
set -e

if [ $FETCH_DB_DUMP == 1 ] ; then
  echo "Updating the reference dump."
  # Do the magic that connects to the server and retrieves the SQL dump.
  # scp $SSH_NAME:$PROD_DB_DUMP_PATH "$APP_ROOT/$DUMP_FILE_NAME.gz"
  # if [[ $? != 0 ]]; then
  #   echo "Impossible to retrieve the dump file. Verify the file name."
  #   exit 1
  # fi
fi

case $ENV in
  dev)
    # if [ -f "$APP_ROOT/$DUMP_FILE_NAME.gz" ]; then
    #   $DRUSH sql-drop -y;
    #   zcat "$APP_ROOT/$DUMP_FILE_NAME.gz" | $DRUSH sqlc
    # else
    #   echo "Database reference bump not found. Keeping the current database."
    # fi
    ;;
  recette|preprod)
    # if [ -f "$APP_ROOT/$DUMP_FILE_NAME.gz" ]; then
    #   $DRUSH sql-drop -y;
    #   zcat "$APP_ROOT/$DUMP_FILE_NAME.gz" | $DRUSH sqlc
    # else
    #   echo "Database reference bump not found. Keeping the current database."
    # fi
    ;;
  prod)
    ;;
  *)
    echo "Unknown environment: $ENV. Please check your name."
    exit 1;
esac
