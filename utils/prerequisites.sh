#!/usr/bin/env bash

#################################
section_separator
#################################

# Check predeploy action script.
message_step "Verifying environment configuration."
if [ ! -f "$COMBAWA_ROOT/.env" ]; then
  notify_fatal "There is no .env file at the moment or its not readable." "You should run the following command to initialize it: 'drupal combawa:generate-environment'."
fi
message_confirm "Environment file... OK!"

#################################
section_separator
#################################

# Check predeploy action script.
message_step "Verifying predeploy action script."
if [ ! -f "$COMBAWA_SCRIPTS_DIR/predeploy_actions.sh" ]; then
  notify_fatal "There is no predeploy actions script at the moment or its not readable." "You should run the following command to initialize it: 'drupal combawa:generate-project'."
fi
message_confirm "Predeploy actions script... OK!"

#################################
section_separator
#################################

# Check postdeploy actions script.
message_step "Verifying postdeploy action script."
if [ ! -f "$COMBAWA_SCRIPTS_DIR/postdeploy_actions.sh" ]; then
  notify_fatal "There is no postdeploy actions script at the moment or its not readable." "You should run the following command to initialize it: 'drupal combawa:generate-project'."
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
      notify_fatal "There is no <app>/scripts/combawa/install.sh script at the moment or its not readable." "You should run the following command to initialize it: 'drupal combawa:generate-project'."
    fi
    message_confirm "Install.sh check... OK!"
    ;;
  "update" )
    message_step "Verifying update.sh action script."
    if [ ! -f "$COMBAWA_SCRIPTS_DIR/update.sh" ]; then
      notify_fatal "There is no <app>/scripts/combawa/update.sh script at the moment or its not readable." "You should run the following command to initialize it: 'drupal combawa:generate-project'."
    fi
    message_confirm "Update.sh check... OK!"
    ;;
  * )
    notify_fatal "Build mode unknown."
    ;;
esac

#################################
section_separator
#################################

# Test DB connection.
message_step "Verifying database connectivity."
{
  $DRUSH sql:query "SHOW TABLES;"
} &> /dev/null || { # catch
  notify_fatal "The connection to the database is impossible."
}
message_confirm "DB connection... OK!"

#################################
section_separator
#################################

if [ $COMBAWA_DB_FETCH_FLAG == 1 ]; then
  # Test DB fetch method parameters.
  message_step "Verifying reference dump fetching method."

  # Verify that the fetch method is supported.
  case $COMBAWA_DB_FETCH_METHOD in
    "cp" )
      message_action "Verifying 'cp' dump fetching parameters."
      # Are our variables defined?
      if [ -z ${COMBAWA_DB_FETCH_PATH_SOURCE+x} ] || [ -z ${COMBAWA_DB_DUMP_PATH+x} ]; then
        notify_fatal "The parameter COMBAWA_DB_FETCH_PATH_SOURCE or COMBAWA_DB_DUMP_PATH is empty. We will not be able to copy a reference dump."
      fi
      message_action "Verifying 'cp' files are accessible."
      # Is the source file accessible/readable?
      if [ ! -f $COMBAWA_DB_FETCH_PATH_SOURCE ]; then
        notify_fatal "There is no $COMBAWA_DB_FETCH_PATH_SOURCE source file at the moment or its not readable." "Is your path correct or accessible?."
      fi
      message_confirm "'cp' dump fetching parameters check... OK!"
      ;;
    "scp" )
      message_action "Verifying 'scp' dump fetching parameters."
      set +e
      # Login with SSH config name or ssh info?
      if [ -z ${COMBAWA_DB_FETCH_SCP_CONFIG_NAME+x} ]; then
        # Are our variables defined?
        if [ -z ${COMBAWA_DB_FETCH_SCP_SERVER+x} ] || [ -z ${COMBAWA_DB_FETCH_SCP_PORT+x} ] || [ -z ${COMBAWA_DB_FETCH_PATH_SOURCE+x} ] ; then
          notify_fatal "One of the parameters COMBAWA_DB_FETCH_SCP_SERVER, COMBAWA_DB_FETCH_SCP_PORT or COMBAWA_DB_FETCH_PATH_SOURCE is empty. We will not be able to copy a reference dump."
        fi
        message_step "Testing connection with remote SSH server from which the dump will be retrieved:"
        # Determine if we have a username to use to login.
        if [ -z ${COMBAWA_DB_FETCH_SCP_USER} ]; then
            # SCP login via servername and current user.
          ssh -q -p $COMBAWA_DB_FETCH_SCP_PORT $COMBAWA_DB_FETCH_SCP_SERVER -o ConnectTimeout=5 echo > /dev/null
        else
          # Also determine if we have a password to use.
          if [ -z ${COMBAWA_DB_FETCH_SCP_PASSWORD} ]; then
            # SCP login via username.
            ssh -q -p $COMBAWA_DB_FETCH_SCP_PORT $COMBAWA_DB_FETCH_SCP_USER@$COMBAWA_DB_FETCH_SCP_SERVER -o ConnectTimeout=5 echo > /dev/null
          else
            # SCP login via username and password.
            ssh -q -p $COMBAWA_DB_FETCH_SCP_PORT $COMBAWA_DB_FETCH_SCP_USER:$COMBAWA_DB_FETCH_SCP_PASSWORD@$COMBAWA_DB_FETCH_SCP_SERVER -o ConnectTimeout=5 echo > /dev/null
          fi
        fi
      else
        ssh -q $COMBAWA_DB_FETCH_SCP_CONFIG_NAME -o ConnectTimeout=5 echo > /dev/null
      fi
      if [ "$?" != "0" ] ; then
        notify_fatal "Impossible to connect to the SSH server." "Check your SSH connection parameters. Should you connect through a VPN?"
      else
        message_confirm "'scp' dump fetching parameters check... OK!"
      fi
      set -e
      ;;
    * )
      notify_fatal "Dump fetching '$COMBAWA_DB_FETCH_METHOD' method unsupported."
      ;;
  esac
  message_confirm "Reference dump fetching method... OK!"
else
  message_action "Do not fetch the reference dump for this build."
fi

#################################
section_separator
#################################

# Test incompatible parameters.
if [[ $COMBAWA_BUILD_MODE == "install" ]] && [[ $COMBAWA_REIMPORT_REF_DUMP_FLAG == 1 ]]; then
  notify_error "Reimport DB is not available in install build mode.\nYou should either change your build mode (-m) to 'update' or disable the reimport mode (-r 0)."
fi
