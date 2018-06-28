<?php

namespace Drupal\Driver\Plugin;

/**
 * Defines an interface for the Driver's plugin managers.
 */
interface DriverPluginMatcherInterface {

  /**
   * Instantiates a plugin class.
   *
   * @param string $id
   *   The plugin id.
   * @param array $config
   *   An array of plugin configuration.
   *
   * @return \Drupal\Component\Plugin\PluginInspectionInterface
   *   An instantiated plugin.
   */
  public function createInstance($id, $config);

  /**
   * Get plugin definitions matching a target, sorted by weight and specificity.
   *
   * @param array|object $rawTarget
   *   An array or object that is the target to match definitions against.
   *
   * @return array
   *   An array of sorted plugin definitions that match that target.
   */
  public function getMatchedDefinitions($rawTarget);

}
