<?php

namespace Combawa\Drush\Commands;

use Consolidation\SiteAlias\SiteAliasManagerAwareInterface;
use Consolidation\SiteAlias\SiteAliasManagerAwareTrait;
use Drupal\Component\Serialization\Json;
use DrupalCodeGenerator\Asset\AssetCollection;
use Drush\Attributes as CLI;
use Drush\Drush;

/**
 * Combawa initialize build scripts drush command.
 */
class InitializeBuildScriptsDrushCommands extends DrushCommandsGeneratorBase implements SiteAliasManagerAwareInterface {

  use SiteAliasManagerAwareTrait;

  const TEMPLATES_PATH = __DIR__ . '/../../../templates/combawa-build';

  /**
   * Generate environment command.
   */
  #[CLI\Command(name: 'combawa:initialize-build-scripts', aliases: ['ibs'])]
  #[CLI\Option(
    name: 'build-mode',
    description: 'The build mode to use. (Accepted: <info>install</info>, <info>update</info>)',
    suggestedValues: ['install', 'update'])]
  #[CLI\Option(
    name: 'overwrite-scripts',
    description: 'Overwrite existing scripts files. (Accepted: <info>boolean</info>)',
    suggestedValues: [TRUE, FALSE])]
  #[CLI\Option(
    name: 'generate-env',
    description: 'Generate environment file. (Accepted: <info>boolean</info>)',
    suggestedValues: [TRUE, FALSE])]
  #[CLI\Option(
    name: 'dry-run',
    description: 'Output the generated code but do not save it to file system.')]
  #[CLI\Usage(name: 'drush combawa:initialize-build-scripts', description: 'Run with wizard')]
  public function generateEnvironment(array $options = [
    'build-mode' => self::REQ,
    'overwrite-scripts' => self::OPT,
    'generate-env' => self::OPT,
    'dry-run' => FALSE,
  ]): int {
    return $this->generate($options);
  }

  /**
   * {@inheritdoc}
   */
  protected function extractOptions(array $options): array {
    $vars = [
      'build_mode' => $options['build-mode'],
      'overwrite_scripts' => $options['overwrite-scripts'],
      'generate_env' => $options['generate-env'],
    ];
    return array_filter($vars, fn ($value) => !\is_null($value));
  }

  /**
   * {@inheritDoc}
   */
  protected function interview(array &$vars): void {
    if (!isset($vars['build_mode'])) {
      $composerData = Json::decode(file_get_contents($this->drupalFinder()->getComposerRoot() . '/composer.json'));
      $defaultValue = $composerData['extra']['combawa']['build_mode'] ?? 'install';
      $choices = ['install', 'update'];
      $choice = $this->io()->choice(
        'What is the build mode to use?',
        $choices,
        $defaultValue,
      );
      $vars['build_mode'] = $choices[$choice];
    }

    $scriptsDir = $this->drupalFinder()->getComposerRoot() . '/scripts/combawa';
    if (
      $this->fileSystem->exists($scriptsDir . '/' . $vars['build_mode'] . '.sh') &&
      !isset($vars['overwrite_scripts'])
    ) {
      $defaultValue = FALSE;
      $vars['overwrite_scripts'] = $this->io()->confirm(
        'Do you want to overwrite your existing scripts located in the scripts/combawa directory?',
        $defaultValue,
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function validateVars(array $vars): void {
    if (isset($vars['build_mode'])) {
      static::validateBuildMode($vars['build_mode']);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function outputVarsSummary(array $vars): void {
    $summary = [
      'Build mode' => $vars['build_mode'],
    ];
    $scriptsDir = $this->drupalFinder()->getComposerRoot() . '/scripts/combawa';
    if ($this->fileSystem->exists($scriptsDir . '/' . $vars['build_mode'] . '.sh')) {
      $summary['Overwrite scripts files'] = $vars['overwrite_scripts'] ? 'Yes' : 'No';
    }

    $this->io()->newLine(1);
    $this->io()->title('Settings summary');
    $output = array_chunk($summary, 1, TRUE);
    $this->io()->definitionList(...$output);
  }

  /**
   * {@inheritdoc}
   */
  protected function preGenerate(array &$vars): void {
    $prevDir = getcwd();
    chdir($this->drupalFinder()->getComposerRoot());

    // Update the build mode.
    $process = $this->processManager()
      ->shell('/usr/bin/env composer config extra.combawa.build_mode ' . $vars['build_mode']);
    $process->mustRun();

    // Update the lock file.
    $process = $this->processManager()
      ->shell('/usr/bin/env composer update --lock');
    $process->mustRun();

    chdir($prevDir);
  }

  /**
   * {@inheritdoc}
   */
  protected function postGenerate(array $vars): void {
    if (!isset($vars['generate_env'])) {
      $defaultValue = FALSE;
      $vars['generate_env'] = $this->io()->confirm(
        'Next, we will need you to generate the environment file (.env). Do you want to do it right after saving the previous settings?',
        $defaultValue,
      );
    }
    if (empty($vars['generate_env'])) {
      return;
    }

    // Run generate-environment drush command on the same build_mode used in
    // this command.
    $process = $this->processManager()->drush(
      $this->siteAliasManager()->getSelf(),
      'combawa:generate-environment',
      [],
      Drush::redispatchOptions() + ['build-mode' => $vars['build_mode']],
    );
    // Enable TTY unless this command was already non interactive.
    $process->setTty($this->input()->isInteractive());
    $process->mustRun();
  }

  /**
   * {@inheritDoc}
   */
  protected function collectAssets(AssetCollection $assets, array $vars): void {
    $scriptsDir = $this->drupalFinder()->getComposerRoot() . '/scripts/combawa';
    if (
      !empty($vars['overwrite_scripts']) ||
      !$this->fileSystem->exists($scriptsDir . '/' . $vars['build_mode'] . '.sh')
    ) {
      $dir = opendir(static::TEMPLATES_PATH);
      while ($file = readdir($dir)) {
        if ($file[0] === '.') {
          continue;
        }

        $destination_file = substr($file, 0, -1 * strlen('.twig'));

        $assets->addFile(
          '../scripts/combawa/' . $destination_file,
          $file,
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function outputGeneratedAssetsSummary(AssetCollection $assets): void {
    $assets->addFile('../composer.json');
    $assets->addFile('../composer.lock');
    parent::outputGeneratedAssetsSummary($assets);
  }

}
