<?php

namespace Drupal\Tests\entitygroupfield\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\entitygroupfield\Traits\GroupCreationTrait;

/**
 * Defines an abstract test base for entitygroupfield kernel tests.
 */
abstract class EntityGroupFieldKernelTestBase extends KernelTestBase {

  use UserCreationTrait;
  use GroupCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'group',
    'variationcache',
    'entity',
    'field',
    'options',
    'entitygroupfield',
  ];

  /**
   * The content enabler plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $groupContentPluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['sequences', 'key_value_expire']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('group');
    $this->installEntitySchema('group_content');
    $this->installConfig(['group']);

    $this->groupContentPluginManager = $this->container->get('plugin.manager.group_content_enabler');

    // Create a default group type.
    $this->createGroupType([
      'id' => 'default',
      'label' => 'Default',
    ]);
  }

}
