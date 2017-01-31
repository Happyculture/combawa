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

# Flush drush cache to identify new commands such as rr.
$DRUSH cr

# Enable the maintenance page.
$DRUSH sset system.maintenance_mode 1

# Run the updates.
$DRUSH updb -y
$DRUSH entup -y

# Flush the caches.
$DRUSH cr

# Revert the features to make sure that the permissions are set.
$DRUSH features-import-all --bundle=$APP_BUNDLE

# Flush the caches againnnnnnnnn.
$DRUSH cr

# Remove the maintenance page.
$DRUSH sset system.maintenance_mode 0
