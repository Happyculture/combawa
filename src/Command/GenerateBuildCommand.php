<?php

namespace Drupal\Console\Combawa\Command;

use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Combawa\Generator\BuildGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateBuildCommand extends Command {

  use ConfirmationTrait;

  const REGEX_MACHINE_NAME = '/^[a-z0-9_]+$/';

  /**
   * @var BuildGenerator
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
   * @param BuildGenerator $generator
   * @param StringConverter  $stringConverter
   * @param string           $app_root
   */
  public function __construct(
    BuildGenerator $generator,
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
      ->setName('combawa:generate-build')
      ->setAliases(['cgb'])
      ->setDescription('Generate build scripts.')
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
        'url',
        null,
        InputOption::VALUE_REQUIRED,
        'The project production URL.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $name = $this->validateName($input->getOption('name'));
    $machine_name = $this->validateMachineName($input->getOption('machine-name'));
    $url = $this->validateUrl($input->getOption('url'));

    $recap_params = [
      ['Name', $name],
      ['Machine name', $machine_name],
      ['URL', $url],
    ];

    $this->getIo()->newLine(1);
    $this->getIo()->commentBlock('Settings recap');
    $this->getIo()->table(['Parameter', 'Value'], $recap_params);

    // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmOperation
    if (!$this->confirmOperation()) {
      return 1;
    }

    $this->generator->generate([
      'name' => $name,
      'machine_name' => $machine_name,
      'url' => $url,
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
