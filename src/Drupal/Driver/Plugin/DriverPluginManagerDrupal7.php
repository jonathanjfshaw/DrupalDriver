<?php

namespace Drupal\Driver\Plugin;

use Drupal\Component\Plugin\Discovery\DiscoveryCachedTrait;
use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Annotation\Plugin\Discovery\AnnotatedClassDiscovery;

/**
 * Provides a plugin manager for the Driver with Drupal 7.
 */
class DriverPluginManagerDrupal7 extends PluginManagerBase implements PluginManagerInterface {

  use DriverPluginManagerTrait;
  use DiscoveryCachedTrait;

  /**
   * Constructor for DriverPluginManagerBase objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param int $version
   *   Drupal major version number.
   * @param string $projectPluginRoot
   *   The directory to search for additional project-specific driver plugins.
   */
  public function __construct(
        $driverPluginType,
        $projectPluginRoot = NULL
    ) {

    // Add the driver to the namespaces searched for plugins.
    $reflection = new \ReflectionClass($this);
    $driverPath = dirname(dirname($reflection->getFileName()));
    // @todo make this work for D7
    //$namespaces = \Drupal::service('container.namespaces')->getArrayCopy();
    //$supplementedNamespaces = new \ArrayObject();
    //foreach ($namespaces as $name => $class) {
    //  $supplementedNamespaces[$name] = $class;
    //}
    $supplementedNamespaces['Drupal\Driver'] = $driverPath;

    if (!is_null($projectPluginRoot)) {
      // Need some way to load project-specific plugins.
      // $supplementedNamespaces['Drupal\Driver'] = $projectPluginRoot;.
    }

    $this->subdir = 'Plugin/' . $driverPluginType;
    $this->namespaces = $supplementedNamespaces;
    $this->pluginDefinitionAnnotationName = 'Drupal\Driver\Annotation\\' . $driverPluginType;
    $this->pluginInterface = 'Drupal\Driver\Plugin\\' . $driverPluginType . 'PluginInterface';
  }

  /**
   * Determines if the provider of a definition exists.
   *
   * @return bool
   *   TRUE if provider exists, FALSE otherwise.
   */
  protected function providerExists($provider) {
    // @todo make this work for D7
    return true;
    return $this->moduleHandler->moduleExists($provider);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!$this->discovery) {
      $discovery = new AnnotatedClassDiscovery($this->namespaces, $this->pluginDefinitionAnnotationName);
      //$this->discovery = new ContainerDerivativeDiscoveryDecorator($discovery);
    }
    return $this->discovery;
  }

}
