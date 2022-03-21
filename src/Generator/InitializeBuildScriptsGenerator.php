<?php

namespace Drupal\Console\Combawa\Generator;

use Drupal\Console\Combawa\Command\InitializeBuildScriptsCommand;
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
    // Alter composer.json file.
    $prevDir = getcwd();
    chdir($this->drupalFinder->getComposerRoot());

    // Build mode.
    $buildParameters['build_mode'] = $parameters['build_mode'];
    exec('/usr/bin/env composer config extra.combawa.build_mode ' . $parameters['build_mode']);

    if (empty(exec('/usr/bin/env composer config extra.combawa.profile_name'))) {
      exec('/usr/bin/env composer config extra.combawa.profile_name minimal');
    }

    // Update the lock file.
    exec('/usr/bin/env composer update --lock');
    chdir($prevDir);

    $this->fileQueue->addFile('../composer.json');
    $this->countCodeLines->addCountCodeLines(5);

    // Create scripts files if needed or forced.
    if ($parameters['overwrite_scripts'] || !is_file(InitializeBuildScriptsCommand::SCRIPTS_FOLDER . '/' . $parameters['build_mode'] .'.sh')) {
      $dir = opendir(static::TPL_DIR . '/combawa-build/');
      while ($file = readdir($dir)) {
        if ($file[0] === '.') {
          continue;
        }

        $destination_file = substr($file, 0, -1 * strlen('.twig'));

        $this->renderFile(
          'combawa-build/' . $file,
          InitializeBuildScriptsCommand::SCRIPTS_FOLDER . '/' . $destination_file,
          $buildParameters
        );
      }
    }
  }

}
