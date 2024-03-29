![Logo Combawa](logo_combawa.png)

* **[Combawa](#combawa)**
* **[Installation](#installation)**
* **[Usage](#usage)**
* **[Drush commands](#drush-commands)**
* **[Advanced usages](#advanced)**
* **[Troubleshooting](#troubleshooting)**

# <a name="combawa"></a>Combawa
Combawa is a bash script that helps you **build** your Drupal projects.

It's compatible with Drupal 10 and is meant to be used by developers and CI applications. (See other branches for lower Drupal versions).

You are encouraged to use Combawa as a daily companion to reinstall/update your local installation or CI environment.

Because Combawa is very cool, you can use a Drush command to setup the required environment variables. See below for details.

## <a name="installation"></a>Installation

- `composer require happyculture/combawa`
- Use `drush combawa:initialize-build-scripts` to initiate the project build files from a template (actions run when the Drupal site is (re)installed or updated).
- If you are in `install` mode using an install profile different than `minimal`, you should update the `scripts/combawa/install.sh` file to replace the profile name in the `$DRUSH site-install` command.
- Use `drush combawa:generate-environment` to setup your environment (configuring your site variables).

If you don't want to use the generated `settings.local.php` file, you will have to add in your `settings.php` (or any other settings file) the following snippet:

```
<?php

// Environment variables are defined in the .env file at the project root.
$databases['default']['default'] = [
  'host' => $_ENV['COMBAWA_DB_HOSTNAME'],
  'port' => $_ENV['COMBAWA_DB_PORT'],
  'database' => $_ENV['COMBAWA_DB_DATABASE'],
  'password' => $_ENV['COMBAWA_DB_PASSWORD'],
  'username' => $_ENV['COMBAWA_DB_USER'],
  'prefix' => '',
  'driver' => 'mysql',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
];
```

### Recommended

#### Combawa wrapper as a global command

If you are lazy as we are (you should), it is possible to use a global command `combawa` instead of `vendor/bin/combawa` in your projects.  
In order to do that, you need to install the Combawa wrapper (https://github.com/Happyculture/combawa-wrapper). It works similarly as the Drush wrapper. 

`composer global require happyculture/combawa-wrapper`

## <a name="usage"></a>Usage

### Conception & philosophy 

**(Re)Build your project with Combawa**

Combawa is the script that will ease your project reinstallation when you want to test your freshly baked feature.

It's designed with an CI context in mind for the local station.
When you will start using Combawa, you will initiate a build scenario (we suggest the production scenario) to set default values to the internal options (should my script backup the site before building? Retrieve a fresh reference dump from production...)
You can build the project from an installation profile, from existing config or from a reference dump directly retrieved from your production server. 

### How to use Combawa

Running Combawa to (re)build your project mostly resumes to running this command: `./vendor/bin/combawa`.

When you are building a site, the following steps are followed:
* Preflight checkup (system requirement + setup verifications)
* Predeployment actions (fetch a remote DB, save a backup of the current install, drop the DB)
* Build actions (install or update mode (differences below))
* Postdeployment (rebuild caches, generate a connection link, setup env specific modules, reindex...)

Combawa takes the default settings when you run it with no extra arguments.
You can override them on the fly (see arguments section below).

#### Environment specific

Combawa is designed to build your site following an environment specific set of rules.

Said otherwise, you have a solution to compile CSS files in prod and not in dev, drop the DB to each rebuild (but not on prod), enable/disable specific modules in development (Devel, Views UI...) or production (no DBLog or UI...)  

The valid environments (option `-e`) are the following:
* Development (dev)
* Testing (testing)
* Production (prod)

When you are targeting a specific environment, you can edit `predeploy_actions.sh` or `postdeploy_actions.sh`. Each file is composed of a switch/case per environment but you can specialize those files per project.

#### Build mode

When you build your projects, you are in two different scenarios:
- Install mode: You are initiating the project and build from an installation profile.
- Update mode: You are advanced in your project life cycle and may have it in production. You want to rebuild from the configuration that you exported or a reference SQL dump.

Each mode (option `-m`) is using a different build file since you don't run the same commands whether you are installing or updating.

Combawa ships with template files for each mode, you can update them when you need to adjust them to your constraints.

#### Combawa options

You can use more arguments such as : `./vendor/bin/combawa.sh --env dev --mode install --backup 1 --fetch-db-dump`

Here is the list of available arguments:
* `--yes`, `-y`: Do not ask for confirmation before running the build. Useful for CI integration.
* `--env`, `-e`: Environment to build. Allowed values are: dev, testing, prod
* `--mode`, `-m`: Build mode. Allowed values are: install, update
* `--backup`, `-b`: Generates a backup before building the project. Allowed values are: 0: do not generate a backup, 1: generate a backup.
* `--reimport`, `-r`: Reimports the site from the reference dump (DB drop and replace). Allowed values are: 0: do not reimport the reference database, 1: reimport the reference database.
* `--fetch-db-dump`, `-f`: Fetches a fresh DB dump from the production site. Used when the reference dump should be updated.
* `--only-predeploy`: Only execute the predeploy script.
* `--only-postdeploy`: Only execute the postdeploy script.
* `--no-predeploy`: Do not execute the predeploy script.
* `--no-postdeploy`: Do not execute the postdeploy script.
* `--stop-after-reimport`: Flag to stop building after reimporting the DB. Useful to version config from prod. 

## Environment files

You can generate or manually create environment files to inject Combawa required variables and extra environment infos.
The `.env` file(s) are expected to be located at the repository root level.

We leverage Symfony Dotenv component so the `.env` file may be overriden by `.env.local`, `.env.$APP_ENV.local` or `.env.$APP_ENV` if defined.

## <a name="drush-commands"></a>Drush commands

### Environment generator

Command `drush combawa:generate-environment`:

Used once per environment, this command creates two files: 
- `.env`: Stores local values for the build script.
- `settings.local.php`: Used to inject the local values into Drupal.

By default the command is interactive. If you want to use it through CLI, you can pass all arguments with there value. If a required value is missing, the command will prompt to collect the missing values.
Eg:

```
 ./vendor/bin/drush combawa:generate-environment \
  --build-mode update \
  --environment dev \
  --environment-url https://mydevsite.coop \
  --backup-db \
  --dump-fetch-update \
  --dump-retrieval-tool scp \
  --scp-connection-username=username \
  --scp-connection-servername myserver.org \
  --scp-connection-port 22 \
  --fetch-source-path /home/dumps-source/my_dump.sql.gz \
  --db-host localhost \
  --db-port 3306 \
  --db-name test_db \
  --db-user db_username \
  --no-interaction
```

See the integrated help using `drush combawa:generate-environment --help` for arguments values.

Please note that the command will NOT generate the settings.local.php if it already exists to avoid data loss. If you any valid reason, you need to override the existing file (eg: in a Continous Integration context), you can do so by passing the extra argument `--force-settings-generation`.

Please also be aware that the `.env` file content will be different if you are in `install` or `update` mode. It means that you should retrigger the command when you switch from `install` to `update` to fill the extra required information (to fetch the DB dumps mostly).

### Script templates generator

Command `drush combawa:initialize-build-scripts`:

This command generates the build scripts used by Combawa to install/update the project from templates. Once those files are generated, you can customize them to your needs and probably want to version them.

## <a name="advanced"></a>Advanced usages

### Pulling changes from production

If some of your users are able to change the configuration on the production environment, you might want to ensure your repository is up-to-date before shipping new features. Those settings being stored in the database, you need to retrieve them on your local environment then export the configuration to your repository.

Run combawa to only replace your local database with your production dump as follow: \
`combawa -f 1 -r 1 --stop-after-reimport`

You are now in the state where the database has been imported. Run `drush config:export -y` to export in code and version the config delta from prod.

## <a name="troubleshooting"></a>Troubleshooting

This version is obviously bug free! If you identify an issue, please open an issue.
