<?php

namespace Drupal\gcontent_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceIdFormatter;

/**
 * Plugin implementation of the 'parent_group_id_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "parent_group_id_formatter",
 *   label = @Translation("Parent group ID"),
 *   description = @Translation("Display the ID of the parent groups."),
 *   field_types = {
 *     "group_content"
 *   }
 * )
 */
class ParentGroupIdFormatter extends EntityReferenceIdFormatter {

  use ParentGroupFormatterTrait;

}
