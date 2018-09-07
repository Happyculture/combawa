#!/bin/bash
# Actions run when the script is in update mode.
# Available variables are defined in settings.sh.

# Return error codes if they happen.
set -xe

# Flush drush cache to identify new commands such as rr.
$DRUSH cr

# Enable the maintenance page.
$DRUSH sset system.maintenance_mode 1

# Run the DB updates.
$DRUSH updb --no-post-updates

# Flush the caches.
$DRUSH cr

# Import the configuration
$DRUSH cim

# Flush the caches againnnnnnnnn.
$DRUSH cr

# Run the post updates.
$DRUSH updb

# Remove the maintenance page.
$DRUSH sset system.maintenance_mode 0

