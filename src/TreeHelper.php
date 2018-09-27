<?php

namespace Drupal\views_tree;

/**
 * The tree helper service.
 */
class TreeHelper {

  /**
   * Builds a tree from a views result.
   *
   * @param array $result
   *   The views results with views_tree_main and views_tree_parent set.
   *
   * @return \Drupal\views_tree\TreeItem
   *   A tree representation.
   */
  public function getTreeFromResult(array $result) {
    $groups = $this->groupResultByParent($result);
    return $this->getTreeFromGroups($groups);
  }

  /**
   * Get a tree from given groups.
   *
   * @param array $groups
   *   The groups.
   * @param string $current_group
   *   The current group.
   *
   * @return \Drupal\views_tree\TreeItem
   *   The tree for the given groups.
   */
  protected function getTreeFromGroups(array $groups, $current_group = '0') {
    $return = new TreeItem(NULL);

    if (empty($groups[$current_group])) {
      return $return;
    }

    foreach ($groups[$current_group] as $item) {
      $tree_item = new TreeItem($item);
      $return->addLeave($tree_item);
      $tree_item->setLeaves($this->getTreeFromGroups($groups, $item->views_tree_main)->getLeaves());
    }
    return $return;
  }

  /**
   * Groups results by parent.
   *
   * @param array $result
   *   The result set.
   *
   * @return array
   *   Result grouped by parent.
   */
  protected function groupResultByParent(array $result) {
    $return = [];

    foreach ($result as $row) {
      $return[$row->views_tree_parent][] = $row;
    }
    return $return;
  }

  /**
   * Applies a given callable to each row and leaf.
   *
   * @param \Drupal\views_tree\TreeItem $tree
   *   The tree item.
   * @param callable $callable
   *   The callable.
   *
   * @return \Drupal\views_tree\TreeItem
   *   The new tree item.
   */
  public function applyFunctionToTree(TreeItem $tree, callable $callable) {
    if (($node = $tree->getNode()) && $node !== NULL) {
      $new_node = $callable($tree->getNode());
    }
    else {
      $new_node = NULL;
    }
    $new_tree = new TreeItem($new_node);
    foreach ($tree->getLeaves() as $leave) {
      $new_tree->addLeave($this->applyFunctionToTree($leave, $callable));
    }
    return $new_tree;
  }

}
