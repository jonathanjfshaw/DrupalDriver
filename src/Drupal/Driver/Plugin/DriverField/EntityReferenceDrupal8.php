<?php

namespace Drupal\Driver\Plugin\DriverField;

use Drupal\Driver\Plugin\DriverFieldPluginDrupal8Base;
use Drupal\Driver\Wrapper\Entity\DriverEntityDrupal8;

/**
 * A driver field plugin for entity reference fields.
 *
 * @DriverField(
 *   id = "entity_reference8",
 *   version = 8,
 *   fieldTypes = {
 *     "entity_reference",
 *   },
 *   weight = -100,
 * )
 */
class EntityReferenceDrupal8 extends DriverFieldPluginDrupal8Base {

  use EntityReferenceTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Determine id & label keys.
    $this->entityTypeId = $this->field->getStorageDefinition()->getSetting('target_type');
    $entity_definition = \Drupal::entityManager()->getDefinition($this->entityTypeId);
    $this->idKey = $entity_definition->getKey('id');
    $this->labelKeys = $this->getLabelKeys();

    // Determine target bundle restrictions.
    if ($this->targetBundles = $this->getTargetBundles()) {
      $this->targetBundleKey = $entity_definition->getKey('bundle');
    }
  }

  /**
   * Gets an new blank driver entity wrapper.
   *
   * @return \Drupal\Driver\Wrapper\Entity\DriverEntityDrupal8;
   *   A driver entity wrapper object.
   */
  protected function getNewDriverEntity() {
    return new DriverEntityDrupal8($this->entityTypeId);
  }

  /**
   * Retrieves bundles for which the field is configured to reference.
   *
   * @return mixed
   *   Array of bundle names, or NULL if not able to determine bundles.
   */
  protected function getTargetBundles() {
    $settings = $this->field->getDefinition()->getSettings();
    if (!empty($settings['handler_settings']['target_bundles'])) {
      return $settings['handler_settings']['target_bundles'];
    }
  }

  /**
   * Find an entity by looking at id and labels keys.
   *
   * @param string $key
   *   The machine name of the field to query.
   * @param string $value
   *   The value to seek in the field.
   *
   * @return int|string
   *   The id of an entity that has $value in the $key field.
   */
  protected function queryByKey($key, $value) {
    $query = \Drupal::entityQuery($this->entityTypeId);
    // @todo make this always case-insensitive.
    $query->condition($key, $value);
    if ($this->targetBundles && $this->targetBundleKey) {
      $query->condition($this->targetBundleKey, $this->targetBundles, 'IN');
    }
    if ($entities = $query->execute()) {
      $target_id = array_shift($entities);
      return $target_id;
    }
  }

}
