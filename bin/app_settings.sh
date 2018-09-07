#!/usr/bin/env bash

########## DEFAULT VARIABLES ##############
# Default environment is prod.
# Override with -e or --env.
ENV="prod";

# Default build mode. Can be install or update.
# Override with -m or --mode.
BUILD_MODE="update";

# Backup base before build.
# Override with -b or --backup.
BACKUP_BASE=1;

# Default action to retrieve a DB dump from the production.
# Override with -f or --fetch-db-dump.
FETCH_DB_DUMP=0

# Has to run offline.
# Override with -o or --offline.
OFFLINE=0
