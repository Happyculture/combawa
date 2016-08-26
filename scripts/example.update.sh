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

# Rebuild the structure since data come from another server structure.
$DRUSH rr

# Flush drush cache to identify new commands such as rr.
$DRUSH cc drush

# Enable the maintenance page.
$DRUSH vset maintenance_mode 1

# Enable the V2.5 module.
$DRUSH en cd_v2

# Run the updates.
$DRUSH updb -y

# Flush the caches.
$DRUSH cc all

# Revert the features to make sure that the permissions are set.
$DRUSH fra --force

# Flush the caches againnnnnnnnn.
$DRUSH cc all

# Remove the maintenance page.
$DRUSH vset maintenance_mode 0

# Fix permissions.
chmod u+w ../www/sites/default
