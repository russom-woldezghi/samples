<?php

namespace Drupal\russom_example\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Provides a Issue Footer Block.
 *
 * @Block(
 *   id = "issue_footer_block",
 *   admin_label = @Translation("Issue Footer block"),
 *   category = @Translation("Node"),
 * )
 */
class IssueFooterBlock extends BlockBase {

  protected $currentArticle;

  protected $referencedNodes;

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'issue_reference_block',
      '#polls' => $this->getReferencedNodesOnField('field_poll_reference'),
      '#issues' => $this->getReferencedNodesOnField('field_other_issues'),
      '#ctas' => $this->getReferencedNodesOnField('field_call_to_action'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // A unique cache item will be generated for each page (node).
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();

    $current_article = $this->getCurrentArticle();

    if (($current_article instanceof NodeInterface) && ($current_article->bundle() === 'issue')) {

      // The cache will be rebuilt when the current node changes.
      $additional_cache_tags = ['node:' . $current_article->id()];

      // The cache will be rebuilt when the reference nodes change.
      $reference_fields = [
        'field_poll_reference',
        'field_other_issues',
        'field_call_to_action',
      ];

      foreach ($reference_fields as $field_name) {
        foreach ($this->getReferencedNodesOnField($field_name) as $nid) {
          $additional_cache_tags[] = 'node:' . $nid;
        }
      }

      $cache_tags = Cache::mergeTags(parent::getCacheTags(), $additional_cache_tags);
    }

    return $cache_tags;
  }

  protected function getCurrentArticle() {
    if (!isset($this->currentArticle)) {
      $this->currentArticle = \Drupal::routeMatch()->getParameter('node');
    }

    return $this->currentArticle;
  }

  /**
   * @param string $field_name
   *
   * @return mixed
   */
  protected function getReferencedNodesOnField(string $field_name) {

    if (!isset($this->referencedNodes[$field_name])) {
      $this->referencedNodes[$field_name] = [];

      $current_article = $this->getCurrentArticle();

      if (($current_article instanceof NodeInterface) && ($current_article->bundle() === 'issue')) {
        foreach ($current_article->get($field_name)->getValue() as $value) {
          $this->referencedNodes[$field_name][] = $value['target_id'];
        }
      }
    }

    return $this->referencedNodes[$field_name];
  }
}
