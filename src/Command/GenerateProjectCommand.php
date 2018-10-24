<?php

namespace Drupal\Console\Combawa\Command;

use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Combawa\Generator\ProjectGenerator;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateProjectCommand extends Command {

  use ConfirmationTrait;

  const REGEX_MACHINE_NAME = '/^[a-z0-9_]+$/';

  /**
   * @var ProjectGenerator
   */
  protected $generator;

  /**
   * @var StringConverter
   */
  protected $stringConverter;

  /**
   * @var string The document root absolute path.
   */
  protected $appRoot;

  /**
   * ProfileCommand constructor.
   *
   * @param ProjectGenerator $generator
   * @param StringConverter  $stringConverter
   * @param string           $app_root
   */
  public function __construct(
    ProjectGenerator $generator,
    StringConverter $stringConverter,
    $app_root
  ) {
    $this->generator = $generator;
    $this->stringConverter = $stringConverter;
    $this->appRoot = $app_root;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this
      ->setName('combawa:generate-project')
      ->setAliases(['cgp'])
      ->setDescription('Generate an install profile, a default theme and an admin theme.')
      ->addOption(
        'name',
        null,
        InputOption::VALUE_REQUIRED,
        'The project readable name (ex: Happyculture).'
      )
      ->addOption(
        'machine-name',
        null,
        InputOption::VALUE_REQUIRED,
        'The project (short) machine name (ex: hc).'
      )
      ->addOption(
        'generate-build',
        null,
        InputOption::VALUE_NONE,
        'Generate the build files.'
      )
      ->addOption(
        'url',
        null,
        InputOption::VALUE_REQUIRED,
        'The project production URL.'
      )
      ->addOption(
        'config-folder',
        null,
        InputOption::VALUE_REQUIRED,
        'The configuration storage folder, relative to the document root.'
      )
      ->addOption(
        'generate-config',
        null,
        InputOption::VALUE_NONE,
        'Change the config to use the new profile and themes by default.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmOperation
    if (!$this->confirmOperation()) {
      return 1;
    }
    $name = $this->validateName($input->getOption('name'));
    $machine_name = $this->validateMachineName($input->getOption('machine-name'));
    $generate_build = (bool) $input->getOption('generate-build');
    $url = $this->validateUrl($input->getOption('url'));
    $config_folder = $this->validatePath($input->getOption('config-folder'));
    $generate_config = (bool) $input->getOption('generate-config');

    $this->generator->generate([
      'name' => $name,
      'machine_name' => $machine_name,
      'generate_build' => $generate_build,
      'url' => $url,
      'config_folder' => $config_folder,
      'generate_config' => $generate_config,
      'profiles_dir' => 'profiles',
      'themes_dir' => 'themes/custom'
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    try {
      // A profile is technically also a module, so we can use the same
      // validator to check the name.
      $name = $input->getOption('name') ? $this->validateName($input->getOption('name')) : null;
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());

      return 1;
    }

    if (!$name) {
      $name = $this->getIo()->ask(
        'What is the human readable name of the project?',
        'Happy Rocket',
        function ($name) {
          return $this->validateName($name);
        }
      );
      $input->setOption('name', $name);
    }

    try {
      $machine_name = $input->getOption('machine-name') ? $this->validateMachineName($input->getOption('machine-name')) : null;
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());

      return 1;
    }

    if (!$machine_name) {
      $machine_name = $this->getIo()->ask(
        'What is the machine name of the project?',
        $this->stringConverter->createMachineName($name),
        function ($machine_name) {
          return $this->validateMachineName($machine_name);
        }
      );
      $input->setOption('machine-name', $machine_name);
    }

    try {
      $generate_build = $input->getOption('generate-build') ? (bool) $input->getOption('generate-build') : null;
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());

      return 1;
    }

    if (!$generate_build) {
      $generate_build = $this->getIo()->confirm(
        'Do you want to generate the build files (settings, install, update and pre/post deploy actions)?',
        TRUE
      );
      $input->setOption('generate-build', $generate_build);
    }

    try {
      $url = $input->getOption('url') ? $this->validateUrl($input->getOption('url')) : null;
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());

      return 1;
    }

    if (!$url) {
      $url = $this->getIo()->ask(
        'What is the production URL of the project?',
        'https://happyculture.coop',
        function ($url) {
          return $this->validateUrl($url);
        }
      );
      $input->setOption('url', $url);
    }

    try {
      $config_folder = $input->getOption('config-folder') ? $this->validatePath($input->getOption('config-folder')) : null;
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());

      return 1;
    }

    if (!$config_folder) {
      $config_folder = $this->getIo()->ask(
        'Where are the configuration files stored (relative to the document root)?',
        '../config/sync',
        function ($config_folder) {
          return $this->validatePath($config_folder);
        }
      );
      $input->setOption('config-folder', $config_folder);
    }

    try {
      $generate_config = $input->getOption('generate-config') ? (bool) $input->getOption('generate-config') : null;
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());

      return 1;
    }

    if (!$generate_config) {
      $generate_config = $this->getIo()->confirm(
        'Do you want the config to be changed so the new profile and themes are used by default?',
        TRUE
      );
      $input->setOption('generate-config', $generate_config);
    }
  }

  /**
   * Validates a module name.
   *
   * @param string $module
   *   The module name.
   * @return string
   *   The module name.
   * @throws \InvalidArgumentException
   */
  protected function validateName($module) {
    if (!empty($module)) {
      return $module;
    }
    else {
      throw new \InvalidArgumentException(sprintf('Name "%s" is invalid.', $module));
    }
  }

  /**
   * Validates a machine name.
   *
   * @param string $machine_name
   *   The machine name.
   * @return string
   *   The machine name.
   * @throws \InvalidArgumentException
   */
  protected function validateMachineName($machine_name) {
    if (preg_match(self::REGEX_MACHINE_NAME, $machine_name)) {
      return $machine_name;
    }
    else {
      throw new \InvalidArgumentException(
        sprintf(
          'Machine name "%s" is invalid, it must contain only lowercase letters, numbers and underscores.',
          $machine_name
        )
      );
    }
  }

  /**
   * Validates a path relative to the document root.
   *
   * @param string $path
   *   The path to validate.
   * @return string
   *   The path.
   */
  protected function validatePath($path) {
    $destination = $this->appRoot . '/' . $path;
    if (is_dir($destination)) {
      return $path;
    }
    else {
      throw new \InvalidArgumentException(
        sprintf(
          '"%s" is not an existing path.',
          $destination
        )
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
