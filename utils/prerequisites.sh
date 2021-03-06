#!/usr/bin/env bash

#################################
section_separator
#################################

# Check predeploy action script.
message_step "Verifying predeploy action script."
if [ ! -f "$APP_SCRIPTS_DIR/predeploy_actions.sh" ]; then
  notify_error "There is no predeploy actions script at the moment or its not readable." "You should run the following command to initialize it: 'drupal combawa:generate-project'."
fi
message_confirm "Predeploy actions script... OK!"

#################################
section_separator
#################################

# Check postdeploy actions script.
message_step "Verifying postdeploy action script."
if [ ! -f "$APP_SCRIPTS_DIR/postdeploy_actions.sh" ]; then
  notify_error "There is no postdeploy actions script at the moment or its not readable." "You should run the following command to initialize it: 'drupal combawa:generate-project'."
fi
message_confirm "Postdeploy actions script... OK!"

#################################
section_separator
#################################

# Preliminary verification to avoid running actions
# if the requiprements are not met.
case $COMBAWA_BUILD_MODE in
  "install" )
    message_step "Verifying install.sh action script."
    if [ ! -f "$APP_SCRIPTS_DIR/install.sh" ]; then
      notify_error "There is no <app>/scripts/install.sh script at the moment or its not readable." "You should run the following command to initialize it: 'drupal combawa:generate-project'."
    fi
    message_confirm "Install.sh check... OK!"
    ;;
  "update" )
    message_step "Verifying update.sh action script."
    if [ ! -f "$APP_SCRIPTS_DIR/update.sh" ]; then
      notify_error "There is no <app>/scripts/update.sh script at the moment or its not readable." "You should run the following command to initialize it: 'drupal combawa:generate-project'."
    fi
    message_confirm "Update.sh check... OK!"
    ;;
  "pull" )
    message_step "Verifying pull.sh action script."
    if [ ! -f "$APP_SCRIPTS_DIR/pull.sh" ]; then
      notify_error "There is no <app>/scripts/pull.sh script at the moment or its not readable." "You should run the following command to initialize it: 'drupal combawa:generate-project'."
    fi
    message_confirm "Pull.sh check... OK!"
    ;;
  * )
    notify_error "Build mode unknown."
    ;;
esac

#################################
section_separator
#################################

# Test DB connection.
message_step "Verifying database connectivity."
{
  $DRUSH sql-connect
} &> /dev/null || { # catch
  notify_error "The connection to the database is impossible."
}
message_confirm "DB connection... OK!"

#################################
section_separator
#################################
