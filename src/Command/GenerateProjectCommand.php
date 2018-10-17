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
   * ProfileCommand constructor.
   *
   * @param ProjectGenerator $generator
   * @param StringConverter  $stringConverter
   */
  public function __construct(
    ProjectGenerator $generator,
    StringConverter $stringConverter
  ) {
    $this->generator = $generator;
    $this->stringConverter = $stringConverter;
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
        InputOption::VALUE_OPTIONAL,
        'The project (short) machine name (ex: hc).'
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
    $name = $this->validateModuleName($input->getOption('name'));
    $machine_name = $this->validateMachineName($input->getOption('machine-name'));

    $this->generator->generate([
      'name' => $name,
      'machine_name' => $machine_name,
      'profiles-dir' => 'profiles',
      'themes-dir' => 'themes/custom'
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    try {
      // A profile is technically also a module, so we can use the same
      // validator to check the name.
      $name = $input->getOption('name') ? $this->validateModuleName($input->getOption('name')) : null;
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());

      return 1;
    }

    if (!$name) {
      $name = $this->getIo()->ask(
        'What is the human readable name of the project?',
        '',
        function ($name) {
          return $this->validateModuleName($name);
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
  protected function validateModuleName($module) {
    if (!empty($module)) {
      return $module;
    }
    else {
      throw new \InvalidArgumentException(sprintf('Module name "%s" is invalid.', $module));
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

}
