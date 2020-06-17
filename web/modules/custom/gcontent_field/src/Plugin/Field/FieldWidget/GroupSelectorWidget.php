<?php

namespace Drupal\gcontent_field\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityRepository;

/**
 * Plugin implementation of the 'group_selector_widget' widget.
 *
 * @FieldWidget(
 *   id = "group_selector_widget",
 *   label = @Translation("Group selector"),
 *   field_types = {
 *     "group_content"
 *   }
 * )
 */
class GroupSelectorWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The Group Content Plugin Manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $groupContentPluginManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepository
   */
  protected $entityRepository;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, GroupContentEnablerManagerInterface $group_content_plugin_manager, AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager, EntityRepository $entity_repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->groupContentPluginManager = $group_content_plugin_manager;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.group_content_enabler'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'widget' => 'autocomplete',
      'multiple' => TRUE,
      'required' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['widget'] = [
      '#type' => 'radios',
      '#title' => t('Widget'),
      '#default_value' => $this->getSetting('widget'),
      '#options' => [
        'select' => t('Select'),
        'autocomplete' => t('Autocomplete'),
      ],
    ];
    $elements['multiple'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Multiple'),
      '#default_value' => $this->getSetting('multiple'),
    ];
    $elements['required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required'),
      '#default_value' => $this->getSetting('required'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Widget: @widget', ['@widget' => $this->getSetting('widget')]);
    $summary[] = ($this->getSetting('multiple')) ? $this->t('Multiple: Yes') : $this->t('Multiple: No');
    $summary[] = ($this->getSetting('required')) ? $this->t('Required: Yes') : $this->t('Required: No');
    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\content_translation\Controller\ContentTranslationController::prepareTranslation()
   *   Uses a similar approach to populate a new translation.
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $account = $this->currentUser->getAccount();
    $field_name = $this->fieldDefinition->getName();
    $parents = $element['#field_parents'];
    $info = [];

    $gcontent_entity = NULL;
    $host = $items->getEntity();
    $widget_state = static::getWidgetState($parents, $field_name, $form_state);

    $target_type = $this->getFieldSetting('target_type');

    $item_mode = isset($widget_state['gcontent'][$delta]['mode']) ? $widget_state['gcontent'][$delta]['mode'] : 'edit';

    $show_must_be_saved_warning = !empty($widget_state['gcontent'][$delta]['show_warning']);

    if (isset($widget_state['gcontent'][$delta]['entity'])) {
      $gcontent_entity = $widget_state['gcontent'][$delta]['entity'];
    }
    elseif (isset($items[$delta]->entity)) {
      $gcontent_entity = $items[$delta]->entity;
      // We don't have a widget state yet, get from selector settings.
      $item_mode = isset($widget_state['gcontent'][$delta]['mode']) ? $widget_state['gcontent'][$delta]['mode'] : 'closed';
    }
    elseif (isset($widget_state['selected_bundle'])) {
      $entity_type = $this->entityTypeManager->getDefinition($target_type);
      $bundle_key = $entity_type->getKey('bundle');

      $gcontent_entity = $this->entityTypeManager->getStorage($target_type)->create([
        $bundle_key => $widget_state['selected_bundle'],
        'gid' => $widget_state['selected_group'],
      ]);
      $item_mode = 'edit';
    }

    if ($item_mode == 'collapsed') {
      $item_mode = 'closed';
    }

    if ($item_mode == 'closed') {
      // Validate closed gcontent and expand if needed.
      // @todo Consider recursion.
      $violations = $gcontent_entity->validate();
      $violations->filterByFieldAccess();
      if (count($violations) > 0) {
        $item_mode = 'edit';
        $messages = [];
        foreach ($violations as $violation) {
          $messages[] = $violation->getMessage();
        }
        $info['validation_error'] = [
          '#type' => 'status_messages',
          '#markup' => $this->t('@messages', ['@messages' => strip_tags(implode('\n', $messages))]),
          '#attributes' => ['class' => ['messages', 'messages--warning']],
        ];
      }
    }

    if ($gcontent_entity) {
      $group = $gcontent_entity->getGroup();
      $entity_plugin_id = isset($widget_state['entity_plugin_id']) ? $widget_state['entity_plugin_id'] : $gcontent_entity->getContentPlugin()->getPluginId();
      $element_parents = $parents;
      $element_parents[] = $field_name;
      $element_parents[] = $delta;
      $element_parents[] = 'subform';

      $id_prefix = implode('-', array_merge($parents, [$field_name, $delta]));
      $wrapper_id = Html::getUniqueId($id_prefix . '-item-wrapper');

      $element += [
        '#type' => 'container',
        '#element_validate' => [[$this, 'elementValidate']],
        '#gcontent_type' => $gcontent_entity->bundle(),
        'subform' => [
          '#type' => 'container',
          '#parents' => $element_parents,
        ],
      ];

      // Setting label if field is not multiple.
      if (!$this->getSetting('multiple')) {
        $element['label'] = [
          '#type' => 'label',
          '#title' => $this->fieldDefinition->getLabel(),
          '#weight' => -1000,
        ];
      }

      $element['#prefix'] = '<div id="' . $wrapper_id . '">';
      $element['#suffix'] = '</div>';

      // Check permissions.
      if ($entity_plugin_id == 'group_membership') {
        if ($items->getEntity()->id() == $account->id()) {
          $can_delete = $group->hasPermission("leave group", $account);
          $can_edit = $group->hasPermission("update own group_membership content", $account);
        }
        else {
          $can_delete = $group->hasPermission("administer members", $account);
          $can_edit = $group->hasPermission("administer members", $account);
        }
      }
      else {
        $can_delete = $host->isNew() ? FALSE : $group->hasPermission("delete any $entity_plugin_id content", $account);
        $can_edit = $group->hasPermission("update any $entity_plugin_id content", $account);
      }
      // Checking if can delete own.
      if (!$can_delete && $entity_plugin_id == 'group_membership') {
        if ($gcontent_entity->id() == $account->id()) {
          $can_delete = $group->hasPermission("leave group", $account);
        }
      }
      if (!$can_delete && $gcontent_entity->getOwnerId() == $account->id()) {
        $can_delete = $group->hasPermission("delete own $entity_plugin_id content", $account);
      }
      // Checking if can update own.
      if (!$can_edit) {
        $group_content_owner = $gcontent_entity->getOwnerId();
        // In case of membership the value to compare is the entity
        // instead owner.
        if ($entity_plugin_id == 'group_membership') {
          $group_content_owner = $gcontent_entity->getEntity()->id();
        }
        if ($group_content_owner == $account->id()) {
          $can_edit = $group->hasPermission("update own $entity_plugin_id content", $account);
        }
      }

      $item_bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($target_type);
      if (isset($item_bundles[$gcontent_entity->bundle()])) {
        $element['top'] = [
          '#type' => 'container',
          '#weight' => -500,
          '#attributes' => [
            'class' => [
              'gcontent-type-top',
            ],
          ],
        ];

        $element['top']['gcontent_type_title'] = [
          '#type' => 'container',
          '#weight' => 0,
          '#attributes' => [
            'class' => [
              'gcontent-type-title',
            ],
          ],
        ];

        $element['top']['gcontent_type_title']['info'] = [
          '#markup' => $gcontent_entity->getGroup()->label(),
        ];

        $actions = [];
        $links = [];

        // Hide the button when translating.
        if ($item_mode != 'remove') {
          $links['remove_button'] = [
            '#type' => 'submit',
            '#value' => $this->t('Remove'),
            '#name' => strtr($id_prefix, '-', '_') . '_remove',
            '#weight' => 501,
            '#submit' => [[get_class($this), 'gcontentItemSubmit']],
            '#limit_validation_errors' => [
              array_merge($parents, [$field_name, 'add_more']),
            ],
            '#delta' => $delta,
            '#ajax' => [
              'callback' => [get_class($this), 'itemAjax'],
              'wrapper' => $widget_state['ajax_wrapper_id'],
              'effect' => 'fade',
            ],
            '#access' => $can_delete,
            '#prefix' => '<li class="remove">',
            '#suffix' => '</li>',
            '#gcontent_mode' => 'remove',
          ];

        }

        if ($item_mode == 'edit') {

          if (isset($items[$delta]->entity)) {
            $links['collapse_button'] = [
              '#type' => 'submit',
              '#value' => $this->t('Collapse'),
              '#name' => strtr($id_prefix, '-', '_') . '_collapse',
              '#weight' => 499,
              '#submit' => [[get_class($this), 'gcontentItemSubmit']],
              '#delta' => $delta,
              '#limit_validation_errors' => [
                array_merge($parents, [$field_name, 'add_more']),
              ],
              '#ajax' => [
                'callback' => [get_class($this), 'itemAjax'],
                'wrapper' => $widget_state['ajax_wrapper_id'],
                'effect' => 'fade',
              ],
              '#access' => $can_edit,
              '#prefix' => '<li class="collapse">',
              '#suffix' => '</li>',
              '#gcontent_mode' => 'collapsed',
              '#gcontent_show_warning' => TRUE,
            ];
          }

          $info['remove_button_info'] = [
            '#type' => 'status_messages',
            '#markup' => $this->t('You are not allowed to remove this item.'),
            '#attributes' => ['class' => ['messages', 'messages--warning']],
            '#access' => !$can_delete,
          ];
        }
        elseif ($item_mode == 'closed') {
          $links['edit_button'] = [
            '#type' => 'submit',
            '#value' => $this->t('Edit'),
            '#name' => strtr($id_prefix, '-', '_') . '_edit',
            '#weight' => 500,
            '#submit' => [[get_class($this), 'gcontentItemSubmit']],
            '#limit_validation_errors' => [
              array_merge($parents, [$field_name, 'add_more']),
            ],
            '#delta' => $delta,
            '#ajax' => [
              'callback' => [get_class($this), 'itemAjax'],
              'wrapper' => $widget_state['ajax_wrapper_id'],
              'effect' => 'fade',
            ],
            '#access' => $can_edit,
            '#prefix' => '<li class="edit">',
            '#suffix' => '</li>',
            '#gcontent_mode' => 'edit',
          ];

          if ($show_must_be_saved_warning) {
            $info['must_be_saved_info'] = [
              '#type' => 'status_messages',
              '#message_list' => [
                'warning' => $this->t('You have unsaved changes on this item.'),
              ],
            ];
          }

          $info['edit_button_info'] = [
            '#type' => 'status_messages',
            '#message_list' => [
              'warning' => $this->t('You are not allowed to edit this item.'),
            ],
            '#access' => !$can_edit && $can_delete,
          ];

          $info['remove_button_info'] = [
            '#type' => 'status_messages',
            '#message_list' => [
              'warning' => $this->t('You are not allowed to remove this item.'),
            ],
            '#access' => !$can_delete && $can_edit,
          ];

          $info['edit_remove_button_info'] = [
            '#type' => 'status_messages',
            '#message_list' => [
              'warning' => $this->t('You are not allowed to edit or remove this item.'),
            ],
            '#access' => !$can_edit && !$can_delete,
          ];
        }
        elseif ($item_mode == 'remove') {

          $element['top']['gcontent_type_title']['info'] = [
            '#markup' => $this->t('Deleted: %group relation', ['%group' => $gcontent_entity->getGroup()->label()]),
          ];

          $links['confirm_remove_button'] = [
            '#type' => 'submit',
            '#value' => $this->t('Confirm removal'),
            '#name' => strtr($id_prefix, '-', '_') . '_confirm_remove',
            '#weight' => 503,
            '#submit' => [[get_class($this), 'gcontentItemSubmit']],
            '#limit_validation_errors' => [
              array_merge($parents, [$field_name, 'add_more']),
            ],
            '#delta' => $delta,
            '#ajax' => [
              'callback' => [get_class($this), 'itemAjax'],
              'wrapper' => $widget_state['ajax_wrapper_id'],
              'effect' => 'fade',
            ],
            '#prefix' => '<li class="confirm-remove">',
            '#suffix' => '</li>',
            '#gcontent_mode' => 'removed',
          ];

          $links['restore_button'] = [
            '#type' => 'submit',
            '#value' => $this->t('Restore'),
            '#name' => strtr($id_prefix, '-', '_') . '_restore',
            '#weight' => 504,
            '#submit' => [[get_class($this), 'gcontentItemSubmit']],
            '#limit_validation_errors' => [
              array_merge($parents, [$field_name, 'add_more']),
            ],
            '#delta' => $delta,
            '#ajax' => [
              'callback' => [get_class($this), 'itemAjax'],
              'wrapper' => $widget_state['ajax_wrapper_id'],
              'effect' => 'fade',
            ],
            '#prefix' => '<li class="restore">',
            '#suffix' => '</li>',
            '#gcontent_mode' => 'edit',
          ];
        }
        if (count($links)) {
          $show_links = 0;
          foreach ($links as $link_item) {
            if (!isset($link_item['#access']) || $link_item['#access']) {
              $show_links++;
            }
          }
          if ($show_links > 0) {

            $element['top']['links'] = $links;
            if ($show_links > 1) {
              $element['top']['links']['#theme_wrappers'] = ['dropbutton_wrapper', 'gc_field_dropbutton_wrapper'];
              $element['top']['links']['prefix'] = [
                '#markup' => '<ul class="dropbutton">',
                '#weight' => -999,
              ];
              $element['top']['links']['suffix'] = [
                '#markup' => '</li>',
                '#weight' => 999,
              ];
            }
            else {
              $element['top']['links']['#theme_wrappers'] = ['gc_field_dropbutton_wrapper'];
              foreach ($links as $key => $link_item) {
                unset($element['top']['links'][$key]['#prefix']);
                unset($element['top']['links'][$key]['#suffix']);
              }
            }
            $element['top']['links']['#weight'] = 2;
          }
        }

        if (count($info)) {
          $show_info = FALSE;
          foreach ($info as $info_item) {
            if (!isset($info_item['#access']) || $info_item['#access']) {
              $show_info = TRUE;
              break;
            }
          }

          if ($show_info) {
            $element['info'] = $info;
            $element['info']['#weight'] = 998;
          }
        }

        if (count($actions)) {
          $show_actions = FALSE;
          foreach ($actions as $action_item) {
            if (!isset($action_item['#access']) || $action_item['#access']) {
              $show_actions = TRUE;
              break;
            }
          }

          if ($show_actions) {
            $element['actions'] = $actions;
            $element['actions']['#type'] = 'actions';
            $element['actions']['#weight'] = 999;
          }
        }
      }

      $display = EntityFormDisplay::collectRenderDisplay($gcontent_entity, $this->getSetting('form_display_mode'));

      if ($item_mode == 'edit') {
        $display->buildForm($gcontent_entity, $element['subform'], $form_state);
        // Fixing subform pathauto states.
        if (isset($element['subform']['path']['widget'][0]['pathauto'])) {
          $selector = sprintf('input[name="%s[%d][subform][path][0][%s]"]', $field_name, $element['#delta'], 'pathauto');
          $element['subform']['path']['widget'][0]['alias']['#states'] = [
            'disabled' => [
              $selector => ['checked' => TRUE],
            ],
          ];
        }
      }
      else {
        $element['subform'] = [];
      }
      $element['subform']['entity_id']['#access'] = FALSE;
      $element['subform']['#attributes']['class'][] = 'gcontent-subform';
      $element['subform']['#access'] = $can_edit;

      if ($item_mode == 'removed') {
        $element['#access'] = FALSE;
      }

      $widget_state['gcontent'][$delta]['entity'] = $gcontent_entity;
      $widget_state['gcontent'][$delta]['display'] = $display;
      $widget_state['gcontent'][$delta]['mode'] = $item_mode;

      static::setWidgetState($parents, $field_name, $form_state, $widget_state);
    }
    else {
      $element['#access'] = FALSE;
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    if ($form_state->get('group_wizard_id')) {
      return [];
    }
    $field_name = $this->fieldDefinition->getName();
    $cardinality = ($this->getSetting('multiple')) ? FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED : 1;
    $is_required = $this->getSetting('required');
    $this->fieldParents = $form['#parents'];
    $field_state = static::getWidgetState($this->fieldParents, $field_name, $form_state);

    $host = $items->getEntity();
    $target_entity_type = $host->getEntityTypeId();
    $target_bundle = $host->bundle();
    $entity_plugin_id = $this->groupContentPluginManager->getPluginIdByEntityType($target_entity_type, $target_bundle);
    $field_state = static::getWidgetState($this->fieldParents, $field_name, $form_state);
    $field_state['entity_plugin_id'] = $entity_plugin_id;
    static::setWidgetState($this->fieldParents, $field_name, $form_state, $field_state);

    $max = $field_state['items_count'];

    $this->realItemCount = $max;
    $is_multiple = $this->getSetting('multiple');

    $title = $this->fieldDefinition->getLabel();
    $description = $this->fieldDefinition->getDescription();

    $elements = [];
    $this->fieldIdPrefix = implode('-', array_merge($this->fieldParents, [$field_name]));
    $this->fieldWrapperId = Html::getUniqueId($this->fieldIdPrefix . '-add-more-wrapper');
    $elements['#prefix'] = '<div id="' . $this->fieldWrapperId . '">';
    $elements['#suffix'] = '</div>';

    $field_state = static::getWidgetState($this->fieldParents, $field_name, $form_state);
    $field_state['ajax_wrapper_id'] = $this->fieldWrapperId;
    // Persist the widget state so formElement() can access it.
    static::setWidgetState($this->fieldParents, $field_name, $form_state, $field_state);

    if ($max > 0) {
      for ($delta = 0; $delta < $max; $delta++) {

        // Add a new empty item if it doesn't exist yet at this delta.
        if (!isset($items[$delta])) {
          $items->appendItem();
        }

        // For multiple fields, title and description are handled by the
        // wrapping table.
        $element = [
          '#title' => $is_multiple ? '' : $title,
          '#description' => $is_multiple ? '' : $description,
        ];
        $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);

        if ($element) {
          // Input field for the delta (drag-n-drop reordering).
          if ($is_multiple) {
            // We name the element '_weight' to avoid clashing with elements
            // defined by widget.
            $element['_weight'] = [
              '#type' => 'weight',
              '#title' => $this->t('Weight for row @number', ['@number' => $delta + 1]),
              '#title_display' => 'invisible',
              // This 'delta' is the FAPI #type 'weight' element's property.
              '#delta' => $max,
              '#default_value' => $items[$delta]->_weight ?: $delta,
              '#weight' => 100,
            ];
          }

          // Access for the top element is set to FALSE only when the gcontent
          // was removed. A gcontent that a user can not edit has access on
          // lower level.
          if (isset($element['#access']) && !$element['#access']) {
            $this->realItemCount--;
          }
          else {
            $elements[$delta] = $element;
          }
        }
      }
    }

    $field_state = static::getWidgetState($this->fieldParents, $field_name, $form_state);
    $field_state['real_item_count'] = $this->realItemCount;
    static::setWidgetState($this->fieldParents, $field_name, $form_state, $field_state);

    $elements += [
      '#element_validate' => [[$this, 'multipleElementValidate']],
      '#required' => $is_required,
      '#field_name' => $field_name,
      '#cardinality' => $cardinality,
      '#max_delta' => $max - 1,
    ];

    if ($this->realItemCount > 0) {
      $elements += [
        '#theme' => 'field_multiple_value_form',
        '#cardinality_multiple' => $is_multiple,
        '#title' => $title,
        '#description' => $description,
      ];
    }
    else {
      $classes = $is_required ? ['form-required'] : [];
      $elements += [
        '#type' => 'container',
        '#theme_wrappers' => ['container'],
        '#cardinality_multiple' => TRUE,
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'strong',
          '#value' => $title,
          '#attributes' => ['class' => $classes],
        ],
        'text' => [
          '#type' => 'container',
          'value' => [
            '#markup' => $this->t('Not yet added to groups.'),
            '#prefix' => '<em>',
            '#suffix' => '</em>',
          ],
        ],
      ];

      if ($is_required) {
        $elements['title']['#attributes']['class'][] = 'form-required';
      }

      if ($description) {
        $elements['description'] = [
          '#type' => 'container',
          'value' => ['#markup' => $description],
          '#attributes' => ['class' => ['description']],
        ];
      }
    }

    $groups = $this->getPluginGroups($entity_plugin_id);
    // Getting groups cardinality.
    if (!isset($field_state['groups_cardinality'])) {
      $groups_cardinality = $this->getGroupsCardinality($groups, $entity_plugin_id);
      $field_state = static::getWidgetState($this->fieldParents, $field_name, $form_state);
      $field_state['groups_cardinality'] = $groups_cardinality;
      static::setWidgetState($this->fieldParents, $field_name, $form_state, $field_state);
    }
    $existing_gcontent = isset($field_state['gcontent']) ? $field_state['gcontent'] : [];
    $allowed_groups = $this->getAllowedGroups($groups, $entity_plugin_id, $existing_gcontent, $field_state['groups_cardinality']);

    if (($this->realItemCount < $cardinality || $cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) && !$form_state->isProgrammed()) {
      $elements['add_more'] = $this->buildAddActions($allowed_groups, $entity_plugin_id);
    }
    $elements['#attached']['library'][] = 'gcontent_field/gcontent_field.admin';

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function elementValidate($element, FormStateInterface $form_state, $form) {
    $field_name = $this->fieldDefinition->getName();
    $widget_state = static::getWidgetState($element['#field_parents'], $field_name, $form_state);
    $delta = $element['#delta'];

    if (isset($widget_state['gcontent'][$delta]['entity'])) {
      $entity = $widget_state['gcontent'][$delta]['entity'];

      /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
      $display = $widget_state['gcontent'][$delta]['display'];

      if ($widget_state['gcontent'][$delta]['mode'] == 'edit') {
        // Extract the form values on submit for getting the current gcontent.
        $display->extractFormValues($entity, $element['subform'], $form_state);
        $display->validateFormValues($entity, $element['subform'], $form_state);
      }
    }

    static::setWidgetState($element['#field_parents'], $field_name, $form_state, $widget_state);
  }

  /**
   * Special handling to validate form elements with multiple values.
   *
   * @param array $elements
   *   An associative array containing the substructure of the form to be
   *   validated in this call.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $form
   *   The complete form array.
   */
  public function multipleElementValidate(array $elements, FormStateInterface $form_state, array $form) {
    $field_name = $this->fieldDefinition->getName();
    $widget_state = static::getWidgetState($elements['#field_parents'], $field_name, $form_state);

    $remove_mode_item_count = $this->getNumberOfGcontentInMode($widget_state, 'remove');
    $non_remove_mode_item_count = $widget_state['real_item_count'] - $remove_mode_item_count;

    if ($elements['#required'] && $non_remove_mode_item_count < 1) {
      $form_state->setError($elements, t('@name field is required.', ['@name' => $this->fieldDefinition->getLabel()]));
    }

    static::setWidgetState($elements['#field_parents'], $field_name, $form_state, $widget_state);
  }

  /**
   * Add 'add more' button, if not working with a programmed form.
   *
   * @return array
   *   The form element array.
   */
  protected function buildAddActions($allowed_groups, $entity_plugin_id) {
    if (!$entity_plugin_id) {
      $add_more_elements['info'] = [
        '#type' => 'status_messages',
        '#message_list' => [
          'warning' => $this->t('There is no Group content plugin available for this entity type.'),
        ]
      ];
      return $add_more_elements;
    }

    // Warnings.
    if ($allowed_groups['warnings']) {
      foreach ($allowed_groups['warnings'] as $type => $message) {
        $add_more_elements['info'] = [
          '#type' => 'status_messages',
          '#message_list' => $allowed_groups['warnings'],
        ];
        return $add_more_elements;
      }
    }

    if ($this->getSetting('widget') == 'autocomplete') {
      return $this->buildAutocompleteAddMode($allowed_groups['autocomplete_allowed_groups']);
    }

    // hide option groups if there is only one group bundle
    if (count($allowed_groups['select_allowed_groups']) == 1) {
      $allowed_groups['select_allowed_groups'] = array_pop($allowed_groups['select_allowed_groups']);
    }

    return $this->buildSelectAddMode($allowed_groups['select_allowed_groups']);
  }

  /**
   * Builds autocomplete for adding new gcontent.
   *
   * @return array
   *   The form element array.
   */
  protected function buildAutocompleteAddMode($allowed_groups) {
    // If there are no available groups, don't build a form element.
    if (empty($allowed_groups)) {
      return [];
    }

    // Hide the button when translating.
    $add_more_elements = [
      '#type' => 'container',
    ];
    $field_name = $this->fieldDefinition->getName();
    $title = $this->fieldDefinition->getLabel();

    $add_more_elements['add_relation'] = [
      '#title' => $this->t('Group Name'),
      '#type' => 'group_autocomplete',
      '#target_type' => 'group',
      '#selection_handler' => 'group:group_content',
      '#selection_settings' => ['allowed_groups' => $allowed_groups],
    ];

    $add_more_elements['add_more_button'] = [
      '#type' => 'submit',
      '#name' => strtr($this->fieldIdPrefix, '-', '_') . '_add_more',
      '#value' => $this->t('Add to Group'),
      '#attributes' => ['class' => ['field-add-more-submit']],
      '#limit_validation_errors' => [
        array_merge($this->fieldParents, [$field_name, 'add_more']),
      ],
      '#submit' => [[get_class($this), 'addMoreSubmit']],
      '#validate' => [[get_class($this), 'addMoreValidate']],
      '#ajax' => [
        'callback' => [get_class($this), 'addMoreAjax'],
        'wrapper' => $this->fieldWrapperId,
        'effect' => 'fade',
      ],
    ];
    return $add_more_elements;
  }

  /**
   * Builds select for adding new gcontent.
   *
   * @return array
   *   The form element array.
   */
  protected function buildSelectAddMode($allowed_groups) {
    // If there are no available groups, don't build a form element.
    if (empty($allowed_groups)) {
      return [];
    }

    // Hide the button when translating.
    $add_more_elements = [
      '#type' => 'container',
    ];
    $field_name = $this->fieldDefinition->getName();

    $add_more_elements['add_relation'] = [
      '#title' => $this->t('Group'),
      '#type' => 'select',
      '#description' => $this->t('Select a group from the options above, then comfirm that selection using the button below.'),
      '#options' => $allowed_groups,
    ];

    $add_more_elements['add_more_button'] = [
      '#type' => 'submit',
      '#name' => strtr($this->fieldIdPrefix, '-', '_') . '_add_more',
      '#value' => $this->t('Confirm add to Group'),
      '#attributes' => ['class' => ['field-add-more-submit']],
      '#limit_validation_errors' => [
        array_merge($this->fieldParents, [$field_name, 'add_more']),
      ],
      '#submit' => [[get_class($this), 'addMoreSubmit']],
      '#ajax' => [
        'callback' => [get_class($this), 'addMoreAjax'],
        'wrapper' => $this->fieldWrapperId,
        'effect' => 'fade',
      ],
    ];

    return $add_more_elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($triggering_element['#array_parents'], 0, -2));

    // Add a DIV around the delta receiving the Ajax effect.
    $delta = $element['#max_delta'];
    $element[$delta]['#prefix'] = '<div class="ajax-new-content">' . (isset($element[$delta]['#prefix']) ? $element[$delta]['#prefix'] : '');
    $element[$delta]['#suffix'] = (isset($element[$delta]['#suffix']) ? $element[$delta]['#suffix'] : '') . '</div>';

    return $element;
  }

  /**
   * Widget items ajax callback.
   */
  public static function itemAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -4));

    $element['#prefix'] = '<div class="ajax-new-content">' . (isset($element['#prefix']) ? $element['#prefix'] : '');
    $element['#suffix'] = (isset($element['#suffix']) ? $element['#suffix'] : '') . '</div>';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function addMoreSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    // Increment the items count.
    $widget_state = static::getWidgetState($parents, $field_name, $form_state);

    if ($widget_state['real_item_count'] < $element['#cardinality'] || $element['#cardinality'] == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      $widget_state['items_count']++;
    }
    $local_keys = [$field_name, 'add_more', 'add_relation'];
    $parent_keys = array_merge($parents, $local_keys);
    $selected_group = NestedArray::getValue($form_state->getValues(), $parent_keys);
    $group = \Drupal::entityTypeManager()->getStorage('group')->load($selected_group);
    $group_content_type_id = $group->getGroupType()->getContentPlugin($widget_state['entity_plugin_id'])->getContentTypeConfigId();
    $widget_state['selected_group'] = $selected_group;
    $widget_state['selected_bundle'] = $group_content_type_id;

    // Clearing relation field.
    $user_input = $form_state->getUserInput();
    $user_input[$field_name]['add_more']['add_relation'] = '';
    $form_state->setUserInput($user_input);

    static::setWidgetState($parents, $field_name, $form_state, $widget_state);

    $form_state->setRebuild();
  }

  /**
   * Add to group autocomplete validation.
   */
  public static function addMoreValidate(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));
    $field_name = $element['#field_name'];

    $select_group_element = NestedArray::getValue($element, [
      'add_more',
      'add_relation',
    ]);
    $selected_group = NestedArray::getValue($form_state->getValues(), [
      $field_name,
      'add_more',
      'add_relation',
    ]);
    if (!$selected_group) {
      $form_state->setError($select_group_element, t('@field_name should not be empty', ['@field_name' => (string) $select_group_element['#title']]));
    }
  }

  /**
   * Submit for item links.
   */
  public static function gcontentItemSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -4));

    $delta = array_slice($button['#array_parents'], -4, -3);
    $delta = $delta[0];

    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    $widget_state = static::getWidgetState($parents, $field_name, $form_state);

    $widget_state['gcontent'][$delta]['mode'] = $button['#gcontent_mode'];

    if (!empty($button['#gcontent_show_warning'])) {
      $widget_state['gcontent'][$delta]['show_warning'] = $button['#gcontent_show_warning'];
    }

    static::setWidgetState($parents, $field_name, $form_state, $widget_state);

    $form_state->setRebuild();
  }

  /**
   * Counts the number of gcontent in a certain mode in a form substructure.
   *
   * @param array $widget_state
   *   The widget state for the form substructure containing information about
   *   the gcontent within.
   * @param string $mode
   *   The mode to look for.
   *
   * @return int
   *   The number of gcontent is the given mode.
   */
  protected function getNumberOfGcontentInMode(array $widget_state, $mode) {
    if (!isset($widget_state['gcontent'])) {
      return 0;
    }

    $gcontent_count = 0;
    foreach ($widget_state['gcontent'] as $gcontent) {
      if ($gcontent['mode'] == $mode) {
        $gcontent_count++;
      }
    }

    return $gcontent_count;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $widget_state = static::getWidgetState($form['#parents'], $field_name, $form_state);
    $element = NestedArray::getValue($form_state->getCompleteForm(), $widget_state['array_parents']);
    foreach ($values as $delta => &$item) {
      $item['target_id'] = NULL;
      if (isset($widget_state['gcontent'][$item['_original_delta']]['entity'])
        && $widget_state['gcontent'][$item['_original_delta']]['mode'] != 'remove') {
        $gcontent_entity = $widget_state['gcontent'][$item['_original_delta']]['entity'];

        /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
        $display = $widget_state['gcontent'][$item['_original_delta']]['display'];
        if ($widget_state['gcontent'][$item['_original_delta']]['mode'] == 'edit') {
          $display->extractFormValues($gcontent_entity, $element[$item['_original_delta']]['subform'], $form_state);
        }

        $item['entity'] = $gcontent_entity;
        if ($gcontent_entity->id()) {
          $item['target_id'] = $gcontent_entity->id();
        }
        $item['needs_save'] = TRUE;
        // If unchanged avoid group content saving.
        if ($widget_state['gcontent'][$item['_original_delta']]['mode'] == 'closed' && !isset($widget_state['gcontent'][$item['_original_delta']]['show_warning'])) {
          unset($item['needs_save']);
        }
      }
    }
    return $values;
  }

  /**
   * Get a list of groups with an specific plugin installed.
   *
   * @param string $plugin_id
   *   The plugin id to filter the groups.
   *
   * @return array
   *   The list of group entities.
   */
  protected function  getPluginGroups($plugin_id) {
    $groups = [];
    $group_type_map = $this->groupContentPluginManager->getGroupTypePluginMap();
    foreach ($group_type_map as $group_type_id => $group_plugins_enabled) {
      foreach ($group_plugins_enabled as $group_plugin_id) {
        if ($group_plugin_id == $plugin_id) {
          $groups_using_plugin = $this->entityTypeManager->getStorage('group')->loadByProperties(['type' => $group_type_id]);
          $groups = array_merge($groups, $groups_using_plugin);
        }
      }
    }
    return $groups;
  }

  /**
   * Returns the groups entity cardinality.
   *
   * @param array $groups
   *   The group allowed by current entity plugin id.
   * @param string $plugin_id
   *   The current entity plugin id.
   *
   * @return array
   *   The groups entity cardinality array.
   */
  protected function  getGroupsCardinality(array $groups, $plugin_id) {
    $groups_cardinality = [];
    if ($groups) {
      foreach ($groups as $group) {
        $groups_cardinality[$group->id()] = $group->getGroupType()->getContentPlugin($plugin_id)->getEntityCardinality();
      }
    }
    return $groups_cardinality;
  }

  /**
   * Get useful lists for group options form elements.
   *
   * @param array $groups
   *   The array of allowed groups.
   * @param string $entity_plugin_id
   *   The plugin id to get existing content.
   * @param array $existing_gcontent
   *   The existing group content.
   * @param array $groups_cardinality
   *   The groups entity cardinality array.
   *
   * @return array
   *   The Lists.
   */
  protected function  getAllowedGroups(array $groups, $entity_plugin_id, array $existing_gcontent, array $groups_cardinality) {
    $all_restricted = TRUE;
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = $this->currentUser->getAccount();
    $allowed_groups = [
      'autocomplete_allowed_groups' => [],
      'select_allowed_groups' => [],
      'warnings' => [],
    ];
    // If empty group it means there are not groups with the plugin enabled.
    if (empty($groups)) {
      $allowed_groups['warnings']['empty_groups'] = $this->t('There are no groups or group types with the needed plugin enabled.');
      return $allowed_groups;
    }

    // Checking cardinality.
    $excluded_groups = [];
    if ($existing_gcontent) {
      $groups_ammounts = [];
      foreach ($existing_gcontent as $gcontent) {
        // Not count the content if was removed.
        if ($gcontent['mode'] == 'removed') {
          continue;
        }
        $gcontent_entity = isset($gcontent['entity']) ? $gcontent['entity'] : FALSE;
        if ($gcontent_entity) {
          $gid = $gcontent_entity->gid->getString();
          $groups_ammounts[$gid] = isset($groups_ammounts[$gid]) ? $groups_ammounts[$gid] + 1 : 1;
          if ($groups_ammounts[$gid] >= $groups_cardinality[$gid]) {
            $excluded_groups[] = $gid;
          }
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
        $allowed_groups['autocomplete_allowed_groups'][$group_bundle][$group->id()] = Html::escape($this->entityRepository->getTranslationFromContext($group)->label());
        $allowed_groups['select_allowed_groups'][$group_bundle_label][$group->id()] = $this->entityRepository->getTranslationFromContext($group)->label();
      }
    }

    // Add warning when all restricted.
    if ($all_restricted && !$existing_gcontent && count($groups) != count($excluded_groups)) {
      $allowed_groups['warnings']['restricted'] = $this->t("You don't have the needed permissions to edit this.");
    }
    return $allowed_groups;
  }

}
