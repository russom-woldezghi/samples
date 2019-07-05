<?php

namespace Drupal\russom_example_forums\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * ForumPrintController generates print page to load content recursively based
 * on forum taxonomy tree structure.
 *
 * @package Drupal\russom_example_forums\Controller
 */
class ForumPrintController extends ControllerBase {

  /**
   * Loads forums by term ids from forums vocabulary and renders child terms
   * and their nodes for custom print page.
   *
   * @param $id
   *
   * @return array
   * Returns rendered taxonomy term and node objects.
   */
  public function load($id) {

    // Service to add 'children' property to forums vocabulary.
    $vocabularies = \Drupal::service('russom_example_forums.taxonomy_term_tree')
      ->load('forums');

    // Filter out term id and it children.
    $filter_vocabulary_tree_ids = $this->searchForTid($id, $vocabularies);

    // Check if $filter_vocabulary_tree_ids is null, so it doesn't continue with NULL values.
    if (!is_null($filter_vocabulary_tree_ids)) {
      // Get term objects.
      $forum_objects = $this->getTermObjects($filter_vocabulary_tree_ids);

      // Now we have our term objects, we need node objects as well for rendering
      $forum_objects = $this->getNodeObjects($forum_objects);

      // Render objects in array.
      $render = $this->renderForumObjects($forum_objects);

      // Output terms and nodes.
      return $render;
    }
  }

  /**
   * Searches for matching $tid in vocabulary tree.
   *
   * @param $tid
   * @param $array
   *
   * @return array
   */
  public function searchForTid($tid, $array) {
    // Reset array instead of given keys.
    array_values($array);

    // Flatten array to find id.
    $iterator = $this->iterator($array);

    // Get children if nested within each term array.
    $result = [];
    foreach ($iterator as $key => $value) {
      if (is_object($value) && $value->tid == $tid) {
        $result = $value;
      }
    }
    return (empty($result) || is_null($result)) ? NULL : $result;
  }

  /**
   * Returns a page title for forum print page.
   *
   * @param $tid
   *
   * @return string
   */
  public function getTitle($tid) {
    $term = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->load($tid);
    if ($term) {
      $result = $term->getName();
      return $result;
    }
  }

  /**
   * Adds term object to vocabulary tree.
   *
   * @param $array
   *
   * @return array
   */
  public function getTermObjects($array) {
    // Populate return array with parent and child terms.
    $result = [];

    // Get the parent object before adding the child terms.
    $parent = $array;

    if ($parent->tid) {
      $parent_term = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties([
          'tid' => $parent->tid,
        ]);
      $result[$parent->tid]['taxonomy_term'] = $parent_term;
    }

    // Flatten array to then load the Drupal term objects.
    $iterator = $this->iterator($array);

    // For each term, get the Drupal term object.
    foreach ($iterator as $key => $item) {
      $term = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties([
          'tid' => $key,
        ]);
      if (is_object($item)) {
        $result[$item->tid]['taxonomy_term'] = $term;
      }
    }
    return $result;
  }

  /**
   * Adds node object(s) to vocabulary tree.
   *
   * @param $array
   *
   * @return array
   */
  public function getNodeObjects($array) {
    // Retrieves node objects associated to term ids in array.
    $result = [];

    // Foreach term object, add node objects to the array.
    foreach ($array as $key => $value) {
      foreach ($value['taxonomy_term'] as $taxonomy) {
        $node = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->loadByProperties([
            'taxonomy_forums' => $taxonomy->id(),
          ]);
        $value['node'] = $node;
      }

      $result[$key] = $value;
    }
    return $result;
  }

  /**
   * Returns rendered term and node objects.
   *
   * @param $array
   *
   * @return array
   */
  public function renderForumObjects($array) {

    $result = [];
    foreach ($array as $key => $value) {
      // Each object is grouped by entity type name as the $key value.
      foreach ($value as $value_key => $item) {
        // Render each object and store rendered object in new array.
        foreach ($item as $item_key => $i) {
          $result[$key][$value_key][] = \Drupal::entityTypeManager()
            ->getViewBuilder($value_key)
            ->view($i, 'default');
        }
      }
    }
    return $result;
  }

  /**
   * Returns flattened tree structure of array.
   *
   * @param $array
   *
   * @return array | object
   */
  private function iterator($array) {
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveArrayIterator($array),
      \RecursiveIteratorIterator::SELF_FIRST
    );
    return $iterator;
  }
}
