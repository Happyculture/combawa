<?php

namespace Drupal\Console\Combawa\Command;

use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Combawa\Generator\BuildGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateBuildCommand extends Command {

  use ConfirmationTrait;

  /**
   * @var BuildGenerator
   */
  protected $generator;

  /**
   * @var StringConverter
   */
  protected $stringConverter;

  /**
   * @var string The document root absolute path.
   */
  protected $appRoot;

  /**
   * ProfileCommand constructor.
   *
   * @param BuildGenerator $generator
   * @param StringConverter  $stringConverter
   * @param string           $app_root
   */
  public function __construct(
    BuildGenerator $generator,
    StringConverter $stringConverter,
    $app_root
  ) {
    $this->generator = $generator;
    $this->stringConverter = $stringConverter;
    $this->appRoot = $app_root;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this
      ->setName('combawa:generate-build')
      ->setAliases(['cgb'])
      ->setDescription('Generate build scripts.')
      ->addOption(
        'core',
        null,
        InputOption::VALUE_REQUIRED,
	'Drupal core version built (Drupal 7, Drupal 8).'
      )
      ->addOption(
        'url',
        null,
        InputOption::VALUE_REQUIRED,
        'The project production URL.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $url = $this->validateUrl($input->getOption('url'));
    $core_version = $this->extractCoreVersion($input->getOption('core'));

    $recap_params = [
      ['Core Version', $core_version],
      ['URL', $url],
    ];

    $this->getIo()->newLine(1);
    $this->getIo()->commentBlock('Settings recap');
    $this->getIo()->table(['Parameter', 'Value'], $recap_params);

    // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmOperation
    if (!$this->confirmOperation()) {
      return 1;
    }

    $this->generator->generate([
      'core' => $core_version,
      'url' => $url,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    $envVars = getenv();

    // Identify the Drupal version built.
    try {
      $core_version = $input->getOption('core') ? $input->getOption('core') : null;
      if (empty($core_version)) {
        $core_version = $this->getIo()->choice(
          'With which version of Drupal will you run this project?',
          ['Drupal 7', 'Drupal 8'],
          'Drupal 8'
        );
        $input->setOption('core', $core_version);
      }
      else if (!in_array($core_version, [7, 8])) {
        throw new \InvalidArgumentException(sprintf('Invalid version "%s" specified (only 7 or 8 are supported at the moment).', $core_version));
      }
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());
      return 1;
    }

    try {
      $url = $input->getOption('url') ? $this->validateUrl($input->getOption('url')) : null;
    } catch (\Exception $error) {
      $this->getIo()->error($error->getMessage());

      return 1;
    }

    if (!$url) {
      $url = $this->getIo()->ask(
        'What is the production URL of the project?',
        array_key_exists('COMBAWA_WEBSITE_URI', $envVars) ? $envVars['COMBAWA_WEBSITE_URI'] : 'https://happyculture.coop',
        function ($url) {
          return $this->validateUrl($url);
        }
      );
      $input->setOption('url', $url);
    }
  }

  /**
   * Validates an url.
   *
   * @param string $url
   *   The url to validate.
   *
   * @return string
   *   The url.
   */
  protected function validateUrl($url) {
    $parts = parse_url($url);
    if ($parts === FALSE) {
      throw new \InvalidArgumentException(
        sprintf(
          '"%s" is a malformed url.',
          $url
        )
      );
    }
    elseif (empty($parts['scheme']) || empty($parts['host'])) {
      throw new \InvalidArgumentException(
        sprintf(
          'Please specify a full URL with scheme and host instead of "%s".',
          $url
        )
      );
    }
    return $url;
  }

  /**
   * @param $core_version
   */
  protected function extractCoreVersion($core_version) {
    $matches = [];
    if (preg_match('`^Drupal ([0-9]+)$`', $core_version, $matches)) {
      $core_version = $matches[1];
    }
    return $core_version;
  }

}
