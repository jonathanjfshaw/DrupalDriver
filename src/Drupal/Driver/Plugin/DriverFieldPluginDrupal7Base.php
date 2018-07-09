<?php

namespace Drupal\Driver\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Base class for Driver field plugins.
 */
class DriverFieldPluginDrupal7Base extends DriverFieldPluginBase implements DriverFieldPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The entity language.
   *
   * @var string
   */
  protected $language;

  /**
   * {@inheritdoc}
   */
  protected function getMainPropertyName() {
    if ($this->field->isConfigProperty()) {
      throw new \Exception("Main property name not used when processing config properties.");
    }
    // @todo Discover if an equivalent method exists for D7.
    //return $this->field->getStorageDefinition()->getMainPropertyName();
    return 'value';
  }

  /**
   * {@inheritdoc}
   */
  public function processValues($values) {
    // Break out of the language wrapper if it's been set by a prior plugin.
    if ($hasLanguage = isset($values[$this->getEntityLanguage()])) {
      $hasValuesArray = is_array($values[$this->getEntityLanguage()]);
    }
    if ($hasLanguage && $hasValuesArray) {
      $values = $values[$this->getEntityLanguage()];
    }

    $processed = parent::processValues($values);

    // For D7, wrap field values in the entity language.
    return [$this->getEntityLanguage() => $processed];
  }

  /**
   * Returns the entity language.
   *
   * @return string
   *   The entity language.
   */
  protected function getEntityLanguage() {
    if (is_null($this->language)) {
      if (field_is_translatable($this->field->getType(), $this->field->getStorageDefinition())) {
        $this->language = entity_language($this->field->getType(), $this->getEntity());
      }
      else {
        $this->language = LANGUAGE_NONE;
      }
    }
    return $this->language;

  }

}
