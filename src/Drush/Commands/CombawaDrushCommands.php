<?php

namespace Combawa\Drush\Commands;

use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

/**
 * Combawa specific drush commands.
 */
class CombawaDrushCommands extends DrushCommands {

  /**
   * Test command.
   */
  #[CLI\Command(name: 'combawa:test', aliases: ['ctest'])]
  #[CLI\Usage(name: 'drush combawa:test', description: 'Test')]
  public function test() {
    $this->io()->success('Ça marche !');
  }

}
