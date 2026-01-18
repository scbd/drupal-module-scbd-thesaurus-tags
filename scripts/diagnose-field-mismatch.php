<?php

/**
 * @file
 * Diagnostic script to identify field_tags entity/field definition mismatches.
 * 
 * Run with: drush @SITE php:script scripts/diagnose-field-mismatch.php
 */

use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

echo "=== Field Tags Diagnostic Report ===\n";
echo "Site: " . \Drupal::config('system.site')->get('name') . "\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

$connection = \Drupal::database();
$schema = $connection->schema();
$entity_type_manager = \Drupal::entityTypeManager();
$field_storage_manager = \Drupal::entityTypeManager()->getStorage('field_storage_config');
$entity_field_manager = \Drupal::service('entity_field.manager');
$last_installed_schema_repository = \Drupal::service('entity.last_installed_schema.repository');

// Entity types to check
$entity_types = ['node', 'comment'];
$field_name = 'field_tags';

foreach ($entity_types as $entity_type_id) {
  echo "=== {$entity_type_id}.{$field_name} ===\n\n";
  
  // 1. Check if field storage config exists
  $field_storage_id = "{$entity_type_id}.{$field_name}";
  $field_storage = $field_storage_manager->load($field_storage_id);
  
  echo "1. Field Storage Config:\n";
  if ($field_storage) {
    echo "   - Status: EXISTS\n";
    echo "   - Type: " . $field_storage->getType() . "\n";
    echo "   - Cardinality: " . $field_storage->getCardinality() . "\n";
    
    // Get the schema columns from the field storage definition
    $schema_def = $field_storage->getSchema();
    echo "   - Schema Columns: " . implode(', ', array_keys($schema_def['columns'] ?? [])) . "\n";
    
    // Check for any third party settings that might be stale
    $third_party = $field_storage->getThirdPartySettings();
    if (!empty($third_party)) {
      echo "   - Third Party Settings: " . json_encode($third_party) . "\n";
    }
  } else {
    echo "   - Status: NOT FOUND\n";
    continue;
  }
  
  echo "\n";
  
  // 2. Check database tables
  $data_table = "{$entity_type_id}__{$field_name}";
  $revision_table = "{$entity_type_id}_revision__{$field_name}";
  
  echo "2. Database Tables:\n";
  
  if ($schema->tableExists($data_table)) {
    echo "   - {$data_table}: EXISTS\n";
    
    // Get actual columns in the table
    try {
      $result = $connection->query("DESCRIBE {$data_table}")->fetchAllAssoc('Field');
      $columns = array_keys($result);
      echo "     Columns: " . implode(', ', $columns) . "\n";
      
      // Check specifically for field_tags columns
      $value_col = "{$field_name}_value";
      $value2_col = "{$field_name}_value2";
      echo "     - {$value_col}: " . (isset($result[$value_col]) ? "EXISTS ({$result[$value_col]->Type})" : "MISSING") . "\n";
      echo "     - {$value2_col}: " . (isset($result[$value2_col]) ? "EXISTS ({$result[$value2_col]->Type})" : "MISSING") . "\n";
    } catch (\Exception $e) {
      echo "     Error reading columns: " . $e->getMessage() . "\n";
    }
  } else {
    echo "   - {$data_table}: NOT FOUND\n";
  }
  
  if ($schema->tableExists($revision_table)) {
    echo "   - {$revision_table}: EXISTS\n";
    
    try {
      $result = $connection->query("DESCRIBE {$revision_table}")->fetchAllAssoc('Field');
      $value_col = "{$field_name}_value";
      $value2_col = "{$field_name}_value2";
      echo "     - {$value_col}: " . (isset($result[$value_col]) ? "EXISTS" : "MISSING") . "\n";
      echo "     - {$value2_col}: " . (isset($result[$value2_col]) ? "EXISTS" : "MISSING") . "\n";
    } catch (\Exception $e) {
      echo "     Error: " . $e->getMessage() . "\n";
    }
  } else {
    echo "   - {$revision_table}: NOT FOUND\n";
  }
  
  echo "\n";
  
  // 3. Check last installed field storage definitions
  echo "3. Last Installed Schema:\n";
  
  try {
    $installed_storage_definitions = $last_installed_schema_repository->getLastInstalledFieldStorageDefinitions($entity_type_id);
    
    if (isset($installed_storage_definitions[$field_name])) {
      $installed_def = $installed_storage_definitions[$field_name];
      echo "   - Status: FOUND in last_installed\n";
      echo "   - Type: " . $installed_def->getType() . "\n";
      
      // Get the installed schema
      if (method_exists($installed_def, 'getSchema')) {
        $installed_schema = $installed_def->getSchema();
        echo "   - Installed Schema Columns: " . implode(', ', array_keys($installed_schema['columns'] ?? [])) . "\n";
      }
      
      // Compare with current definition
      $current_definitions = $entity_field_manager->getFieldStorageDefinitions($entity_type_id);
      if (isset($current_definitions[$field_name])) {
        $current_def = $current_definitions[$field_name];
        $current_schema = $current_def->getSchema();
        echo "   - Current Definition Columns: " . implode(', ', array_keys($current_schema['columns'] ?? [])) . "\n";
        
        // Compare columns
        $installed_cols = array_keys($installed_schema['columns'] ?? []);
        $current_cols = array_keys($current_schema['columns'] ?? []);
        
        $missing_in_installed = array_diff($current_cols, $installed_cols);
        $extra_in_installed = array_diff($installed_cols, $current_cols);
        
        if (!empty($missing_in_installed)) {
          echo "   - MISMATCH: Missing in installed: " . implode(', ', $missing_in_installed) . "\n";
        }
        if (!empty($extra_in_installed)) {
          echo "   - MISMATCH: Extra in installed: " . implode(', ', $extra_in_installed) . "\n";
        }
        if (empty($missing_in_installed) && empty($extra_in_installed)) {
          echo "   - Schema definitions MATCH\n";
        }
      }
    } else {
      echo "   - Status: NOT FOUND in last_installed\n";
    }
  } catch (\Exception $e) {
    echo "   - Error: " . $e->getMessage() . "\n";
  }
  
  echo "\n";
  
  // 4. Check key_value storage for field definitions
  echo "4. Key-Value Storage (entity.definitions.installed):\n";
  
  try {
    $key_value = \Drupal::keyValue('entity.definitions.installed');
    $stored_definitions = $key_value->get("{$entity_type_id}.field_storage_definitions");
    
    if ($stored_definitions && isset($stored_definitions[$field_name])) {
      $stored_def = $stored_definitions[$field_name];
      echo "   - Status: FOUND in key_value\n";
      
      // Get the stored schema
      if (method_exists($stored_def, 'getSchema')) {
        $stored_schema = $stored_def->getSchema();
        echo "   - Stored Schema Columns: " . implode(', ', array_keys($stored_schema['columns'] ?? [])) . "\n";
      } else {
        // It might be serialized differently
        echo "   - Definition type: " . get_class($stored_def) . "\n";
      }
    } else {
      echo "   - Status: NOT FOUND in key_value\n";
    }
  } catch (\Exception $e) {
    echo "   - Error: " . $e->getMessage() . "\n";
  }
  
  echo "\n";
}

// 5. Check scbd_field module schema version
echo "=== Module Status ===\n";
echo "scbd_field schema version: " . drupal_get_installed_schema_version('scbd_field') . "\n";

// 6. Run entity update check
echo "\n=== Entity Update Check ===\n";
try {
  $entity_definition_update_manager = \Drupal::entityDefinitionUpdateManager();
  $changes = $entity_definition_update_manager->getChangeList();
  
  if (empty($changes)) {
    echo "No entity definition changes detected.\n";
  } else {
    foreach ($changes as $entity_type => $entity_changes) {
      echo "{$entity_type}:\n";
      foreach ($entity_changes as $change_type => $change_data) {
        echo "  {$change_type}: " . json_encode($change_data) . "\n";
      }
    }
  }
} catch (\Exception $e) {
  echo "Error checking changes: " . $e->getMessage() . "\n";
}

echo "\n=== End Report ===\n";
