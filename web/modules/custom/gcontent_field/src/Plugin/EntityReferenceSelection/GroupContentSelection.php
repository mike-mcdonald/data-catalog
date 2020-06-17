<?php

namespace Drupal\gcontent_field\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Provides specific access control for the comment entity type.
 *
 * @EntityReferenceSelection(
 *   id = "group:group_content",
 *   label = @Translation("Groups to add content"),
 *   entity_types = {"group"},
 *   group = "group",
 *   weight = 1
 * )
 */
class GroupContentSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $allowed_groups = $this->getConfiguration()['allowed_groups'];
    $options = [];

    foreach ($allowed_groups as $bundle => $groups) {
      foreach ($groups as $group_id => $group_name) {
        if (preg_match('/' . $match . '/i', $group_name)) {
          $options[$bundle][$group_id] = $group_name;
        }
      }
    }

    return $options;
  }

}
