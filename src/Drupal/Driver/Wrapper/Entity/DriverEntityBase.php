<?php

namespace Drupal\Driver\Wrapper\Entity;

use Drupal\Driver\Plugin\DriverEntityPluginInterface;
use Drupal\Driver\Plugin\DriverEntityPluginMatcher;
use Drupal\Driver\Plugin\DriverPluginMatcherInterface;
use Drupal\Driver\Wrapper\Field\DriverFieldInterface;
use Drupal\Driver\Plugin\DriverNameMatcher;

/**
 * A base class for a Driver entity object that wraps a Drupal entity.
 */
abstract class DriverEntityBase implements DriverEntityWrapperInterface {

  /**
   * Entity type's machine name.
   *
   * @var string
   */
  protected $type;

  /**
   * Entity bundle's machine name.
   *
   * @var string
   */
  protected $bundle;

  /**
   * A driver entity plugin matcher object.
   *
   * @var \Drupal\Driver\Plugin\DriverPluginMatcherInterface
   */
  protected $entityPluginMatcher;

  /**
   * A driver field plugin matcher object.
   *
   * @var \Drupal\Driver\Plugin\DriverPluginMatcherInterface
   */
  protected $fieldPluginMatcher;

  /**
   * The directory to search for additional project-specific driver plugins.
   *
   * @var string
   */
  protected $projectPluginRoot;

  /**
   * The preliminary bundle-agnostic matched driver entity plugin.
   *
   * @var \Drupal\Driver\Plugin\DriverEntityPluginInterface
   */
  protected $provisionalPlugin;

  /**
   * The final bundle-specific matched driver entity plugin.
   *
   * @var \Drupal\Driver\Plugin\DriverEntityPluginInterface
   */
  protected $finalPlugin;

  /**
   * Constructs a driver entity wrapper object.
   *
   * @param string $type
   *   Machine name of the entity type.
   * @param string $bundle
   *   (optional) Machine name of the entity bundle.
   * @param \Drupal\Driver\Plugin\DriverPluginMatcherInterface $entityPluginMatcher
   *   (optional) An driver entity plugin matcher.
   * @param \Drupal\Driver\Plugin\DriverPluginMatcherInterface $fieldPluginMatcher
   *   (optional) An driver entity plugin matcher.
   * @param string $projectPluginRoot
   *   The directory to search for additional project-specific driver plugins .
   */
  public function __construct(
        $type,
        $bundle = NULL,
        DriverPluginMatcherInterface $entityPluginMatcher = NULL,
        DriverPluginMatcherInterface $fieldPluginMatcher = NULL,
        $projectPluginRoot = NULL
    ) {

    $this->projectPluginRoot = $projectPluginRoot;
    $this->setEntityPluginMatcher($entityPluginMatcher);
    $this->fieldPluginMatcher = $fieldPluginMatcher;
    $this->setType($type);

    // Provisional plugin set before bundle as it's used in bundle validation.
    $this->setProvisionalPlugin($this->getPlugin());

    if (!empty($bundle)) {
      $this->setBundle($bundle);
      // Only set final plugin if bundle is known.
      $this->setFinalPlugin($this->getPlugin());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __call($name, $arguments) {
    // Forward unknown calls to the plugin.
    if ($this->hasFinalPlugin()) {
      return call_user_func_array([
        $this->getFinalPlugin(),
        $name,
      ], $arguments);
    }
    throw new \Exception("Method '$name' unknown on Driver entity wrapper and plugin not yet available.");
  }

  /**
   * {@inheritdoc}
   */
  public function __get($name) {
    // Forward unknown calls to the plugin.
    if ($this->hasFinalPlugin()) {
      return $this->getFinalPlugin()->$name;
    }
    throw new \Exception("Property '$name' unknown on Driver entity wrapper and plugin not yet available.");
  }

  /**
   * {@inheritdoc}
   */
  public function bundle() {
    // Default to entity type as bundle. This is used when the bundle is not
    // yet known, for example during DriverField processing of the bundle field.
    // If no bundle is supplied, this default is permanently set as the bundle
    // later by getFinalPlugin().
    if (is_null($this->bundle)) {
      return $this->getEntityTypeId();
    }
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $this->getEntity()->delete();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return $this->getFinalPlugin()->getEntity();
  }

  /**
   * Get an entity plugin.
   *
   * This may or may not be bundle-specific, depending on whether the bundle is
   * known at this point.
   *
   * @return \Drupal\Driver\Plugin\DriverEntityPluginInterface
   *   An instantiated driver entity plugin object.
   */
  protected function getPlugin() {
    if (is_null($this->getEntityTypeId())) {
      throw new \Exception("Entity type is required to discover matched plugins.");
    }

    // Build the basic config for the plugin.
    $config = [
      'type' => $this->getEntityTypeId(),
      'bundle' => $this->bundle(),
      'projectPluginRoot' => $this->projectPluginRoot,
      'fieldPluginMatcher' => $this->fieldPluginMatcher,
    ];

    // Discover, instantiate and store plugin.
    // Get only the highest priority matched plugin.
    $matchedDefinitions = $this->entityPluginMatcher->getMatchedDefinitions($this);
    if (count($matchedDefinitions) === 0) {
      throw new \Exception("No matching DriverEntity plugins found.");
    }
    $topDefinition = $matchedDefinitions[0];
    $plugin = $this->entityPluginMatcher->createInstance($topDefinition['id'], $config);
    if (!($plugin instanceof DriverEntityPluginInterface)) {
      throw new \Exception("DriverEntity plugin '" . $topDefinition['id'] . "' failed to instantiate.");
    }
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getFinalPlugin() {
    if (!$this->hasFinalPlugin()) {
      // Commit to default bundle if still using that.
      if ($this->isBundleMissing()) {
        $this->setBundle($this->bundle());
      }
      $this->setFinalPlugin($this->getPlugin());
    }
    if (!$this->hasFinalPlugin()) {
      throw new \Exception("Failed to discover or instantiate bundle-specific plugin.");
    }

    return $this->finalPlugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->getFinalPlugin()->id();
  }

  /**
   * {@inheritdoc}
   */
  public function isNew() {
    if ($this->hasFinalPlugin()) {
      return $this->getFinalPlugin()->isNew();
    }
    else {
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getFinalPlugin()->label();
  }

  /**
   * {@inheritdoc}
   */
  public function load($entityId) {
    if (!is_string($entityId) && !is_int($entityId)) {
      throw new \Exception("Entity ID to be loaded must be string or integer.");
    }
    if ($this->hasFinalPlugin()) {
      $this->getFinalPlugin()->load($entityId);
    }
    else {
      $entity = $this->getProvisionalPlugin()->load($entityId);
      if ($this->isBundleMissing()) {
        $this->setBundle($entity->bundle());
      }
      $this->getFinalPlugin()->load($entityId);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function reload() {
    $this->getFinalPlugin()->reload();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $this->getFinalPlugin()->save();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function set($identifier, $field) {
    $this->setFields([$identifier => $field]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setBundle($identifier) {
    if ($this->hasFinalPlugin()) {
      throw new \Exception("Cannot change entity bundle after final plugin discovery has taken place");
    }
    $this->bundle = $identifier;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setFinalPlugin(DriverEntityPluginInterface $plugin) {
    if ($this->hasFinalPlugin()) {
      throw new \Exception("Cannot change entity plugin without risk of data loss.");
    }
    $this->finalPlugin = $plugin;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setFields(array $fields) {
    // We don't try to identify all the fields here - or even check that they
    // are all identifiable - because we want to pass everything on to the
    // plugin as raw as possible. But we must extract the bundle field (if the
    // bundle is not already known) as the bundle is used in plugin discovery.
    if ($this->isBundleMissing()) {
      $fields = $this->extractBundleField($fields);
    }
    $this->getFinalPlugin()->setFields($fields);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function url($rel = 'canonical', array $options = []) {
    return $this->getFinalPlugin()->url($rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    return $this->getFinalPlugin()->tearDown();
  }

  /**
   * Extract the bundle field from a set of fields, and store the bundle.
   *
   * @param array $fields
   *   An array of inputs that represent fields.
   *
   * @return array
   *   An array of inputs that represent fields, without the bundle field.
   */
  protected function extractBundleField(array $fields) {
    $bundleKey = $this->getProvisionalPlugin()->getBundleKey();
    // If this is a bundle-less entity, there's nothing to do.
    if (empty($bundleKey)) {
      return $fields;
    }
    else {
      // BC support for identifying the bundle by the name 'step_bundle'.
      if (isset($fields['step_bundle'])) {
        $fields[$bundleKey] = $fields['step_bundle'];
        unset($fields['step_bundle']);
      }
      // Find the bundle field, if it is present among the fields.
      $bundleKeyLabels = $this->getProvisionalPlugin()->getBundleKeyLabels();
      $candidates = [];
      foreach ($bundleKeyLabels as $label) {
        $candidates[$label] = $bundleKey;
      }
      $matcher = new DriverNameMatcher($candidates);
      $bundleFieldMatch = $matcher->identifySet($fields);

      // If the bundle field has been found, process it and set the bundle.
      // Don't throw an exception if none if found, as it is possible to have
      // entities (like entity_test) that have a bundle key but don't require
      // a bundle to be set.
      if (count($bundleFieldMatch) !== 0) {
        if ($bundleFieldMatch[$bundleKey] instanceof DriverFieldInterface) {
          $bundleField = $bundleFieldMatch[$bundleKey];
        }
        else {
          $bundleField = $this->getNewDriverField($bundleKey, $bundleFieldMatch[$bundleKey]);
        }
        $this->setBundle($bundleField->getProcessedValues()[0]['target_id']);
      }

      // Return the other fields (with the bundle field now removed).
      return $matcher->getUnmatchedTargets();
    }
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
    $driverFieldVersionClass = "Drupal\Driver\Wrapper\Field\DriverFieldDrupal" . $this->version;
    $field = new $driverFieldVersionClass(
    $values,
    $fieldName,
    $this->getEntityTypeId(),
    $this->bundle(),
    $this->projectPluginRoot,
    $this->fieldPluginMatcher
    );
    return $field;
  }

  /**
   * Gets the provisional entity plugin.
   *
   * @return \Drupal\Driver\Plugin\DriverEntityPluginInterface
   *   The provisional (bundle-unaware) entity plugin.
   */
  protected function getProvisionalPlugin() {
    if ($this->hasFinalPlugin()) {
      return $this->getFinalPlugin();
    }
    return $this->provisionalPlugin;
  }

  /**
   * Whether a matched plugin has yet been discovered and stored.
   *
   * @return bool
   *   Whether a matched plugin has yet been discovered and stored.
   */
  protected function hasFinalPlugin() {
    $hasFinalPlugin = !is_null($this->finalPlugin);
    if ($hasFinalPlugin) {
      $hasFinalPlugin = $this->finalPlugin instanceof DriverEntityPluginInterface;
    }
    return $hasFinalPlugin;
  }

  /**
   * Whether a bundle has been set yet.
   *
   * @return bool
   *   Whether a bundle has been set yet.
   */
  protected function isBundleMissing() {
    $supportsBundles = $this->getProvisionalPlugin()->supportsBundles();
    return ($supportsBundles && is_null($this->bundle));
  }

  /**
   * Set the driver entity plugin matcher.
   *
   * @param mixed $matcher
   *   The driver entity plugin matcher.
   */
  protected function setEntityPluginMatcher($matcher) {
    if (!($matcher instanceof DriverPluginMatcherInterface)) {
      $matcher = new DriverEntityPluginMatcher($this->version, $this->projectPluginRoot);
    }
    $this->entityPluginMatcher = $matcher;
  }

  /**
   * Sets the provisional entity plugin.
   *
   * @param \Drupal\Driver\Plugin\DriverEntityPluginInterface $plugin
   *   The provisional entity plugin.
   */
  protected function setProvisionalPlugin(DriverEntityPluginInterface $plugin) {
    $this->provisionalPlugin = $plugin;
  }

  /**
   * Set the entity type.
   *
   * @param string $identifier
   *   A machine or human-friendly name for an entity type .
   */
  protected function setType($identifier) {
    $this->type = $identifier;
  }
}
