<?php

namespace Drupal\performance_graph\Plugin\GraphQL\DataProducer;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\performance_graph\GraphQL\Response\EntityResponse;

/**
 * Saves contact entities.
 *
 * @DataProducer(
 *   id = "save_contact",
 *   name = @Translation("Save contact"),
 *   description = @Translation("Saves a contact."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Contact")
 *   ),
 *   consumes = {
 *     "data" = @ContextDefinition("any",
 *       label = @Translation("Contact input data")
 *     )
 *   }
 * )
 */
class SaveContact extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * SaveContact constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
  }

  /**
   * Creates a contact.
   *
   * @param array $data
   *   The submitted values for the contact.
   *
   * @return \Drupal\performance_graph\GraphQL\Response\EntityResponse
   *   The newly created contact.
   *
   * @throws \Exception
   */
  public function resolve(array $data) {
    $response = new EntityResponse();

    $mode = array_key_exists('id', $data) ? 'update': 'create';

    if ($mode === 'create') {
      if ($this->currentUser->hasPermission("create contact_info content")) {
        $values = [
          'type' => 'contact_info'
        ];

        $filtered_keys = array_filter(
          array_keys($data),
          function ($key) { return !in_array($key, ['id', 'uuid']); }
        );
        foreach($filtered_keys as $key) {
          $values[$this->mapKey($key)] = $data[$key];
        }
        $node = Node::create($values);
        $node->save();
        $response->setEntity($node);
      }
      else {
        $response->addViolation(
          $this->t('You do not have permissions to create articles.')
        );
      }
    }
    else if ($mode === 'update') {
      $node = Node::load($data['id']);
      $node->setNewRevision();

      $filtered_keys = array_filter(
        array_keys($data),
        function ($key) { return !in_array($key, ['id', 'uuid']); }
      );
      foreach($filtered_keys as $key) {
        $node->set($this->mapKey($key), $data[$key]);
      }

      $node->save();
      $response->setEntity($node);
    }

    return $response;
  }

  protected function mapKey($key) {
    $map = [
      'name' => 'title',
      'type' => 'field_type',
      'phoneNumbers' => 'field_phone_number',
      'emailAddresses' => 'field_email_address'
    ];

    return isset($map[$key]) ? $map[$key] : $key;
  }
}
