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

    $name = $this->validator->validateModuleName($input->getOption('name'));
    $machine_name = $this->validator->validateMachineName($input->getOption('machine-name'));

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
    $validators = $this->validator;

    try {
      // A profile is technically also a module, so we can use the same
      // validator to check the name.
      $name = $input->getOption('name') ? $validators->validateModuleName($input->getOption('name')) : null;
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());

      return 1;
    }

    if (!$name) {
      $name = $this->getIo()->ask(
        'What is the human readable name of the project?',
        '',
        function ($name) use ($validators) {
          return $validators->validateModuleName($name);
        }
      );
      $input->setOption('name', $name);
    }

    try {
      $machine_name = $input->getOption('machine-name') ? $validators->validateMachineName($input->getOption('machine-name')) : null;
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());

      return 1;
    }

    if (!$machine_name) {
      $machine_name = $this->getIo()->ask(
        'What is the machine name of the project?',
        $this->stringConverter->createMachineName($name),
        function ($machine_name) use ($validators) {
          return $validators->validateMachineName($machine_name);
        }
      );
      $input->setOption('machine-name', $machine_name);
    }
  }

}
