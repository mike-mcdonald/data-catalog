<?php

namespace Drupal\gcontent_field\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

/**
 * Plugin implementation of the 'group_content' field type.
 *
 * @FieldType(
 *   id = "group_content",
 *   label = @Translation("Groups"),
 *   description = @Translation("This is a computed field to relate content with groups"),
 *   default_widget = "group_selector_widget",
 *   default_formatter = "parent_group_entity_formatter",
 *   list_class = "\Drupal\gcontent_field\Field\GcontentFieldItemList",
 * )
 */
class GroupContentItem extends EntityReferenceItem {

}
