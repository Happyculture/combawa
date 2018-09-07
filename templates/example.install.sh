#!/bin/bash
# Actions run when the script is in install mode.
# Available variables are defined in settings.sh.

# Return error codes if they happen.
set -xe

# Install the site.
$DRUSH site-install $INSTALL_PROFILE --locale=fr

