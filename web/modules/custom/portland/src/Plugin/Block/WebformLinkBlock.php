<?php

namespace Drupal\portland\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\Entity\Webform;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block linking to webforms.
 *
 * @Block(
 *   id = "webform_link_block",
 *   admin_label = @Translation("Webform link"),
 *   category = @Translation("Block")
 * )
 *
 */
class WebformLinkBlock extends BlockBase implements ContainerFactoryPluginInterface
{
  /**
   * Constructs a new WebformLinkBlock plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE)
  {
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration()
  {
    return [
      'webform_id' => NULL,
      'prefix_message' => '',
      'webform_message' => '',
      'suffix_message' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state)
  {
    $form['webform_id'] = [
      '#type' => 'entity_autocomplete',
      '#required' => TRUE,
      '#target_type' => 'webform',
      '#default_value' => Webform::load($this->configuration['webform_id']),
      '#title' => $this->t('Select the webform you want to link to.'),
      '#placeholder' => $this->t('Enter webform name'),
      '#size' => '28',
    ];
    $form['prefix_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix message'),
      '#default_value' => $this->configuration['prefix_message'],
      '#description' => $this->t('Enter the message that will be displayed before the link to the webform.')
    ];
    $form['webform_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Display message'),
      '#default_value' => $this->configuration['webform_message'],
      '#description' => $this->t('Enter the message that will be displayed as the link to the webform.')
    ];
    $form['suffix_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Suffix message'),
      '#default_value' => $this->configuration['suffix_message'],
      '#description' => $this->t('Enter the message that will be displayed after the link to the webform.')
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state)
  {
    $this->configuration['webform_id'] = $form_state->getValue('webform_id');
    $this->configuration['prefix_message'] = $form_state->getValue('prefix_message');
    $this->configuration['webform_message'] = $form_state->getValue('webform_message');
    $this->configuration['suffix_message'] = $form_state->getValue('suffix_message');
  }

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    $link = Webform::load($this->configuration['webform_id'])->toLink($this->configuration['webform_message']);


    return [
      '#type' => 'container',
      'prefix' => [
        '#children' => $this->configuration['prefix_message'] . ' ',
      ],
      'link' => [
        '#children' => $link->toString(),
      ],
      'suffix' => [
        '#children' => ' ' . $this->configuration['suffix_message'],
      ],
    ];
  }
}
