<?php

namespace Drupal\precision_modifier\services;

use \Drupal\Core\Entity\EntityTypeManagerInterface;
use \Drupal\Core\Database\Connection;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

class PrecisionModifierService implements PrecisionModifierServiceInterface {
  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;
  public function __construct(Connection $connection, EntityTypeManagerInterface $entityTypeManager) {
    $this->connection = $connection;
    $this->entityTypeManager = $entityTypeManager;
  }
  public function increasePrecision($field, $bundle, $precision) {
    $database = $this->connection;
    $tables = [
      "node_revision__{$field}",
      "node__{$field}",
    ];
    $settings = ['precision' => $precision, 'scale' => 2,];
    $existingData = [];



    foreach ($tables as $table) {
      $existingData[$table] = $database->select($table)
        ->fields($table)
        ->execute()
        ->fetchAll(\PDO::FETCH_ASSOC);

      $database->truncate($table)->execute();
    }

    $config = \Drupal::service('config.factory')->getEditable('field.storage.node'.$field);
    $config->set('settings', $settings)->save();
    $fieldStorage = FieldStorageConfig::loadByName('node', $field);
    $fieldStorage->set('settings', $settings);
    $fieldStorage->save();
    $this->entityTypeManager->clearCachedDefinitions();

    // Restore the data.
    foreach ($tables as $table) {
      $insert_query = $database
        ->insert($table)
        ->fields(array_keys(end($existingData[$table])));
      foreach ($existingData[$table] as $row) {
        $insert_query->values(array_values($row));
      }
      $insert_query->execute();
    }
  }
}