<?php

namespace Drupal\Console\Combawa\Command;

use Drupal\Console\Combawa\Generator\ChangeBuildModeGenerator;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ChangeBuildModeCommand extends Command {

  use ConfirmationTrait;

  /**
   * The generator.
   *
   * @var \Drupal\Console\Combawa\Generator\ChangeBuildModeGenerator
   */
  protected $generator;

  /**
   * ProfileCommand constructor.
   *
   * @param \Drupal\Console\Combawa\Generator\ChangeBuildModeGenerator $generator
   *   The generator.
   */
  public function __construct(ChangeBuildModeGenerator $generator) {
    $this->generator = $generator;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('combawa:change-build-mode')
      ->setAliases(['ccbm'])
      ->setDescription('Change Combawa build mode.')
      ->addOption(
        'mode',
        null,
        InputOption::VALUE_OPTIONAL,
        'The new build mode (install or update).'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $mode = $this->validateBuildMode($input->getOption('mode'));

    $recap_params = [
      ['Build mode', $mode],
    ];

    $this->getIo()->newLine(1);
    $this->getIo()->commentBlock('Settings recap');
    $this->getIo()->table(['Parameter', 'Value'], $recap_params);

    // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmOperation
    if (!$this->confirmOperation()) {
      return 1;
    }

    $this->generator->generate([
      'mode' => $mode,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {

    try {
      $mode = $input->getOption('mode') ? $this->validateBuildMode($input->getOption('mode')) : null;
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());

      return 1;
    }

    if (!$mode) {
      $mode = $this->getIo()->choice(
        'Which build mode do you want to use?',
        ['install', 'update'],
        'install'
      );
      $input->setOption('mode', $mode);
    }

  }

  /**
   * Validates a build mode.
   *
   * @param string $mode
   *   The build mode.
   * @return string
   *   The build mode.
   * @throws \InvalidArgumentException
   */
  protected function validateBuildMode($mode) {
    $mode = strtolower($mode);
    if (in_array($mode, ['isntall', 'update'])) {
      return $mode;
    }
    else {
      throw new \InvalidArgumentException(
        sprintf(
          'Build mode "%s" is invalid. Allowed values: install, update',
          $mode
        )
      );
    }
  }

}
