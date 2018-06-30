<?php

namespace Drupal\Driver\Wrapper\Field;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Driver\Plugin\DriverNameMatcher;

/**
 * A Driver field object that holds information about Drupal 7 field.
 *
 * @todo update this for D7, currently it's just a clone.
 */
class DriverFieldDrupal7 extends DriverFieldBase implements DriverFieldInterface {

  /**
   * The general field definition.
   *
   * For D7 this is the field definition, for
   * D8 the field_config.
   *
   * @var object|array
   */
  protected $definition;

  /**
   * The particular field definition.
   *
   * For D7 this is the field instance definition, for D8 the
   * field_storage_config.
   *
   * @var object|array
   */
  protected $storageDefinition;

  /**
   * Whether this driver field is wrapping the property of a config entity.
   *
   * The wrapper can hold config entity properties or content entity fields.
   *
   * @var bool
   */
  protected $isConfigProperty = FALSE;

  /**
   * The config schema of this config entity property.
   *
   * @var array
   */
  protected $configSchema;

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
        $rawValues,
        $fieldName,
        $entityType,
        $bundle = NULL,
        $projectPluginRoot = NULL,
        $fieldPluginMatcher = NULL
    ) {
    $entityTypeDefinition = \Drupal::EntityTypeManager()
      ->getDefinition($entityType);
    if ($entityTypeDefinition->entityClassImplements(ConfigEntityInterface::class)) {
      $this->isConfigProperty = TRUE;
      $configPrefix = $entityTypeDefinition->getConfigPrefix();
      $configProperties = \Drupal::service('config.typed')->getDefinition("$configPrefix.*")['mapping'];
      $this->configSchema = $configProperties;
    }

    parent::__construct($rawValues, $fieldName, $entityType, $bundle, $projectPluginRoot, $fieldPluginMatcher);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition() {
    if (is_null($this->definition) && !$this->isConfigProperty) {
      $entityFieldMatcher = \Drupal::service('entity_field.manager');
      $definitions = $entityFieldMatcher->getFieldDefinitions($this->getEntityType(), $this->getBundle());
      $this->definition = $definitions[$this->getName()];
    }
    return $this->definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageDefinition() {
    return $this->getDefinition()->getFieldStorageDefinition();
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    if ($this->isConfigProperty) {
      return $this->configSchema[$this->getName()]['type'];
    }
    else {
      return $this->getDefinition()->getType();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isConfigProperty() {
    return $this->isConfigProperty;
  }

  /**
   * Get the machine name of the field from a human-readable identifier.
   *
   * @return string
   *   The machine name of a field.
   */
  protected function identify($identifier) {
    // Get all the candidate fields. Assemble them into an array of field
    // machine names and labels ready for DriverNameMatcher. Read-only fields
    // are not removed because DriverFields can be used for comparing as well
    // as writing values.
    $candidates = [];
    if ($this->isConfigProperty()) {
      foreach ($this->configSchema as $id => $subkeys) {
        $label = isset($subkeys['label']) ? $subkeys['label'] : $id;
        $candidates[$label] = $id;
      }
    }
    else {
      $entityMatcher = \Drupal::service('entity_field.manager');
      $fields = $entityMatcher->getFieldDefinitions($this->entityType, $this->bundle);
      foreach ($fields as $machineName => $definition) {
        $label = (string) $definition->getLabel();
        $label = empty($label) ? $machineName : $label;
        $candidates[$label] = $machineName;
      }
    }

    $matcher = new DriverNameMatcher($candidates, "field_");
    $result = $matcher->identify($identifier);
    if (is_null($result)) {
      throw new \Exception("Field or property cannot be identified. '$identifier' does not match anything on '" . $this->getEntityType() . "'.");
    }
    return $result;
  }

}
