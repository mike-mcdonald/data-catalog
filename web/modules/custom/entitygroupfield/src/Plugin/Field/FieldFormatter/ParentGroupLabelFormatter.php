<?php

namespace Drupal\entitygroupfield\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;

/**
 * Plugin implementation of the 'parent_group_label_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "parent_group_label_formatter",
 *   label = @Translation("Parent group label"),
 *   description = @Translation("Display the label of the parent groups."),
 *   field_types = {
 *     "entitygroupfield"
 *   }
 * )
 */
class ParentGroupLabelFormatter extends EntityReferenceLabelFormatter {

  use ParentGroupFormatterTrait;

}
