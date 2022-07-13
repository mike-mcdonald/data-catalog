<?php

/**
 * @file
 * Entity Group Field tokens.
 */

/**
 * Implements hook_token_info().
 */
function entitygroupfield_token_info() {
  if ($entity_types = entitygroupfield_get_entity_types()) {
    $field_name = 'entitygroupfield';
    $entity_types_ids = array_keys($entity_types);
    foreach ($entity_types_ids as $entity_type_id) {
      $token_type = $entity_type_id;
      $field_token_name = $token_type . '-' . $field_name;
      $info['tokens'][$token_type][$field_name] = [
        'name' => 'Entity Groups',
        'description' => 'Entity reference field.',
        'module' => 'token',
        // For multivalue fields the field token is a list type.
        'type' => "list<$field_token_name>",
      ];

      // Field token type.
      $info['types'][$field_token_name] = [
        'name' => 'Entity Groups',
        'description' => t('@label tokens.', ['@label' => 'Entity Groups']),
        'needs-data' => $field_token_name,
        'nested' => TRUE,
      ];
      // Field list token type.
      $info['types']["list<$field_token_name>"] = [
        'name' => t('List of @type values', ['@type' => 'Entity Groups']),
        'description' => t('Tokens for lists of @type values.', ['@type' => 'Entity Groups']),
        'needs-data' => "list<$field_token_name>",
        'nested' => TRUE,
      ];

      // Show a different token for each field delta.
      for ($delta = 0; $delta < 3; $delta++) {
        $info['tokens']["list<$field_token_name>"][$delta] = [
          'name' => t('@type type with delta @delta', ['@type' => 'Entity Groups', '@delta' => $delta]),
          'module' => 'token',
          'type' => $field_token_name,
        ];
      }

      $info['tokens'][$field_token_name]['entity'] = [
        'name' => 'Entity Group',
        'description' => 'The referenced entity',
        'module' => 'token',
      ];
      $info['tokens'][$field_token_name]['target_id'] = [
        'name' => 'Entity Group ID',
        'description' => 'The referenced entity ID',
        'module' => 'token',
      ];
    }
    return $info;
  }
}
