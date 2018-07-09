<?php

namespace Drupal\Driver\Plugin\DriverField;

use Drupal\Driver\Plugin\DriverFieldPluginDrupal7Base;

/**
 * A driver field plugin that is a fallback for any field.
 *
 * @DriverField(
 *   id = "taxonomy_term_reference7",
 *   version = 7,
 *   fieldTypes={
 *     "taxonomy_term_reference",
 *   },
 *   weight = -100,
 * )
 */
class TaxonomyTermReferenceDrupal7 extends DriverFieldPluginDrupal7Base {

  /**
   * {@inheritdoc}
   */
  protected function getMainPropertyName() {
    return 'tid';
  }

  /**
   * {@inheritdoc}
   */
  public function processValue($value) {
      $termIdentifier = $value[$this->getMainPropertyName()];
      $terms = taxonomy_get_term_by_name($termIdentifier, $this->getVocab());
      if (!$terms) {
        throw new \Exception(sprintf("No term '%s' exists.", $termIdentifier));
      }
      return [$this->getMainPropertyName() => array_shift($terms)->tid];
  }

  /**
   * Attempt to determine the vocabulary for which the field is configured.
   *
   * @return mixed
   *   Returns a string containing the vocabulary in which the term must be
   *   found or NULL if unable to determine.
   */
  protected function getVocab() {
    if (!empty($this->field->getStorageDefinition()['settings']['allowed_values'][0]['vocabulary'])) {
      return $this->field->getStorageDefinition()['settings']['allowed_values'][0]['vocabulary'];
    }
  }
}
