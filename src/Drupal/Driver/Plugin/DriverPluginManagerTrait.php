<?php

namespace Drupal\Driver\Plugin;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * Provides a plugin manager for the Driver with Drupal 7.
 */
trait DriverPluginManagerTrait {


  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Component\Plugin\PluginManagerInterface.
   */
  public function getDefinitions() {
    // Fetch definitions if they're not loaded yet.
    // $definitions is defined in DiscoveryCachedTrait.
    if (!isset($this->definitions)) {
      $this->definitions = $this->findDefinitions();
    }
    return $this->definitions;
  }

  /**
   * Finds plugin definitions.
   *
   * Differs from D8's DefaultPluginManager in that it always retains discovered
   * plugins with the provider 'driver', and doesn't allow modules to hook in
   * and alter definitions.
   *
   * @return array
   *   List of discovered plugin definitions.
   */
  protected function findDefinitions() {
    $definitions = $this->getDiscovery()->getDefinitions();
    foreach ($definitions as $plugin_id => &$definition) {
      $this->processDefinition($definition, $plugin_id);
    }
    // If this plugin was provided by a module that does not exist, remove the
    // plugin definition.
    foreach ($definitions as $plugin_id => $plugin_definition) {
      $provider = $this->extractProviderFromDefinition($plugin_definition);
      if ($provider && !in_array($provider, ['driver', 'core', 'component']) && !$this->providerExists($provider)) {
        unset($definitions[$plugin_id]);
      }
    }
    return $definitions;
  }

  /**
   * Extracts the provider from a plugin definition.
   *
   * Copied from D8's DefaultPluginManager.
   *
   * @param mixed $plugin_definition
   *   The plugin definition. Usually either an array or an instance of
   *   \Drupal\Component\Plugin\Definition\PluginDefinitionInterface
   *
   * @return string|null
   *   The provider string, if it exists. NULL otherwise.
   */
  protected function extractProviderFromDefinition($plugin_definition) {
    if ($plugin_definition instanceof PluginDefinitionInterface) {
      return $plugin_definition->getProvider();
    }

    // Attempt to convert the plugin definition to an array.
    if (is_object($plugin_definition)) {
      $plugin_definition = (array) $plugin_definition;
    }

    if (isset($plugin_definition['provider'])) {
      return $plugin_definition['provider'];
    }
  }

  /**
   * Performs extra processing on plugin definitions.
   *
   * Copied from D8's DefaultPluginManager.
   * By default we add defaults for the type to the definition. If a type has
   * additional processing logic they can do that by replacing or extending the
   * method.
   */
  public function processDefinition(&$definition, $plugin_id) {
    // Only array-based definitions can have defaults merged in.
    if (is_array($definition) && !empty($this->defaults) && is_array($this->defaults)) {
      $definition = NestedArray::mergeDeep($this->defaults, $definition);
    }

    // Keep class definitions standard with no leading slash.
    if ($definition instanceof PluginDefinitionInterface) {
      $definition->setClass(ltrim($definition->getClass(), '\\'));
    }
    elseif (is_array($definition) && isset($definition['class'])) {
      $definition['class'] = ltrim($definition['class'], '\\');
    }
  }

}
