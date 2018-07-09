<?php

namespace Drupal\Driver\Plugin\DriverField;

use Drupal\Driver\Plugin\DriverFieldPluginDrupal7Base;

/**
 * A driver field plugin that suits an entity property.
 *
 * It overrides the processValues method (rather than the normal processValue)
 * in order to assume properties are singletons not arrays, and uses final=TRUE
 * in order to avoid the generic plugin coming after and recasting as array.
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
    if (is_array($values)) {
      $values = $values[0];
    }
    return $values;
  }

}
