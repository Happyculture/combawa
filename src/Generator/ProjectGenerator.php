<?php

namespace Drupal\Console\Combawa\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Core\Utils\TwigRenderer;
use Symfony\Component\Filesystem\Filesystem;

class ProjectGenerator extends Generator {

  const TPL_DIR = __DIR__ . '/../../templates';

  /**
   * {@inheritdoc}
   */
  public function setRenderer(TwigRenderer $renderer)
  {
    $this->renderer = $renderer;
    $this->renderer->addSkeletonDir(self::TPL_DIR);
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
  }

  /**
   * Generates an installation profile.
   *
   * @param $parameters
   */
  protected function generateProfile($parameters) {
    $profiles_dir = $parameters['profiles-dir'];
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
  }

  /**
   * Generates an administration theme based on Adminimal.
   *
   * @param $parameters
   */
  protected function generateAdminTheme($parameters) {
    $themes_dir = $parameters['themes-dir'];
    $machine_name = $parameters['machine_name'];

    $this->checkDir(($themes_dir == '/' ? '' : $themes_dir) . '/' . $machine_name . '_admin_theme');

    $adminThemePath = ($themes_dir == '/' ? '' : $themes_dir) . '/' . $machine_name . '_admin_theme' . '/' . $machine_name . '_admin_theme';
    $adminThemeParameters = [
      'profile' => $parameters['name'],
      'theme' => $parameters['name'] . ' Admin',
      'machine_name' => $machine_name . '_admin_theme',
    ];

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
  }

  /**
   * Generates a theme based on Classy.
   *
   * @param $parameters
   */
  protected function generateDefaultTheme($parameters) {
    $themes_dir = $parameters['themes-dir'];
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
    (new Filesystem())->mirror(self::TPL_DIR . '/combawa-theme/sass', dirname($defaultThemePath) . '/assets-src/sass');

    // Copy the entire templates directory as we don't need any variable
    // replacement.
    (new Filesystem())->mirror(self::TPL_DIR . '/combawa-theme/templates', dirname($defaultThemePath) . '/templates');

    // Gitkeeps.
    $this->renderFile('combawa-theme/gitkeep.twig', dirname($defaultThemePath) . '/assets-src/fonts/.gitkeep');
    $this->renderFile('combawa-theme/gitkeep.twig', dirname($defaultThemePath) . '/assets-src/images/.gitkeep');

    $this->renderFile('combawa-theme/gitkeep.twig', dirname($defaultThemePath) . '/dist/css/.gitkeep');
    $this->renderFile('combawa-theme/gitkeep.twig', dirname($defaultThemePath) . '/dist/fonts/.gitkeep');
    $this->renderFile('combawa-theme/gitkeep.twig', dirname($defaultThemePath) . '/dist/images/.gitkeep');
    $this->renderFile('combawa-theme/gitkeep.twig', dirname($defaultThemePath) . '/dist/js/.gitkeep');

    // Safety.
    $this->renderFile('combawa-theme/htaccess.deny.twig', dirname($defaultThemePath) . '/assets-src/.htaccess');
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
