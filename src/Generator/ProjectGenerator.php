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
    $profiles_dir = $parameters['profiles-dir'];
    $themes_dir = $parameters['themes-dir'];
    $machine_name = $parameters['machine_name'];

    // Check directories.
    $this->checkDir(($profiles_dir == '/' ? '' : $profiles_dir) . '/' . $machine_name);
    $this->checkDir(($themes_dir == '/' ? '' : $themes_dir) . '/' . $machine_name . '_theme');
    $this->checkDir(($themes_dir == '/' ? '' : $themes_dir) . '/' . $machine_name . '_admin_theme');

    // Generate profile.
    $profilePath = ($profiles_dir == '/' ? '' : $profiles_dir) . '/' . $machine_name . '_theme' . '/' . $machine_name . '_theme';
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

    // Generate default theme.
    $defaultThemePath = ($profiles_dir == '/' ? '' : $profiles_dir) . '/' . $machine_name . '_theme' . '/' . $machine_name . '_theme';
    $defaultThemeParameters = [
      'profile' => $parameters['name'],
      'theme' => $parameters['name'],
      'machine_name' => $machine_name . '_theme',
    ];
    // TODO continue

    // Generate admin theme.
    $adminThemePath = ($profiles_dir == '/' ? '' : $profiles_dir) . '/' . $machine_name . '_admin_theme' . '/' . $machine_name . '_admin_theme';
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
