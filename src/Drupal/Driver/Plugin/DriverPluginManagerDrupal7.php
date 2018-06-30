<?php

namespace Drupal\Driver\Plugin;

use Drupal\Component\Plugin\Discovery\DiscoveryCachedTrait;
use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Annotation\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Component\FileCache\FileCacheFactory;

/**
 * Provides a plugin manager for the Driver with Drupal 7.
 */
class DriverPluginManagerDrupal7 extends PluginManagerBase implements PluginManagerInterface {

  use DriverPluginManagerTrait;
  use DiscoveryCachedTrait;

  protected $pluginDefinitionAnnotationName;
  protected $subdir;
  protected $namespaces;
  protected $pluginInterface;

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

    // Ensure that FileCacheFactory has a prefix.
    // @todo this is insecure, as cache can be polluted by third party;
    // it might be possible to provide a malicious plugin.
    FileCacheFactory::setPrefix('prefix');

    // Add the driver to the namespaces searched for plugins.
    $reflection = new \ReflectionClass($this);
    $driverPath = dirname(dirname($reflection->getFileName()));
    // @todo make something like this work for D7 so as to load from modules
    //$namespaces = \Drupal::service('container.namespaces')->getArrayCopy();
    //$supplementedNamespaces = new \ArrayObject();
    //foreach ($namespaces as $name => $class) {
    //  $supplementedNamespaces[$name] = $class;
    //}
    $supplementedNamespaces = [];
    $supplementedNamespaces['Drupal\Driver\Plugin\\' . $driverPluginType] = [$driverPath . '/Plugin/' . $driverPluginType];

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
      $this->discovery = new AnnotatedClassDiscovery($this->namespaces, $this->pluginDefinitionAnnotationName);
      //$this->discovery = new ContainerDerivativeDiscoveryDecorator($discovery);
    }
    return $this->discovery;
  }

}
