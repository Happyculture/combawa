<?php

namespace Drupal\Console\Combawa\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Core\Utils\TwigRenderer;
use Symfony\Component\Filesystem\Filesystem;

class EnvironmentGenerator extends Generator {

  const TPL_DIR = __DIR__ . '/../../templates';

  /**
   * @var \Symfony\Component\Filesystem\Filesystem;
   */
  protected $fs;

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
    $this->renderFile(
      'combawa-env/env.twig',
      '../.env',
      $parameters
    );

    $this->renderFile(
      'combawa-env/settings.local.php.twig',
      'sites/default/settings.local.php',
      $parameters
    );

    // Uncomment settings.local.php inclusion in the settings.php file.
    $drupalRoot = $parameters['app_root'];
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
