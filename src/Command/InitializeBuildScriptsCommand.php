<?php

namespace Drupal\Console\Combawa\Command;

use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Combawa\Generator\InitializeBuildScriptsGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InitializeBuildScriptsCommand extends Command {

  use ConfirmationTrait;

  const REGEX_MACHINE_NAME = '/^[a-z0-9_]+$/';

  /**
   * @var InitializeBuildScriptsGenerator
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
   * @param InitializeBuildScriptsGenerator $generator
   * @param StringConverter  $stringConverter
   * @param string           $app_root
   */
  public function __construct(InitializeBuildScriptsGenerator $generator, StringConverter $stringConverter, $app_root) {
    $this->generator = $generator;
    $this->stringConverter = $stringConverter;
    $this->appRoot = $app_root;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('combawa:initialize-build-scripts')
      ->setAliases(['ibs'])
      ->setDescription('Initialize Combawa required scripts.')
      ->addOption(
        'machine-name',
        null,
        InputOption::VALUE_REQUIRED,
        'The project (short) machine name (ex: hc).'
      )
      ->addOption(
        'build-mode',
        null,
        InputOption::VALUE_REQUIRED,
        'The build module to use (install or update) if wanted.',
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $machine_name = $this->validateMachineName($input->getOption('machine-name'));
    $build_mode = $this->validateBuildMode($input->getOption('build-mode'));

    $recap_params = [
      ['Machine name', $machine_name],
      ['Build mode', $build_mode],
    ];

    $this->getIo()->newLine(1);
    $this->getIo()->commentBlock('Settings recap');
    $this->getIo()->table(['Parameter', 'Value'], $recap_params);

    // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmOperation
    if (!$this->confirmOperation()) {
      return 1;
    }

    $this->generator->generate([
      'machine_name' => $machine_name,
      'build_mode' => $build_mode,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {

    try {
      $machine_name = $input->getOption('machine-name') ? $this->validateMachineName($input->getOption('machine-name')) : null;
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());

      return 1;
    }

    if (!$machine_name) {
      $machine_name = $this->getIo()->ask(
        'What is the machine name of your install profile?',
        'new_project',
        function ($machine_name) {
          return $this->validateMachineName($machine_name);
        }
      );
      $input->setOption('machine-name', $machine_name);
    }

    try {
      $build_mode = $input->getOption('build-mode') ? $this->validateBuildMode($input->getOption('build-mode')) : NULL;
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());

      return 1;
    }

    if (!$build_mode) {
      $build_mode = $this->getIo()->ask(
        'What is the build mode to use (install or update)?',
        'install',
        function ($build_mode) {
          return $this->validateBuildMode($build_mode);
        }
      );
      $input->setOption('build-mode', $build_mode);
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
    if (preg_match(static::REGEX_MACHINE_NAME, $machine_name)) {
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
   * Validates the build mode input.
   *
   * @param string $build_mode
   *   The build mode.
   * @return string
   *   The build mode.
   * @throws \InvalidArgumentException
   */
  protected function validateBuildMode($build_mode) {
    if (in_array($build_mode, ['install', 'update'])) {
      return $build_mode;
    }
    else {
      throw new \InvalidArgumentException(
        sprintf(
          'Build mode "%s" is invalid, it must either be install or update.',
          $build_mode
        )
      );
    }
  }

}
