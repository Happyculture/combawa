<?php

namespace Drupal\Console\Combawa\Generator;

use Drupal\Console\Core\Generator\Generator;

class InitializeBuildScriptsGenerator extends Generator {

  const TPL_DIR = __DIR__ . '/../../templates';

  /**
   * Generates the build scripts.
   *
   * Method called by the combawa:generate-build command.
   *
   * @param array $parameters
   */
  public function generate(array $parameters) {
    $scripts_folder = '../scripts/combawa';

    // Alter composer.json file.
    $prevDir = getcwd();
    chdir($this->drupalFinder->getComposerRoot());

    // Build mode.
    $buildParameters['build_mode'] = $parameters['build_mode'];
    exec('/usr/bin/env composer config extra.combawa.build_mode ' . $parameters['build_mode']);

    // Machine name.
    if (!empty($parameters['machine_name'])) {
      $buildParameters['machine_name'] = $parameters['machine_name'];
      exec('/usr/bin/env composer config extra.combawa.machine_name ' . $parameters['machine_name']);
    }

    // Update the lock file.
    exec('/usr/bin/env composer update --lock');
    chdir($prevDir);

    // Then finish the templates generation.
    $this->fileQueue->addFile('../composer.json');
    $this->countCodeLines->addCountCodeLines(5);

    // Create scripts files.
    $dir = opendir(static::TPL_DIR . '/combawa-build/');
    while ($file = readdir($dir)) {
      if ($file[0] === '.') {
        continue;
      }

      $destination_file = substr($file, 0, -1 * strlen('.twig'));

      $this->renderFile(
        'combawa-build/' . $file,
        $scripts_folder . '/' . $destination_file,
        $buildParameters
      );
    }
  }

}
