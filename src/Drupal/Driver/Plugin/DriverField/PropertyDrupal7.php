<?php

namespace Drupal\Driver\Plugin\DriverField;

use Drupal\Driver\Plugin\DriverFieldPluginDrupal7Base;

/**
 * A driver field plugin that is a fallback for any field.
 *
 * @DriverField(
 *   id = "_property7",
 *   version = 7,
 *   fieldTypes = {
 *     "_property",
 *   },
 *   weight = -100,
 *   final = TRUE,
 * )
 */
class PropertyDrupal7 extends DriverFieldPluginDrupal7Base {

  /**
   * {@inheritdoc}
   */
  public function processValues($values) {
    return $values;
  }

}
