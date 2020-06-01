<?php

namespace Drupal\gcontent_field\Field;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\group\Entity\GroupContent;

/**
 * A computed property for the related groups.
 */
class GcontentFieldItemList extends EntityReferenceFieldItemList {

  // Support non-database views. Ex: Search API Solr.
  use DependencySerializationTrait;
  use ComputedItemListTrait;

  /**
   * The Group Content Plugin Manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $groupContentPluginManager;

  /**
   * Constructs a GcontentFieldItemList object.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The data definition.
   * @param string $name
   *   (optional) The name of the created property, or NULL if it is the root
   *   of a typed data tree. Defaults to NULL.
   * @param \Drupal\Core\TypedData\TypedDataInterface $parent
   *   (optional) The parent object of the data property, or NULL if it is the
   *   root of a typed data tree. Defaults to NULL.
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    $this->groupContentPluginManager = \Drupal::service('plugin.manager.group_content_enabler');
  }

  /**
   * {@inheritdoc}
   */
  public function computeValue() {
    $plugin_id = $this->groupContentPluginManager->getPluginIdByEntityType($this->getEntity()->getEntityTypeId(), $this->getEntity()->bundle());
    if (!$plugin_id) {
      return NULL;
    }
    // No value will exist if the entity has not been created so exit early.
    if ($this->getEntity()->isNew()) {
      return NULL;
    }

    $group_contents = GroupContent::loadByEntity($this->getEntity());
    if (empty($group_contents)) {
      return NULL;
    }

    $this->list = [];
    if (!empty($group_contents)) {
      foreach ($group_contents as $delta => $group_content) {
        $this->list[] = $this->createItem($delta, [
          'target_id' => $group_content->id(),
        ]);
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * We need to override the presave to avoid gcontent saving without
   * host entity id generated.
   */
  public function preSave() {
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    if ($this->valueComputed) {
      $host_entity = $this->getEntity();
      $gcontent_ids = [];
      foreach ($this->getIterator() as $delta => $item) {
        $gcontent_entity = $item->entity;
        $gcontent_entity->entity_id = $host_entity->id();
        // Saving entities.
        if (isset($item->needs_save)) {
          $gcontent_entity->save();
        }
        $gcontent_ids[] = $gcontent_entity->id();
      }
      // Deleting entities.
      $group_contents = GroupContent::loadByEntity($host_entity);
      foreach ($group_contents as $gcontent_id => $gcontent_entity) {
        if (!in_array($gcontent_id, $gcontent_ids)) {
          $gcontent_entity->delete();
        }
      }
    }
    return parent::postSave($update);
  }

}
