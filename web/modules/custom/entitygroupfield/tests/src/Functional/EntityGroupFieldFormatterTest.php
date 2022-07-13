<?php

namespace Drupal\Tests\entitygroupfield\Functional;

use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\entitygroupfield\Traits\GroupCreationTrait;
use Drupal\Tests\entitygroupfield\Traits\TestGroupsTrait;

/**
 * Tests field formatters for group associations.
 *
 * @group entitygroupfield
 */
class EntityGroupFieldFormatterTest extends BrowserTestBase {

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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser([
      'administer content types',
      'administer node fields',
      'administer node display',
      'bypass node access',
      // @todo Don't use this perm, be more careful with Group memberships.
      'bypass group access',
    ]));

    // Setup the group types and test groups from the TestGroupsTrait.
    $this->initializeTestGroups();

    // Create node type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => t('Article')]);

    // Enable article nodes to be assigned to only 'A' group type.
    $this->entityTypeManager->getStorage('group_content_type')
      ->createFromPlugin($this->groupTypeA, 'group_node:article')->save();
  }

  /**
   * Test group field formatters.
   */
  public function testFieldFormatters() {
    // Create an article and assign to a single A group.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Article 1',
    ]);
    $this->assertNotEmpty(Node::load($node->id()));
    $this->groupA1->addContent($node, 'group_node:article');

    // Visit the node. Since we haven't configured any formatter to display, we
    // shouldn't see the group name (yet).
    $this->drupalGet("node/" . $node->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkNotExists('group-A1');
    $this->assertNoText('group-A1');

    // Now configure the formatter to show the group (as a link) and try again.
    $this->configureViewDisplay([
      'type' => 'parent_group_label_formatter',
      'settings' => [
        'link' => TRUE,
      ],
    ]);

    // Visit the node and we should now see a link to group-A1.
    $this->drupalGet("node/" . $node->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkExists('group-A1');
    $this->clickLink('group-A1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEquals('group-A1', $this->cssSelect('main h1')[0]->getText());

    // Now turn off the 'link' setting and try again.
    $this->configureViewDisplay([
      'type' => 'parent_group_label_formatter',
      'settings' => [
        'link' => FALSE,
      ],
    ]);
    $this->drupalGet("node/" . $node->id());
    $this->assertSession()->statusCodeEquals(200);
    // Make sure there's no link, but the label should still be there.
    $this->assertSession()->linkNotExists('group-A1');
    $this->assertText('group-A1');

    // Now try the ID formatter.
    $this->configureViewDisplay([
      'type' => 'parent_group_id_formatter',
    ]);
    $this->drupalGet("node/" . $node->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkNotExists('group-A1');
    // Since this is Stark, there are no classes we can use to target this. We
    // have to search for the 'Groups' label, and then the following-sibling div
    // should contain the value.
    $elements = $this->xpath("//div[text()='Groups']/following-sibling::div");
    $this->assertCount(1, $elements);
    $this->assertEquals($this->groupA1->id(), $elements[0]->getText());

    // @todo Try the parent group entity render formatter.
  }

  /**
   * Configures the view display mode for the 'entitygroupfield' field.
   *
   * @param array $config
   *   The configuration array to use for the 'entitygroupfield' field.
   */
  protected function configureViewDisplay(array $config) {
    \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', 'article')
      ->setComponent('entitygroupfield', $config + [
        'label' => 'above',
      ])
      ->save();
  }

}
