<?php

namespace Drupal\Driver\Wrapper\Entity;

use Drupal\Driver\Plugin\DriverPluginMatcherInterface;
use Drupal\Driver\Plugin\DriverNameMatcher;

/**
 * A Driver wrapper for Drupal 8 entities.
 */
class DriverEntityDrupal8 extends DriverEntityBase implements DriverEntityWrapperInterface {

  /**
   * The Drupal version being driven.
   *
   * @var int
   */
  protected $version = 8;

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
    $entity = new DriverEntityDrupal8(
        $type,
        $bundle
    );
    $entity->setFields($fields);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setBundle($identifier) {
    // Don't set a bundle if the entity doesn't support bundles.
    $supportsBundles = $this->getProvisionalPlugin()->supportsBundles();
    if ($supportsBundles) {
      $bundles = $this->getProvisionalPlugin()->getBundles();
      $matcher = new DriverNameMatcher($bundles);
      $result = $matcher->identify($identifier);
      if (is_null($result)) {
        throw new \Exception("'$identifier' could not be identified as a bundle of the '" . $this->getEntityTypeId() . "' entity type.");
      }
      parent::setBundle($result);
    }
    return $this;
  }

  /**
   * Set the entity type.
   *
   * @param string $identifier
   *   A machine or human-friendly name for an entity type .
   */
  protected function setType($identifier) {
    $typeDefinitions = \Drupal::EntityTypeManager()->getDefinitions();
    $candidates = [];
    foreach ($typeDefinitions as $machineName => $typeDefinition) {
      $label = (string) $typeDefinition->getLabel();
      $candidates[$label] = $machineName;
    }
    $matcher = new DriverNameMatcher($candidates);
    $result = $matcher->identify($identifier);
    if (is_null($result)) {
      throw new \Exception("'$identifier' could not be identified as an entity type.");
    }
    $this->type = $result;
  }

}
