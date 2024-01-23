<?php

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Dotenv\Exception\PathException;

$dotenv = new Dotenv();
// Backward compatibility with scripts using getenv() like drush.
$dotenv->usePutenv(TRUE);
try {
  // This file is located in our dependency (vendor/happyculture/combawa) so we have to climb up the arborescence
  // to load the environment files expected to be at the root level or our project repository.
  // Load .env, .env.local, and .env.$APP_ENV.local or .env.$APP_ENV if defined.
  $dotenv->loadEnv(__DIR__ . '/../../../.env');
}
catch (PathException $exception) {
  // Do nothing. Production environments rarely use .env files.
}
