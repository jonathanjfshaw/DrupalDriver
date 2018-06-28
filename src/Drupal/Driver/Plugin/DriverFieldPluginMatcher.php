<?php

namespace Drupal\Driver\Plugin;

/**
 * Provides the plugin matcher for the Driver's field plugins.
 */
class DriverFieldPluginMatcher extends DriverPluginMatcherBase {

  /**
   * {@inheritdoc}
   */
  protected $driverPluginType = 'DriverField';

  /**
   * {@inheritdoc}
   */
  protected $filters = [
    'fieldNames',
    'fieldTypes',
    'entityBundles',
    'entityTypes',
  ];

  /**
   * {@inheritdoc}
   */
  protected $specificityCriteria = [
    ['fieldNames', 'entityBundles', 'entityTypes'],
    ['fieldNames', 'entityBundles'],
    ['fieldNames', 'entityTypes'],
    ['fieldNames', 'fieldTypes'],
    ['fieldNames'],
    ['fieldTypes', 'entityBundles'],
    ['fieldTypes', 'entityTypes'],
    ['fieldTypes'],
    ['entityBundles', 'entityTypes'],
    ['entityBundles'],
    ['entityTypes'],
  ];

  /**
   * {@inheritdoc}
   */
  protected function getFilterableTarget($field) {
    return [
      'fieldNames' => $field->getName(),
      'fieldTypes' => $field->getType(),
      'entityTypes' => $field->getEntityType(),
      'entityBundles' => $field->getBundle(),
    ];
  }

}
