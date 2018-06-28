<?php

namespace Drupal\Driver\Plugin\DriverEntity;

use Drupal\Driver\Plugin\DriverEntityPluginDrupal7Base;

/**
 * A driver field plugin used to test selecting an arbitrary plugin.
 *
 * @DriverEntity(
 *   id = "node7",
 *   version = 7,
 *   weight = -100,
 *   entityTypes = {
 *     "node",
 *   },
 * )
 */
class NodeDrupal7 extends DriverEntityPluginDrupal7Base {

  /**
   * The id of the attached node.
   *
   * @var int
   *
   * @deprecated Use id() instead.
   */
  public $nid;

  /**
   * {@inheritdoc}
   */
  public function delete() {
    node_delete($this->id());
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->getEntity()->nid;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getEntity()->title;
  }

  /**
   * {@inheritdoc}
   */
  public function loadById($entityId) {
    // @todo implement this method for D8 too.
    $node = node_load($entityId);
    $this->nid = $node->nid;
    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    node_save($this->getEntity());
    $this->nid = $this->id();
  }

  /**
   * {@inheritdoc}
   */
  public function setFields(array $fields) {
    if (!$this->hasEntity()) {
      $this->entity = (object) $fields;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function url($rel = 'canonical', array $options = []) {
    return url('node/'. $this->id());
  }

}
