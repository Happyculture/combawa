#!/bin/bash
set -xe

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

# Install the site.
#$DRUSH sql-drop -y
#$DRUSH sqlc < "$DIR/../reference_dump.sql"

# Flush drush cache to identify new commands such as rr.
$DRUSH cc drush

# Rebuild the structure since data come from another server structure.
$DRUSH rr

# Disable APC to avoid features revert issues.
$DRUSH dis apc

# Run the updates.
$DRUSH updb -y

# Flush the caches.
$DRUSH cc all

# Revert the features to make sure that the permissions are set.
$DRUSH fra

# Flush the caches againnnnnnnnn.
$DRUSH cc all

# Fix permissions.
chmod u+w ../www/sites/default

