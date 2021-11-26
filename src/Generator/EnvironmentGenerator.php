<?php

namespace Drupal\Console\Combawa\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Core\Utils\DrupalFinder;
use Drupal\Console\Core\Utils\TwigRenderer;
use Symfony\Component\Filesystem\Filesystem;

class EnvironmentGenerator extends Generator {

  const TPL_DIR = __DIR__ . '/../../templates';

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

  public function getCombawaRoot() {
    return $this->combawaRoot;
  }
  public function getCombawaWeboot() {
    return $this->combawaWebroot;
  }

  /**
   * {@inheritdoc}
   */
  public function setRenderer(TwigRenderer $renderer)
  {
    $this->renderer = $renderer;
    $this->renderer->addSkeletonDir(self::TPL_DIR);
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

  /**
   * Generates all the stuff.
   *
   * Method called by the combawa:generate-project command.
   *
   * @param array $parameters
   */
  public function generate(array $parameters) {
    $drupalRoot = $parameters['webroot'];

    $this->renderFile(
      'combawa-env/env.twig',
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
