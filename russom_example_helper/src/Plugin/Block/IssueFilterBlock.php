<?php

namespace Drupal\russom_example\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a Issue Filter Block.
 *
 * @Block(
 *   id = "issue_filter_block",
 *   admin_label = @Translation("Issue Filter block"),
 *   category = @Translation("Issue Filter for generating tabbing like browse
 *   functionality."),
 * )
 */
class IssueFilterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $organized_year_decade = $this->publishedIssueDates();
    return [
      '#theme' => 'issue_filter_block',
      '#dates' => $organized_year_decade,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   * Gets all the published issue node date values for views filtering
   */
  protected function publishedIssueDates() {

    // Query for published issue nodes
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'issue')
      ->condition('status', 1)
      ->sort('field_publication_date', 'DSC');
    $result = $query->execute();

    // Result of issue query
    if ($result) {
      $nodes = Node::loadMultiple($result);

      $published_years = [];
      foreach ($nodes as $key => $node) {
        $date = $node->field_publication_date->value;
        if ($date) {
          $date = new DrupalDateTime($date, new \DateTimeZone('UTC'));
          // Give a different file format for display of year
          $year = $date->format('Y');
          $decade = floor((int) $year / 10) * 10;

          // Filter URL for each year, adds is-active class to active paths
          $path = '/issues?year=' . $year; // prefixed with /
          $url = Url::fromUri('internal:' . $path, ['set_active_class' => TRUE]);
          $link = Link::fromTextAndUrl($year, $url);
          $link = $link->toRenderable();
          $url = render($link);

          // published year value
          $published_years[$key] = [
            'year' => $year,
            'decade' => $decade . '\'s',
            'url' => $url,
          ];
        }
      }
    }
    // Re-organize fields by decade
    // Group each year by decade
    $organized_year_decade = [];

    foreach ($published_years as $key => $item) {
      $organized_year_decade[$item['decade']][] = $item;
    }

    return $organized_year_decade;
  }

}
