<?php

namespace Drupal\entitygroupfield\Plugin\Field\FieldWidget;

/**
 * Plugin implementation of the 'entitygroupfield_select_widget' widget.
 *
 * @FieldWidget(
 *   id = "entitygroupfield_select_widget",
 *   label = @Translation("Group select"),
 *   field_types = {
 *     "entitygroupfield"
 *   }
 * )
 */
class EntityGroupFieldSelectWidget extends EntityGroupFieldWidgetBase {

  /**
   * {@inheritdoc}
   */
  protected function buildAddElement($entity_plugin_id, array $existing_gcontent) {
    // Get the list of all allowed groups, given the circumstances.
    $allowed_groups = $this->getAllowedGroups($entity_plugin_id, $existing_gcontent);

    // If there are no available groups, don't build a form element.
    if (empty($allowed_groups)) {
      return [];
    }

    return [
      '#type' => 'select',
      '#title' => $this->getSetting('label'),
      '#description' => $this->getSetting('help_text'),
      '#options' => $allowed_groups,
      '#empty_option' => $this->t('- Select a group -'),
      '#empty_value' => '',
    ];
  }

  /**
   * Gets a list of groups with a specific plugin installed.
   *
   * @param string $plugin_id
   *   The plugin ID to filter the groups.
   *
   * @return \Drupal\group\Entity\GroupInterface[]
   *   The list of group entities.
   */
  protected function getPluginGroups($plugin_id) {
    $group_types = $this->getPluginGroupTypes($plugin_id);
    return empty($group_types) ? [] : $this->entityTypeManager
      ->getStorage('group')->loadByProperties(['type' => $group_types]);
  }

  /**
   * Gets allowed group options for a select form element.
   *
   * @param string $entity_plugin_id
   *   The plugin ID to get existing content.
   * @param array $existing_gcontent
   *   The existing group content.
   *
   * @return array
   *   Allowed groups options using optgroup for the group types.
   */
  protected function getAllowedGroups($entity_plugin_id, array $existing_gcontent) {
    $groups = $this->getPluginGroups($entity_plugin_id);
    // If there are no groups with the plugin enabled, return early.
    if (empty($groups)) {
      return [];
    }

    $allowed_groups = [];
    $all_restricted = TRUE;
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = $this->currentUser->getAccount();

    $excluded_groups = [];
    if ($existing_gcontent) {
      foreach ($existing_gcontent as $gcontent) {
        // Do not count the content if it was removed.
        if ($gcontent['mode'] == 'removed') {
          continue;
        }
        if (isset($gcontent['entity'])) {
          $excluded_groups[] = $gcontent['entity']->gid->getString();
        }
      }
    }

    /** @var \Drupal\group\Entity\Group $group */
    foreach ($groups as $group) {
      if (in_array($group->id(), $excluded_groups)) {
        continue;
      }
      // Check creation permissions.
      $can_create = FALSE;
      if ($entity_plugin_id == 'group_membership') {
        $can_create = $group->hasPermission("administer members", $account);
      }
      if (!$can_create) {
        $can_create = $group->hasPermission("create $entity_plugin_id entity", $account);
      }
      if ($can_create) {
        $all_restricted = FALSE;
        $group_bundle = $group->bundle();
        $group_bundle_label = $group->getGroupType()->label();
        $allowed_groups[$group_bundle_label][$group->id()] = $this->entityRepository->getTranslationFromContext($group)->label();
      }
    }

    return $allowed_groups;
  }

}
