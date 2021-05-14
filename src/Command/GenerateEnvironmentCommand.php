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
      'backup_base' => $input->getOption('backup-db') ? 1 : 0,
      'write_db_settings' => $input->getOption('write-db-settings'),
    ];

    // Improve attributes readibility.
    $recap_db_password = empty($generateParams['db_password']) ? 'No password' : 'Your secret password';
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

}
