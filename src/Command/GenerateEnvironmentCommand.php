<?php

namespace Drupal\Console\Combawa\Command;

use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Combawa\Generator\EnvironmentGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class GenerateEnvironmentCommand extends Command {

  use ConfirmationTrait;

  /**
   * @var EnvironmentGenerator
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
        'core',
        null,
        InputOption::VALUE_REQUIRED,
        'Drupal core version built (Drupal 7, Drupal 8).'
      )
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
        'dump-always-update',
        null,
        InputOption::VALUE_NONE,
        'Always update the reference dump before building.'
      )
      ->addOption(
        'dump-retrieval-tool',
        null,
        InputOption::VALUE_NONE,
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
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    $defaults = [
      'app_root' => $this->generator->getCombawaRoot(),
      'webroot' => $this->generator->getCombawaWeboot(),
      'environment' => $this->validateEnvironment($input->getOption('environment')),
      'db_host' => $input->getOption('db-host'),
      'db_port' => $input->getOption('db-port'),
      'db_name' => $input->getOption('db-name'),
      'db_user' => $input->getOption('db-user'),
      'db_password' => $input->getOption('db-password'),
      'core' => $this->extractCoreVersion($input->getOption('core')),
      'environment_url' => '',
      'backup_base' => 1,
      'reimport' => 0,
      'dump_always_update' => 0,
      'fetch_source_path' => '',
      'fetch_dest_path' => 'reference_dump.sql.gz',
    ];

    $generateParams = [];
    if ($defaults['environment'] != 'prod') {
      $generateParams += [
        'environment_url' => $this->validateUrl($input->getOption('environment-url')),
        'backup_base' => $input->getOption('backup-db') ? 1 : 0,
        'reimport' => $input->getOption('reimport') ? 1 : 0,
        'dump_always_update' => $input->getOption('dump-always-update') ? 1 : 0,
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
        'dump_fetch_command' => $recap_fetch_command,
        'dump_file_name' => $input->getOption('fetch-dest-path'),
      ];
    }
    $generateParams += $defaults;

    // Improve attributes readibility.
    $recap_db_password = empty($generateParams['db_password']) ? 'No password' : 'Your secret password';
    $recap_backup_base = $generateParams['backup_base'] ? 'Yes' : 'No';
    $recap_update_ref_dump = $generateParams['dump_always_update'] ? 'Yes' : 'No';
    $recap_reimport = $generateParams['reimport'] ? 'Yes' : 'No';

    $recap_params = [
      ['App root', $generateParams['app_root']],
      ['Core Version', $generateParams['core']],
      ['Environment', $generateParams['environment']],
      ['DB Host', $generateParams['db_host']],
      ['DB Port', $generateParams['db_port']],
      ['DB name', $generateParams['db_user']],
      ['DB Username', $generateParams['db_name']],
      ['DB password', $recap_db_password],
      ['Site URL', $generateParams['environment_url']],
      ['Backup DB before build', $recap_backup_base],
      ['Update ref dump before build', $recap_update_ref_dump],
      ['Retrieve dump', $recap_fetch_command],
      ['Reference dump filename', $generateParams['dump_file_name']],
      ['Always reimport from reference dump', $recap_reimport],
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
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    $envVars = getenv();

    // Identify the Drupal version built.
    try {
      $core_version = $input->getOption('core') ? $input->getOption('core') : null;
      if (empty($core_version)) {
        $core_version = $this->getIo()->choice(
          'With which version of Drupal will you run this project?',
          ['Drupal 7', 'Drupal 8'],
          'Drupal 8'
        );
        $input->setOption('core', $core_version);
      }
      else if (!in_array($core_version, [7, 8])) {
        throw new \InvalidArgumentException(sprintf('Invalid version "%s" specified (only 7 or 8 are supported at the moment).', $core_version));
      }
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());
      return 1;
    }

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

    if ($environment != 'prod') {

      try {
        $environment_url = $input->getOption('environment-url') ? $this->validateUrl($input->getOption('environment-url')) : null;
      } catch (\Exception $error) {
        $this->getIo()->error($error->getMessage());

        return 1;
      }

      if (!$environment_url) {
        $environment_url = $this->getIo()->ask(
          'What is the URL of the project for the ' . $environment . ' environment?',
          array_key_exists('COMBAWA_WEBSITE_URI', $envVars) ? $envVars['COMBAWA_WEBSITE_URI'] : 'https://' . $environment . '.happyculture.coop',
          function ($environment_url) {
            return $this->validateUrl($environment_url);
          }
        );
        $input->setOption('environment-url', $environment_url);
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

      $build_mode = array_key_exists('COMBAWA_BUILD_MODE', $envVars) ? $envVars['COMBAWA_BUILD_MODE'] : 'install';
      if ($build_mode == 'update') {
        $always_update_ref_dump = $this->getIo()->confirm(
          'Do you want to update the reference dump before each build?',
          array_key_exists('COMBAWA_DB_FETCH_FLAG', $envVars) ? $envVars['COMBAWA_DB_FETCH_FLAG'] : TRUE
        );
        $input->setOption('dump-always-update', $always_update_ref_dump);

        try {
          $always_update_ref_dump = $input->getOption('dump-always-update') ? (bool) $input->getOption('dump-always-update') : FALSE;
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

        $validateDumpExtension = function ($path) {
          switch (pathinfo($path, PATHINFO_EXTENSION)) {
            case 'gz':
              return TRUE;
            default:
              throw new \InvalidArgumentException(
                sprintf(
                  'The file extension "%s" is not supported (only Gzipped files).',
                  $path
                )
              );
          }
        };
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
            $validateDumpExtension
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
            'What should the destination reference dump path in this repo (include filename, only Gzipped files supported)?',
            array_key_exists('COMBAWA_DB_FETCH_DEST', $envVars) ? $envVars['COMBAWA_DB_FETCH_DEST'] : 'dumps/reference_dump.sql.gz',
            $validateDumpExtension
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
            array_key_exists('COMBAWA_REIMPORT_REF_DUMP', $envVars) ? $envVars['COMBAWA_REIMPORT_REF_DUMP'] : FALSE
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
        array_key_exists('COMBAWA_DB_HOST', $envVars) ? $envVars['COMBAWA_DB_HOST'] : 'localhost'
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
        array_key_exists('COMBAWA_DB_NAME', $envVars) ? $envVars['COMBAWA_DB_NAME'] : 'drupal8'
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
   * Validates a retrieval tool name.
   *
   * @param string $tool
   *   The tool name.
   * @return string
   *   The tool name.
   * @throws \InvalidArgumentException
   */
  protected function validateRetrievalTool($tool) {
    $tool = strtolower($tool);
    if (in_array($tool, ['cp', 'scp'])) {
      return $tool;
    }
    else {
      throw new \InvalidArgumentException(sprintf('The retrieval tool name "%s" is invalid (only cp or scp allowed).', $tool));
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
   * @param $core_version
   */
  protected function extractCoreVersion($core_version) {
    $matches = [];
    if (preg_match('`^Drupal ([0-9]+)$`', $core_version, $matches)) {
      $core_version = $matches[1];
    }
    return $core_version;
  }

}
