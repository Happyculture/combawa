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
use Drupal\Console\Combawa\Command\GenerateEnvironmentCommand;

class InitializeBuildScriptsCommand extends Command {

  use ConfirmationTrait;

  const SCRIPTS_FOLDER = '../scripts/combawa';

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
      )
      ->addOption(
        'overwrite-scripts',
        null,
        InputOption::VALUE_NONE,
        'Overwrite existing scripts files.'
      )
      ->addOption(
        'generate-env',
        null,
        InputOption::VALUE_OPTIONAL,
        'Generate environment file.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $build_mode = $this->validateBuildMode($input->getOption('build-mode'));
    $overwrite_scripts = (bool) $input->getOption('overwrite-scripts');
    $recap_params = [
      ['Build mode', $build_mode],
    ];

    if (is_file(self::SCRIPTS_FOLDER . '/' . $build_mode . '.sh')) {
      $recap_params[] = ['Overwrite scripts files', $overwrite_scripts ? 'Yes' : 'No'];
    }

    $this->getIo()->newLine(1);
    $this->getIo()->commentBlock('Settings recap');
    $this->getIo()->table(['Parameter', 'Value'], $recap_params);

    // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmOperation
    if (!$this->confirmOperation()) {
      return 1;
    }

    $this->generator->generate([
      'build_mode' => $build_mode,
      'overwrite_scripts' => $overwrite_scripts,
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
      $build_mode = $this->getIo()->choice(
        'What is the build mode to use?',
        ['install', 'update'],
        'install'
      );
      $input->setOption('build-mode', $build_mode);
    }

    if (is_file(self::SCRIPTS_FOLDER . '/' . $build_mode . '.sh')) {
      try {
        $overwrite_scripts = $input->getOption('overwrite-scripts') ? (bool) $input->getOption('overwrite-scripts') : null;
      } catch (\Exception $error) {
        $this->getIo()->error($error->getMessage());

        return 1;
      }

      if (null === $overwrite_scripts) {
        $overwrite_scripts = $this->getIo()->confirm(
          'Do you want to overwrite your existing scripts located in the scripts/combawa directory?',
          FALSE
        );
        $input->setOption('overwrite-scripts', $overwrite_scripts);
      }
    }

    try {
      $generate_env = $input->getOption('generate-env');
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());

      return 1;
    }

    if (null === $generate_env) {
      $this->run_gen_env_command = $this->getIo()->confirm(
        'Next, we will need you to generate the environment file (.env). Do you want to do it right after saving the previous settings?'
      );
    }
    else {
      $this->run_gen_env_command = (bool) $generate_env;
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
