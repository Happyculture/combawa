#!/bin/bash
# Action to run after the main and shared deployment actions.
# It can be useful to enable specific modules for instance.

# Available variables are:
# $DRUSH: Executable to Drush with -y --root arguments specified.
# $WEBROOT: Path of the repo root path.
# $BUILD_MODE: Action to do, either install or update.
# $ENV: Environnement on which the build is done.
# $BACKUP_BASE: Flag if the backup of the database must be generated.
# $URI: URI of the site you build.
# $FETCH_DB_DUMP: Flag to retrieve a DB dump from the production server.
# $OFFLINE: Flag if we must avoid remote connections.

BUNDLE=/usr/local/rvm/wrappers/default/bundle
if [ ! -f /usr/local/rvm/wrappers/default/bundle ]; then
  BUNDLE=`which bundle`
fi

# Return error codes if they happen.
set -e

case $ENV in
  dev)
    # Compile CSS for development.
    # (
    #   cd $WEBROOT/sites/all/themes/custom_theme/
    #   if [ $OFFLINE == 0] ; then $BUNDLE install; fi # reads .bundle/config to find the vendor path.
    #   $BUNDLE exec compass compile --force -e development
    # )
    #
    # Turn off the aggregation to avoid to turn crazy.
    # $DRUSH vset preprocess_css 0;
    # $DRUSH vset preprocess_js 0;
    #
    # Enable UIs.
    # $DRUSH en -y devel diff features_ui field_ui views_ui;
    #
    # Fetch missing images from the remote server.
    # if [ $OFFLINE == 0] ; then
    #   $DRUSH en -y stage_file_proxy
    #   $DRUSH vset stage_file_proxy_origin "https://www.example.org"
    # fi
    #
    # Connect.
    # $DRUSH uli
    ;;
  recette|preprod)
    # Compile CSS for development.
    # (
    #   cd $WEBROOT/sites/all/themes/custom_theme/
    #   if [ $OFFLINE == 0] ; then $BUNDLE install; fi # reads .bundle/config to find the vendor path.
    #   $BUNDLE exec compass compile --force -e development
    # )

    # Fetch missing images from the remote server.
    # if [ $OFFLINE == 0] ; then
    #   $DRUSH en -y stage_file_proxy
    #   $DRUSH vset stage_file_proxy_origin "https://www.example.org"
    # fi
    ;;
  prod)
    # Compile CSS for production.
    # (
    #   cd $WEBROOT/sites/all/themes/custom_theme/
    #   if [ $OFFLINE == 0] ; then $BUNDLE install; fi # reads .bundle/config to find the vendor path.
    #   $BUNDLE exec compass compile --force -e production
    # )

    # Disable dev modules.
    # $DRUSH dis -y devel field_ui diff views_ui
    ;;
  *)
    echo "Unknown environment: $ENV. Please check your name."
    exit 1;
esac

