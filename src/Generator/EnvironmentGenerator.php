<?php

namespace Drupal\Console\Combawa\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Core\Utils\DrupalFinder;
use Drupal\Console\Core\Utils\TwigRenderer;
use Symfony\Component\Filesystem\Filesystem;

class EnvironmentGenerator extends Generator {

  const TPL_DIR = __DIR__ . '/../../templates';

  protected $env_build_mode_template = NULL;

  /**
   * @var \Symfony\Component\Filesystem\Filesystem;
   */
  protected $fs;

  /**
   * @var DrupalFinder
   */
  protected $drupalFinder;

  protected $combawaRoot;
  protected $combawaWebroot;

  public function __construct(DrupalFinder $drupalFinder) {
    $this->drupalFinder = $drupalFinder;
    $this->combawaRoot = $drupalFinder->getComposerRoot();
    $this->combawaWebroot = $drupalFinder->getDrupalRoot();
  }

  /**
   * Helper to determine the path to the env template file.
   */
  public function computeBuildModeTemplate() {
    $build_mode = exec('/usr/bin/env composer config extra.combawa.build_mode  -d ' . $this->drupalFinder->getComposerRoot());
    if (!in_array($build_mode, ['install', 'update'])) {
      throw new \InvalidArgumentException('The build mode can not be determined or is not install or update.');
    }
    $this->env_build_mode_template = 'combawa-env/env-' . $build_mode . '.twig';
    return $build_mode;
  }

  /**
   * {@inheritdoc}
   */
  public function setRenderer(TwigRenderer $renderer)
  {
    $this->renderer = $renderer;
    $this->renderer->addSkeletonDir(static::TPL_DIR);
  }

  /**
   * Gets an helper to manipulate files.
   *
   * @return \Symfony\Component\Filesystem\Filesystem
   */
  public function getFs() {
    if (NULL === $this->fs) {
      $this->fs = new Filesystem();
    }
    return $this->fs;
  }

  public function getCombawaRoot() {
    return $this->combawaRoot;
  }
  public function getCombawaWeboot() {
    return $this->combawaWebroot;
  }

  /**
   * Generates all the stuff.
   *
   * Method called by the combawa:generate-project command.
   *
   * @param array $parameters
   */
  public function generate(array $parameters) {
    $drupalRoot = $parameters['app_root'];

    $this->renderFile(
      $this->env_build_mode_template,
      '../.env',
      $parameters
    );

    if (!$this->getFs()->exists($drupalRoot . '/sites/default/settings.local.php') && $parameters['write_db_settings'] == 1) {
      $this->renderFile(
        'combawa-env/settings.local.php.twig',
        'sites/default/settings.local.php',
        $parameters
      );
    }
    else {
      $content = $this->renderer->render(
        'combawa-env/settings.local.php.twig',
        $parameters
      );
      $this->getIo()->writeln('File sites/default/settings.local.php already exist. Skipping generation.');
      $this->getIo()->writeln('You can use the following code into your settings.local.php to use variables defined in the .env file.');
      $this->getIo()->comment($content);
    }

    // Uncomment settings.local.php inclusion in the settings.php file.
    $filename = 'sites/default/settings.php';
    if ($this->getFs()->exists($drupalRoot . '/' . $filename)) {
      // Only uncomment the include line as we are sure to have a
      // settings.local.php file.
      $data = file_get_contents($drupalRoot . '/' . $filename);
      $data = str_replace("#   include \$app_root . '/' . \$site_path . '/settings.local.php';", "  include \$app_root . '/' . \$site_path . '/settings.local.php';", $data);
      file_put_contents($drupalRoot . '/' . $filename, $data);

      $this->getFs()->chmod($drupalRoot . '/sites/default/settings.php', 0666);
      $this->fileQueue->addFile($filename);
      $this->countCodeLines->addCountCodeLines(1);
    }
  }

}
