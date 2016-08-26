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

# Make drush a variable to use the one shipped with the repository.
DRUSH="$DIR/../drush/drush -y --root=$DIR"

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

