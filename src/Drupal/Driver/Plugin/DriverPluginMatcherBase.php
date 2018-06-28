<?php

namespace Drupal\Driver\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Provides a base class for the Driver's plugin managers.
 */
class DriverPluginMatcherBase implements DriverPluginMatcherInterface {

  /**
   * The name of the plugin type this is the manager for.
   *
   * @var string
   */
  protected $driverPluginType;

  /**
   * Discovered plugin definitions that match targets.
   *
   * An array, keyed by target. Each array value is a sub-array of sorted
   * plugin definitions that match that target.
   *
   * @var array
   */
  protected $matchedDefinitions;

  /**
   * An array of target characteristics that plugins should be filtered by.
   *
   * @var array
   */
  protected $filters;

  /**
   * An multi-dimensional array of sets of target characteristics.
   *
   * The order indicates the specificity of the match between the plugin
   * definition and the target; earlier arrays are a more precise match.
   *
   * @var array
   */
  protected $specificityCriteria;

  /**
   * The Drupal version being driven.
   *
   * @var int
   */
  protected $version;

  /**
   * The directory to search for additional project-specific driver plugins.
   *
   * @var string
   */
  protected $projectPluginRoot;

  /**
   * A plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * Constructor for DriverPluginManagerBase objects.
   *
   * @param int $version
   *   Drupal major version number.
   * @param string $projectPluginRoot
   *   The directory to search for additional project-specific driver plugins.
   */
  public function __construct(
    $version,
    $projectPluginRoot = NULL
  ) {

    $this->version = $version;
    $this->projectPluginRoot = $projectPluginRoot;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($id, $config) {
    return $this->getManager()->createInstance($id, $config);
  }

  /**
   * {@inheritdoc}
   */
  public function getMatchedDefinitions($rawTarget) {
    // Make sure the target is in a filterable format.
    $target = $this->getFilterableTarget($rawTarget);
    foreach ($this->getFilters() as $filter) {
      if (!isset($target[$filter])) {
        throw new \Exception("Plugin target is missing required filter property '" . $filter . "'.");
      }
    }

    // Get stored plugins if available.
    $targetKey = serialize($target);
    if (isset($this->matchedDefinitions[$targetKey])) {
      return $this->matchedDefinitions[$targetKey];
    }

    // Discover plugins, discard those that don't match the target, and sort.
    $definitions = $this->getDefinitions();
    $filteredDefinitions = $this->filterDefinitionsByTarget($target, $definitions);
    $sortedDefinitions = $this->sortDefinitions($filteredDefinitions);

    $this->setMatchedDefinitions($targetKey, $sortedDefinitions);
    return $this->matchedDefinitions[$targetKey];
  }

  /**
   * Discover all available plugins.
   *
   * @return array
   *   An array of plugin definitions.
   */
  protected function getDefinitions() {
    return $this->getManager()->getDefinitions();
  }

  /**
   * Convert a target object into a filterable target.
   *
   * @param array|object $rawTarget
   *   An array or object that is the target to match definitions against.
   *
   * @return array
   *   An array with a key for each filter used by this plugin manager.
   */
  protected function getFilterableTarget($rawTarget) {
    return $rawTarget;
  }

  /**
   * Get a plugin manager suitable for this Drupal version.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   *   A plugin manager.
   */
  protected function getManager() {
    if (!($this->manager instanceof PluginManagerInterface)) {
      if ($this->getVersion() == '6') {
        $this->manager = New DriverPluginManagerDrupal6($this->getDriverPluginType(), $this->projectPluginRoot);
      }
      elseif ($this->getVersion() == '7') {
        $this->manager = New DriverPluginManagerDrupal7($this->getDriverPluginType(), $this->projectPluginRoot);
      }
      else {
        $this->manager = New DriverPluginManagerDrupal8($this->getDriverPluginType(), $this->getVersion(), $this->projectPluginRoot);
      }
    }
    return $this->manager;
  }

  /**
   * Sort an array of plugin definitions by specificity and weight.
   *
   * @param array $definitions
   *   An array of definitions.
   *
   * @return array
   *   An array of definitions sorted by specificity and weight.
   */
  protected function sortDefinitions($definitions) {
    // Group the plugins definitions according to weight.
    $weighted_definitions = [];
    foreach ($definitions as $definition) {
      $weight = $definition['weight'];
      $weighted_definitions[$weight][] = $definition;
    }

    // Group by specificity within each weight group.
    $groupedDefinitions = [];
    foreach ($weighted_definitions as $weight => $weightGroup) {
      $groupedDefinitions[$weight] = $this->sortDefinitionsBySpecificity($weightGroup);
    }

    // Sort the weight groups high to low.
    krsort($groupedDefinitions);

    // Flatten the weight and specificity groups, while preserving sort order.
    if (count($groupedDefinitions) === 0) {
      $flattenedDefinitions = [];
    }
    else {
      $flattenedDefinitions = call_user_func_array('array_merge', $groupedDefinitions);
      $flattenedDefinitions = call_user_func_array('array_merge', $flattenedDefinitions);
    }
    return $flattenedDefinitions;
  }

  /**
   * Sort an array of definitions by their specificity.
   *
   * @param array $definitions
   *   An array of definitions.
   *
   * @return array
   *   An array of definitions sorted by the specificity criteria.
   */
  protected function sortDefinitionsBySpecificity(array $definitions) {
    // Group definitions by which criteria they match.
    $groupedDefinitions = [];
    foreach ($definitions as $definition) {
      $group = $this->findSpecificityGroup($definition);
      $groupedDefinitions[$group][] = $definition;
    }

    // Sort alphabetically by id within groups.
    $sortedDefinitions = [];
    foreach ($groupedDefinitions as $groupName => $groupDefinitions) {
      usort($groupDefinitions, function ($a, $b) {
        return strcmp($a['id'], $b['id']);
      });
      $sortedDefinitions[$groupName] = $groupDefinitions;
    }

    // Sort groups by the order of the specificity criteria.
    ksort($sortedDefinitions);
    return $sortedDefinitions;
  }

  /**
   * Find the specificity group a plugin definition belongs to.
   *
   * @param array $definition
   *   A plugin definition with keys for the specificity criteria.
   *
   * @return int
   *   An integer for which of the specificity criteria the definition fits.
   */
  protected function findSpecificityGroup(array $definition) {
    // Work  though specificity criteria until a match is found.
    foreach ($this->getSpecificityCriteria() as $key => $criteria) {
      foreach ($criteria as $criterion) {
        if (!isset($definition[$criterion])) {
          continue(2);
        }
      }
      return $key;
    }

    // If it matched no criteria, it must be a catch-all plugin.
    return count($this->getSpecificityCriteria());
  }

  /**
   * Remove plugin definitions that don't fit a target according to filters.
   *
   * @param array $target
   *   An array with keys for each filter that plugins may or may not match.
   * @param array $definitions
   *   An array of plugin definitions to match against the target.
   *
   * @return array
   *   An array of plugin definitions, only those which match the target.
   */
  protected function filterDefinitionsByTarget(array $target, array $definitions) {
    $filters = $this->getFilters();
    $filteredDefinitions = [];
    foreach ($definitions as $definition) {
      // Drop plugins for other Drupal versions if version specified.
      if (isset($definition['version']) && $definition['version'] !== $this->getVersion()) {
        continue;
      }
      reset($filters);
      foreach ($filters as $filter) {
        // If a definition doesn't contain the value specified by the target,
        // for this filter, then skip this definition and don't store it.
        $isCompatibleArray = isset($definition[$filter]) &&
                is_array($definition[$filter]) && (count($definition[$filter]) > 0);
        if ($isCompatibleArray) {
          // Use case insensitive comparison.
          $definitionFilters = array_map('mb_strtolower', $definition[$filter]);
          if (!in_array(mb_strtolower($target[$filter]), $definitionFilters, TRUE)) {
            continue(2);
          }
        }
      }
      $filteredDefinitions[] = $definition;
    }
    return $filteredDefinitions;
  }

  /**
   * Get the name of the type of driver plugin being matched.
   *
   * @return string
   *   The name of the type of driver plugin being managed.
   */
  protected function getDriverPluginType() {
    return $this->driverPluginType;
  }

  /**
   * Get the specificity criteria for this driver plugin type.
   *
   * @return array
   *   An multi-dimensional array of sets of target characteristics. The order
   *   indicates the specificity of the match between the plugin definition and
   *   the target; earlier arrays are a more precise match.
   */
  protected function getSpecificityCriteria() {
    return $this->specificityCriteria;
  }

  /**
   * Get the filters for this driver plugin type.
   *
   * @return array
   *   An array of target characteristics that plugins should be filtered by.
   */
  protected function getFilters() {
    return $this->filters;
  }

  /**
   * Get the Drupal version being driven.
   *
   * @return int
   *   The Drupal major version number.
   */
  protected function getVersion() {
    return $this->version;
  }

  /**
   * Sets the matched plugin definitions.
   *
   * @param string $targetKey
   *   A serialized representation of a filterable target.
   * @param array $definitions
   *   An array of plugin definitions matched & sorted against the target key.
   */
  protected function setMatchedDefinitions($targetKey, array $definitions) {
    $this->matchedDefinitions[$targetKey] = $definitions;
  }

}
