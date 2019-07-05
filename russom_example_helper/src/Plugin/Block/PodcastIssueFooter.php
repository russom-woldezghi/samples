<?php

namespace Drupal\russom_example\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Provides a Podcast Issue Footer Block.
 *
 * @Block(
 *   id = "podcast_issue_block",
 *   admin_label = @Translation("Podcast Issue Footer block"),
 *   category = @Translation("Node"),
 * )
 */
class PodcastIssueFooter extends BlockBase {

  protected $podcastField = 'field_podcast_reference';

  protected $currentPodcast;

  /**
   * {@inheritdoc}
   */
  public function build() {
    $podcast_issue = $this->podcastIssueFooterNid();

    return [
      '#theme' => 'podcast_issue_block',
      '#nids_in_issue' => $podcast_issue['nids_in_issue'],
      '#issue_publication_date' => $podcast_issue['issue_publication_date'],
      '#issue_url' => $podcast_issue['issue_url'],
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

    $current_node = $this->getCurrentPodcast();

    if (($current_node instanceof NodeInterface) && ($current_node->bundle() === 'podcast')) {

      // The cache will be rebuilt when the current node changes.
      $additional_cache_tags = ['node:' . $current_node->id()];

      $cache_tags = Cache::mergeTags(parent::getCacheTags(), $additional_cache_tags);
    }

    return $cache_tags;
  }

  protected function getCurrentPodcast() {
    if (!isset($this->currentPodcast)) {
      $this->currentPodcast = \Drupal::routeMatch()->getParameter('node');
    }

    return $this->currentPodcast;
  }

  /**
   * Gets all the other podcasts in a issue.
   *
   * @return Array with other podcast nids in issue and publication date of
   *   issue.
   */
  protected function podcastIssueFooterNid() {

    $node = $this->getCurrentPodcast();

    if ($node instanceof NodeInterface) {
      $podcast_nid = $node->id();
    }

    // Only podcasts should render this block
    if ($node->bundle() != 'podcast') {
      return TRUE;
    }

    // Query published issues
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'issue')
      ->condition($this->podcastField, $podcast_nid);
    $query_result = $query->execute();

    $issue_nid = reset($query_result);

    // Get issue node and referenced podcasts
    if ($issue_nid) {
      $issue_node = Node::load($issue_nid);

      $issue_podcast_nids = [];
      foreach ($issue_node->{$this->podcastField} as $value) {
        // Must have podcast nid referenced in field_podcast_reference field for block to render
        if ($value->entity) {
          $issue_podcast_nids[] = [
            'id' => $value->entity->id(),
            'view_mode' => 'teaser_footer',
          ];
        }
      }

      // Grab any articles in the issue
      if ($issue_podcast_nids) {
        foreach ($issue_node->get('field_article')->getValue() as $value) {
          $issue_podcast_nids[] = [
            'id' => $value['target_id'],
            'view_mode' => 'teaser_reverse',
          ];
        }
      }

      // Strip out any possible duplicate podcast nid
      foreach ($issue_podcast_nids as $key => $value) {
        if ($value['id'] == $podcast_nid) {
          unset($issue_podcast_nids[$key]);
        }
      }

      // Get a unique list of referenced podcast and articles, remove any dupes
      $other_podcast_nids = array_unique($issue_podcast_nids, SORT_REGULAR);

      // Variables for issue footer in podcast node, printed on page.html.twig file
      $output['nids_in_issue'] = $other_podcast_nids;
      $output['issue_publication_date'] = $issue_node->field_publication_date->value;

      // Get clean url string
      $output['issue_url'] = Url::fromRoute('entity.node.canonical', ['node' => $issue_nid])
        ->toString();

      return $output;
    }
  }

}
