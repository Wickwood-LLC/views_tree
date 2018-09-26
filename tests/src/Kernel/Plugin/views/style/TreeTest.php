<?php

namespace Drupal\Tests\views_tree\Kernel\Plugin\views\style;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the views tree list style plugin.
 *
 * @group views_tree
 *
 * @coversDefaultClass \Drupal\views_tree\Plugin\views\style\Tree
 */
class TreeTest extends ViewsKernelTestBase {

  use EntityReferenceTestTrait;

  /**
   * Parent entities.
   *
   * @var \Drupal\entity_test\Entity\EntityTest[]
   */
  protected $parents;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_test',
    'field',
    'views_tree',
    'views_tree_test',
  ];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['views_tree_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp(FALSE);
    ViewTestData::createTestViews(get_class($this), ['views_tree_test']);

    $this->installEntitySchema('entity_test');

    // Create reference from entity_test to entity_test.
    $this->createEntityReferenceField('entity_test', 'entity_test', 'field_test_parent', 'field_test_parent', 'entity_test', 'default', [], FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $this->createHierarchy();
  }

  /**
   * Tests the tree style plugin.
   */
  public function testTreeStyle() {
    $view = Views::getView('views_tree_test');
    $this->executeView($view);
    $this->assertCount(15, $view->result);

    // Render the view, which will re-sort the result.
    // @see template_preprocess_views_tree()
    $output = $view->render('default');
    $rendered_output = \Drupal::service('renderer')->renderRoot($output);

    // Verify parents are properly set in the result.
    $result = $view->result;
    $this->assertEquals(1, $result[0]->views_tree_parent);
    $this->assertEquals(6, $result[11]->views_tree_parent);

    // Verify rendered output.
    $this->setRawContent($rendered_output);
    $rows = $this->xpath('//span[contains(@class, "field-content")]');
    $this->assertEquals('parent 1', (string) $rows[0]);
    $this->assertEquals('child 1 (parent 1)', (string) $rows[1]);
    $this->assertEquals('parent 2', (string) $rows[4]);
    $this->assertEquals('grand child 1 (c 1, p 2)', (string) $rows[6]);
    $this->assertEquals('parent 3', (string) $rows[11]);
  }

  /**
   * Creates a hierarchy of entity_test entities.
   */
  protected function createHierarchy() {
    // Create 3 parent nodes.
    foreach (range(1, 3) as $i) {
      $entity = EntityTest::create(['name' => 'parent ' . $i]);
      $entity->save();
      $this->parents[$entity->id()] = $entity;

      // Add 3 child entities for each parent.
      foreach (range(1, 3) as $j) {
        $child = EntityTest::create([
          'name' => 'child ' . $j . ' (parent ' . $i . ')',
          'field_test_parent' => ['target_id' => $entity->id()],
        ]);
        $child->save();

        // For parent 2, child 1, add 3 grandchildren.
        if ($i === 2 && $j === 1) {
          foreach (range(1, 3) as $k) {
            $grand_child = EntityTest::create([
              'name' => 'grand child ' . $k . ' (c ' . $j . ', p ' . $i . ')',
              'field_test_parent' => ['target_id' => $child->id()],
            ]);
            $grand_child->save();
          }
        }
      }
    }
  }

}
