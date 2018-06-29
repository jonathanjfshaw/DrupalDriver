<?php

namespace Drupal\Driver\Plugin;

use Drupal\Driver\Wrapper\Field\DriverFieldInterface;
use Drupal\Driver\Wrapper\Field\DriverFieldDrupal7;
use Drupal\Driver\Wrapper\Entity\DriverEntityInterface;

/**
 * Provides a base class for the Driver's entity plugins.
 */
class DriverEntityPluginDrupal7Base extends DriverEntityPluginBase implements DriverEntityPluginInterface, DriverEntityInterface {

  /**
   * The id of the attached entity.
   *
   * @var int|string
   *
   * @deprecated Use id() instead.
   */
  public $id;

  /**
   * Entity type definition.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The saved Drupal entity this object is wrapping for the Driver.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The driver field plugin manager.
   *
   * @var \Drupal\Driver\Plugin\DriverPluginManagerInterface
   */
  protected $fieldPluginManager;

  /**
   * Whether the entity is new.
   *
   * @var bool
   */
  protected $isNew = TRUE;


  /**
   * {@inheritdoc}
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $this->getEntity()->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleKey() {
    $info = entity_get_info($this->type);
    if (isset($info['entity keys']['bundle'])) {
      return $info['entity keys']['bundle'];
    }
    else {
      // This entity does not have bundles.
      return NULL;
    }
  }

  /**
 * {@inheritdoc}
 */
  public function getBundleKeyLabels() {
    // @todo find the bundle key label
    $bundleKey = $this->getBundleKey();
    return [(string) $bundleKey];
  }

  /**
   * {@inheritdoc}
   */
  public function getBundles() {
    $info = entity_get_info($this->type);
    $bundleInfo = $info['bundles'];
    // Parse into array structure used by DriverNameMatcher.
    $bundles = [];
    foreach ($bundleInfo as $machineName => $bundleSettings) {
      $bundles[$bundleSettings['label']] = $machineName;
    }
    return $bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    // @todo is there any way to validate an object is a Drupal entity in D7?
  }

  /**
   * {@inheritdoc}
   */
  public function getLabelKeys() {
    $labelKeys = parent::getLabelKeys();
    if (empty($labelKeys)) {
      $labelKeys = [
        entity_get_info($this->type)['entity keys']['label']
      ];
    }
    return $labelKeys;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->getEntity()->id();
  }

  /**
   * {@inheritdoc}
   */
  public function isNew() {
    if ($this->hasEntity() && $this->isNew === FALSE) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getEntity()->label();
  }

  /**
   * {@inheritdoc}
   */
  public function load($entityId) {
    if ($this->hasEntity()) {
      throw new \Exception("A Drupal entity is already attached to this plugin");
    }
    $this->entity = $this->loadById();
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function reload() {
    if (!$this->hasEntity()) {
      throw new \Exception("There is no attached entity so it cannot be reloaded");
    }
    $entityId = $this->getEntity()->id();
    // @todo Investigate whether cache resetting is needed in D7 as it is in D8
    //$this->getStorage()->resetCache([$entityId]);
    $this->entity = $this->loadById();
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $this->getEntity()->save();
    $this->isNew = FALSE;
    $this->id = $this->id();
  }

  /**
   * {@inheritdoc}
   */
  public function set($identifier, $field) {
    if (!($field instanceof DriverFieldInterface)) {
      $field = $this->getNewDriverField($identifier, $field);
    }
    $this->getEntity()->set($field->getName(), $field->getProcessedValues());
  }

  /**
   * {@inheritdoc}
   */
  public function url($rel = 'canonical', array $options = []) {
    return $this->getEntity()->url($rel, $options);
  }

  /**
   * Get a new driver field with values.
   *
   * @param string $fieldName
   *   A string identifying an entity field.
   * @param string|array $values
   *   An input that can be transformed into Driver field values.
   */
  protected function getNewDriverField($fieldName, $values) {
    $field = new DriverFieldDrupal7(
        $values,
        $fieldName,
        $this->type,
        $this->bundle,
        $this->projectPluginRoot,
        $this->fieldPluginManager
    );
    return $field;
  }

  /**
   * Get a new entity object. This doesn't make sense without an entity API.
   *
   * @return object
   *   An empty entity object.
   */
  protected function getNewEntity() {
    return new \stdClass();
  }

}
