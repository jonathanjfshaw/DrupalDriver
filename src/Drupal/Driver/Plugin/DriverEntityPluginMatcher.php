<?php

namespace Drupal\Driver\Plugin;

/**
 * Provides the plugin matcher for the Driver's entity plugins.
 */
class DriverEntityPluginMatcher extends DriverPluginMatcherBase {

  /**
   * {@inheritdoc}
   */
  protected $driverPluginType = 'DriverEntity';

  /**
   * {@inheritdoc}
   */
  protected $filters = [
    'entityBundles',
    'entityTypes',
  ];

  /**
   * {@inheritdoc}
   */
  protected $specificityCriteria = [
    ['entityBundles', 'entityTypes'],
    ['entityBundles'],
    ['entityTypes'],
  ];

  /**
   * {@inheritdoc}
   */
  protected function getFilterableTarget($entity) {
    return [
      'entityTypes' => $entity->getEntityTypeId(),
      'entityBundles' => $entity->bundle(),
    ];
  }

}
