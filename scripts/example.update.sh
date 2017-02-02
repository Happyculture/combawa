#!/bin/bash
# Actions run when the script is in update mode.
# Available variables are defined in settings.sh.

# Return error codes if they happen.
set -xe

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
