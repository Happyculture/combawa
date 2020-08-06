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
   * @var string The document root absolute path.
   */
  protected $appRoot;

  /**
   * ProfileCommand constructor.
   *
   * @param EnvironmentGenerator $generator
   * @param StringConverter  $stringConverter
   * @param string           $app_root
   */
  public function __construct(
    EnvironmentGenerator $generator,
    $app_root
  ) {
    $this->generator = $generator;
    $this->appRoot = $app_root;
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
        'fetch-dump',
        null,
        InputOption::VALUE_NONE,
        'Fetch a database dump from a remote server before the build.'
      )
      ->addOption(
        'ssh-config-name',
        null,
        InputOption::VALUE_REQUIRED,
        'The remote server name where to find the dump.'
      )
      ->addOption(
        'ssh-dump-path',
        null,
        InputOption::VALUE_REQUIRED,
        'The remote server path where to find the dump.'
      )
      ->addOption(
        'dump-file-name',
        null,
        InputOption::VALUE_REQUIRED,
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
      'app_root' => $this->appRoot,
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
      'fetch_dump' => 0,
      'ssh_config_name' => '',
      'ssh_dump_path' => '',
      'dump_file_name' => '',
    ];

    $generateParams = [];
    if ($defaults['environment'] != 'prod') {
      $generateParams += [
        'environment_url' => $this->validateUrl($input->getOption('environment-url')),
        'backup_base' => $input->getOption('backup-db') ? 1 : 0,
        'reimport' => $input->getOption('reimport') ? 1 : 0,
        'fetch_dump' => $input->getOption('fetch-dump') ? 1 : 0,
        'dump_file_name' => $input->getOption('dump-file-name'),
      ];

      if ($generateParams['fetch_dump']) {
        $generateParams += [
          'ssh_config_name' => $input->getOption('ssh-config-name'),
          'ssh_dump_path' => $input->getOption('ssh-dump-path'),
        ];
      }
    }
    $generateParams += $defaults;

    // Improve attributes readibility.
    $recap_db_password = empty($generateParams['db_password']) ? 'No password' : 'Your secret password';
    $recap_backup_base = $generateParams['backup_base'] ? 'Yes' : 'No';
    $recap_fetch_dump = $generateParams['fetch_dump'] ? 'Yes' : 'No';
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
      ['Fetch remote dump', $recap_fetch_dump],
      ['Reference dump filename', $generateParams['dump_file_name']],
      ['SSH config name', $generateParams['ssh_config_name']],
      ['Remote dump path', $generateParams['ssh_dump_path']],
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

      try {
        $fetch_dump = $input->getOption('fetch-dump') ? (bool) $input->getOption('fetch-dump') : FALSE;
      } catch (\Exception $error) {
        $this->getIo()->error($error->getMessage());

        return 1;
      }

      if (!$fetch_dump) {
        $fetch_dump = $this->getIo()->confirm(
          'Do you want the database dump to be fetched from a remote server before each build?',
          array_key_exists('COMBAWA_DB_FETCH_FLAG', $envVars) ? $envVars['COMBAWA_DB_FETCH_FLAG'] : TRUE
        );
        $input->setOption('fetch-dump', $fetch_dump);
      }

      if ($fetch_dump) {

        try {
          $ssh_config_name = $input->getOption('ssh-config-name');
        } catch (\Exception $error) {
          $this->getIo()->error($error->getMessage());

          return 1;
        }

        if (!$ssh_config_name) {
          $ssh_config_name = $this->getIo()->ask(
            'What is the name for the dump remote server in your ~/.ssh/config file?',
            array_key_exists('COMBAWA_SSH_CONFIG_NAME', $envVars) ? $envVars['COMBAWA_SSH_CONFIG_NAME'] : 'my_remote'
          );
          $input->setOption('ssh-config-name', $ssh_config_name);
        }

        try {
          $ssh_dump_path = $input->getOption('ssh-dump-path');
        } catch (\Exception $error) {
          $this->getIo()->error($error->getMessage());

          return 1;
        }

        if (!$ssh_dump_path) {
          $ssh_dump_path = $this->getIo()->ask(
            'What is the full path of the dump file on the remote server?',
            array_key_exists('COMBAWA_PROD_DB_DUMP_PATH', $envVars) ? $envVars['COMBAWA_PROD_DB_DUMP_PATH'] : '/home/dumps/my_dump.sql.gz'
          );
          $input->setOption('ssh-dump-path', $ssh_dump_path);
        }

      }

      try {
        $dump_file_name = $input->getOption('dump-file-name');
      } catch (\Exception $error) {
        $this->getIo()->error($error->getMessage());

        return 1;
      }

      if (!$dump_file_name) {
        $dump_file_name = $this->getIo()->ask(
          'What is the local name of the dump file to be loaded before the builds? Do not include the .gz extension.',
          array_key_exists('COMBAWA_DUMP_FILE_NAME', $envVars) ? $envVars['COMBAWA_DUMP_FILE_NAME'] : 'reference_dump.sql'
        );
        $input->setOption('dump-file-name', $dump_file_name);
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
