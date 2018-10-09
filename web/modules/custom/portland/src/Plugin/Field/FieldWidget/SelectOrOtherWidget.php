<?php

namespace Drupal\portland\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'portland_select_or_other' widget.
 *
 * @FieldWidget(
 *   id = "portland_select_or_other",
 *   label = @Translation("Portland select or other"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = FALSE
 * )
 */
class SelectOrOtherWidget extends OptionsSelectWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $entity = $items->getEntity();

    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#type'] = 'portland_select_or_other';

    $element = $element + [
      '#target_type' => $this->getFieldSetting('target_type'),
      '#selection_handler' => $this->getFieldSetting('handler'),
      '#selection_settings' => $this->getFieldSetting('handler_settings'),
      '#autocreate' => [
        'bundle' => $this->getAutocreateBundle(),
        'uid' => ($entity instanceof EntityOwnerInterface) ? $entity->getOwnerId() : \Drupal::currentUser()
          ->id()
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $key => $value) {
      // The form element returns an array when an entity
      // was "autocreated", so we need to move it up a level.
      if (is_array($value['target_id'])) {
        unset($values[$key]['target_id']);
        $values[$key] += $value['target_id'];
      }
    }

    return $values;
  }

  /**
   * Returns the value of a setting for the entity reference selection handler.
   *
   * @todo This is shamelessly copied from EntityAutocomplete. We should
   * probably file a core issue to turn this into a trait. The same goes for
   * $this::getAutoCreateBundle()
   *
   * @param string $setting_name
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   *
   * @codeCoverageIgnore
   *   Ignore this function because it is a straight copy->paste.
   */
  protected function getSelectionHandlerSetting($setting_name) {
    $settings = $this->getFieldSetting('handler_settings');
    return isset($settings[$setting_name]) ? $settings[$setting_name] : NULL;
  }

  /**
   * Returns the name of the bundle which will be used for autocreated entities.
   *
   * @todo This is shamelessly copied from EntityAutocomplete. We should
   * probably file a core issue to turn this into a trait. The same goes for
   * $this::getSelectionHandlerSetting()
   *
   * @return string
   *   The bundle name.
   *
   * @codeCoverageIgnore
   *   Ignore this function because it is a straight copy->paste.
   */
  protected function getAutocreateBundle() {
    $bundle = NULL;
    if ($this->getSelectionHandlerSetting('auto_create')) {
      // If the 'target_bundles' setting is restricted to a single choice, we
      // can use that.
      if (($target_bundles = $this->getSelectionHandlerSetting('target_bundles')) && count($target_bundles) == 1) {
        $bundle = reset($target_bundles);
      }
      // Otherwise use the first bundle as a fallback.
      else {
        // @todo Expose a proper UI for choosing the bundle for autocreated
        // entities in https://www.drupal.org/node/2412569.
        $bundles = \Drupal::entityManager()
          ->getBundleInfo($this->getFieldSetting('target_type'));
        $bundle = key($bundles);
      }
    }

    return $bundle;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $options = $field_definition->getSettings();
    $handler_settings = isset($options['handler_settings']) ? $options['handler_settings'] : NULL;
    $handler = \Drupal::service('plugin.manager.entity_reference_selection')
      ->getInstance($options);
    return $handler instanceof SelectionWithAutocreateInterface
    && isset($handler_settings['auto_create'])
    && $handler_settings['auto_create'];
  }

}
