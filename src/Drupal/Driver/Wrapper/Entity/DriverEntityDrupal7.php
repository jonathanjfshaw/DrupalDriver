<?php

namespace Drupal\Driver\Wrapper\Entity;

use Drupal\Driver\Plugin\DriverPluginMatcherInterface;
use Drupal\Driver\Plugin\DriverNameMatcher;

/**
 * A Driver wrapper for Drupal 7 entities.
 */
class DriverEntityDrupal7 extends DriverEntityBase implements DriverEntityWrapperInterface {

  /**
   * The Drupal version being driven.
   *
   * @var int
   */
  protected $version = 7;

  /**
   * {@inheritdoc}
   */
  public function __construct(
        $type,
        $bundle = NULL,
        DriverPluginMatcherInterface $entityPluginMatcher = NULL,
        DriverPluginMatcherInterface $fieldPluginMatcher = NULL,
        $projectPluginRoot = NULL
    ) {

    parent::__construct($type, $bundle, $entityPluginMatcher, $fieldPluginMatcher, $projectPluginRoot);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $fields, $type, $bundle = NULL) {
    $entity = new DriverEntityDrupal7(
        $type,
        $bundle
    );
    $entity->setFields($fields);
    return $entity;
  }

  /**
   * Get the processed bundle value from the field plugin.
   *
   * @param \Drupal\Driver\Wrapper\Field\DriverFieldInterface $bundleField
   *   A wrapper for the bundle field.
   *
   * @return string
   *   The bundle ID.
   */
  protected function getProcessedBundle($bundleField) {
    return $bundleField->getProcessedValues();
  }

}
