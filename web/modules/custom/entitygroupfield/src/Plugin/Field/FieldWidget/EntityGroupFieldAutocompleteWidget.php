<?php

namespace Drupal\entitygroupfield\Plugin\Field\FieldWidget;

/**
 * Plugin implementation of the 'entitygroupfield_autocomplete_widget' widget.
 *
 * @FieldWidget(
 *   id = "entitygroupfield_autocomplete_widget",
 *   label = @Translation("Group autocomplete"),
 *   field_types = {
 *     "entitygroupfield"
 *   }
 * )
 */
class EntityGroupFieldAutocompleteWidget extends EntityGroupFieldWidgetBase {

  /**
   * {@inheritdoc}
   */
  protected function buildAddElement($entity_plugin_id, array $existing_gcontent) {
    $excluded_groups = [];
    foreach ($existing_gcontent as $gcontent) {
      // Don't count the content if was removed.
      if ($gcontent['mode'] === 'removed') {
        continue;
      }
      if (!empty($gcontent['entity'])) {
        $excluded_groups[] = $gcontent['entity']->gid->getString();
      }
    }
    return [
      '#type' => 'group_autocomplete',
      '#title' => $this->getSetting('label'),
      '#description' => $this->getSetting('help_text'),
      '#selection_settings' => [
        'excluded_groups' => $excluded_groups,
        'target_bundles' => $this->getPluginGroupTypes($entity_plugin_id),
      ],
    ];
  }

}
