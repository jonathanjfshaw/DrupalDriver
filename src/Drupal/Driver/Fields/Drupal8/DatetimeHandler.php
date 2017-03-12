<?php

namespace Drupal\Driver\Fields\Drupal8;

/**
 * Datetime field handler for Drupal 8.
 */
class DatetimeHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    // A Drupal install has a default user-facing timezone, but nonetheless
    // uses UTC for internal storage. If no timezone is specified in a date
    // field value by the step author, assume it is in the default timezone of
    // the Drupal install, and therefore transform it into UTC for storage.
    $dtz = \Drupal::config('system.date')->get('timezone.default');
    // Drupal timezone is not always set, see
    // https://api.drupal.org/api/drupal/core!includes!bootstrap.inc/function/drupal_get_user_timezone/8.2.x
    // Ignore PHP strict notice if timezone has not yet been set in the php.ini.
    $dtz = !empty($dtz) ? $dtz : @date_default_timezone_get();
    $dtz = new DateTimeZone($dtz);
    $utc = new DateTimeZone('UTC');
    foreach ($values as $key => $value) {
      $date = new DateTime($value, $dtz);
      $date->setTimezone($utc);
      $values[$key] = $date->format('Y-m-d\TH:i:s');
    }
    return $values;  }

}
