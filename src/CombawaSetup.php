<?php

namespace happyculture;

use Composer\Script\Event;
use Composer\Util\ProcessExecutor;

class CombawaSetup {
  // @TODO: Document the next steps for the user. Run the db setup script.
  public static function setupScript(Event $event) {
    $io = $event->getIO();
    $io->warning('Lancer la commande vendor/bin/combawa.sh pour initialiser le projet.');
  }

}

