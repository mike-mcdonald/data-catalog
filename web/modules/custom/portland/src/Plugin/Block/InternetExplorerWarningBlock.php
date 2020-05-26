<?php

namespace Drupal\portland\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block for a warning against using Internet Explorer < 12.
 *
 * @Block(
 *   id = "internet_explorer_warning_block",
 *   admin_label = @Translation("Internet Explorer warning"),
 *   category = @Translation("Alerts")
 * )
 *
 */
class InternetExplorerWarningBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * Constructs a new InternetExplorerWarningBlock plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'display_message' => 'You are using an unsupported browser, features of this site will not function. Please use a different browser.',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['display_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Display message'),
      '#default_value' => $this->configuration['display_message'],
      '#description' => $this->t('Enter the message that will be displayed to users if their browser is unsupported.')
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['display_message'] = $form_state->getValue('display_message');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#attributes' => [
        'class' => ['js-internet-explorer-warning'],
        'style' => 'display: none;',
      ],
      '#children' => $this->configuration['display_message'],
      '#attached' => [
        'library' => [
          'portland/internet_explorer_warning'
        ]
      ]
    ];
  }

}
