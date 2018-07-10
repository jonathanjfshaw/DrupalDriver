<?php

namespace Drupal\Driver\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Driver field plugins.
 */
class DriverFieldPluginDrupal8Base extends DriverFieldPluginBase implements DriverFieldPluginInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getMainPropertyName() {
    if ($this->field->isConfigProperty()) {
      throw new \Exception("Main property name not used when processing config properties.");
    }
    return $this->field->getStorageDefinition()->getMainPropertyName();
  }

}
