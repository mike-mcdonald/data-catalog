<?php

namespace Drupal\entitygroupfield\Field;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\group\Entity\GroupContent;

/**
 * A computed property for the related groups.
 */
class EntityGroupFieldItemList extends EntityReferenceFieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  public function computeValue() {
    // No value will exist if the entity has not been created, so exit early.
    if ($this->getEntity()->isNew()) {
      return NULL;
    }

    // If this entity/bundle has no group content enabler plugins enabled,
    // there's no way there could be any group associations, so exit early.
    if (!entitygroupfield_get_group_content_plugin_ids($this->getEntity()->getEntityTypeId(), $this->getEntity()->bundle())) {
      return NULL;
    }

    $group_contents = GroupContent::loadByEntity($this->getEntity());
    if (empty($group_contents)) {
      return NULL;
    }

    $this->list = [];
    foreach ($group_contents as $delta => $group_content) {
      $this->list[] = $this->createItem($delta, [
        'target_id' => $group_content->id(),
      ]);
    }
  }

  /**
   * {@inheritdoc}
   *
   * We need to override the presave to avoid saving without the host entity id
   * generated.
   */
  public function preSave() {
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    if ($this->valueComputed) {
      $host_entity = $this->getEntity();
      $group_content_ids = [];
      foreach ($this->getIterator() as $delta => $item) {
        $group_content_entity = $item->entity;
        $group_content_entity->entity_id = $host_entity->id();
        // Saving entities.
        if (isset($item->needs_save)) {
          $group_content_entity->save();
        }
        $group_content_ids[] = $group_content_entity->id();
      }
      // Deleting entities.
      $group_contents = GroupContent::loadByEntity($host_entity);
      foreach ($group_contents as $group_content_id => $group_content_entity) {
        if (!in_array($group_content_id, $group_content_ids)) {
          $group_content_entity->delete();
        }
      }
    }
    return parent::postSave($update);
  }

}
