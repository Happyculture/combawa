<?php

namespace Drupal\Console\Combawa\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Core\Utils\TwigRenderer;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class ProjectGenerator extends Generator {

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
    $this->generateProfile($parameters);
    $this->generateAdminTheme($parameters);
    $this->generateDefaultTheme($parameters);
    $this->generateConfig($parameters);
    $this->generateBuild($parameters);
  }

  /**
   * Generates an installation profile.
   *
   * @param $parameters
   */
  protected function generateProfile($parameters) {
    $profiles_dir = $parameters['profiles_dir'];
    $machine_name = $parameters['machine_name'];

    $this->checkDir(($profiles_dir == '/' ? '' : $profiles_dir) . '/' . $machine_name);

    $profilePath = ($profiles_dir == '/' ? '' : $profiles_dir) . '/' . $machine_name . '/' . $machine_name;
    $profileParameters = [
      'profile' => $parameters['name'],
      'machine_name' => $machine_name,
      'themes' => [ $machine_name . '_theme', $machine_name . '_admin_theme' ],
    ];

    $this->renderFile(
      'combawa-profile/info.yml.twig',
      $profilePath . '.info.yml',
      $profileParameters
    );

    $this->renderFile(
      'combawa-profile/profile.twig',
      $profilePath . '.profile',
      $profileParameters
    );

    $this->renderFile(
      'combawa-profile/install.twig',
      $profilePath . '.install',
      $profileParameters
    );

    // TODO This should be in the default theme but it is not possible due to a
    // Core bug. Consider moving it when https://www.drupal.org/node/2904550
    // is commited.
    $this->renderFile(
      'combawa-profile/breakpoints.yml.twig',
      $profilePath . '.breakpoints.yml',
      $profileParameters
    );

    $this->renderFile(
      'combawa-profile/src/Helpers/StaticBlockBase.php.twig',
      dirname($profilePath) . '/src/Helpers/StaticBlockBase.php',
      $profileParameters
    );
  }

  /**
   * Generates an administration theme based on Adminimal.
   *
   * @param $parameters
   */
  protected function generateAdminTheme($parameters) {
    $themes_dir = $parameters['themes_dir'];
    $machine_name = $parameters['machine_name'];

    $this->checkDir(($themes_dir == '/' ? '' : $themes_dir) . '/' . $machine_name . '_admin_theme');

    $adminThemePath = ($themes_dir == '/' ? '' : $themes_dir) . '/' . $machine_name . '_admin_theme' . '/' . $machine_name . '_admin_theme';
    $adminThemeParameters = [
      'profile' => $parameters['name'],
      'theme' => $parameters['name'] . ' Admin',
      'machine_name' => $machine_name . '_admin_theme',
    ];

    // Base files.
    $this->renderFile(
      'combawa-admin-theme/info.yml.twig',
      $adminThemePath . '.info.yml',
      $adminThemeParameters
    );

    $this->renderFile(
      'combawa-admin-theme/theme.twig',
      $adminThemePath . '.theme',
      $adminThemeParameters
    );

    $this->renderFile(
      'combawa-admin-theme/libraries.yml.twig',
      $adminThemePath . '.libraries.yml',
      $adminThemeParameters
    );

    $this->renderFile(
      'combawa-admin-theme/base.css.twig',
      dirname($adminThemePath) . '/css/' . $machine_name . '.css',
      $adminThemeParameters
    );

    // Blocks configuration.
    $config_folder = $parameters['config_folder'];
    $dir = opendir(self::TPL_DIR . '/combawa-admin-theme/config/blocks');
    while ($file = readdir($dir)) {
      if ($file[0] === '.') {
        continue;
      }

      $block_id = substr($file, 0, -1 * strlen('.yml.twig'));
      $this->renderFile(
        'combawa-admin-theme/config/blocks/' . $file,
        $config_folder . '/block.block.' . $machine_name . '_admin_theme_' . $block_id . '.yml',
        $adminThemeParameters
      );
    }
  }

  /**
   * Generates a theme based on Classy.
   *
   * @param $parameters
   */
  protected function generateDefaultTheme($parameters) {
    $themes_dir = $parameters['themes_dir'];
    $machine_name = $parameters['machine_name'];

    $this->checkDir(($themes_dir == '/' ? '' : $themes_dir) . '/' . $machine_name . '_theme');

    $defaultThemePath = ($themes_dir == '/' ? '' : $themes_dir) . '/' . $machine_name . '_theme' . '/' . $machine_name . '_theme';
    $defaultThemeParameters = [
      'profile' => $parameters['name'],
      'theme' => $parameters['name'],
      'machine_name' => $machine_name . '_theme',
    ];

    $this->renderFile(
      'combawa-theme/gitignore.twig',
      dirname($defaultThemePath) . '/.gitignore',
      $defaultThemeParameters
    );

    $this->renderFile(
      'combawa-theme/gulpfile.js.twig',
      dirname($defaultThemePath) . '/gulpfile.js',
      $defaultThemeParameters
    );

    $this->renderFile(
      'combawa-theme/info.yml.twig',
      $defaultThemePath . '.info.yml',
      $defaultThemeParameters
    );

    $this->renderFile(
      'combawa-theme/libraries.yml.twig',
      $defaultThemePath . '.libraries.yml',
      $defaultThemeParameters
    );

    $this->renderFile(
      'combawa-theme/theme.twig',
      $defaultThemePath . '.theme',
      $defaultThemeParameters
    );

    $this->renderFile(
      'combawa-theme/package.json.twig',
      dirname($defaultThemePath) . '/package.json',
      $defaultThemeParameters
    );

    $this->renderFile(
      'combawa-theme/readme.twig',
      dirname($defaultThemePath) . '/README.md',
      $defaultThemeParameters
    );

    // Assets.
    $this->renderFile(
      'combawa-theme/global.js.twig',
      dirname($defaultThemePath) . '/assets-src/js/global.js',
      $defaultThemeParameters
    );

    // Copy the entire sass directory as we don't need any variable replacement.
    $this->getFs()->mirror(self::TPL_DIR . '/combawa-theme/sass', dirname($defaultThemePath) . '/assets-src/sass');
    $this->trackGeneratedDirectory(dirname($defaultThemePath) . '/assets-src/sass');

    // Copy the entire templates directory as we don't need any variable
    // replacement.
    $this->getFs()->mirror(self::TPL_DIR . '/combawa-theme/templates', dirname($defaultThemePath) . '/templates');
    $this->trackGeneratedDirectory(dirname($defaultThemePath) . '/templates');

    // Gitkeeps.
    $this->renderFile('combawa-theme/gitkeep.twig', dirname($defaultThemePath) . '/assets-src/fonts/.gitkeep');
    $this->renderFile('combawa-theme/gitkeep.twig', dirname($defaultThemePath) . '/assets-src/images/.gitkeep');

    $this->renderFile('combawa-theme/gitkeep.twig', dirname($defaultThemePath) . '/dist/css/.gitkeep');
    $this->renderFile('combawa-theme/gitkeep.twig', dirname($defaultThemePath) . '/dist/fonts/.gitkeep');
    $this->renderFile('combawa-theme/gitkeep.twig', dirname($defaultThemePath) . '/dist/images/.gitkeep');
    $this->renderFile('combawa-theme/gitkeep.twig', dirname($defaultThemePath) . '/dist/js/.gitkeep');

    // Safety.
    $this->renderFile('combawa-theme/htaccess.deny.twig', dirname($defaultThemePath) . '/assets-src/.htaccess');

    // Blocks configuration.
    $config_folder = $parameters['config_folder'];
    $dir = opendir(self::TPL_DIR . '/combawa-theme/config/blocks');
    while ($file = readdir($dir)) {
      if ($file[0] === '.') {
        continue;
      }

      $block_id = substr($file, 0, -1 * strlen('.yml.twig'));
      $this->renderFile(
        'combawa-theme/config/blocks/' . $file,
        $config_folder . '/block.block.' . $machine_name . '_theme_' . $block_id . '.yml',
        $defaultThemeParameters
      );
    }
  }

  /**
   * Generates the configuration to enable the profile and themes by default.
   *
   * @param $parameters
   */
  protected function generateConfig($parameters) {
    if (empty($parameters['generate_config'])) {
      return;
    }
    $machine_name = $parameters['machine_name'];
    $config_folder = $parameters['config_folder'];

    // Enable profile and themes in the core.extension.yml file.
    $filename = $config_folder . '/core.extension.yml';
    $config = $this->readConfig($filename);
    $current_profile = $config['profile'];

    $config['module'][$machine_name] = 1000;
    unset($config['module'][$current_profile]);
    asort($config['module']);

    $config['theme'][$machine_name . '_theme'] = 0;
    $config['theme'][$machine_name . '_admin_theme'] = 0;
    unset($config['theme']['bartik']);

    $config['profile'] = $machine_name;

    $this->writeConfig($filename, $config);

    // Set themes in the system.theme.yml file.
    $filename = $config_folder . '/system.theme.yml';
    $config = $this->readConfig($filename);

    $config['admin'] = $machine_name . '_admin_theme';
    $config['default'] = $machine_name . '_theme';

    $this->writeConfig($filename, $config);
  }

  /**
   * Generates the build scripts.
   *
   * @param $parameters
   */
  protected function generateBuild($parameters) {
    if (empty($parameters['generate_build'])) {
      return;
    }
    $scripts_folder = '../scripts';

    $buildParameters = [
      'machine_name' => $parameters['machine_name'],
      'production_url' => $parameters['url'],
    ];

    $dir = opendir(self::TPL_DIR . '/combawa-build');
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

  /**
   * Extracts a Yaml config file content.
   *
   * @param $filename
   * @return array
   */
  protected function readConfig($filename) {
    return Yaml::parse(file_get_contents($filename));
  }

  /**
   * Encodes an array of data and write it in a Yaml file.
   *
   * @param $filename
   * @param $data
   */
  protected function writeConfig($filename, $data) {
    $current_lines = count(file($filename));
    file_put_contents($filename, Yaml::dump($data));
    $this->trackGeneratedFile($filename, $current_lines);
  }

  /**
   * Track files generated without using a template.
   *
   * @param string $filename
   * @param int $current_lines
   */
  protected function trackGeneratedFile($filename, $current_lines = 0) {
    $this->fileQueue->addFile($filename);
    $this->countCodeLines->addCountCodeLines(count(file($filename)) - $current_lines);
  }

  /**
   * Track directories generated without using templates.
   *
   * @param string $dirname
   */
  protected function trackGeneratedDirectory($dirname) {
    $iterator = new \RecursiveDirectoryIterator($dirname);
    foreach (new \RecursiveIteratorIterator($iterator) as $file) {
      $this->trackGeneratedFile($file->getPathname());
    }
  }

  /**
   * Checks if a directory can be created or is writable.
   *
   * @param $dir
   * @throws \RuntimeException
   */
  protected function checkDir($dir) {
    if (file_exists($dir)) {
      if (!is_dir($dir)) {
        throw new \RuntimeException(
          sprintf(
            'Unable to generate the profile as the target directory "%s" exists but is a file.',
            realpath($dir)
          )
        );
      }
      $files = scandir($dir);
      if ($files != ['.', '..']) {
        throw new \RuntimeException(
          sprintf(
            'Unable to generate the profile as the target directory "%s" is not empty.',
            realpath($dir)
          )
        );
      }
      if (!is_writable($dir)) {
        throw new \RuntimeException(
          sprintf(
            'Unable to generate the profile as the target directory "%s" is not writable.',
            realpath($dir)
          )
        );
      }
    }
  }

}
