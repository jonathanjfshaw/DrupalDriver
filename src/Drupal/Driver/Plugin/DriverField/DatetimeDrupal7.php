<?php

namespace Drupal\Driver\Plugin\DriverField;

use Drupal\Driver\Plugin\DriverFieldPluginDrupal7Base;

/**
 * A driver field plugin that is a fallback for any field.
 *
 * @DriverField(
 *   id = "datetime7",
 *   version = 7,
 *   fieldTypes={
 *     "datetime",
 *   },
 *   weight = -100,
 * )
 */
class DatetimeDrupal7 extends DriverFieldPluginDrupal7Base {


  /**
   * {@inheritdoc}
   */
  public function processValue($value) {
    if (isset($this->field->getStorageDefinition()['columns']['value2'])) {
        return [
          'value' => $value[0],
          'value2' => $value[1],
        ];
    }
    else {
        return $value;
    }
  }

}
