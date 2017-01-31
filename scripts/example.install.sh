#!/bin/bash
set -xe

# Available variables are:
# $DRUSH: Executable to Drush with -y --root arguments specified.
# $WEBROOT: Path of the repo root path.
# $BUILD_MODE: Action to do, either install or update.
# $ENV: Environnement on which the build is done.
# $BACKUP_BASE: Flag if the backup of the database must be generated.
# $URI: URI of the site you build.
# $FETCH_DB_DUMP: Flag to retrieve a DB dump from the production server.
# $OFFLINE: Flag if we must avoid remote connections.

# Install the site.
#$DRUSH sql-drop -y
#$DRUSH sqlc < "$DIR/../reference_dump.sql"

# Flush drush cache to identify new commands such as rr.
$DRUSH cr

# Rebuild the structure since data come from another server structure.
$DRUSH rr

# Disable APC to avoid features revert issues.
$DRUSH dis apc

# Run the updates.
$DRUSH updb -y

# Flush the caches.
$DRUSH cr

# Revert the features to make sure that the permissions are set.
$DRUSH fra

# Flush the caches againnnnnnnnn.
$DRUSH cr

# Fix permissions.
chmod u+w ../www/sites/default

