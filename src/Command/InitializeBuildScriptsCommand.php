<?php

namespace Drupal\Console\Combawa\Command;

use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Combawa\Generator\InitializeBuildScriptsGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class InitializeBuildScriptsCommand extends Command {

  use ConfirmationTrait;

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

  protected $run_gen_env_command = FALSE;

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
    $build_mode = $this->validateBuildMode($input->getOption('build-mode'));
    $recap_params = [
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
      'build_mode' => $build_mode,
    ]);
    if ($this->run_gen_env_command) {
      $process = new Process([$this->drupalFinder->getVendorDir() . '/bin/drupal', 'combawa:generate-environment']);
      $process->setTty(true);
      $process->run();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
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

    $this->run_gen_env_command = $this->getIo()->confirm(
      'Next, we will need you to generate the environment file (.env). Do you want to do it right after saving the previous settings?',
      TRUE);
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
