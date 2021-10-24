<?php

namespace Drupal\Console\Combawa\Generator;

use Drupal\Console\Core\Generator\Generator;

class ChangeBuildModeGenerator extends Generator {

  /**
   * Change the build mode.
   *
   * Method called by the combawa:change-build-mode command.
   *
   * @param array $parameters
   */
  public function generate(array $parameters) {
    // Alter composer.json file.
    $prevDir = getcwd();
    chdir($this->drupalFinder->getComposerRoot());
    exec('/usr/bin/env composer config extra.combawa.build_mode ' . $parameters['mode']);
    exec('/usr/bin/env composer update --lock');
    chdir($prevDir);
    $this->fileQueue->addFile('../composer.json');
    $this->countCodeLines->addCountCodeLines(1);
  }

}
