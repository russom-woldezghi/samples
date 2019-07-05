<?php

namespace Drupal\russom_example\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a Header Search Bar Block.
 *
 * @Block(
 *   id = "header_search_bar_block",
 *   admin_label = @Translation("Header Search Bar Block"),
 *   category = @Translation("Node"),
 * )
 */
class HeaderSearchBarBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Load custom header search bar
    $form = \Drupal::formBuilder()
      ->getForm('Drupal\russom_example\Form\HeaderSearchBar');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

}
