<?php

namespace Drupal\entitygroupfield\Plugin\Field\FieldWidget;

/**
 * Plugin implementation of the deprecated 'group_selector_widget' widget.
 *
 * @deprecated in entitygroupfield:0.0.0 and is removed from
 *   entitygroupfield:1.0.0. Use either 'entitygroupfield_select_widget' or
 *   'entitygroupfield_autocomplete_widget' instead.
 * @see https://www.drupal.org/project/entitygroupfield/issues/3153401
 *
 * @FieldWidget(
 *   id = "group_selector_widget",
 *   label = @Translation("(Deprecated) Group selector"),
 *   field_types = {
 *     "group_content"
 *   }
 * )
 */
class GroupSelectorWidget extends EntityGroupFieldSelectWidget {

}
