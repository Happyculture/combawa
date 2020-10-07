#!/usr/bin/env bash

#################################
section_separator
#################################

# Check predeploy action script.
message_step "Verifying environment configuration."
if [ ! -f "$COMBAWA_ROOT/.env" ]; then
  notify_error "There is no .env file at the moment or its not readable." "You should run the following command to initialize it: 'drupal combawa:generate-environment'."
fi
message_confirm "Environment file... OK!"

#################################
section_separator
#################################

# Check predeploy action script.
message_step "Verifying predeploy action script."
if [ ! -f "$COMBAWA_SCRIPTS_DIR/predeploy_actions.sh" ]; then
  notify_error "There is no predeploy actions script at the moment or its not readable." "You should run the following command to initialize it: 'drupal combawa:generate-project'."
fi
message_confirm "Predeploy actions script... OK!"

#################################
section_separator
#################################

# Check postdeploy actions script.
message_step "Verifying postdeploy action script."
if [ ! -f "$COMBAWA_SCRIPTS_DIR/postdeploy_actions.sh" ]; then
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
    if [ ! -f "$COMBAWA_SCRIPTS_DIR/install.sh" ]; then
      notify_error "There is no <app>/scripts/install.sh script at the moment or its not readable." "You should run the following command to initialize it: 'drupal combawa:generate-project'."
    fi
    message_confirm "Install.sh check... OK!"
    ;;
  "update" )
    message_step "Verifying update.sh action script."
    if [ ! -f "$COMBAWA_SCRIPTS_DIR/update.sh" ]; then
      notify_error "There is no <app>/scripts/update.sh script at the moment or its not readable." "You should run the following command to initialize it: 'drupal combawa:generate-project'."
    fi
    message_confirm "Update.sh check... OK!"
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
