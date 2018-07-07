<?php

namespace Drupal\Driver\Plugin\DriverField;

use Drupal\Driver\Plugin\DriverFieldPluginDrupal7Base;

/**
 * A driver field plugin for link fields.
 *
 * @DriverField(
 *   id = "link7",
 *   version = 7,
 *   fieldTypes = {
 *     "link_field",
 *   },
 *   weight = -100,
 * )
 */
class LinkDrupal7 extends DriverFieldPluginDrupal7Base {

  /**
   * {@inheritdoc}
   */
  protected function assignPropertyNames($value) {
    // For links we support unkeyed arrays in which the first item is the title,
    // and the second is the url.
    $keyedValue = $value;
    if (!is_array($value)) {
      $keyedValue = ['url' => $value];
    }
    elseif (count($value) === 1) {
      $keyedValue = ['url' => end($value)];
    }
    // Convert unkeyed array.
    else {
      if (!isset($value['url']) && isset($value[1])) {
        $keyedValue['url'] = $value[1];
        unset($keyedValue[1]);
      }
      if (!isset($value['title']) && isset($value[0])) {
        $keyedValue['title'] = $value[0];
        unset($keyedValue[0]);
      }
      if (!isset($value['options']) && isset($value[2])) {
        $keyedValue['options'] = $value[2];
        unset($keyedValue[2]);
      }
    }
    if (!isset($keyedValue['url'])) {
      throw new \Exception("Url could not be identified from passed value: " . print_r($value, TRUE));
    }
    return $keyedValue;
  }

  /**
   * {@inheritdoc}
   */
  protected function processValue($value) {
    // Default title to uri.
    $title = $value['url'];
    if (isset($value['title'])) {
      $title = $value['title'];
    }

    $processedValue = [
      'url' => $value['url'],
      'title' => $title
    ];
    return $processedValue;
  }

}
