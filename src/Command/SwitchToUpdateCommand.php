<?php

namespace Drupal\Console\Combawa\Command;

use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Combawa\Generator\EnvironmentInstallGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class SwitchToUpdateCommand extends Command {

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
  protected function configure() {
    $this
      ->setName('combawa:switch-update')
      ->setAliases(['csu'])
      ->setDescription('Switch to the update mode.')
      ->addOption(
        'dump-fetch-update',
        null,
        InputOption::VALUE_NONE,
        'Always update the reference dump before building.'
      )
      ->addOption(
        'dump-fetch-tool',
        null,
        InputOption::VALUE_NONE,
        'Tool used to retrieve the reference dump.'
      )
      ->addOption(
        'fetch-scp-user',
        null,
        InputOption::VALUE_OPTIONAL,
        'The SCP username.'
      )
      ->addOption(
        'fetch-scp-port',
        null,
        InputOption::VALUE_OPTIONAL,
        'The SCP port.'
      )
      ->addOption(
        'fetch-scp-server',
        null,
        InputOption::VALUE_OPTIONAL,
        'The SCP server name.'
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
        InputOption::VALUE_REQUIRED,
        'Reimport the website from the reference dump on each build.'
      )
      ->addOption(
        'write-db-settings',
        null,
        InputOption::VALUE_REQUIRED,
        'Flag to write the DB settings code.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    $defaults = [
      'app_root' => $this->generator->getCombawaRoot(),
      'webroot' => $this->generator->getCombawaWeboot(),
      'environment_url' => $this->validateUrl($input->getOption('environment-url')),
      'backup_base' => $input->getOption('backup-db') ? 1 : 0,
      'reimport' => 0,
      'dump_fetch_update' => 0,
      'fetch_source_path' => '',
      'fetch_dest_path' => 'reference_dump.sql.gz',
      'write_db_settings' => $input->getOption('write-db-settings'),
    ];

    $generateParams = [];
    if ($defaults['environment'] != 'prod') {
      $generateParams += [
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
        $fetch_method = 'scp';
        $fetch_path_source = $scp_connection . ':' . $input->getOption('fetch-source-path');
        $fetch_path_dest = $this->generator->getCombawaRoot() . '/' . $input->getOption('fetch-dest-path');
        $generateParams += [
          'dump_fetch_scp_user' => $input->getOption('fetch-scp-user'),
          'dump_fetch_scp_port' => $input->getOption('fetch-scp-port'),
          'dump_fetch_scp_server' => $input->getOption('fetch-scp-server'),
        ];
      }
      else {
        $fetch_method = 'cp';
        $fetch_path_source = $input->getOption('fetch-source-path');
        $fetch_path_dest = $this->generator->getCombawaRoot() . '/' . $input->getOption('fetch-dest-path');
      }
      $generateParams += [
        'dump_fetch_method' => $fetch_method,
        'dump_fetch_path_source' => $fetch_path_source,
        'dump_fetch_path_dest' => $fetch_path_dest,
        'dump_file_name' => $input->getOption('fetch-dest-path'),
      ];
    }
    $generateParams += $defaults;

    // Improve attributes readibility.
    $recap_backup_base = $generateParams['backup_base'] ? 'Yes' : 'No';
    $recap_update_ref_dump = $generateParams['dump_fetch_update'] ? 'Yes' : 'No';
    $recap_reimport = $generateParams['reimport'] ? 'Yes' : 'No';
    $recap_write_settings = $generateParams['write_db_settings'] ? 'Yes' : 'No';
    $recap_fetch_command = $fetch_method . ' ' . $fetch_path_source . ' ' . $fetch_path_dest;

    $recap_params = [
      ['App root', $generateParams['app_root']],
      ['Environment', $generateParams['environment']],
      ['Site URL', $generateParams['environment_url']],
      ['Update ref dump before build', $recap_update_ref_dump],
      ['Retrieve dump', $recap_fetch_command],
      ['Reference dump filename', $generateParams['dump_file_name']],
      ['Backup DB before build', $recap_backup_base],
      ['Always reimport from reference dump', $recap_reimport],
      ['Write code to connect to DB', $recap_write_settings],
    ];

    $this->getIo()->newLine(1);
    $this->getIo()->commentBlock('Settings recap');
    $this->getIo()->table(['Parameter', 'Value'], $recap_params);

    // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmOperation
    if (!$this->confirmOperation()) {
      return 1;
    }

    $this->generator->setIo($this->getIo());
    // @TODO: Get install variables.
    $this->generator->generate($generateParams);
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
      $fetch_update_ref_dump = $this->getIo()->confirm(
        'Do you want to update the reference dump before each build?',
        array_key_exists('COMBAWA_DB_FETCH_FLAG', $envVars) ? $envVars['COMBAWA_DB_FETCH_FLAG'] : TRUE
      );
      $input->setOption('dump-fetch-update', $fetch_update_ref_dump);

      try {
        $fetch_update_ref_dump = $input->getOption('dump-fetch-update') ? (bool) $input->getOption('dump-fetch-update') : FALSE;
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

}
