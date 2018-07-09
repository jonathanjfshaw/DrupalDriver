<?php

namespace Drupal\Driver\Plugin\DriverField;

use Drupal\Driver\Plugin\DriverFieldPluginDrupal7Base;

/**
 * A driver field plugin for 'created' fields.
 *
 * @DriverField(
 *   id = "created7",
 *   version = 7,
 *   fieldNames = {
 *     "created",
 *   },
 *   entityTypes = {
 *     "node",
 *     "user",
 *   },
 *   weight = -100,
 *   final = TRUE,
 * )
 */
class CreatedDrupal7 extends DriverFieldPluginDrupal7Base {

  /**
   * {@inheritdoc}
   */
  public function processValues($value) {
    $processedValue = $value[0];
    if (!empty($processedValue) && !is_numeric($processedValue)) {
      $processedValue = strtotime($processedValue);
    }
    return $processedValue;
  }

}
