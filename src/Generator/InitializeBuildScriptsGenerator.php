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
    $scripts_folder = '../scripts';

    $buildParameters = [
      'name' => $parameters['name'],
      'machine_name' => $parameters['machine_name'],
      'production_url' => $parameters['url'],
    ];

    $dir = opendir(self::TPL_DIR . '/combawa-build/' . $parameters['core'] . '');
    while ($file = readdir($dir)) {
      if ($file[0] === '.') {
        continue;
      }

      $destination_file = substr($file, 0, -1 * strlen('.twig'));

      $destination = $scripts_folder . '/' . $destination_file;
      if ($destination_file == 'env.combawa') {
        $destination = '../.combawa';
      }
      $this->renderFile(
        'combawa-build/' . $parameters['core'] . '/' . $file,
        $destination,
        $buildParameters
      );
    }
  }

}
