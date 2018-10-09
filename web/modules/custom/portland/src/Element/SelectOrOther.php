<?php

namespace Drupal\portland\Element;

use Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form element with a select box and other option.
 *
 * @see ElementBase
 *
 * @FormElement("portland_select_or_other")
 */
class SelectOrOther extends FormElement {

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#process' => [[$class, 'processSelectOrOther']],
      '#multiple' => FALSE,
      '#theme_wrappers' => ['form_element'],
      '#options' => [],
      '#target_type' => NULL,
      '#selection_handler' => 'default',
      '#selection_settings' => [],
      '#autocreate' => NULL,
      '#element_validate' => [[$class, 'validateSelectOrOther']],
      '#tree' => TRUE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function processSelectOrOther(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['select'] = [
      '#type' => 'select',
      '#default_value' => $element['#default_value'],
      '#required' => $element['#required'],
      '#multiple' => $element['#multiple'],
      '#options' => self::addOtherOption($element['#options']),
      '#weight' => 10,
    ];

    $element['other'] = [
      '#type' => 'textfield',
      '#weight' => 20,
      '#states' => [
        'visible' => [
          'select[name="' . $element['#name'] . '[select]' . '"]' => ['value' => '_other'],
        ],
      ],
    ];

    return $element;
  }

  /**
   * Adds an 'other' option to the selectbox.
   */
  protected static function addOtherOption($options) {
    $options['_other'] = t('Other');

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $values = [];
    if ($input !== FALSE && !empty($input['select'])) {
      if ($input['select'] === '_other') {
        $values = [$input['other']];
        // Add the other option to the available options to prevent
        // validation errors.
        $element['#options'][$input['other']] = $input['other'];
      }
      else {
        $values = [$input['select']];
      }
    }

    return $values;
  }

  /**
   * Form element validation handler for portland_select_or_other elements.
   */
  public static function validateSelectOrOther(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $value = NULL;

    if (!empty($element['#value'])) {
      $options = $element['#selection_settings'] + [
        'target_type' => $element['#target_type'],
        'handler' => $element['#selection_handler'],
      ];
      /** @var /Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface $handler */
      $handler = \Drupal::service('plugin.manager.entity_reference_selection')->getInstance($options);
      $autocreate = (bool) $element['#autocreate'] && $handler instanceof SelectionWithAutocreateInterface;

      // GET forms might pass the validated data around on the next request, in
      // which case it will already be in the expected format.
      if (is_array($element['#value'])) {
        $value = $element['#value'];
      }
      else {
        $input_values = [$element['#value']];

        foreach ($input_values as $input) {

          if ($autocreate) {
            /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface $handler */
            // Auto-create item. See an example of how this is handled in
            // \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem::presave().
            $value[] = [
              'entity' => $handler->createNewEntity($element['#target_type'], $element['#autocreate']['bundle'], $input, $element['#autocreate']['uid']),
            ];
          }
        }
      }
    }

    $form_state->setValueForElement($element, $value);
  }
}
