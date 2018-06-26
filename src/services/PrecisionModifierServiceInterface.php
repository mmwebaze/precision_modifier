<?php

namespace Drupal\precision_modifier\services;


interface PrecisionModifierServiceInterface {
  public function increasePrecision($field, $bundle, $precision);
}