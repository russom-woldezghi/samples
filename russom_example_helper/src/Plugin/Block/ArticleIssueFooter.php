<?php

namespace Drupal\russom_example\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Provides a Article Issue Footer Block.
 *
 * @Block(
 *   id = "article_issue_block",
 *   admin_label = @Translation("Article Issue Footer block"),
 *   category = @Translation("Node"),
 * )
 */
class ArticleIssueFooter extends BlockBase {

  protected $articleField = 'field_article';

  protected $currentArticle;

  protected $currentIssue;

  protected $currentIssueOtherArticles;

  /**
   * {@inheritdoc}
   */
  public function build() {

    $current_issue = $this->getCurrentIssue();
    $current_article = $this->getCurrentArticle();

    if (($current_issue instanceof NodeInterface) && ($current_issue->bundle() === 'issue')) {
      return [
        '#theme' => 'article_issue_block',
        '#other_articles_in_issue' => $this->getCurrentIssueOtherArticles($current_article),
        '#issue_publication_date' => $current_issue->field_publication_date->value,
        '#issue_url' => Url::fromRoute('entity.node.canonical', ['node' => $current_issue->id()])
          ->toString(),
      ];
    }
    else {
      return [];
    }
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

    $current_node = $this->getCurrentArticle();

    if (($current_node instanceof NodeInterface) && ($current_node->bundle() === 'article')) {

      // The cache will be rebuilt when the current node changes.
      $additional_cache_tags = ['node:' . $current_node->id()];

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

  protected function getCurrentIssue() {
    if (!isset($this->currentIssue)) {

      $currentArticle = $this->getCurrentArticle();

      if (($currentArticle instanceof NodeInterface) && ($currentArticle->bundle() === 'article')) {

        $issue_query = \Drupal::entityQuery('node')
          ->condition('type', 'issue')
          ->condition($this->articleField, $currentArticle->id())
          ->execute();

        // The issue should be the first result.
        $issue_nid = reset($issue_query);

        if ($issue_nid) {
          $this->currentIssue = Node::load($issue_nid);
        }
      }
    }

    return $this->currentIssue;
  }

  protected function getCurrentIssueOtherArticles($current_article) {
    $issue_articles = $current_article->field_in_this_issue;
    $articles_array = [];
    foreach ($issue_articles as $a) {
      array_push($articles_array, $a->entity->id());
    }
    return $articles_array;
  }
}
