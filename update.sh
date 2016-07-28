#!/bin/bash
set -xe

# Helper to let you run the install script from anywhere.
currentscriptpath () {
local fullpath=`echo "$(readlink -f $0)"`
local fullpath_length=`echo ${#fullpath}`
local scriptname="$(basename $0)"
local scriptname_length=`echo ${#scriptname}`
local result_length=`echo $fullpath_length - $scriptname_length - 1 | bc`
local result=`echo $fullpath | head -c $result_length`
echo $result
}

# Working directory.
RESULT=$(currentscriptpath)
DIR="$RESULT/../www"

# Make drush a variable to use default parameters.
DRUSH="drush -y --root=$DIR"

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
