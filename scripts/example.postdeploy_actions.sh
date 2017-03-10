#!/bin/bash
# Action to run after the main and shared deployment actions.
# It can be useful to enable specific modules for instance.
# Available variables are defined in settings.sh.

# Return error codes if they happen.
set -e

case $ENV in
  dev)
    # Compile CSS for development.
    (
      cd $WEBROOT/sites/all/themes/custom_theme/
      if [ $OFFLINE == 0 ] ; then $BUNDLE install; fi # reads .bundle/config to find the vendor path.
      $BUNDLE exec compass compile --force -e development
    )

    # Turn off the aggregation to avoid to turn crazy.
    $DRUSH cset system.performance css.preprocess 0;
    $DRUSH cset system.performance js.preprocess 0;

    # Enable UIs.
    $DRUSH en -y dblog devel diff features_ui field_ui views_ui;

    # Fetch missing images from the remote server.
    if [ $OFFLINE == 0 ] ; then
      $DRUSH en -y stage_file_proxy
      $DRUSH cset stage_file_proxy.settings origin "https://www.example.org"
    fi

    # Connect.
    $DRUSH uli
    ;;
  recette|preprod)
    # Compile CSS for development.
    (
      cd $WEBROOT/sites/all/themes/custom_theme/
      if [ $OFFLINE == 0 ] ; then $BUNDLE install; fi # reads .bundle/config to find the vendor path.
      $BUNDLE exec compass compile --force -e development
    )

    # Enable extra modules.
    $DRUSH en -y dblog

    # Fetch missing images from the remote server.
    if [ $OFFLINE == 0 ] ; then
      $DRUSH en -y stage_file_proxy
      $DRUSH cset stage_file_proxy.settings origin "https://www.example.org"
    fi
    ;;
  prod)
    # Compile CSS for production.
    (
      cd $WEBROOT/sites/all/themes/custom_theme/
      if [ $OFFLINE == 0 ] ; then $BUNDLE install; fi # reads .bundle/config to find the vendor path.
      $BUNDLE exec compass compile --force -e production
    )

    # Disable dev modules.
    $DRUSH dis -y devel field_ui diff views_ui
    ;;
  *)
    echo "Unknown environment: $ENV. Please check your name."
    exit 1;
esac

