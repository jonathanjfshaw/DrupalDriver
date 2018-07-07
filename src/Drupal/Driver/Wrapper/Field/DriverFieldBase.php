<?php

namespace Drupal\Driver\Wrapper\Field;

use Drupal\Driver\Plugin\DriverPluginMatcherInterface;
use Drupal\Driver\Plugin\DriverFieldPluginMatcher;
use Drupal\Driver\Plugin\DriverNameMatcher;

/**
 * A base class for a Driver field wrapper.
 */
abstract class DriverFieldBase implements DriverFieldInterface {

  /**
   * Human-readable text intended to identify the field instance.
   *
   * @var string
   */
  protected $identifier;

  /**
   * Field instance's machine name.
   *
   * @var string
   */
  protected $name;

  /**
   * Entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Entity bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * Raw field values before processing by DriverField plugins.
   *
   * @var array
   */
  protected $rawValues;

  /**
   * Field values after processing by DriverField plugins.
   *
   * @var array
   */
  protected $processedValues;

  /**
   * A driver field plugin matcher object.
   *
   * @var \Drupal\Driver\Plugin\DriverPluginMatcherInterface
   */
  protected $fieldPluginMatcher;

  /**
   * Directory to search for additional project-specific driver plugins.
   *
   * @var string
   */
  protected $projectPluginRoot;

  /**
   * Construct a DriverField object.
   *
   * @param mixed $rawValues
   *   Raw values for the field. Typically an array, one for each value of a
   *   multivalue field, but can be single. Values are typically string.
   * @param string $identifier
   *   The machine name of the field or property.
   * @param string $entityType
   *   The machine name of the entity type the field is attached to.
   * @param string $bundle
   *   (optional) Machine name of the entity bundle the field is attached to.
   * @param string $projectPluginRoot
   *   The directory to search for additional project-specific driver plugins.
   * @param null|\Drupal\Driver\Plugin\DriverPluginMatcherInterface $fieldPluginMatcher
   *   (optional) A driver field plugin matcher.
   */
  public function __construct(
        $rawValues,
        $identifier,
        $entityType,
        $bundle = NULL,
        $projectPluginRoot = NULL,
        $fieldPluginMatcher = NULL
    ) {

    // Default to entity type as bundle if no bundle specified.
    if (empty($bundle)) {
      $bundle = $entityType;
    }
    // Wrap single values into an array so single and multivalue fields can be
    // handled identically.
    if (!is_array($rawValues)) {
      $rawValues = [$rawValues];
    }
    $this->projectPluginRoot = $projectPluginRoot;
    $this->setFieldPluginMatcher($fieldPluginMatcher);
    $this->rawValues = $rawValues;
    $this->entityType = $entityType;
    $this->bundle = $bundle;
    $this->name = $this->identify($identifier);
    $this->identifier = $identifier;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundle() {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessedValues() {
    if (is_null($this->processedValues)) {
      $this->setProcessedValues($this->getRawValues());
      $fieldPluginMatcher = $this->getFieldPluginMatcher();
      $definitions = $fieldPluginMatcher->getMatchedDefinitions($this);
      if (empty($definitions)) {
        throw new \Exception("No suitable driver field plugin could be found.");
      }
      // Process values through matched plugins, until a plugin
      // declares it is the final one.
      foreach ($definitions as $definition) {
        $plugin = $fieldPluginMatcher->createInstance($definition['id'], ['field' => $this]);
        $processedValues = $plugin->processValues($this->processedValues);
        //if (!is_array($processedValues)) {
        //  throw new \Exception("Field plugin failed to return array of processed values.");
       // }
        $this->setProcessedValues($processedValues);
        if ($plugin->isFinal($this)) {
          break;
        };
      }
    }

    // Don't pass an array back to singleton config properties.
    if ($this->isConfigProperty()) {
      if ($this->getType() !== 'sequence') {
        if (count($this->processedValues) > 1) {
          throw new \Exception("Config properties not of the type sequence should not have array input.");
        }
        return $this->processedValues[0];
      }
    }
    return $this->processedValues;
  }

  /**
   * {@inheritdoc}
   */
  public function getProjectPluginRoot() {
    return $this->projectPluginRoot;
  }

  /**
   * {@inheritdoc}
   */
  public function getRawValues() {
    return $this->rawValues;
  }

  /**
   * Sets the processed values.
   *
   * @return \Drupal\Driver\Plugin\DriverPluginMatcherInterface
   *   The field plugin matcher.
   */
  protected function getFieldPluginMatcher() {
    return $this->fieldPluginMatcher;
  }

  /**
   * Get the machine name of the field from a human-readable identifier.
   *
   * @return string
   *   The machine name of a field.
   */
  protected function identify($identifier) {
    $matcher = new DriverNameMatcher($this->getEntityFieldCandidates(), "field_");
    $result = $matcher->identify($identifier);
    if (is_null($result)) {
      throw new \Exception("Field or property cannot be identified. '$identifier' does not match anything on '" . $this->getEntityType() . "'.");
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function isConfigProperty() {
    return FALSE;
  }

  /**
   * Sets the processed values.
   *
   * @param mixed $values
   *   An variable or array of processed field value sets.
   */
  protected function setProcessedValues($values) {
    $this->processedValues = $values;
  }

  /**
   * Set the driver field plugin matcher.
   *
   * @param mixed $matcher
   *   The driver entity plugin matcher.
   */
  protected function setFieldPluginMatcher($matcher) {
    if (!($matcher instanceof DriverPluginMatcherInterface)) {
      $matcher = new DriverFieldPluginMatcher($this->version, $this->projectPluginRoot);
    }
    $this->fieldPluginMatcher = $matcher;
  }

}
