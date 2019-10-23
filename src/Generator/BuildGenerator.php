<?php

namespace Drupal\Console\Combawa\Generator;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class BuildGenerator extends ProjectGenerator {

  /**
   * Generates all the stuff.
   *
   * Method called by the combawa:generate-build command.
   *
   * @param array $parameters
   */
  public function generate(array $parameters) {
    $parameters['generate_build'] = TRUE;
    $this->core_version = $parameters['core'];
    $this->generateBuild($parameters);
  }

}
