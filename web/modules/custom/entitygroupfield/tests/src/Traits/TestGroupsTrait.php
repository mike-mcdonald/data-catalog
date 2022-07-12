<?php

namespace Drupal\Tests\entitygroupfield\Traits;

/**
 * Provides group types and group entities for use in test classes.
 *
 * This trait provides protected members to store 2 group types ('a' and 'b')
 * and then 2 groups of each type. Calling initializeTestGroups() will
 * initialize everything and populate all the protected member variables.
 */
trait TestGroupsTrait {

  /**
   * A dummy group type with ID 'a'.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupTypeA;

  /**
   * A dummy group type with ID 'b'.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupTypeB;

  /**
   * Test group A1, of type 'a'.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $groupA1;

  /**
   * Test group A2, of type 'a'.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $groupA2;

  /**
   * Test group B1, of type 'b'.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $groupB1;

  /**
   * Test group B2, of type 'b'.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $groupB2;

  /**
   * Initializes all the test group types and groups.
   */
  protected function initializeTestGroups() {
    // Create the group types.
    $this->groupTypeA = $this->createGroupType([
      'id' => 'a',
      'label' => 'Type A',
    ]);
    $this->groupTypeB = $this->createGroupType([
      'id' => 'b',
      'label' => 'Type B',
    ]);

    // Create the groups.
    $this->groupA1 = $this->createGroup(['label' => 'group-A1', 'type' => 'a']);
    $this->groupA2 = $this->createGroup(['label' => 'group-A2', 'type' => 'a']);
    $this->groupB1 = $this->createGroup(['label' => 'group-B1', 'type' => 'b']);
    $this->groupB2 = $this->createGroup(['label' => 'group-B2', 'type' => 'b']);
  }

}
