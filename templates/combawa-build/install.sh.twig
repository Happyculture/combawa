#!/bin/bash
# Actions run when the script is in install mode.

# Install the site.
$DRUSH site-install --sites-subdir=default --locale=fr --existing-config minimal

# Ensure all configuration has been imported (even the splitted config).
$DRUSH cr
$DRUSH config:import

# Add administrator role to the user #1.
$DRUSH user:role:add administrator admin

# Fix Drupal annoying need to change permissions.
chmod u+w "$WEBROOT/sites/default"
chmod u+w "$WEBROOT/sites/default/settings.php"
