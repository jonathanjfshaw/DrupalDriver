<?php

namespace Drupal\Driver\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Provides a plugin manager for the Driver with Drupal 8.
 */
class DriverPluginManagerDrupal8 extends DefaultPluginManager implements PluginManagerInterface {

  use DriverPluginManagerTrait;

  /**
   * The Drupal version being driven.
   *
   * @var int
   */
  protected $version;

  /**
   * Constructor for DriverPluginManagerBase objects.
   *
   * @param string $driverPluginType
   *   The name of the plugin type to manage.
   * @param int $version
   *   Drupal major version number.
   * @param string $projectPluginRoot
   *   The directory to search for additional project-specific driver plugins.
   */
  public function __construct(
        $driverPluginType,
        $version,
        $projectPluginRoot = NULL
    ) {

    $this->version = $version;

    // Add the driver to the namespaces searched for plugins.
    $reflection = new \ReflectionClass($this);
    $driverPath = dirname(dirname($reflection->getFileName()));
    $namespaces = \Drupal::service('container.namespaces')->getArrayCopy();
    $supplementedNamespaces = new \ArrayObject();
    foreach ($namespaces as $name => $class) {
      $supplementedNamespaces[$name] = $class;
    }
    $supplementedNamespaces['Drupal\Driver'] = $driverPath;

    if (!is_null($projectPluginRoot)) {
      // Need some way to load project-specific plugins.
      // $supplementedNamespaces['Drupal\Driver'] = $projectPluginRoot;.
    }

    parent::__construct(
        'Plugin/' . $driverPluginType,
        $supplementedNamespaces,
        \Drupal::service('module_handler'),
        'Drupal\Driver\Plugin\\' . $driverPluginType . 'PluginInterface',
        'Drupal\Driver\Annotation\\' . $driverPluginType
    );

    $cache_backend = $cache_backend = \Drupal::service('cache.discovery');
    if (!is_null($cache_backend)) {
      $this->setCacheBackend($cache_backend, $driverPluginType . '_plugins');
    }
  }

}
