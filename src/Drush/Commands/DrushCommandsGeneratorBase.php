<?php

namespace Combawa\Drush\Commands;

use DrupalCodeGenerator\Asset\AssetCollection;
use DrupalCodeGenerator\Asset\RenderableInterface;
use DrupalCodeGenerator\Asset\Resolver\ReplaceResolver;
use DrupalCodeGenerator\Helper\Dumper\DryDumper;
use DrupalCodeGenerator\Helper\Dumper\FileSystemDumper;
use DrupalCodeGenerator\Helper\QuestionHelper;
use DrupalCodeGenerator\Helper\Renderer\TwigRenderer;
use DrupalCodeGenerator\InputOutput\IO;
use DrupalCodeGenerator\Twig\TwigEnvironment;
use DrupalCodeGenerator\Utils;
use DrupalFinder\DrupalFinder;
use Drush\Commands\DrushCommands;
use Drush\Log\Logger;
use Psr\Container\ContainerInterface as DrushContainer;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Loader\FilesystemLoader as TemplateLoader;

/**
 * Combawa generator commands helper.
 */
abstract class DrushCommandsGeneratorBase extends DrushCommands {

  const TEMPLATES_PATH = __DIR__ . '/../../../templates';

  /**
   * Command constructor.
   */
  public function __construct(
    protected readonly DrupalFinder $drupalFinder,
    protected readonly Filesystem $fileSystem,
    protected readonly TwigRenderer $renderer,
  ) {
    parent::__construct();
    $this->drupalFinder->locateRoot(__DIR__);
    $this->renderer->setLogger(new Logger($this->output()));
    $this->renderer->registerTemplatePath(static::TEMPLATES_PATH);
  }

  /**
   * Command creator.
   */
  public static function createEarly(DrushContainer $drush_container): static {
    return new static(
      new DrupalFinder(),
      new Filesystem(),
      new TwigRenderer(new TwigEnvironment(new TemplateLoader())),
    );
  }

  /**
   * Run the generation process.
   *
   * @param array $options
   *   The options array given to the command.
   */
  protected function generate(array $options): int {
    // Extract generation data from options.
    $vars = $this->extractOptions($options);

    // Ask questions to complete existing data.
    $this->interview($vars);

    // Ensure all vars are correct.
    $this->validateVars($vars);
    $vars = Utils::processVars($vars);

    // Show collected data.
    $this->outputVarsSummary($vars);

    $proceed = $this->io()->confirm('Are you sure you want to proceed?');
    if (!$proceed) {
      $this->io()->info('Generation aborted.');
      return self::EXIT_SUCCESS;
    }

    $this->preGenerate($vars);

    // Collect and generate assets.
    $assets = new AssetCollection();
    $this->collectAssets($assets, $vars);
    $generatedAssets = $this->generateAssets($assets, $vars);
    $this->outputGeneratedAssetsSummary($generatedAssets);

    $this->postGenerate($vars);

    return self::EXIT_SUCCESS;
  }

  /**
   * Extract generator data from the command options.
   *
   * @param array $options
   *   The array of the command options.
   *
   * @return array
   *   The data used by the generator.
   */
  protected function extractOptions(array $options): array {
    return $options;
  }

  /**
   * Ask needed questions to generate the files.
   *
   * @param array $vars
   *   The preset vars to be amended.
   */
  abstract protected function interview(array &$vars): void;

  /**
   * Validate generator data content.
   *
   * @param array $vars
   *   The generator data.
   */
  protected function validateVars(array $vars): void {}

  /**
   * Outputs the summary of vars used to generate the files.
   *
   * @param array $vars
   *   The vars used to generate the files.
   */
  protected function outputVarsSummary(array $vars): void {
    $this->io()->newLine();
    $this->io()->title('Settings summary');
    $output = array_chunk($vars, 1, TRUE);
    $this->io()->definitionList(...$output);
  }

  /**
   * Act before assets generation.
   *
   * @param array $vars
   *   The generator data.
   */
  protected function preGenerate(array &$vars): void {}

  /**
   * Act after assets generation.
   *
   * @param array $vars
   *   The generator data.
   */
  protected function postGenerate(array $vars): void {}

  /**
   * Collect assets to be generated.
   *
   * @param \DrupalCodeGenerator\Asset\AssetCollection $assets
   *   The asset collection.
   * @param array $vars
   *   The generator data.
   */
  abstract protected function collectAssets(AssetCollection $assets, array $vars): void;

  /**
   * Generate assets.
   *
   * @param \DrupalCodeGenerator\Asset\AssetCollection $assets
   *   The assets to be generated.
   * @param array $vars
   *   The generator data.
   *
   * @return \DrupalCodeGenerator\Asset\AssetCollection
   *   The assets that have been generated.
   */
  protected function generateAssets(AssetCollection $assets, array $vars): AssetCollection {
    $resolver = new ReplaceResolver($this->dcgIo());

    foreach ($assets as $asset) {
      $asset->resolver($resolver);
      $asset->vars(\array_merge($vars, Utils::processVars($asset->getVars())));
      if ($asset instanceof RenderableInterface) {
        $asset->render($this->renderer);
      }
    }

    if ($this->input()->getOption('dry-run')) {
      $dumper = new DryDumper($this->fileSystem);
      $this->io()->newLine();
      $this->io()->title('Files that would have been created or updated:');
    }
    else {
      $dumper = new FileSystemDumper($this->fileSystem);
    }
    $dumper->io($this->dcgIo());
    return $dumper->dump($assets, $this->drupalFinder->getDrupalRoot());
  }

  /**
   * Display files that have been generated.
   *
   * @param \DrupalCodeGenerator\Asset\AssetCollection $assets
   *   The generated asset collection.
   */
  protected function outputGeneratedAssetsSummary(AssetCollection $assets): void {
    if (\count($assets) === 0 || $this->input()->getOption('dry-run')) {
      return;
    }

    $this->io()->newLine();
    $this->io()->title('The following directories and files have been created or updated:');

    $items = [];
    foreach ($assets->getSorted() as $asset) {
      $items[] = $asset->getPath();
    }

    $this->io()->listing($items);
  }

  /**
   * Get IO object for DrupalCodeGenerator services.
   *
   * @return \DrupalCodeGenerator\InputOutput\IO
   *   The IO crafted for our use of DrupalCodeGenerator services.
   */
  protected function dcgIo(): IO {
    if (!isset($this->dcgIo)) {
      $inputDefinition = new InputDefinition([
        new InputOption('replace'),
        new InputOption('full-path'),
        new InputOption('dry-run'),
      ]);
      $input = new ArrayInput([], $inputDefinition);
      $input->setInteractive($this->input()->isInteractive());
      $input->setOption('dry-run', $this->input()->getOption('dry-run'));
      $this->dcgIo = new IO($input, $this->output(), new QuestionHelper());
    }
    return $this->dcgIo;
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
  public static function validateBuildMode($build_mode): string {
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
