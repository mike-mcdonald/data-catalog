<?php

namespace Drupal\entitygroupfield\Element;

use Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;

/**
 * Provides a group autocomplete form element.
 *
 * @FormElement("group_autocomplete")
 *
 * Properties:
 * - #default_value: Either an entity object or an array of entity objects.
 * - #selection_settings: Array of settings to pass to the entity reference
 *   selection handler ('group:entitygroupfield'), which supports this key:
 *   - excluded_groups: Array of group IDs to exclude (if any).
 *   Plus keys from EntityReferenceSelection\DefaultSelection, such as:
 *   - target_bundles: Array of group type IDs to require (if any).
 *   - sort: Nested array of sort options, including 'field' and 'direction'.
 *
 * Usage example:
 * @code
 * $form['group_name'] = [
 *   '#type' => 'group_autocomplete',
 *   '#title' => 'Group autocomplete example',
 *   '#selection_settings' => [
 *     // Exclude group IDs 1 and 2 from the choices.
 *     'excluded_groups' => [1, 2],
 *     // Use only two specific group types. If empty, all types are allowed.
 *     'target_bundles' => ['default', 'special'],
 *     // Sort suggestions alphabetically by group label.
 *     'sort' => [
 *       'field' => 'label',
 *       'direction' => 'ASC',
 *     ],
 *   ],
 * ];
 * @endcode
 *
 * @see Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection
 * @see Drupal\entitygroupfield\Plugin\EntityReferenceSelection\GroupContentSelection
 * @see Drupal\Core\Entity\Element\EntityAutocomplete
 */
class GroupAutocomplete extends EntityAutocomplete {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    // Apply default form element properties.
    $info['#target_type'] = 'group';
    $info['#selection_handler'] = 'group:entitygroupfield';
    return $info;
  }

  /**
   * {@inheritdoc}
   */
  protected static function matchEntityByTitle(SelectionInterface $handler, $input, array &$element, FormStateInterface $form_state, $strict) {
    $groups_by_bundle = $handler->getReferenceableEntities($input, 'CONTAINS', 6);
    $groups = array_reduce($groups_by_bundle, function ($flattened, $bundle_groups) {
      return $flattened + $bundle_groups;
    }, []);

    $params = [
      '%value' => $input,
    ];
    if (empty($groups)) {
      if ($strict) {
        // Error if there are no groups available for a required field.
        $form_state->setError($element, t('There are no groups called "%value".', $params));
      }
    }
    elseif (count($groups) > 5) {
      $id = key($groups);
      $name = reset($groups);
      $params['%specific'] = "$name ($id)";
      // Error if there are more than 5 matching groups.
      $form_state->setError($element, t('Many groups are called "%value". Pick one by appending the ID in parentheses, for example: %specific', $params));
    }
    elseif (count($groups) > 1) {
      // More helpful error if there are only a few matching groups.
      $multiples = [];
      foreach ($groups as $id => $name) {
        $multiples[] = $name . ' (' . $id . ')';
      }
      $params['%specific'] = "$name ($id)";
      $form_state->setError($element, t('Multiple groups match: %multiple. Pick one by appending the ID in parentheses, for example: %specific', ['%multiple' => implode(', ', $multiples)] + $params));
    }
    else {
      // Take the one and only matching entity.
      return key($groups);
    }
  }

}
