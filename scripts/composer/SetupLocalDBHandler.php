<?php

namespace DrupalProject\composer;

use Composer\Script\Event;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Filesystem\Filesystem;

class SetupLocalDBHandler {

  public static function saveLocalDBInfo(Event $event) {
    $fs = new Filesystem();
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $drupalRoot = $drupalFinder->getDrupalRoot();
    $db_config_filepath = '/tmp/combawa-sql.conf';

    // Prepare the local settings file for installation.
    if (!$fs->exists($drupalRoot . '/sites/default/settings.local.php') and $fs->exists($drupalRoot . '/sites/example.settings.local.php') and $fs->exists($db_config_filepath)) {
      $filename = $drupalRoot . '/sites/default/settings.local.php';

      // Copy the example file.
      $fs->copy($drupalRoot . '/sites/example.settings.local.php', $filename);

      // Uncomment all lines.
      $data = file_get_contents($filename);
      str_replace('# ', '', $data);

      // Check if we have the DB config file, if so, add its content to the
      // settings local file.
      if ($fs->exists($db_config_filepath)) {
        $db_config_info = parse_ini_file($db_config_filepath);
        $db_connection_info = <<<DB_INFO
\$databases['default']['default'] = array(
  'database' => '{$db_config_info['DB_NAME']}',
  'username' => '{$db_config_info['DB_SERVER_LOGIN']}',
  'password' => '{$db_config_info['DB_SERVER_PWD']}',
  'host' => '{$db_config_info['DB_SERVER_NAME']}',
  'port' => '3306',
  'driver' => 'mysql',
  'prefix' => '',
  'collation' => 'utf8mb4_general_ci',
);
DB_INFO;
        $data .= $db_connection_info;

        $event->getIO()->write("Adding DB connection info to the generated settings.local.php file.");
      }
      file_put_contents($filename, $data);

      $fs->chmod($filename, 0666);
      $event->getIO()->write("Create a sites/default/settings.local.php file with chmod 0666");
    }

    // Uncomment settings.php to include settings.local.php.
    if ($fs->exists($drupalRoot . '/sites/default/settings.php')) {
      $filename = $drupalRoot . '/sites/default/settings.php';

      // Only uncomment the include line as we are sure to have a
      // settings.local.php file.
      $data = file_get_contents($filename);
      $data = str_replace("#   include \$app_root . '/' . \$site_path . '/settings.local.php';", "  include \$app_root . '/' . \$site_path . '/settings.local.php';", $data);
      file_put_contents($filename, $data);

      $fs->chmod($drupalRoot . '/sites/default/settings.php', 0666);
      $event->getIO()->write("Uncommented settings.local.php include in sites/default/settings.php");
    }
  }

}

