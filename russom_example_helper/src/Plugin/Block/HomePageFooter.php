<?php

namespace Drupal\russom_example\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Provides a Homepage Footer Block.
 *
 * @Block(
 *   id = "homepage_footer_block",
 *   admin_label = @Translation("Homepage Footer block"),
 *   category = @Translation("Node"),
 * )
 */
class HomePageFooter extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $homepage_reference = $this->homepageFooterNids();
    return [
      '#theme' => 'homepage_footer_block',
      '#polls' => isset($homepage_reference['referenced_poll']) ? $homepage_reference['referenced_poll'] : '',
      '#archives' => isset($homepage_reference['referenced_archives']) ? $homepage_reference['referenced_archives'] : '',
      '#ctas' => isset($homepage_reference['referenced_cta']) ? $homepage_reference['referenced_cta'] : '',
      '#upcoming_themes' => isset($homepage_reference['upcoming_themes']) ? $homepage_reference['upcoming_themes'] : '',
      '#about_title' => $homepage_reference['about_title'],
      '#about_description' => $homepage_reference['about_description'],
      '#about_link' => Url::fromUri($homepage_reference['about_link'][0]['uri']),
      '#about_link_title' => $homepage_reference['about_link'][0]['title'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   * Gets all the nids of nodes in a homepage.
   *
   * @return Array with node nids in homepage content type.
   */
  protected function homepageFooterNids() {
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node instanceof NodeInterface) {
      $homepage_nid = $node->id();
    }

    // Only homepage should render this block
    if ($node->bundle() != 'homepage') {
      return TRUE;
    }

    // Reference field
    $ref_field = [
      'referenced_poll' => 'field_poll_reference',
      'referenced_archives' => 'field_from_the_archive',
      'referenced_cta' => 'field_call_to_action_homepage',
      'about_title' => 'field_about_title',
      // 'about_description' => 'field_about_description',
      'about_link' => 'field_about_link',
      'upcoming_themes' => 'field_upcoming_themes',
    ];

    // Get issue node and referenced fields
    if ($homepage_nid) {
      $homepage_node = Node::load($homepage_nid);

      /**
       * Reference fields
       */

      $referenced_fields = [];
      foreach ($ref_field as $key => $field) {
        foreach ($homepage_node->get($field)->getValue() as $value) {
          if (isset($value['uri']) || isset($value['value'])) {
            $referenced_fields[$key][] = $value;
          }
          if (isset($value['target_id'])) {
            $referenced_fields[$key][] = $value['target_id'];
          }
        }
      }

      // Storing variable seperately to safely pass accepted HTML characters without using unsafe filters on Twig
      // field_about_description is text area field
      $about_description_text = $homepage_node->get('field_about_description')
        ->getValue()[0]['value'];
      $referenced_fields['about_description'][] = ['#markup' => $about_description_text];

      return $referenced_fields;
    }
  }
}
