<?php

namespace Drupal\Console\Combawa\Command;

use Drupal\Console\Combawa\Generator\EnvironmentGenerator;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateEnvironmentCommand extends Command {

  use ConfirmationTrait;

  const FETCH_DEST_PATH = 'dumps/reference_dump.sql.gz';

  /**
   * @var \Drupal\Console\Combawa\Generator\EnvironmentGenerator
   */
  protected $generator;

  /**
   * ProfileCommand constructor.
   *
   * @param EnvironmentGenerator $generator
   */
  public function __construct(EnvironmentGenerator $generator) {
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
        'scp-config-name',
        null,
        InputOption::VALUE_REQUIRED,
        'The remote server name where to find the dump.'
      )
      ->addOption(
        'scp-connection-username',
        null,
        InputOption::VALUE_OPTIONAL,
        'SCP connection username.'
      )
      ->addOption(
        'scp-connection-password',
        null,
        InputOption::VALUE_OPTIONAL,
        'SCP connection password.'
      )
      ->addOption(
        'scp-connection-servername',
        null,
        InputOption::VALUE_OPTIONAL,
        'SCP connection server name.'
      )
      ->addOption(
        'scp-connection-port',
        null,
        InputOption::VALUE_OPTIONAL,
        'SCP connection port.'
      )
      ->addOption(
        'fetch-source-path',
        null,
        InputOption::VALUE_REQUIRED,
        'Source path to copy the reference dump from.'
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
        'force-settings-generation',
        null,
        InputOption::VALUE_NONE,
        'Forces settings.local.php file generation.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $build_mode = $this->generator->computeBuildModeTemplate();

    $environment = $this->validateEnvironment($input->getOption('environment'));
    $generateParams = [
      'app_root' => $this->generator->getCombawaRoot(),
      'webroot' => $this->generator->getCombawaWeboot(),
      'environment' => $environment,
      'environment_url' => $this->validateUrl($input->getOption('environment-url')),
      'db_host' => $input->getOption('db-host'),
      'db_port' => $input->getOption('db-port'),
      'db_name' => $input->getOption('db-name'),
      'db_user' => $input->getOption('db-user'),
      'db_password' => escapeshellarg($input->getOption('db-password')),
      'backup_base' => $input->getOption('backup-db'),
      'reimport' => 0,
      'dump_fetch_update' => 0,
      'force_settings_generation' => $input->getOption('force-settings-generation'),
      'fetch_source_path' => '',
      'fetch_dest_path' => 'reference_dump.sql.gz',
      'write_db_settings' => $input->getOption('write-db-settings'),
    ];

    // Improve attributes readibility.
    $recap_db_password = empty($input->getOption('db-password')) ? 'No password' : 'Your secret password';
    $recap_backup_base = $generateParams['backup_base'] ? 'Yes' : 'No';
    $recap_write_settings = $generateParams['write_db_settings'] ? 'Yes' : 'No';

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
    ];

    if ($environment != 'prod') {
      $generateParams['backup_base'] = $input->getOption('backup-db') ? 1 : 0;
      $generateParams['reimport'] = $input->getOption('reimport') ? 1 : 0;
      $generateParams['dump_fetch_update'] = $input->getOption('dump-fetch-update') ? 1 : 0;

      $recap_fetch_path_source = $input->getOption('fetch-source-path');
      $recap_fetch_path_dest = $this->generator->getCombawaRoot() . '/' . static::FETCH_DEST_PATH;

      if ($build_mode == 'update') {
        if ($input->getOption('dump-retrieval-tool') == 'scp') {
          $recap_fetch_command = 'scp';
          if (!empty($input->getOption('scp-config-name'))) {
            $scp_connection = $input->getOption('scp-config-name');
            $generateParams['dump_scp_config_name'] = $scp_connection;
            $connection_string_recap = 'scp ' . $scp_connection . ':' . $recap_fetch_path_source . ' ' . $recap_fetch_path_dest;
            $recap_params[] = ['Fetch command', $connection_string_recap];
          }
          else {
            $generateParams['dump_scp_user'] = $input->getOption('scp-connection-username');
            // Escape scp password string for storage.
            $generateParams['dump_scp_password'] = escapeshellarg($input->getOption('scp-connection-password'));
            $generateParams['dump_scp_servername'] = $input->getOption('scp-connection-servername');
            $generateParams['dump_scp_port'] = $input->getOption('scp-connection-port');
            $connection_string_recap = 'scp ';
            if (!empty($generateParams['dump_scp_user'])) {
              if (!empty($input->getOption('scp-connection-password'))) {
                // Get the non escaped password from source input for readability.
                $connection_string_recap .= $generateParams['dump_scp_user'] . ':' . $input->getOption('scp-connection-password') . '@';
              }
              else {
                $connection_string_recap .= $generateParams['dump_scp_user'] . '@';
              }
            }
            $connection_string_recap .= $generateParams['dump_scp_servername'];
            $connection_string_recap .= ':' . $recap_fetch_path_source . ' ' . $recap_fetch_path_dest;
            $recap_params[] = ['Fetch command', $connection_string_recap];
          }
        }
        else {
          $recap_fetch_command = 'cp';
          $cp_string_recap = 'cp ' . $recap_fetch_path_source . ' ' . $recap_fetch_path_dest;
          $recap_params[] = ['Fetch command', $cp_string_recap];
        }
        $generateParams += [
          'dump_fetch_method' => $recap_fetch_command,
          'dump_fetch_path_source' => $recap_fetch_path_source,
          'dump_fetch_path_dest' => $recap_fetch_path_dest,
        ];

        $recap_db_reimport = $generateParams['reimport'] ? 'Yes' : 'No';
        $recap_db_fetch = $generateParams['dump_fetch_update'] ? 'Yes' : 'No';

        $recap_params[] = ['Always reimport DB before building?', $recap_db_reimport];
        $recap_params[] = ['Always update ref DB before building?', $recap_db_fetch];
      }
    }

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
    $command_fragment = ['./vendor/bin/drupal ' . $input->getFirstArgument()];
    foreach ($input->getOptions() as $key => $value) {
      // We inherit from Symfony Console default options commands. We want to
      // filter out some of them.
      if (empty($value) || in_array($key, ['uri', 'env', 'no-interaction', 'write-db-settings'])) {
        continue;
      }
      else if (in_array($key, ['backup-db', 'reimport', 'dump-fetch-update'])) {
        $command_fragment[] = '--' . $key;
      }
      else {
        if (is_array($value)) {
          $value = implode(',', $value);
        }
        $command_fragment[] = '--' . $key . ' ' . $value;
      }
    }
    $command_fragment[] = '--no-interaction';
    $command = implode(" \\\n", $command_fragment);

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



    try {
      $db_host = $input->getOption('db-host');
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());

      return 1;
    }

    if (!$db_host) {
      $db_host = $this->getIo()->ask(
        'What is the hostname of your database server?',
        array_key_exists('COMBAWA_DB_HOSTNAME', $envVars) ? $envVars['COMBAWA_DB_HOSTNAME'] : '127.0.0.1'
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

    if ($environment != 'prod') {
      $build_mode = exec('/usr/bin/env composer config extra.combawa.build_mode  -d ' . $this->drupalFinder->getComposerRoot());
      if ($build_mode == 'update') {
        try {
          $always_update_ref_dump = $input->getOption('dump-fetch-update') ? (bool) $input->getOption('dump-fetch-update') : FALSE;
        } catch (\Exception $error) {
          $this->getIo()->error($error->getMessage());

          return 1;
        }
        if (!$always_update_ref_dump) {
          $always_update_ref_dump = $this->getIo()->confirm(
            'Do you want to update the reference dump before each build?',
            array_key_exists('COMBAWA_DB_FETCH_FLAG', $envVars) ? $envVars['COMBAWA_DB_FETCH_FLAG'] : TRUE
          );
          $input->setOption('dump-fetch-update', $always_update_ref_dump);
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
              $ssh_config_name = $input->getOption('scp-config-name');
            } catch (\Exception $error) {
              $this->getIo()->error($error->getMessage());

              return 1;
            }
            if (!$ssh_config_name) {
              $ssh_config_name = $this->getIo()->ask(
                '[SCP] What is the name of you config entry in your ~/.ssh/config file?',
                array_key_exists('COMBAWA_DB_FETCH_SSH_CONFIG_NAME', $envVars) ? $envVars['COMBAWA_DB_FETCH_SSH_CONFIG_NAME'] : 'my_remote'
              );
              $input->setOption('scp-config-name', $ssh_config_name);
            }
          }
          else {
            try {
              $scp_username = $input->getOption('scp-connection-username');
            } catch (\Exception $error) {
              $this->getIo()->error($error->getMessage());

              return 1;
            }
            if (!$scp_username) {
              $scp_username = $this->getIo()->askEmpty(
                '[SCP] What is the connection username?',
                array_key_exists('COMBAWA_DB_FETCH_SCP_USER', $envVars) ? $envVars['COMBAWA_DB_FETCH_SCP_USER'] : '',
              );
              $input->setOption('scp-connection-username', $scp_username);
            }

            try {
              $scp_password = $input->getOption('scp-connection-password');
            } catch (\Exception $error) {
              $this->getIo()->error($error->getMessage());

              return 1;
            }

            if (!$scp_password) {
              $scp_password = $this->getIo()->askEmpty(
                '[SCP] What is the connection password?',
                array_key_exists('COMBAWA_DB_FETCH_SCP_PASSWORD', $envVars) ? $envVars['COMBAWA_DB_FETCH_SCP_PASSWORD'] : ''
              );
              $input->setOption('scp-connection-password', $scp_password);
            }

            try {
              $scp_servername = $input->getOption('scp-connection-servername');
            } catch (\Exception $error) {
              $this->getIo()->error($error->getMessage());

              return 1;
            }

            if (!$scp_servername) {
              $scp_servername = $this->getIo()->ask(
                '[SCP] What is the connection server name or IP?',
                array_key_exists('COMBAWA_DB_FETCH_SCP_SERVER', $envVars) ? $envVars['COMBAWA_DB_FETCH_SCP_SERVER'] : '',
                function ($scp_servername) {
                  return $this->validateDomainOrIPFormat($this->validateOptionalValueWhenRequested($scp_servername, 'scp-connection-servername'));
                }
              );
              $input->setOption('scp-connection-servername', $scp_servername);
            }

            try {
              $scp_port = $input->getOption('scp-connection-port');
            } catch (\Exception $error) {
              $this->getIo()->error($error->getMessage());

              return 1;
            }

            if (!$scp_port) {
              $scp_port = $this->getIo()->ask(
                '[SCP] What is the connection server port?',
                array_key_exists('COMBAWA_DB_FETCH_SCP_PORT', $envVars) ? $envVars['COMBAWA_DB_FETCH_SCP_PORT'] : 22
              );
              $input->setOption('scp-connection-port', $scp_port);
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
   * Validates a domain name format.
   *
   * @param string $connection_str
   *   The string to validate.
   *
   * @return string
   *   The domain or IP address.
   */
  protected function validateDomainOrIPFormat($connection_str) {
    // Format an IP address.
    if (preg_match('/^[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}$/', $connection_str)) {
      return $connection_str;
    }
    // Or a domain.
    else if (preg_match('/[-\w.]+\.[-\w.]+$/', $connection_str)) {
      return $connection_str;
    }
    throw new \InvalidArgumentException(sprintf('The connection string "%s" does not look like a valid domain or IP address.', $connection_str));
  }

  /**
   * Validator for empty arguments that may be optionnel from the CLI command.
   *
   * @param $value
   *   Value to evaluate.
   * @param $param_name
   *   Value's param name to prompt for when the field is empty.
   *
   * @return mixed
   */
  static public function validateOptionalValueWhenRequested($value, $param_name) {
    if (empty($value)) {
      throw new \InvalidArgumentException(sprintf('Option "%s" value can not be empty.', $param_name));
    }
    return $value;
  }

}
