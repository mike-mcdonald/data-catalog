<?php

namespace Drupal\Tests\entitygroupfield\Kernel;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;

/**
 * Tests the 'group_autocomplete' form element.
 *
 * @group entitygroupfield
 */
class GroupAutocompleteFormElementTest extends EntityGroupFieldKernelTestBase implements FormInterface {

  /**
   * Test groups.
   *
   * @var \Drupal\group\Entity\GroupInterface[]
   */
  protected $testGroups;

  /**
   * Form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->formBuilder = $this->container->get('form_builder');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entitygroupfield_group_autocomplete_form_element_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['group_autocomplete_all'] = [
      '#title' => 'Group (all)',
      '#type' => 'group_autocomplete',
    ];

    $form['group_autocomplete_exclude'] = [
      '#title' => 'Group (exclude)',
      '#type' => 'group_autocomplete',
      '#selection_settings' => [
        'excluded_groups' => [1, 2],
      ],
    ];

    $form['group_autocomplete_restrict'] = [
      '#title' => 'Group (restrict)',
      '#type' => 'group_autocomplete',
      '#selection_settings' => [
        'target_bundles' => ['special'],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Tests the group_autocomplete form element when no groups exist.
   */
  public function testGroupAutocompleteNoGroups() {
    // Empty values.
    $form_state = (new FormState())
      ->setValues([
        'group_autocomplete_all' => '',
        'group_autocomplete_exclude' => '',
      ]);
    $this->formBuilder->submitForm($this, $form_state);
    $this->assertEmpty($form_state->getErrors());

    // Group not found.
    $form_state->setValues([
      'group_autocomplete_all' => 'missing',
    ]);
    $this->formBuilder->submitForm($this, $form_state);
    $form_errors = $form_state->getErrors();
    $this->assertCount(1, $form_errors);
    $this->assertEquals('There are no groups called "<em class="placeholder">missing</em>".', $form_errors['group_autocomplete_all']);

    // Invalid ID.
    $form_state->setValues([
      'group_autocomplete_all' => 'invalid (x)',
    ]);
    $this->formBuilder->submitForm($this, $form_state);
    $form_errors = $form_state->getErrors();
    $this->assertCount(1, $form_errors);
    $this->assertEquals('The referenced entity (<em class="placeholder">group</em>: <em class="placeholder">x</em>) does not exist.', $form_errors['group_autocomplete_all']);
  }

  /**
   * Tests the group_autocomplete form element with existing groups.
   */
  public function testGroupAutocomplete() {
    $form_state = new FormState();

    // Set up the current user. Use 'bypass group access' here since we don't
    // care about Group access controls for this test.
    $test_user = $this->setUpCurrentUser([],
      [
        'access content',
        'bypass group access',
      ]
    );

    // Create the initial group.
    $group = $this->createGroup(['label' => 'group-A']);
    $this->testGroups[$group->id()] = $group;

    // Try to use it and it should work.
    $form_state->setValues([
      'group_autocomplete_all' => 'group-A',
      'group_autocomplete_exclude' => '',
    ]);
    $this->formBuilder->submitForm($this, $form_state);
    $this->assertCount(0, $form_state->getErrors());
    $this->assertEquals(1, $form_state->getValue('group_autocomplete_all'));

    // Add a duplicate 'group-A' group.
    $group = $this->createGroup(['label' => 'group-A']);
    $this->testGroups[$group->id()] = $group;
    $form_state->setValues([
      'group_autocomplete_all' => 'group-A',
      'group_autocomplete_exclude' => 'group-A',
      'group_autocomplete_restrict' => 'group-A',
    ]);
    $this->formBuilder->submitForm($this, $form_state);
    $form_errors = $form_state->getErrors();
    $this->assertCount(3, $form_errors);
    $this->assertEquals('Multiple groups match: <em class="placeholder">group-A (1), group-A (2)</em>. Pick one by appending the ID in parentheses, for example: <em class="placeholder">group-A (2)</em>', $form_errors['group_autocomplete_all']);
    // Due to excluded groups, we should get the group not found error.
    $this->assertEquals('There are no groups called "<em class="placeholder">group-A</em>".', $form_errors['group_autocomplete_exclude']);
    // Due to restricted group types, we should get the group not found error.
    $this->assertEquals('There are no groups called "<em class="placeholder">group-A</em>".', $form_errors['group_autocomplete_restrict']);

    // Create the group type that the 'restrict' element requires.
    $special_type = $this->createGroupType([
      'id' => 'special',
      'label' => 'Special',
    ]);
    // Add a 3rd 'group-A' of the new special type, and now it should work both
    // in the excluded and restricted elements.
    $group = $this->createGroup(['label' => 'group-A', 'type' => 'special']);
    $this->testGroups[$group->id()] = $group;
    $form_state = (new FormState())
      ->setValues([
        'group_autocomplete_all' => '',
        'group_autocomplete_exclude' => 'group-A',
        'group_autocomplete_restrict' => 'group-A',
      ]);
    $this->formBuilder->submitForm($this, $form_state);
    $this->assertCount(0, $form_state->getErrors());
    $this->assertEquals(3, $form_state->getValue('group_autocomplete_exclude'));
    $this->assertEquals(3, $form_state->getValue('group_autocomplete_restrict'));

    // Add another bunch of 'group-A' groups so we end up with more than 5.
    for ($i = 0; $i < 3; $i++) {
      $group = $this->createGroup(['label' => 'group-A']);
      $this->testGroups[$group->id()] = $group;
    }
    $form_state = (new FormState())
      ->setValues([
        'group_autocomplete_all' => 'group-A',
        'group_autocomplete_exclude' => '',
        'group_autocomplete_restrict' => 'group-A',

      ]);
    $this->formBuilder->submitForm($this, $form_state);
    $form_errors = $form_state->getErrors();
    $this->assertCount(1, $form_errors);
    $this->assertEquals('Many groups are called "<em class="placeholder">group-A</em>". Pick one by appending the ID in parentheses, for example: <em class="placeholder">group-A (1)</em>', $form_errors['group_autocomplete_all']);
    // No error for the restricted element since everything else used 'default'.
    $this->assertEquals(3, $form_state->getValue('group_autocomplete_restrict'));
  }

}
