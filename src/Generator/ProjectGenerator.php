<?php

namespace Drupal\Console\Combawa\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Core\Utils\TwigRenderer;

class ProjectGenerator extends Generator {

  const TPL_DIR = __DIR__ . '/../../templates';

  /**
   * @param $renderer
   */
  public function setRenderer(TwigRenderer $renderer)
  {
    $this->renderer = $renderer;
    $this->renderer->addSkeletonDir(self::TPL_DIR);
  }

  /**
   * {@inheritdoc}
   */
  public function generate(array $parameters) {
    $this->generateProfile($parameters);
    $this->generateAdminTheme($parameters);
    $this->generateDefaultTheme($parameters);
  }

  /**
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
    // TODO continue
  }

  /**
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
