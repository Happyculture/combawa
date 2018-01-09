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
      cd $WEBROOT/themes/custom/$CUSTOM_THEME
      if [ $OFFLINE == 0 ] ; then $NPM install; fi
      $NPM run build-dev
    )

    # Turn off the aggregation to avoid to turn crazy.
    $DRUSH cset system.performance css.preprocess 0;
    $DRUSH cset system.performance js.preprocess 0;

    # Enable UIs.
    $DRUSH en -y dblog devel diff field_ui views_ui;

    # Fetch missing images from the remote server.
    if [ $OFFLINE == 0 ] ; then
      $DRUSH en -y stage_file_proxy
      $DRUSH cset stage_file_proxy.settings origin "https://www.example.org"
    fi

    # Environment indicator.
    $DRUSH en environment_indicator
    $DRUSH config-set environment_indicator.indicator bg_color "#768706"
    $DRUSH config-set environment_indicator.indicator fg_color "#FFFFFF"
    $DRUSH config-set environment_indicator.indicator name "Dev"

    # Connect.
    $DRUSH uli
    ;;
  recette|preprod)
    # Compile CSS for production.
    (
      cd $WEBROOT/themes/custom/$CUSTOM_THEME
      if [ $OFFLINE == 0 ] ; then $NPM install; fi
      $NPM run build
    )

    # Enable extra modules.
    $DRUSH en -y dblog

    # Fetch missing images from the remote server.
    if [ $OFFLINE == 0 ] ; then
      $DRUSH en -y stage_file_proxy
      $DRUSH cset stage_file_proxy.settings origin "https://www.example.org"
    fi

    # Environment indicator.
    $DRUSH en environment_indicator
    $DRUSH config-set environment_indicator.indicator bg_color "#a25509"
    $DRUSH config-set environment_indicator.indicator fg_color "#FFFFFF"
    $DRUSH config-set environment_indicator.indicator name "Recette"
    ;;
  prod)
    # Compile CSS for production.
    (
      cd $WEBROOT/themes/custom/$CUSTOM_THEME
      if [ $OFFLINE == 0 ] ; then $NPM install; fi
      $NPM run build
    )

    # Disable dev modules.
    $DRUSH pmu -y devel field_ui diff views_ui
    ;;
  *)
    echo "Unknown environment: $ENV. Please check your name."
    exit 1;
esac

