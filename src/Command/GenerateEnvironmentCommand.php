<?php

namespace Drupal\Console\Combawa\Command;

use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Combawa\Generator\EnvironmentInstallGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateEnvironmentCommand extends Command {

  use ConfirmationTrait;

  /**
   * @var EnvironmentInstallGenerator
   */
  protected $generator;

  /**
   * ProfileCommand constructor.
   *
   * @param EnvironmentInstallGenerator $generator
   */
  public function __construct(EnvironmentInstallGenerator $generator) {
    $this->generator = $generator;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this
      ->setName('combawa:generate-environment')
      ->setAliases(['cge'])
      ->setDescription('Generate the project .env file.')
      ->addOption(
        'environment',
        null,
        InputOption::VALUE_REQUIRED,
        'The built environment (prod, testing or dev).'
      )
      ->addOption(
        'environment-url',
        null,
        InputOption::VALUE_REQUIRED,
        'The URL on which the project is reachable for this environment.'
      )
      ->addOption(
        'backup-db',
        null,
        InputOption::VALUE_NONE,
        'Backup the database on each build.'
      )
      ->addOption(
        'dump-fetch-update',
        null,
        InputOption::VALUE_NONE,
        'Always update the reference dump before building.'
      )
      ->addOption(
        'dump-retrieval-tool',
        null,
        InputOption::VALUE_REQUIRED,
        'Tool used to retrieve the reference dump.'
      )
      ->addOption(
        'ssh-config-name',
        null,
        InputOption::VALUE_REQUIRED,
        'The remote server name where to find the dump.'
      )
      ->addOption(
        'scp-connection-info',
        null,
        InputOption::VALUE_REQUIRED,
        'Connection string to the remote server (user@host.com).'
      )
      ->addOption(
        'fetch-source-path',
        null,
        InputOption::VALUE_REQUIRED,
        'Source path to copy the reference dump from.'
      )
      ->addOption(
        'fetch-dest-path',
        null,
        InputOption::VALUE_REQUIRED,
        'Destination path to copy the reference dump to.'
      )
      ->addOption(
        'dump-file-name',
        null,
        InputOption::VALUE_OPTIONAL,
        'The name of the local dump file to load before building.'
      )
      ->addOption(
        'reimport',
        null,
        InputOption::VALUE_NONE,
        'Reimport the website from the reference dump on each build.'
      )
      ->addOption(
        'db-host',
        null,
        InputOption::VALUE_REQUIRED,
        'The host of the local database.'
      )
      ->addOption(
        'db-port',
        null,
        InputOption::VALUE_REQUIRED,
        'The port of the local database.'
      )
      ->addOption(
        'db-name',
        null,
        InputOption::VALUE_REQUIRED,
        'The name of the local database.'
      )
      ->addOption(
        'db-user',
        null,
        InputOption::VALUE_REQUIRED,
        'The user name of the local database.'
      )
      ->addOption(
        'db-password',
        null,
        InputOption::VALUE_OPTIONAL,
        'The password of the local database.'
      )
      ->addOption(
        'write-db-settings',
        null,
        InputOption::VALUE_REQUIRED,
        'Flag to write the DB settings code.'
      )
      ->addOption(
        'enable-splits',
        null,
        InputOption::VALUE_OPTIONAL,
        'Config splits to enable separated by a comma.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    $generateParams = [
      'app_root' => $this->generator->getCombawaRoot(),
      'webroot' => $this->generator->getCombawaWeboot(),
      'environment' => $this->validateEnvironment($input->getOption('environment')),
      'environment_url' => $this->validateUrl($input->getOption('environment-url')),
      'db_host' => $input->getOption('db-host'),
      'db_port' => $input->getOption('db-port'),
      'db_name' => $input->getOption('db-name'),
      'db_user' => $input->getOption('db-user'),
      'db_password' => $input->getOption('db-password'),
      'environment_url' => $this->validateUrl($input->getOption('environment-url')),
      'backup_base' => 1,
      'reimport' => 0,
      'dump_fetch_update' => 0,
      'fetch_source_path' => '',
      'fetch_dest_path' => 'reference_dump.sql.gz',
      'write_db_settings' => $input->getOption('write-db-settings'),
      'enable_splits' => $input->getOption('enable-splits'),
    ];
    if (!is_array($defaults['enable_splits'])) {
      $defaults['enable_splits'] = explode(',', $defaults['enable_splits']);
    }
    $defaults['enable_splits'] = $this->validateConfigSplits(array_filter($defaults['enable_splits']));

    $generateParams = [];
    if ($defaults['environment'] != 'prod') {
      $generateParams += [
        'backup_base' => $input->getOption('backup-db') ? 1 : 0,
        'reimport' => $input->getOption('reimport') ? 1 : 0,
        'dump_fetch_update' => $input->getOption('dump-fetch-update') ? 1 : 0,
      ];

      if ($input->getOption('dump-retrieval-tool') == 'scp') {
        if (!empty($input->getOption('ssh-config-name'))) {
          $scp_connection = $input->getOption('ssh-config-name');
        }
        else {
          $scp_connection = $input->getOption('scp-connection-info');
        }
        $recap_fetch_command = 'scp ' . $scp_connection . ':' . $input->getOption('fetch-source-path') . ' ' . $this->generator->getCombawaRoot() . '/' . $input->getOption('fetch-dest-path');
      }
      else {
        $recap_fetch_command = 'cp ' . $input->getOption('fetch-source-path') . ' ' . $this->generator->getCombawaRoot() . '/' . $input->getOption('fetch-dest-path');
      }
      $generateParams += [
        'dump_fetch_method' => $recap_fetch_command,
        'dump_fetch_path_source' => $recap_fetch_path_source,
        'dump_fetch_path_dest' => $recap_fetch_path_dest,
        'dump_file_name' => $input->getOption('fetch-dest-path'),
      ];
    }
    $generateParams += $defaults;

    // Improve attributes readibility.
    $recap_db_password = empty($generateParams['db_password']) ? 'No password' : 'Your secret password';
    $recap_backup_base = $generateParams['backup_base'] ? 'Yes' : 'No';
    $recap_write_settings = $generateParams['write_db_settings'] ? 'Yes' : 'No';
    $enable_splits = implode(', ', $generateParams['enable_splits']);

    $recap_params = [
      ['App root', $generateParams['app_root']],
      ['Environment', $generateParams['environment']],
      ['DB Host', $generateParams['db_host']],
      ['DB Port', $generateParams['db_port']],
      ['DB name', $generateParams['db_user']],
      ['DB Username', $generateParams['db_name']],
      ['DB password', $recap_db_password],
      ['Site URL', $generateParams['environment_url']],
      ['Backup DB before build', $recap_backup_base],
      ['Write code to connect to DB', $recap_write_settings],
      ['Config splits to enable', $enable_splits],
    ];
    $this->getIo()->newLine(1);
    $this->getIo()->commentBlock('Settings recap');
    $this->getIo()->table(['Parameter', 'Value'], $recap_params);

    // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmOperation
    if (!$this->confirmOperation()) {
      return 1;
    }

    $this->generator->setIo($this->getIo());
    $this->generator->generate($generateParams);

    // Display the command to run in non-interactive mode to get the same
    // result.
    $command = './vendor/bin/drupal ' . $input->getFirstArgument() . ' \\';
    foreach ($input->getOptions() as $key => $value) {
      if (empty($value) || $key === 'uri') {
        continue;
      }
      if (is_array($value)) {
        $value = implode(',', $value);
      }
      $command .= "\n" . '  --' . $key . ' ' . $value . ' \\';
    }
    $command .= "\n" . '  --no-interaction';
    $this->getIo()->simple('Next time you could just use:');
    $this->getIo()->comment($command);
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    $envVars = $_SERVER;

    try {
      $environment = $input->getOption('environment') ? $this->validateEnvironment($input->getOption('environment')) : null;
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());

      return 1;
    }

    if (!$environment) {
      $environment = $this->getIo()->choice(
        'Which kind of environment is it?',
        ['dev', 'testing', 'prod'],
        array_key_exists('COMBAWA_BUILD_ENV', $envVars) ? $envVars['COMBAWA_BUILD_ENV'] : 'prod'
      );
      $input->setOption('environment', $environment);
    }
    try {
      $environment_url = $input->getOption('environment-url') ? $this->validateUrl($input->getOption('environment-url')) : null;
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());

      return 1;
    }

    if (!$environment_url) {
      $environment_url = $this->getIo()->ask(
        'What is the URL of the project for the ' . $environment . ' environment?',
        array_key_exists('DRUSH_OPTIONS_URI', $envVars) ? $envVars['DRUSH_OPTIONS_URI'] : 'https://' . $environment . '.happyculture.coop',
        function ($environment_url) {
          return $this->validateUrl($environment_url);
        }
      );
      $input->setOption('environment-url', $environment_url);
    }

    if ($environment != 'prod') {

      try {
        $backup_db = $input->getOption('backup-db') ? (bool) $input->getOption('backup-db') : null;
      } catch (\Exception $error) {
        $this->getIo()->error($error->getMessage());

        return 1;
      }

      if (!$backup_db) {
        $backup_db = $this->getIo()->confirm(
          'Do you want the database to be backed up before each build?',
          array_key_exists('COMBAWA_DB_BACKUP_FLAG', $envVars) ? $envVars['COMBAWA_DB_BACKUP_FLAG'] : TRUE
        );
        $input->setOption('backup-db', $backup_db);
      }

      $build_mode = exec('/usr/bin/env composer config extra.combawa.build_mode  -d ' . $this->drupalFinder->getComposerRoot());
      if ($build_mode == 'update') {
        $always_update_ref_dump = $this->getIo()->confirm(
          'Do you want to update the reference dump before each build?',
          array_key_exists('COMBAWA_DB_FETCH_FLAG', $envVars) ? $envVars['COMBAWA_DB_FETCH_FLAG'] : TRUE
        );
        $input->setOption('dump-fetch-update', $always_update_ref_dump);

        try {
          $always_update_ref_dump = $input->getOption('dump-fetch-update') ? (bool) $input->getOption('dump-fetch-update') : FALSE;
        } catch (\Exception $error) {
          $this->getIo()->error($error->getMessage());

          return 1;
        }

        $retrieval_tool = $this->getIo()->choice(
          'When updated, what is the tool used to retrieve the reference dump?',
          ['cp', 'scp'],
          array_key_exists('COMBAWA_DB_RETRIEVAL_TOOL', $envVars) ? $envVars['COMBAWA_DB_RETRIEVAL_TOOL'] : 'scp'
        );
        $input->setOption('dump-retrieval-tool', $retrieval_tool);

        if ($retrieval_tool == 'scp') {

          $use_ssh_config_name = $this->getIo()->confirm(
            'Do you have an SSH config name from your ~/.ssh/config to use to retrieve the dump?',
            FALSE
          );

          if ($use_ssh_config_name) {
            try {
              $ssh_config_name = $input->getOption('ssh-config-name');
            } catch (\Exception $error) {
              $this->getIo()->error($error->getMessage());

              return 1;
            }

            if (!$ssh_config_name) {
              $ssh_config_name = $this->getIo()->ask(
                'What is the name of you config entry in your ~/.ssh/config file?',
                array_key_exists('COMBAWA_DB_FETCH_SSH_CONFIG_NAME', $envVars) ? $envVars['COMBAWA_DB_FETCH_SSH_CONFIG_NAME'] : 'my_remote'
              );
              $input->setOption('ssh-config-name', $ssh_config_name);
            }
          }
          else {
            try {
              $ssh_connection_info = $input->getOption('scp-connection-info');
            } catch (\Exception $error) {
              $this->getIo()->error($error->getMessage());

              return 1;
            }

            if (!$ssh_connection_info) {
              $ssh_connection_info = $this->getIo()->ask(
                'What is the connection string to the remote server?',
                array_key_exists('COMBAWA_DB_FETCH_SCP_CONNECTION', $envVars) ? $envVars['COMBAWA_DB_FETCH_SCP_CONNECTION'] : 'user@server.org'
              );
              $input->setOption('scp-connection-info', $ssh_connection_info);
            }
          }
        }

        try {
          $fetch_source_path = $input->getOption('fetch-source-path');
        } catch (\Exception $error) {
          $this->getIo()->error($error->getMessage());

          return 1;
        }

        if (!$fetch_source_path) {
          $fetch_source_path = $this->getIo()->ask(
            'What is the source path of the reference dump to copy (only Gzipped file supported at the moment)?',
            array_key_exists('COMBAWA_DB_FETCH_SOURCE', $envVars) ? $envVars['COMBAWA_DB_FETCH_SOURCE'] : '/home/dumps-source/my_dump.sql.gz',
            function ($path) {
              return $this->validateDumpExtension($path);
            }
          );
          $input->setOption('fetch-source-path', $fetch_source_path);
        }

        try {
          $fetch_dest_path = $input->getOption('fetch-dest-path');
        } catch (\Exception $error) {
          $this->getIo()->error($error->getMessage());

          return 1;
        }

        if (!$fetch_dest_path) {
          $fetch_dest_path = $this->getIo()->ask(
            'What will be the path of the fetched dump in this repo? (ex: dumps/reference_dump.sql.gz - include filename, only Gzipped files are supported)',
            array_key_exists('COMBAWA_DB_FETCH_DEST', $envVars) ? $envVars['COMBAWA_DB_FETCH_DEST'] : 'dumps/reference_dump.sql.gz',
            function ($path) {
              return $this->validateDumpExtension($path);
            }
          );
          $input->setOption('fetch-dest-path', $fetch_dest_path);
        }

        try {
          $reimport = $input->getOption('reimport') ? (bool) $input->getOption('reimport') : null;
        } catch (\Exception $error) {
          $this->getIo()->error($error->getMessage());

          return 0;
        }

        if (!$reimport) {
          $reimport = $this->getIo()->confirm(
            'Do you want the site to be reimported from the reference dump on each build?',
            array_key_exists('COMBAWA_REIMPORT_REF_DUMP_FLAG', $envVars) ? $envVars['COMBAWA_REIMPORT_REF_DUMP_FLAG'] : FALSE
          );
          $input->setOption('reimport', $reimport);
        }
      }
    }

    try {
      $db_host = $input->getOption('db-host');
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());

      return 1;
    }

    if (!$db_host) {
      $db_host = $this->getIo()->ask(
        'What is the hostname of your database server?',
        array_key_exists('COMBAWA_DB_HOSTNAME', $envVars) ? $envVars['COMBAWA_DB_HOSTNAME'] : 'localhost'
      );
      $input->setOption('db-host', $db_host);
    }

    try {
      $db_port = $input->getOption('db-port');
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());

      return 1;
    }

    if (!$db_port) {
      $db_port = $this->getIo()->ask(
        'What is the port of your database server?',
        array_key_exists('COMBAWA_DB_PORT', $envVars) ? $envVars['COMBAWA_DB_PORT'] : '3306'
      );
      $input->setOption('db-port', $db_port);
    }

    try {
      $db_name = $input->getOption('db-name');
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());

      return 1;
    }

    if (!$db_name) {
      $db_name = $this->getIo()->ask(
        'What is the name of your database?',
        array_key_exists('COMBAWA_DB_DATABASE', $envVars) ? $envVars['COMBAWA_DB_DATABASE'] : 'drupal8'
      );
      $input->setOption('db-name', $db_name);
    }

    try {
      $db_user = $input->getOption('db-user');
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());

      return 1;
    }

    if (!$db_user) {
      $db_user = $this->getIo()->ask(
        'What is the user name of your database?',
        array_key_exists('COMBAWA_DB_USER', $envVars) ? $envVars['COMBAWA_DB_USER'] : 'root'
      );
      $input->setOption('db-user', $db_user);
    }

    try {
      $db_password = $input->getOption('db-password');
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());

      return 1;
    }

    if (!$db_password) {
      $db_password = $this->getIo()->askEmpty(
        'What is the password of your database?',
        array_key_exists('COMBAWA_DB_PASSWORD', $envVars) ? $envVars['COMBAWA_DB_PASSWORD'] : ''
      );
      $input->setOption('db-password', $db_password);
    }

    try {
      $backup_db = $input->getOption('backup-db') ? (bool) $input->getOption('backup-db') : null;
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());

      return 1;
    }

    if (!$backup_db) {
      $backup_db = $this->getIo()->confirm(
        'Do you want the database to be backed up before each build?',
        array_key_exists('COMBAWA_DB_BACKUP_FLAG', $envVars) ? $envVars['COMBAWA_DB_BACKUP_FLAG'] : TRUE
      );
      $input->setOption('backup-db', $backup_db);
    }

    try {
      $db_write = $input->getOption('write-db-settings');
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());

      return 1;
    }

    if (!$db_write) {
      $db_write = $this->getIo()->confirm(
        'Do you want Combawa to create a settings.local.php file that will ease your DB connection? You can do it yourself later on, the code to copy/paste will be prompted in the next step.',
        TRUE
      );
      $input->setOption('write-db-settings', $db_write);
    }

    try {
      $enable_splits = $input->getOption('enable-splits');
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());

      return 1;
    }

    if (!$enable_splits) {
      $available_splits = $this->getAvailableConfigSplits();
      $enable_splits = $this->getIo()->choice(
        'Which config splits do you want to enable? (comma separated keys)',
        $available_splits,
        NULL,
        TRUE
      );
      $input->setOption('enable-splits', $enable_splits);
    }
  }

  /**
   * Validates an environment name.
   *
   * @param string $env
   *   The environment name.
   * @return string
   *   The environment name.
   * @throws \InvalidArgumentException
   */
  protected function validateEnvironment($env) {
    $env = strtolower($env);
    if (in_array($env, ['dev', 'testing', 'prod'])) {
      return $env;
    }
    else {
      throw new \InvalidArgumentException(sprintf('Environment name "%s" is invalid (only dev, testing or prod allowed).', $env));
    }
  }

  /**
   * Validates an url.
   *
   * @param string $url
   *   The url to validate.
   *
   * @return string
   *   The url.
   */
  protected function validateUrl($url) {
    $parts = parse_url($url);
    if ($parts === FALSE) {
      throw new \InvalidArgumentException(
        sprintf(
          '"%s" is a malformed url.',
          $url
        )
      );
    }
    elseif (empty($parts['scheme']) || empty($parts['host'])) {
      throw new \InvalidArgumentException(
        sprintf(
          'Please specify a full URL with scheme and host instead of "%s".',
          $url
        )
      );
    }
    return $url;
  }

  /**
   * Helper to validate that the dump filetype is supported.
   *
   * @param $path
   *   Dump file path.
   *
   * @return bool
   */
  protected function validateDumpExtension($path) {
    switch (pathinfo($path, PATHINFO_EXTENSION)) {
      case 'gz':
        return $path;
      default:
        throw new \InvalidArgumentException(
          sprintf(
            'The file extension "%s" is not supported (only Gzipped files).',
            $path
          )
        );
    }
  }

  /**
   * Get available config splits from project's configuration.
   *
   * @return string[]
   *   The list of available config splits.
   */
  protected function getAvailableConfigSplits() {
    /** @var \Drupal\Core\Config\FileStorage $confiStorage */
    $confiStorage = \Drupal\Core\Config\FileStorageFactory::getSync();
    $availableSplits = $confiStorage->listAll('config_split.config_split.');
    array_walk($availableSplits, function (&$split) {
      $split = str_replace('config_split.config_split.', '', $split);
    });
    return $availableSplits;
  }

  /**
   * Validate that all selected config splits are available for this project.
   *
   * @param array $splits
   *   The selected configuration splits.
   *
   * @return array
   *   The selected configuration splits.
   */
  protected function validateConfigSplits(array $splits) {
    $available = $this->getAvailableConfigSplits();
    $filteredSplits = array_filter($splits, function ($split) use ($available) {
      return in_array($split, $available, TRUE);
    });

    if (count($filteredSplits) !== count($splits)) {
      throw new \InvalidArgumentException(
        sprintf(
          'The "%s" configuration splits are not available in this project. (Available: %s)',
          implode(', ', array_diff($splits, $filteredSplits)),
          implode(', ', $available)
        )
      );
    }

    return $filteredSplits;
  }

}
