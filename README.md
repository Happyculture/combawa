![Logo Combawa](logo_combawa.png)

* **[Combawa](#combawa)**
* **[Installation](#installation)**
* **[Usage](#usage)**
* **[Drupal console commands](#drupal-console-commands)**
* **[Troubleshooting](#troubleshooting)**

# <a name="combawa"></a>Combawa
Combawa is a bash script that helps you **build** your Drupal projects.

It's compatible with Drupal 8 and 7 and is meant to be used by developers and CI applications.

You are encouraged to use Combawa as a daily companion to reinstall/update your local installation.

Because Combawa is very cool, you can also use it when you bootstrap your project. Combawa lands with 2 Drupal console commands to help you setup your environment variables and bootstrap your project. See below for details.

## <a name="installation"></a>Installation

- `composer require happyculture/combawa`
- Use `drupal combawa:generate-build` to initiate your build files.
- Use `drupal combawa:generate-environment` to setup your environment.

**Drupal 7 users**

Check the below section [Drupal 7 installation steps](#drupal-7).

### Recommanded

#### Combawa wrapper as a global command

If you are lazy as we are (you should), it is possible to use a global command `combawa` instead of `vendor/bin/combawa` in your projects.  
In order to do that, you need to install the Combawa wrapper (https://github.com/Happyculture/combawa-wrapper). It works similarly as the Drush wrapper. 

`composer require happyculture/combawa-wrapper`


#### Drupal console commands

We are able to generate the files needed to build the project and environment variables files.  
We recommand you to install Drupal console to benefit from scaffolding features for environment variables and settings (see below).

`composer require drupal/console`

## <a name="usage"></a>Usage

### Conception & philosophy 

**(Re)Build your project with Combawa**

Combawa is the script that will ease your project reinstallation when you want to test your freshly baked feature.

It's designed with an IC context in mind for the local station.
When you will start using Combawa, you will initiate a build scenario (we suggest the production scenario) to set default values to the internal options (should you backup the site when building? Retrieve a reference dump...)
You can build the project from an installation profile, from existing config or from a reference dump directly retrieved from your production server. 

### How to use Combawa

Running Combawa to (re)build your project mostly resumes to run this command: `./vendor/bin/combawa`.

When you are building a site, the following steps are followed:
* Preflight checkup (system requirement + setup verifications)
* Predeployment actions (fetch a remote DB, save a backup of the current install, drop the DB)
* Build actions (install, update or pull mode (differences below))
* Postdeployment (rebuild caches, generate a connection link...)

Combawa takes the default settings when you run it with no extra arguments.
You can override them on the fly (see arguments section below).

#### Environment specific

Combawa is designed to build your site following an environment specific set of rules.

Said otherwise, you have a solution to compile CSS files in prod and not in dev, drop the DB to each rebuild (but not on prod), enable/disable specific modules in development (Devel, Views UI...) or production (no DBLog or UI...)  

The valid environments (option `-e`) are the following:
* Development (dev)
* Staging (recette)
* Preproduction (preprod)
* Production (prod)

When you are targeting a specific environment, you can edit `predeploy_actions.sh` or `postdeploy_actions.sh`. Each file is composed of a switch/case per environment.

#### Build mode

When you build your projects, you are in three different scenarios:
- Install mode: You are initiating the project and build from an installation profile.
- Update mode: You are advanced in your project life cycle and may have it in production. You want to rebuild from the configuration that you exported or a reference SQL dump.
- Pull mode: You are working in a team and need to retrieve the feature pused by a coworker without loosing your work.

Each mode (option `-m`) is using a different build file since you don't run the same commands whether you are installing/updating/pulling.

Combawa ships with template files for each mode, you can update them when you need to adjust to your constraints.

#### Combawa options

You can use more arguments such as : `./vendor/bin/combawa.sh --env dev --mode install --backup 1 --uri http://hc.fun --fetch-db-dump`

Here is the list of available arguments:
* `--env`, `-e`: Environment to build. Allowed values are: dev, recette, preprod, prod
* `--mode`, `-m`: Build mode. Allowed values are: install, update, pull
* `--backup`, `-e`: Generates a backup before building the project. Allowed values are: 0: does not generate a backup, 1: generates a backup.
* `--uri`, `-u`: Local URL of your project. Used when the final drush uli command is runned.
* `--fetch-db-dump`, `-f`: Fetch a fresh DB dump from the production site. Used when the reference dump should be updated.

## <a name="drupal-console-commands"></a>Drupal console commands

### Environment generator

Used once per environment, this command creates two files: 
- `.env`: Used to override the default settings set in `settings.sh`.
- `settings.local.php`: Used to include the dynamic values of the variables set by the environment.

To generate those files in interactive mode, just run `drupal combawa:generate-environment`.\
All interactive options are also available in non-interactive mode if you need this to be run by your CI server for example. See the integrated help using `drupal help combawa:generate-environment`.

Eg:
```
drupal combawa:generate-environment \
    --core=8 \
    --environment=preprod \
    --environment-url=https://mysite.url \
    --dump-file-name=reference_dump.sql \
    --db-host=localhost \
    --db-port=3306 \
    --db-name=db_name \
    --db-user=db_user \
    --db-password=db_password \
    --no-interaction
```

### Build generator

This command creates the build scripts used by combawa to install/update the project.

To use it in interactive mode, just run `drupal combawa:generate-build`.

The files generated by this command should be added to your git repository.



## <a name="drupal-7"></a>Drupal 7 installation steps

The installation steps are the same as Drupal 8 projects but you also need to do few extras in order to be able to plug environment variables that Drupal 7 isn't ready for out of the box.

You must require the following extra dependency:
`composer require vlucas/dotenv:"^3.0"` 
With it, add the following section to your composer.json file.
```
    "autoload": {
        "files": ["load.environment.php"]
    },
```
And create a new file `load.environment.php` at your repo's root with the following content in it:
```
<?php

/**
 * This file is included very early. See autoload.files in composer.json and
 * https://getcomposer.org/doc/04-schema.md#files
 */

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;

/**
 * Load any .env file. See /.env.example.
 */
$dotenv = Dotenv::create(__DIR__);
try {
  $dotenv->load();
}
catch (InvalidPathException $e) {
  // Do nothing. Production environments rarely use .env files.
}

```

You must also add DB credentials environment variables to your Apache configuration to be "populated".
Add the following lines within your `VirtualHost` to do so :

```
<VirtualHost *:80>
  # [...] Other directives.
  
  # Combawa variables configuration.
  SetEnv COMBAWA_DB_HOSTNAME <DB_HOSTNAME>
  SetEnv COMBAWA_DB_PORT <DB_PORT>
  SetEnv COMBAWA_DB_DATABASE <DB_NAME>
  SetEnv COMBAWA_DB_USER <DB_USERNAME>
  SetEnv COMBAWA_DB_PASSWORD <DB_PASSWORD>
  
  # [...] Other directives.
  
</VirtualHost>
```
With that you should be good to go.

If you want to have your `settings.php` file and `files` directory automatically added when running composer install command, add this code portion into your composer.json file:

```
    "autoload": {
        "classmap": [
            "scripts/composer/ScriptHandler.php",
        ],
    },

```
And use the [drupal project script content](https://github.com/drupal-composer/drupal-project/blob/7.x/scripts/composer/ScriptHandler.php) into that `scripts/composer/ScriptHandler.php` file.


## <a name="troubleshooting"></a>Troubleshooting

If you encounter the following error:

```
  [InvalidArgumentException]                              
  Package type "drupal-console-library" is not supported 
```

Add this line to the `composer.json` file to specify the package location in the `extra` > `installer-paths` section.

`"vendor/{$vendor}/{$name}": ["type:drupal-console-library"]`
