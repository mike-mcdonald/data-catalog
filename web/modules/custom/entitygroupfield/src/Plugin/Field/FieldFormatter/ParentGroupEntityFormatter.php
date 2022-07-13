<?php

namespace Drupal\entitygroupfield\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'parent_group_entity_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "parent_group_entity_formatter",
 *   label = @Translation("Parent group rendered entity"),
 *   description = @Translation("Display the parent groups rendered by entity_view()."),
 *   field_types = {
 *     "entitygroupfield"
 *   }
 * )
 */
class ParentGroupEntityFormatter extends EntityReferenceEntityFormatter {

  use ParentGroupFormatterTrait;

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['view_mode'] = [
      '#type' => 'select',
      '#options' => $this->entityDisplayRepository->getViewModeOptions('group'),
      '#title' => t('View mode'),
      '#default_value' => $this->getSetting('view_mode'),
      '#required' => TRUE,
    ];

    return $elements;
  }

}
