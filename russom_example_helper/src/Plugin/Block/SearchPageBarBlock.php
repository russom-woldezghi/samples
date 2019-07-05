<?php

namespace Drupal\russom_example\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a Search Page Bar Block.
 *
 * @Block(
 *   id = "search_page_bar_block",
 *   admin_label = @Translation("Search Page Bar Block"),
 *   category = @Translation("Node"),
 * )
 */
class SearchPageBarBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Load search bar form for view page
    $form = \Drupal::formBuilder()
      ->getForm('Drupal\russom_example\Form\SearchBar');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

}
