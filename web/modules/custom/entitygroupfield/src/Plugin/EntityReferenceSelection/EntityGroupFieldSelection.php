<?php

namespace Drupal\entitygroupfield\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Provides specific access control for group entities.
 *
 * @EntityReferenceSelection(
 *   id = "group:entitygroupfield",
 *   label = @Translation("Groups"),
 *   entity_types = {"group"},
 *   group = "group",
 *   weight = 1
 * )
 */
class EntityGroupFieldSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $configuration = $this->getConfiguration();
    $target_type = $configuration['target_type'];
    $entity_type = $this->entityTypeManager->getDefinition($target_type);

    $query = parent::buildEntityQuery($match, $match_operator);
    if (!empty($configuration['excluded_groups'])) {
      $query->condition($entity_type->getKey('id'), $configuration['excluded_groups'], 'NOT IN');
    }

    return $query;
  }

}
