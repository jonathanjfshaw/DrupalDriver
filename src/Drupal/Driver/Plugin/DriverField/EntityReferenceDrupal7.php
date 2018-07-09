<?php

namespace Drupal\Driver\Plugin\DriverField;

use Drupal\Driver\Plugin\DriverFieldPluginDrupal7Base;
use Drupal\Driver\Wrapper\Entity\DriverEntityDrupal7;

/**
 * A driver field plugin for entity reference fields.
 *
 * @DriverField(
 *   id = "entity_reference7",
 *   version = 7,
 *   fieldTypes = {
 *     "entityreference",
 *   },
 *   weight = -100,
 * )
 */
class EntityReferenceDrupal7 extends DriverFieldPluginDrupal7Base {

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
    $this->entityTypeId = $this->field->getStorageDefinition()['settings']['target_type'];
    $entity_info = entity_get_info($this->entityTypeId);
    $this->idKey = $entity_info['entity keys']['id'];
    $this->labelKeys = $this->getLabelKeys();

    // Determine target bundle restrictions.
    if ($this->targetBundles = $this->getTargetBundles()) {
      $this->targetBundleKey = $entity_info['entity keys']['bundle'];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getMainPropertyName() {
    return 'target_id';
  }

  /**
   * Gets an new blank driver entity wrapper.
   *
   * @return \Drupal\Driver\Wrapper\Entity\DriverEntityDrupal7;
   *   A driver entity wrapper object.
   */
  protected function getNewDriverEntity() {
    return new DriverEntityDrupal7($this->entityTypeId);
  }

  /**
   * Retrieves bundles for which the field is configured to reference.
   *
   * @return mixed
   *   Array of bundle names, or NULL if not able to determine bundles.
   */
  protected function getTargetBundles() {
    $target_bundles = $this->field->getStorageDefinition()['settings']['handler_settings']['target_bundles'];
    if (!empty($target_bundles)) {
      return $target_bundles;
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
    $entity_info = entity_get_info($this->entityTypeId);
    $query = db_select($entity_info['base table'], 't')
      ->fields('t', [$this->idKey])
      // @todo make this always case-insensitive.
      ->condition('t.' . $key, $value);
    if ($this->targetBundles && $this->targetBundleKey) {
      $query->condition($this->targetBundleKey, $this->targetBundles, 'IN');
    }
    $target_id = $query->execute()->fetchField();
    if ($target_id) {
      return $target_id;
    }
  }

}
