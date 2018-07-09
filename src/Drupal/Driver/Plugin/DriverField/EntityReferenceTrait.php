<?php

namespace Drupal\Driver\Plugin\DriverField;

use Drupal\Driver\Plugin\DriverEntityPluginMatcher;

/**
 * Common methoods for working with entity reference fields in any version.
 */
trait EntityReferenceTrait {

  /**
   * The entity type id.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Machine names of the fields or properties to use as labels for targets.
   *
   * @var array
   */
  protected $labelKeys;

  /**
   * The machine name of the field or property to use as id for targets.
   *
   * @var string
   */
  protected $idKey;

  /**
   * The bundles that targets must belong to.
   *
   * @var string
   */
  protected $targetBundles;

  /**
   * The machine name of the field that holds the bundle reference for targets.
   *
   * @var string
   */
  protected $targetBundleKey;

  /**
   * {@inheritdoc}
   */
  public function processValue($value) {
    $targetIdentifier = $value[$this->getMainPropertyName()];
    if (is_array($targetIdentifier)) {
      throw new \Exception("Array value not expected: " . print_r($targetIdentifier, TRUE));
    }

    // Build a set of strategies for matching target entities with the supplied
    // identifier text.
    // Id key is useful for matching config entities as they have string ids.
    // Id exact match takes precedence over label matches; label matches take
    // precedence over id key without underscores matches.
    $matchTypes = [];
    $matchTypes[] = ['key' => $this->idKey, 'value' => $targetIdentifier];
    foreach ($this->labelKeys as $labelKey) {
      $matchTypes[] = ['key' => $labelKey, 'value' => $targetIdentifier];
    }
    $matchTypes[] = [
      'key' => $this->idKey,
      'value' => str_replace(' ', '_', $targetIdentifier),
    ];

    // Try various matching strategies until we find a match.
    foreach ($matchTypes as $matchType) {
      // Ignore this strategy if the needed key has not been determined.
      // D8 key look ups return empty strings if there is no key of that kind.
      if (empty($matchType['key'])) {
        continue;
      }
      $targetId = $this->queryByKey($matchType['key'], $matchType['value']);
      if (!is_null($targetId)) {
        break;
      }
    }

    if (is_null($targetId)) {
      throw new \Exception(sprintf("No entity of type '%s' has id or label matching '%s'.", $this->entityTypeId, $targetIdentifier));
    }
    return [$this->getMainPropertyName() => $targetId];
  }

  /**
   * Retrieves fields to try as the label on the entity being referenced.
   *
   * @return array
   *   Array of field machine names.
   */
  protected function getLabelKeys() {
    $plugin = $this->getEntityPlugin();
    return $plugin->getLabelKeys();
  }

  /**
   * Get an entity plugin for the entity reference target entity type.
   *
   * @return \Drupal\Driver\Plugin\DriverEntityPluginInterface
   *   An instantiated driver entity plugin object.
   */
  protected function getEntityPlugin() {
    // @todo can this all be done using DriverEntityDrupal8 rather than
    // duplicating plugin instantiation code here?
    $projectPluginRoot = $this->field->getProjectPluginRoot();

    // Build the basic config for the plugin.
    $targetEntity = $this->getNewDriverEntity();
    $config = [
      'type' => $this->entityTypeId,
      'projectPluginRoot' => $projectPluginRoot,
    ];

    // Get a bundle specific plugin only if the entity reference field is
    // targeting a single bundle.
    if (is_array($this->targetBundles) && count($this->targetBundles) === 1) {
      $config['bundle'] = $this->targetBundles[0];
      $targetEntity->setBundle($this->targetBundles[0]);
    }
    else {
      $config['bundle'] = $this->entityTypeId;
    }

    // Discover & instantiate plugin.
    $matcher = new DriverEntityPluginMatcher($this->pluginDefinition['version'], $this->field->getProjectPluginRoot());

    // Get only the highest priority matched plugin.
    $matchedDefinitions = $matcher->getMatchedDefinitions($targetEntity);
    if (count($matchedDefinitions) === 0) {
      throw new \Exception("No matching DriverEntity plugins found.");
    }
    $topDefinition = $matchedDefinitions[0];
    $plugin = $matcher->createInstance($topDefinition['id'], $config);
    return $plugin;
  }

}
