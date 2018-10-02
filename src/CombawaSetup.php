<?php

namespace happyculture;

use Symfony\Component\Console\Application;
use Composer\Script\Event;
use Composer\Util\ProcessExecutor;

class CombawaSetup extends Command {
  // @TODO: Document the next steps for the user. Run the db setup script.
  public static function setupScript(Event $event) {
    $io = $event->getIO();
    $process = new ProcessExecutor($io);
    $pw = $io->askConfirmation('Enter your Password: ', 'yes');
  }

}

