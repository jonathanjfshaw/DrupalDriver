<?php

namespace Drupal\Tests\Driver\Kernel\Drupal8\Entity;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\Driver\Kernel\DriverKernelTestTrait;
use Drupal\Driver\Plugin\DriverFieldPluginMatcher;
use Drupal\Driver\Plugin\DriverEntityPluginMatcher;

/**
 * Base class for all Driver entity kernel tests.
 */
class DriverEntityKernelTestBase extends EntityKernelTestBase {

  use DriverKernelTestTrait;

  /**
   * Machine name of the entity type being tested.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Absolute path to test project plugins.
   *
   * @var string
   */
  protected $projectPluginRoot;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setUpDriver();
    if (empty($this->config)) {
      $this->storage = \Drupal::entityTypeManager()
        ->getStorage($this->entityType);
    }

    $namespaces = \Drupal::service('container.namespaces');
    $cache_backend = \Drupal::service('cache.discovery');
    $module_handler = \Drupal::service('module_handler');

    $reflection = new \ReflectionClass($this);
    // Specify a folder where plugins for the current project can be found.
    // @todo This should be the same folder where Behat contexts live.
    $this->projectPluginRoot = "/path/to/project/plugins";
    $this->fieldPluginMatcher = new DriverFieldPluginMatcher($namespaces, $cache_backend, $module_handler, 8, $this->projectPluginRoot);
    $this->entityPluginMatcher = new DriverEntityPluginMatcher($namespaces, $cache_backend, $module_handler, 8, $this->projectPluginRoot);
  }

}
