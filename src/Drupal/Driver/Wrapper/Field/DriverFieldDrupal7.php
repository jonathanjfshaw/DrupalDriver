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
  protected $version = 7;

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
    //$entityTypeDefinition = \Drupal::EntityTypeManager()
    //  ->getDefinition($entityType);
    //if ($entityTypeDefinition->entityClassImplements(ConfigEntityInterface::class)) {
    //  $this->isConfigProperty = TRUE;
      //$configPrefix = $entityTypeDefinition->getConfigPrefix();
      //$configProperties = \Drupal::service('config.typed')->getDefinition("$configPrefix.*")['mapping'];
      //$this->configSchema = $configProperties;
    //}

    parent::__construct($rawValues, $fieldName, $entityType, $bundle, $projectPluginRoot, $fieldPluginMatcher);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition() {
    if (is_null($this->definition) && !$this->isConfigProperty) {
      $definitions = field_info_instances($this->entityType, $this->bundle);
      if (isset($definitions[$this->getName()])) {
        $this->definition = $definitions[$this->getName()];
      }
      else {
        $this->definition = [];
      }
    }
    return $this->definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageDefinition() {
    //return $this->getDefinition()->getFieldStorageDefinition();
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    if (!empty($this->getDefinition())) {
      $type =  field_info_field($this->getName())['type'];
    }
    else {
      $type = '_property';
    }
    return $type;
  }

  /**
   * {@inheritdoc}
   */
  public function isConfigProperty() {
    return $this->isConfigProperty;
  }

  /**
   * Gets the machine names and labels for all fields on an entity.
   *
   * Fields are assembled into an array of field machine names and labels ready
   * for DriverNameMatcher. Read-only fields are not removed because
   * DriverFields can be used for comparing as well as writing values.
   *
   * @return array
   *   An array of machine names keyed by label.
   */
  protected function getEntityFieldCandidates() {
    $candidates = [];
    $fields = field_info_instances($this->entityType, $this->bundle);
    foreach ($fields as $machineName => $definition) {
      if ((isset($definition['label']) && (!empty($definition['label'])))) {
        $label = $definition['label'];
      }
      else {
          $label = $machineName;
      }
      $candidates[$label] = $machineName;
    }
    // @todo this is a hack to get around the fact that node type is a property not a field
    $candidates['type'] = 'type';
    return $candidates;
  }


}
