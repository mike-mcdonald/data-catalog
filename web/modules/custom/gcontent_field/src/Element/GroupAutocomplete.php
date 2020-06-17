<?php

namespace Drupal\gcontent_field\Element;

use Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;

/**
 * Provides a group autocomplete form element.
 *
 * The #default_value accepted by this element is either an entity object or an
 * array of entity objects.
 *
 * @FormElement("group_autocomplete")
 */
class GroupAutocomplete extends EntityAutocomplete {

  /**
   * {@inheritdoc}
   */
  protected static function matchEntityByTitle(SelectionInterface $handler, $input, array &$element, FormStateInterface $form_state, $strict) {
    $groups_by_bundle = $handler->getReferenceableEntities($input, '=', 6);
    $groups = array_reduce($groups_by_bundle, function ($flattened, $bundle_groups) {
      return $flattened + $bundle_groups;
    }, []);
    $params = [
      '%value' => $input,
      '@value' => $input,
    ];
    if (empty($groups)) {
      if ($strict) {
        // Error if there are no groups available for a required field.
        $form_state->setError($element, t('There are no groups called "%value".', $params));
      }
    }
    elseif (count($groups) > 5) {
      $params['@id'] = key($groups);
      // Error if there are more than 5 matching groups.
      $form_state->setError($element, t('Many groups are called %value. Pick one by appending the ID in parentheses, like "@value (@id)".', $params));
    }
    elseif (count($groups) > 1) {
      // More helpful error if there are only a few matching groups.
      $multiples = [];
      foreach ($groups as $id => $name) {
        $multiples[] = $name . ' (' . $id . ')';
      }
      $params['@id'] = $id;
      $form_state->setError($element, t('Multiple groups match: "%multiple". Pick one by appending the ID in parentheses, like "@value (@id)".', ['%multiple' => implode('", "', $multiples)] + $params));
    }
    else {
      // Take the one and only matching entity.
      return key($groups);
    }
  }

}
