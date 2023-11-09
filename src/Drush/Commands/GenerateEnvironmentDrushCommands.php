<?php

namespace Combawa\Drush\Commands;

use Drupal\Component\Serialization\Json;
use DrupalCodeGenerator\Asset\AssetCollection;
use DrupalCodeGenerator\Validator\Chained;
use DrupalCodeGenerator\Validator\Required;
use Drush\Attributes as CLI;

/**
 * Combawa specific drush commands.
 */
class GenerateEnvironmentDrushCommands extends DrushCommandsGeneratorBase {

  const FETCH_DEST_PATH = 'dumps/reference_dump.sql.gz';
  const TEMPLATES_PATH = __DIR__ . '/../../../templates/combawa-env';

  /**
   * Generate environment command.
   */
  #[CLI\Command(name: 'combawa:generate-environment', aliases: ['cge'])]
  #[CLI\Option(
    name: 'build-mode',
    description: 'Override the discovered build mode. (Accepted: <info>install</info>, <info>update</info>)',
    suggestedValues: ['install', 'update'])]
  #[CLI\Option(
    name: 'environment',
    description: 'The build environment. (Accepted: <info>dev</info>, <info>testing</info>, <info>prod</info>)',
    suggestedValues: ['dev', 'testing', 'prod'])]
  #[CLI\Option(
    name: 'webroot',
    description: 'Override the discovered Drupal webroot location.')]
  #[CLI\Option(
    name: 'environment-url',
    description: 'The URL on which the project is reachable for this environment.')]
  #[CLI\Option(
    name: 'backup-db',
    description: 'Backup the database on each build. (Accepted: <info>boolean</info>)',
    suggestedValues: [TRUE, FALSE])]
  #[CLI\Option(
    name: 'dump-fetch-update',
    description: 'Always update the reference dump before building. (Accepted: <info>boolean</info>)',
    suggestedValues: [TRUE, FALSE])]
  #[CLI\Option(
    name: 'dump-retrieval-tool',
    description: 'Tool used to retrieve the reference dump. (Accepted: <info>cp</info>, <info>scp</info>)',
    suggestedValues: ['cp', 'scp'])]
  #[CLI\Option(
    name: 'scp-config-name',
    description: 'The remote server name where to find the dump.')]
  #[CLI\Option(
    name: 'scp-connection-username',
    description: 'SCP connection username.')]
  #[CLI\Option(
    name: 'scp-connection-password',
    description: 'SCP connection password.')]
  #[CLI\Option(
    name: 'scp-connection-servername',
    description: 'SCP connection server name.')]
  #[CLI\Option(
    name: 'scp-connection-port',
    description: 'SCP connection port.')]
  #[CLI\Option(
    name: 'fetch-source-path',
    description: 'Source path to copy the reference dump from. Must include the dump file name and use a .gz extension.')]
  #[CLI\Option(
    name: 'reimport',
    description: 'Reimport the website from the reference dump on each build? (Accepted: <info>boolean</info>)',
    suggestedValues: [TRUE, FALSE])]
  #[CLI\Option(
    name: 'db-host',
    description: 'The host of the database.')]
  #[CLI\Option(
    name: 'db-port',
    description: 'The port of the database.')]
  #[CLI\Option(
    name: 'db-name',
    description: 'The name of the database.')]
  #[CLI\Option(
    name: 'db-user',
    description: 'The user name of the database.')]
  #[CLI\Option(
    name: 'db-password',
    description: 'The password of the database.')]
  #[CLI\Option(
    name: 'write-db-settings',
    description: 'Write DB settings code in settings.local.php? (Accepted: <info>boolean</info>)',
    suggestedValues: [TRUE, FALSE])]
  #[CLI\Option(
    name: 'dry-run',
    description: 'Output the generated code but not save it to file system.')]
  #[CLI\Usage(name: 'drush combawa:generate-environment', description: 'Run with wizard')]
  public function generateEnvironment(array $options = [
    'build-mode' => self::REQ,
    'environment' => self::REQ,
    'webroot' => self::REQ,
    'environment-url' => self::REQ,
    'backup-db' => self::OPT,
    'dump-fetch-update' => self::OPT,
    'dump-retrieval-tool' => self::REQ,
    'scp-config-name' => self::REQ,
    'scp-connection-username' => self::REQ,
    'scp-connection-password' => self::REQ,
    'scp-connection-servername' => self::REQ,
    'scp-connection-port' => self::REQ,
    'fetch-source-path' => self::REQ,
    'reimport' => self::OPT,
    'db-host' => self::REQ,
    'db-port' => self::REQ,
    'db-name' => self::REQ,
    'db-user' => self::REQ,
    'db-password' => self::REQ,
    'write-db-settings' => self::OPT,
    'dry-run' => FALSE,
  ]): int {
    return $this->generate($options);
  }

  /**
   * {@inheritdoc}
   */
  protected function extractOptions(array $options): array {
    $vars = [
      'build_mode' => $options['build-mode'],
      'environment' => $options['environment'],
      'webroot' => $options['webroot'],
      'environment_url' => $options['environment-url'],
      'backup_db' => $options['backup-db'],
      'dump_fetch_update' => $options['dump-fetch-update'],
      'dump_fetch_method' => $options['dump-retrieval-tool'],
      'dump_fetch_scp_config_name' => $options['scp-config-name'],
      'dump_fetch_scp_user' => $options['scp-connection-username'],
      'dump_fetch_scp_password' => $options['scp-connection-password'],
      'dump_fetch_scp_host' => $options['scp-connection-servername'],
      'dump_fetch_scp_port' => $options['scp-connection-port'],
      'dump_fetch_source_path' => $options['fetch-source-path'],
      'reimport' => $options['reimport'],
      'db_host' => $options['db-host'],
      'db_port' => $options['db-port'],
      'db_name' => $options['db-name'],
      'db_user' => $options['db-user'],
      'db_password' => $options['db-password'],
      'write_db_settings' => $options['write-db-settings'],
    ];
    return array_filter($vars, fn ($value) => !\is_null($value));
  }

  /**
   * {@inheritdoc}
   */
  protected function interview(array &$vars): void {
    if (!isset($vars['build_mode'])) {
      $composerData = Json::decode(file_get_contents($this->drupalFinder()->getComposerRoot() . '/composer.json'));
      $defaultValue = $composerData['extra']['combawa']['build_mode'] ?? 'install';
      $choices = ['install', 'update'];
      $choice = $this->io()->choice(
        'What is your current build mode?',
        $choices,
        $defaultValue,
      );
      $vars['build_mode'] = $choices[$choice];
    }

    if (!isset($vars['environment'])) {
      $defaultValue = $_SERVER['COMBAWA_BUILD_ENV'] ?? 'prod';
      $choices = ['dev', 'testing', 'prod'];
      $choice = $this->io()->choice(
        'Which kind of environment is it?',
        $choices,
        $defaultValue,
      );
      $vars['environment'] = $choices[$choice];
    }

    if (!isset($vars['webroot'])) {
      $detectedDefaultValue = ltrim(substr($this->drupalFinder()->getDrupalRoot(), strlen($this->drupalFinder()->getComposerRoot())), DIRECTORY_SEPARATOR);
      $defaultValue = $_SERVER['COMBAWA_WEBROOT_PATH'] ?? $detectedDefaultValue;
      $vars['webroot'] = $this->io()->ask(
        'In which directory is your Drupal webroot located?',
        $defaultValue,
      );
    }

    if (!isset($vars['environment_url'])) {
      $defaultValue = $_SERVER['DRUSH_OPTIONS_URI'] ?? 'https://' . $vars['environment'] . '.happyculture.coop';
      $vars['environment_url'] = $this->io()->ask(
        'What is the URL of the project for the ' . $vars['environment'] . ' environment?',
        $defaultValue,
        new Chained(
          new Required(),
          static fn (string $value): string => static::validateUrl($value),
        ),
      );
    }

    // Database credentials.
    foreach (['host', 'port', 'name', 'user', 'password'] as $key) {
      $varName = 'db_' . $key;
      $defaultValueKey = match($key) {
        'host' => 'COMBAWA_DB_HOSTNAME',
        'port' => 'COMBAWA_DB_PORT',
        'name' => 'COMBAWA_DB_DATABASE',
        'user' => 'COMBAWA_DB_USER',
        'password' => 'COMBAWA_DB_PASSWORD',
      };
      $defaultValueValue = match($key) {
        'host' => '127.0.0.1',
        'port' => '3306',
        'name' => 'drupal',
        'user' => 'root',
        'password' => '',
      };
      $question = match($key) {
        'host' => 'What is the hostname of your database server?',
        'port' => 'What is the port of your database server?',
        'name' => 'What is the name of your database?',
        'user' => 'What is the name of your database user?',
        'password' => 'What is the password of your database user?',
      };
      $validator = match($key) {
        'host' => new Chained(
          new Required(),
          static fn (string $value): string => static::validateDomainOrIpFormat($value),
        ),
        'port' => new Required(),
        'name' => new Required(),
        'user' => new Required(),
        'password' => NULL,
      };
      if (!isset($vars[$varName])) {
        $defaultValue = $_SERVER[$defaultValueKey] ?? $defaultValueValue;
        $vars[$varName] = $this->io()->ask(
          $question,
          $defaultValue,
          $validator,
        );
      }
    }

    if (!isset($vars['backup_db'])) {
      $defaultValue = $_SERVER['COMBAWA_DB_BACKUP_FLAG'] ?? TRUE;
      $vars['backup_db'] = $this->io()->confirm(
        'Do you want the database to be backed up before each build?',
        $defaultValue,
      );
    }

    if ($vars['environment'] !== 'prod' && $vars['build_mode'] === 'update') {
      if (!isset($vars['dump_fetch_update'])) {
        $defaultValue = $_SERVER['COMBAWA_DB_FETCH_FLAG'] ?? TRUE;
        $vars['dump_fetch_update'] = $this->io()->confirm(
          'Do you want to update the reference dump before each build?',
          $defaultValue,
        );
      }

      if (!isset($vars['dump_fetch_method'])) {
        $defaultValue = $_SERVER['COMBAWA_DB_RETRIEVAL_TOOL'] ?? 'scp';
        $choices = ['cp', 'scp'];
        $choice = $this->io()->choice(
          'When updated, what is the tool used to retrieve the reference dump?',
          $choices,
          $defaultValue,
        );
        $vars['dump_fetch_method'] = $choices[$choice];
      }

      if ($vars['dump_fetch_method'] === 'scp') {
        if (!isset($vars['dump_use_ssh_config_name'])) {
          $defaultValue = TRUE;
          $vars['dump_use_ssh_config_name'] = $this->io()->confirm(
            '[SCP] Do you have an SSH config name from your ~/.ssh/config to use to retrieve the dump?',
            $defaultValue,
          );
        }

        if ($vars['dump_use_ssh_config_name'] === TRUE) {
          if (!isset($vars['dump_fetch_scp_config_name'])) {
            $defaultValue = $_SERVER['COMBAWA_DB_FETCH_SCP_CONFIG_NAME'] ?? 'my_remote';
            $vars['dump_fetch_scp_config_name'] = $this->io()->ask(
              '[SCP] What is the name of you config entry in your ~/.ssh/config file?',
              $defaultValue,
              new Required(),
            );
          }
        }
        else {
          // SCP credentials.
          foreach (['host', 'port', 'user', 'password'] as $key) {
            $varName = 'dump_fetch_scp_' . $key;
            $defaultValueKey = match($key) {
              'host' => 'COMBAWA_DB_FETCH_SCP_SERVER',
              'port' => 'COMBAWA_DB_FETCH_SCP_SERVER',
              'user' => 'COMBAWA_DB_FETCH_SCP_USER',
              'password' => 'COMBAWA_DB_FETCH_SCP_PASSWORD',
            };
            $defaultValueValue = match($key) {
              'host' => '',
              'port' => 22,
              'user' => '',
              'password' => '',
            };
            $question = match($key) {
              'host' => '[SCP] What is the connection server name or IP?',
              'port' => '[SCP] What is the connection server port?',
              'user' => '[SCP] What is the connection user name?',
              'password' => '[SCP] What is the connection password?',
            };
            $validator = match($key) {
              'host' => new Chained(
                new Required(),
                static fn (string $value): string => static::validateDomainOrIpFormat($value),
              ),
              'port' => new Required(),
              'user' => new Required(),
              'password' => NULL,
            };
            if (!isset($vars[$varName])) {
              $defaultValue = $_SERVER[$defaultValueKey] ?? $defaultValueValue;
              $vars[$varName] = $this->io()->ask(
                $question,
                $defaultValue,
                $validator,
              );
            }
          }
        }
      }

      if (!isset($vars['dump_fetch_source_path'])) {
        $defaultValue = $_SERVER['COMBAWA_DB_FETCH_PATH_SOURCE'] ?? '/home/dumps-source/my_dump.sql.gz';
        $vars['dump_fetch_source_path'] = $this->io()->ask(
          'What is the source path of the reference dump to copy (only Gzipped file supported at the moment)?',
          $defaultValue,
          new Chained(
            new Required(),
            static fn (string $value): string => static::validateDumpExtension($value),
          ),
        );
      }

      if (!isset($vars['reimport'])) {
        $defaultValue = $_SERVER['COMBAWA_REIMPORT_REF_DUMP_FLAG'] ?? FALSE;
        $vars['reimport'] = $this->io()->confirm(
          'Do you want the site to be reimported from the reference dump on each build?',
          $defaultValue,
        );
      }
    }

    if (!isset($vars['write_db_settings'])) {
      $defaultValue = FALSE;
      $vars['write_db_settings'] = $this->io()->confirm(
        'Do you want Combawa to create a settings.local.php file that will ease your DB connection? You can do it yourself later on, the code to copy/paste will be prompted in the next step.',
        $defaultValue,
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function validateVars(array $vars): void {
    if (isset($vars['build_mode'])) {
      static::validateBuildMode($vars['build_mode']);
    }

    if (isset($vars['build_mode']) && !in_array($vars['build_mode'], ['install', 'update'])) {
      throw new \UnexpectedValueException('Build mode must be either install or update.');
    }

    if (isset($vars['environment']) && !in_array($vars['environment'], ['dev', 'testing', 'prod'])) {
      throw new \UnexpectedValueException('Environment must be either dev, testing or prod.');
    }

    if (isset($vars['environment_url'])) {
      static::validateUrl($vars['environment_url']);
    }

    if (isset($vars['db_host'])) {
      static::validateDomainOrIpFormat($vars['db_host']);
    }

    if (isset($vars['dump_fetch_method']) && !in_array($vars['dump_fetch_method'], ['cp', 'scp'])) {
      throw new \UnexpectedValueException('Fetch method must be either cp or scp.');
    }

    if (isset($vars['dump_fetch_scp_host'])) {
      static::validateDomainOrIpFormat($vars['dump_fetch_scp_host']);
    }

    if (isset($vars['dump_fetch_source_path'])) {
      static::validateDumpExtension($vars['dump_fetch_source_path']);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function outputVarsSummary(array $vars): void {
    $summary = [
      'Build mode' => $vars['build_mode'],
      'Environment' => $vars['environment'],
      'App root' => $vars['webroot'],
      'DB Host' => $vars['db_host'],
      'DB Port' => $vars['db_port'],
      'DB name' => $vars['db_user'],
      'DB Username' => $vars['db_name'],
      'DB password' => empty($vars['db_password']) ? 'No password' : 'Your secret password',
      'Site URL' => $vars['environment_url'],
      'Backup DB before build' => $vars['backup_db'] ? 'Yes' : 'No',
      'Write code to connect to DB' => $vars['write_db_settings'] ? 'Yes' : 'No',
    ];
    if ($vars['environment'] !== 'prod' && $vars['build_mode'] === 'update') {
      if ($vars['dump_fetch_method'] === 'scp') {
        if (!empty($vars['dump_fetch_scp_config_name'])) {
          // Expected: scp <CONFIG_NAME>.
          $fetch_command = 'scp ' . $vars['dump_fetch_scp_config_name'];
        }
        else {
          // Expected: scp [-P PORT] <USER>[:PASS]@<HOST>.
          $fetch_command = 'scp';
          $fetch_command .= !empty($vars['dump_fetch_scp_port']) ? ' -P ' . $vars['dump_fetch_scp_port'] : '';
          $fetch_command .= ' ' . $vars['dump_fetch_scp_user'];
          $fetch_command .= !empty($vars['dump_fetch_scp_password']) ? ':' . $vars['dump_fetch_scp_password'] : '';
          $fetch_command .= '@' . $vars['dump_fetch_scp_host'];
        }
        // Expected: scp <SOURCE>:<SOURCE_PATH> <DEST_PATH>.
        $fetch_command .= ':' . $vars['dump_fetch_source_path'];
        $fetch_command .= ' ' . $this->drupalFinder()->getComposerRoot() . '/' . static::FETCH_DEST_PATH;
      }
      elseif ($vars['dump_fetch_method'] === 'cp') {
        // Expected: cp <SOURCE_PATH> <DEST_PATH>.
        $fetch_command = 'cp';
        $fetch_command .= ' ' . $vars['dump_fetch_source_path'];
        $fetch_command .= ' ' . $this->drupalFinder()->getComposerRoot() . '/' . static::FETCH_DEST_PATH;
      }
      $summary['Fetch command'] = $fetch_command;
      $summary['Always reimport DB before building?'] = $vars['reimport'] ? 'Yes' : 'No';
      $summary['Always update ref DB before building?'] = $vars['dump_fetch_update'] ? 'Yes' : 'No';
    }

    $this->io()->newLine(1);
    $this->io()->title('Settings summary');
    $output = array_chunk($summary, 1, TRUE);
    $this->io()->definitionList(...$output);
  }

  /**
   * {@inheritdoc}
   */
  protected function postGenerate(array $vars): void {
    if (empty($vars['write_db_settings'])) {
      $content = $this->renderer->render(
        'settings.local.php.twig',
        $vars
      );
      $this->io()->newLine(1);
      $this->io()->title('Additional informations:');
      $this->io()->note('You can use the following code into your settings.local.php to use variables defined in the .env file.');
      $this->io()->text($content);
    }

    // Uncomment settings.local.php inclusion in the settings.php file.
    $filename = 'sites/default/settings.php';
    $filepath = $this->drupalFinder()->getDrupalRoot() . '/' . $filename;
    if ($this->fileSystem->exists($filepath)) {
      // Only uncomment the include line as we are sure to have a
      // settings.local.php file.
      $data = file_get_contents($filepath);
      $data = str_replace("#   include \$app_root . '/' . \$site_path . '/settings.local.php';", "  include \$app_root . '/' . \$site_path . '/settings.local.php';", $data);

      $this->fileSystem->chmod(dirname($filepath), 0775);
      $this->fileSystem->chmod($filepath, 0664);
      $this->fileSystem->dumpFile($filepath, $data);

      $this->io()->note('Your settings.php file has been updated to include settings.local.php if it exists.');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function collectAssets(AssetCollection $assets, array $vars): void {
    $assets->addFile(
      '../.env',
      'env-' . $vars['build_mode'] . '.twig',
    );

    if (!empty($vars['write_db_settings'])) {
      $assets->addFile(
        'sites/default/settings.local.php',
        'settings.local.php.twig',
      );
    }
  }

  /**
   * Validates an url.
   *
   * @param string $url
   *   The url to validate.
   *
   * @return string
   *   The validated url.
   *
   * @throws \UnexpectedValueException
   */
  public static function validateUrl($url): string {
    $parts = parse_url($url);
    if ($parts === FALSE) {
      throw new \UnexpectedValueException(
        sprintf(
          '"%s" is a malformed url.',
          $url
        )
      );
    }
    elseif (empty($parts['scheme']) || empty($parts['host'])) {
      throw new \UnexpectedValueException(
        sprintf(
          'Please specify a full URL with scheme and host instead of "%s".',
          $url
        )
      );
    }
    return $url;
  }

  /**
   * Validates a domain name format.
   *
   * @param string $connection_str
   *   The domain or IP address to validate.
   *
   * @return string
   *   The validated domain or IP address.
   *
   * @throws \UnexpectedValueException
   */
  public static function validateDomainOrIpFormat($connection_str): string {
    // Format an IP address.
    if (preg_match('/^[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}$/', $connection_str)) {
      return $connection_str;
    }
    // Or a domain.
    elseif (preg_match('/^[a-zA-Z0-9](?:[-\w]*[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[-\w]*[a-zA-Z0-9])?)*$/', $connection_str)) {
      return $connection_str;
    }
    throw new \UnexpectedValueException(sprintf('The connection string "%s" does not look like a valid domain or IP address.', $connection_str));
  }

  /**
   * Helper to validate that the dump filetype is supported.
   *
   * @param string $path
   *   The file path to validate.
   *
   * @return string
   *   The validated file path.
   *
   * @throws \UnexpectedValueException
   */
  public static function validateDumpExtension(string $path): string {
    switch (pathinfo($path, PATHINFO_EXTENSION)) {
      case 'gz':
        return $path;

      default:
        throw new \UnexpectedValueException(
          sprintf(
            'The file extension "%s" is not supported (only Gzipped files).',
            $path
          )
        );
    }
  }

}
