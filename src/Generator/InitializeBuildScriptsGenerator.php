<?php

namespace Drupal\Console\Combawa\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Core\Utils\DrupalFinder;

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

    $buildParameters = [
      'machine_name' => $parameters['machine_name'],
    ];

    // Alter composer.json file.
    $prevDir = getcwd();
    chdir($this->drupalFinder->getComposerRoot());
    exec('/usr/bin/env composer config name ' . $parameters['machine_name'] . '/' . $parameters['machine_name']);
    exec('/usr/bin/env composer config extra.combawa.machine_name ' . $parameters['machine_name']);
    exec('/usr/bin/env composer config extra.combawa.build_mode install');
    chdir($prevDir);
    $this->fileQueue->addFile('../composer.json');
    $this->countCodeLines->addCountCodeLines(5);

    // Create scripts files.
    $dir = opendir(self::TPL_DIR . '/combawa-build/');
    while ($file = readdir($dir)) {
      if ($file[0] === '.') {
        continue;
      }

      $destination_file = substr($file, 0, -1 * strlen('.twig'));

      $this->renderFile(
        'combawa-build/' . $parameters['core'] . '/' . $file,
        $scripts_folder . '/' . $destination_file,
        $buildParameters
      );
    }
  }

}
