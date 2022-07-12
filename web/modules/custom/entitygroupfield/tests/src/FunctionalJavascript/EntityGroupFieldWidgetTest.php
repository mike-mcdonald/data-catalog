<?php

namespace Drupal\Tests\entitygroupfield\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\entitygroupfield\Traits\GroupCreationTrait;
use Drupal\Tests\entitygroupfield\Traits\TestGroupsTrait;

/**
 * Tests the JavaScript AJAX functionality of the entitygroupfield widgets.
 *
 * @group entitygroupfield
 */
class EntityGroupFieldWidgetTest extends WebDriverTestBase {

  use GroupCreationTrait;
  use TestGroupsTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'field_ui',
    'group',
    'gnode',
    'entitygroupfield',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Regular authenticated User for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser([
      'administer account settings',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer users',
      'bypass node access',
      // @todo Don't use this perm, be more careful with Group memberships.
      'bypass group access',
    ]));

  }

  /**
   * Initialize the test.
   *
   * These are things that would otherwise be in self::setUp(), but that we want
   * to do after some initial assertions.
   */
  protected function initializeTest() {
    // Setup the group types and test groups from the TestGroupsTrait.
    $this->initializeTestGroups();

    // Enable article nodes to be assigned to only 'A' group type.
    $this->entityTypeManager->getStorage('group_content_type')
      ->createFromPlugin($this->groupTypeA, 'group_node:article')->save();
    // Let page nodes be assigned to both 'A' and 'B' groups.
    $this->entityTypeManager->getStorage('group_content_type')
      ->createFromPlugin($this->groupTypeA, 'group_node:page')->save();
    $this->entityTypeManager->getStorage('group_content_type')
      ->createFromPlugin($this->groupTypeB, 'group_node:page')->save();
  }

  /**
   * Test group field widgets.
   */
  public function testFieldWidgets() {
    // Before we configure anything, make sure we don't see our widgets.
    // Verify users.
    $this->drupalGet('/admin/people/create');
    $page = $this->getSession()->getPage();
    $groups_widget = $page->findAll('css', '#edit-entitygroupfield');
    $this->assertEmpty($groups_widget);
    $groups_element = $page->findField('entitygroupfield[add_more][add_relation]');
    $this->assertEmpty($groups_element);
    $add_group_button = $page->findButton('Add to Group');
    $this->assertEmpty($add_group_button);

    // Create node types.
    $this->drupalCreateContentType(['type' => 'article', 'name' => t('Article')]);
    $this->drupalCreateContentType(['type' => 'page', 'name' => t('Basic page')]);

    // Verify article nodes.
    $this->drupalGet('/node/add/article');
    $page = $this->getSession()->getPage();
    $groups_widget = $page->findAll('css', '#edit-entitygroupfield');
    $this->assertEmpty($groups_widget);
    $groups_element = $page->findField('entitygroupfield[add_more][add_relation]');
    $this->assertEmpty($groups_element);
    $add_group_button = $page->findButton('Add to Group');
    $this->assertEmpty($add_group_button);
    // Verify page nodes.
    $this->drupalGet('/node/add/page');
    $page = $this->getSession()->getPage();
    $groups_widget = $page->findAll('css', '#edit-entitygroupfield');
    $this->assertEmpty($groups_widget);
    $groups_element = $page->findField('entitygroupfield[add_more][add_relation]');
    $this->assertEmpty($groups_element);
    $add_group_button = $page->findButton('Add to Group');
    $this->assertEmpty($add_group_button);

    // Note: the rest of this function invokes protected helper methods, instead
    // of defining those as entirely new test* methods, to avoid the (intense)
    // startup costs of FunctionalJavascript tests.
    //
    // Before we create any groups or types, try the select widget on a user.
    $this->checkUserSelectWidgetBeforeGroups();

    // Initialize our test group types and groups.
    $this->initializeTest();

    // Try the select widget on users now that group types and groups exist.
    $this->checkUserSelectWidget();

    // Try both of the widgets on each of the node types.
    $this->checkArticleAutocompleteWidget();
    $this->checkArticleSelectWidget();
    $this->checkPageAutocompleteWidget();
    $this->checkPageSelectWidget();
  }

  /**
   * Test the 'select' group field widget on users before any groups exist.
   */
  protected function checkUserSelectWidgetBeforeGroups() {
    // Configure users to use the select widget.
    $this->configureFormDisplay('user', 'user', [
      'type' => 'entitygroupfield_select_widget',
      'settings' => [
        'multiple' => TRUE,
        'required' => FALSE,
      ],
    ]);

    // Now we should see the widget.
    $this->drupalGet('/admin/people/create');
    $page = $this->getSession()->getPage();
    $groups_widget = $page->findAll('css', '#edit-entitygroupfield');
    $this->assertNotEmpty($groups_widget);
    $this->assertSession()->pageTextContains('Group memberships');
    $this->assertSession()->pageTextContains('Not yet added to groups.');
    $groups_select = $page->findField('entitygroupfield[add_more][add_relation]');
    $this->assertEmpty($groups_select);
    $add_group_button = $page->findButton('Add to Group');
    $this->assertEmpty($add_group_button);
  }

  /**
   * Test the 'select' group field widget on users with groups and types.
   */
  protected function checkUserSelectWidget() {
    // Configure users to use the select widget.
    $custom_label = $this->randomMachineName(10);
    $custom_help_text = $this->randomMachineName(20);
    $this->configureFormDisplay('user', 'user', [
      'type' => 'entitygroupfield_select_widget',
      'settings' => [
        'multiple' => TRUE,
        'required' => FALSE,
        'label' => $custom_label,
        'help_text' => $custom_help_text,
      ],
    ]);

    // We should see the widget.
    $this->drupalGet('/admin/people/create');
    $page = $this->getSession()->getPage();
    $groups_widget = $page->findAll('css', '#edit-entitygroupfield');
    $this->assertNotEmpty($groups_widget);
    $this->assertSession()->pageTextContains('Group memberships');
    $groups_select_name = 'entitygroupfield[add_more][add_relation]';
    $groups_select = $page->findField($groups_select_name);
    $this->assertNotEmpty($groups_select);

    // Ensure our custom label and help text are used.
    $label = $this->xpath('//label[@for=:for and contains(text(), :value)]', [':for' => 'edit-entitygroupfield-add-more-add-relation', ':value' => $custom_label]);
    $this->assertNotEmpty($label);
    $help_text = $this->xpath('//div[@id=:id and contains(text(), :value)]', [':id' => 'edit-entitygroupfield-add-more-add-relation--description', ':value' => $custom_help_text]);
    $this->assertNotEmpty($help_text);

    // Ensure the default option makes sense.
    $default_option = $this->assertSession()->optionExists($groups_select_name, '- Select a group -');
    $this->assertNotEmpty($default_option);
    $this->assertTrue($default_option->hasAttribute('selected'));
    // Since this is a user, all 4 groups should be options.
    $this->assertNotEmpty($groups_select->find('named', ['option', 1]));
    $this->assertNotEmpty($groups_select->find('named', ['option', 2]));
    $this->assertNotEmpty($groups_select->find('named', ['option', 3]));
    $this->assertNotEmpty($groups_select->find('named', ['option', 4]));
    // And both opt groups.
    $this->assertNotEmpty($groups_select->find('named', ['optgroup', $this->groupTypeA->label()]));
    $this->assertNotEmpty($groups_select->find('named', ['optgroup', $this->groupTypeB->label()]));

    // Try to add to groupA1.
    $groups_select->setValue($this->groupA1->id());
    $add_group_button = $page->findButton('Add to Group');
    $this->assertNotEmpty($add_group_button);
    $add_group_button->click();
    $groups_table = $this->assertSession()->waitForElementVisible('css', '#edit-entitygroupfield-wrapper table');
    $this->assertNotEmpty($groups_table);
    // @todo Assert that the table looks right.
  }

  /**
   * Test the 'autocomplete' group field widget on article nodes.
   */
  protected function checkArticleAutocompleteWidget() {
    // Configure articles to use the autocomplete widget.
    $this->configureFormDisplay('node', 'article', [
      'type' => 'entitygroupfield_autocomplete_widget',
      'settings' => [
        'multiple' => TRUE,
        'required' => FALSE,
      ],
    ]);

    // Now we should see the widget.
    $this->drupalGet('/node/add/article');
    $page = $this->getSession()->getPage();
    $groups_widget = $page->findAll('css', '#edit-entitygroupfield');
    $this->assertNotEmpty($groups_widget);
    $groups_autocomplete = $page->findField('entitygroupfield[add_more][add_relation]');
    $this->assertNotEmpty($groups_autocomplete);

    // Actually test the autocomplete.
    $groups_autocomplete->setValue('group');
    $this->getSession()->getDriver()->keyDown($groups_autocomplete->getXpath(), ' ');
    $this->assertSession()->waitOnAutocomplete();

    // Check the autocomplete results.
    $results = $page->findAll('css', '.ui-autocomplete li');
    $this->assertCount(2, $results);
    $this->assertEquals($this->groupA1->label(), $results[0]->getText());
    $this->assertEquals($this->groupA2->label(), $results[1]->getText());

    // Click on the first result and make sure it works.
    $page->find('css', '.ui-autocomplete li:first-child a')->click();
    $this->assertSession()->fieldValueEquals('entitygroupfield[add_more][add_relation]', $this->groupA1->label() . ' (' . $this->groupA1->id() . ')');

    // @todo: Actually try to save the new article and make sure it worked.
  }

  /**
   * Test the 'select' group field widget on article nodes.
   */
  protected function checkArticleSelectWidget() {
    $assert_session = $this->assertSession();

    // Configure articles to use the select widget.
    $this->configureFormDisplay('node', 'article', [
      'type' => 'entitygroupfield_select_widget',
      'settings' => [
        'multiple' => TRUE,
        'required' => FALSE,
      ],
    ]);

    // Now we should see the widget.
    $this->drupalGet('/node/add/article');
    $page = $this->getSession()->getPage();
    $groups_widget = $page->findAll('css', '#edit-entitygroupfield');
    $this->assertNotEmpty($groups_widget);
    $groups_select = $page->findField('entitygroupfield[add_more][add_relation]');
    $this->assertNotEmpty($groups_select);
    // Since this is an article, only 'A' type groups should be options.
    $this->assertNotEmpty($groups_select->find('named', ['option', '- Select a group -']));
    $this->assertNotEmpty($groups_select->find('named', ['option', 1]));
    $this->assertNotEmpty($groups_select->find('named', ['option', 2]));
    $this->assertEmpty($groups_select->find('named', ['option', 3]));
    $this->assertEmpty($groups_select->find('named', ['option', 4]));
    $groups_select->setValue('1');
    $add_group_button = $page->findButton('Add to Group');
    $this->assertNotEmpty($add_group_button);
    $add_group_button->click();
    $groups_table = $assert_session->waitForElementVisible('css', '#edit-entitygroupfield-wrapper table');
    $this->assertNotEmpty($groups_table);
    // @todo Assert that the table looks right.
    $groups_select = $page->findField('entitygroupfield[add_more][add_relation]');
    $this->assertNotEmpty($groups_select);
    // Make sure the group we added is no longer an option in the select list.
    // @todo We'll have to be more careful with this once we correctly handle
    //   group cardinality settings.
    // @see https://www.drupal.org/project/entitygroupfield/issues/3152719
    $this->assertNotEmpty($groups_select->find('named', ['option', '- Select a group -']));
    $this->assertEmpty($groups_select->find('named', ['option', 1]));
    $this->assertNotEmpty($groups_select->find('named', ['option', 2]));
    $this->assertEmpty($groups_select->find('named', ['option', 3]));
    $this->assertEmpty($groups_select->find('named', ['option', 4]));

    // Add the 2nd group.
    $groups_select->setValue('2');
    $add_group_button = $page->findButton('Add to Group');
    $this->assertNotEmpty($add_group_button);
    $add_group_button->click();
    // Wait for a row with 'group-A2' to appear in the 'Groups' table.
    $group_a2_cell = $assert_session->waitForElementVisible('xpath', '//div[@id="edit-entitygroupfield-wrapper"]//table/tbody/tr/td//div[contains(text(), "group-A2")]');
    $this->assertNotEmpty($group_a2_cell);

    // Now that we used both groups, there shouldn't be an add button anymore.
    $groups_select = $page->findField('entitygroupfield[add_more][add_relation]');
    $this->assertEmpty($groups_select);

    // @todo Test trying to remove a group from the table.
  }

  /**
   * Test the 'autocomplete' group field widget on page nodes.
   */
  protected function checkPageAutocompleteWidget() {
    // Configure pages to use the autocomplete widget.
    $this->configureFormDisplay('node', 'page', [
      'type' => 'entitygroupfield_autocomplete_widget',
      'settings' => [
        'multiple' => TRUE,
        'required' => FALSE,
      ],
    ]);

    // Now we should see the widget.
    $this->drupalGet('/node/add/page');
    $page = $this->getSession()->getPage();
    $groups_widget = $page->findAll('css', '#edit-entitygroupfield');
    $this->assertNotEmpty($groups_widget);
    $groups_autocomplete = $page->findField('entitygroupfield[add_more][add_relation]');
    $this->assertNotEmpty($groups_autocomplete);

    // Actually test the autocomplete.
    $groups_autocomplete->setValue('group');
    $this->getSession()->getDriver()->keyDown($groups_autocomplete->getXpath(), ' ');
    $this->assertSession()->waitOnAutocomplete();

    // Check the autocomplete results.
    $results = $page->findAll('css', '.ui-autocomplete li');
    $this->assertCount(4, $results);
    $this->assertEquals($this->groupA1->label(), $results[0]->getText());
    $this->assertEquals($this->groupA2->label(), $results[1]->getText());
    $this->assertEquals($this->groupB1->label(), $results[2]->getText());
    $this->assertEquals($this->groupB2->label(), $results[3]->getText());

    // Click on the last result and make sure it works.
    $page->find('css', '.ui-autocomplete li:last-child a')->click();
    $this->assertSession()->fieldValueEquals('entitygroupfield[add_more][add_relation]', $this->groupB2->label() . ' (' . $this->groupB2->id() . ')');
  }

  /**
   * Test the 'select' group field widget on page nodes.
   */
  protected function checkPageSelectWidget() {
    $assert_session = $this->assertSession();

    // Configure pages to use the select widget.
    $this->configureFormDisplay('node', 'page', [
      'type' => 'entitygroupfield_select_widget',
      'settings' => [
        'multiple' => TRUE,
        'required' => FALSE,
      ],
    ]);
    $this->drupalGet('/node/add/page');
    $page = $this->getSession()->getPage();
    $groups_widget = $page->findAll('css', '#edit-entitygroupfield');
    $this->assertNotEmpty($groups_widget);
    $groups_select = $page->findField('entitygroupfield[add_more][add_relation]');
    $this->assertNotEmpty($groups_select);
    // Since this is a page node, all 4 groups should be options.
    $this->assertNotEmpty($groups_select->find('named', ['option', '- Select a group -']));
    $this->assertNotEmpty($groups_select->find('named', ['option', 1]));
    $this->assertNotEmpty($groups_select->find('named', ['option', 2]));
    $this->assertNotEmpty($groups_select->find('named', ['option', 3]));
    $this->assertNotEmpty($groups_select->find('named', ['option', 4]));
    // And both opt groups.
    $this->assertNotEmpty($groups_select->find('named', ['optgroup', $this->groupTypeA->label()]));
    $this->assertNotEmpty($groups_select->find('named', ['optgroup', $this->groupTypeB->label()]));

    // @todo: Anything else we should test with both A and B groups that we
    // didn't already cover with articles?
  }

  /**
   * Configures the form display mode for the 'entitygroupfield' field.
   *
   * @param string $entity_type
   *   The entity type to configure.
   * @param string $bundle
   *   (Optional) The entity bundle to configure.
   * @param array $config
   *   The configuration array to use for the 'entitygroupfield' field.
   */
  protected function configureFormDisplay($entity_type, $bundle, array $config) {
    \Drupal::service('entity_display.repository')
      ->getFormDisplay($entity_type, $bundle)
      ->setComponent('entitygroupfield', $config)
      ->save();
  }

}
