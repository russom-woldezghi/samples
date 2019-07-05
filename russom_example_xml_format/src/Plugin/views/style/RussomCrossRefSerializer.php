<?php

namespace Drupal\russom_example_xml_format\Plugin\views\style;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\rest\Plugin\views\style\Serializer;
use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;

/**
 * Plugin for serialized output formats using XML format.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "russom_example_xml_crossref_serializer",
 *   title = @Translation("Custom CrossRef XML Serializer"),
 *   help = @Translation("Serializes views row data using the Serializer
 *   component for CrossRef."), display_types = {"data"}
 * )
 */
class RussomCrossRefSerializer extends Serializer implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  public function render() {
    /**
     * @var \Drupal\views\ViewExecutable $view
     */
    $view = $this->view;
    $rows = [];
    // If the Data Entity row plugin is used, this will be an array of entities
    // which will pass through Serializer to one of the registered Normalizers,
    // which will transform it to arrays/scalars. If the Data field row plugin
    // is used, $rows will not contain objects and will pass directly to the
    // Encoder.
    foreach ($view->result as $row_index => $row) {
      $view->row_index = $row_index;
      $rows[] = $view->rowPlugin->render($row);
    }
    $view->row_index = NULL;

    // Get the format configured in the display or fallback to the default.
    $format = !empty($this->options['formats']) ? reset($this->options['formats']) : 'xml';
    if (empty($view->live_preview)) {
      $format = $this->displayHandler->getContentType();
    }

    // Issue nid
    $row_issue_nid = $rows[0]->get('nid')->getString();
    $issue_node = Node::load($row_issue_nid);

    // Get all the nids for articles referenced in issues content types
    $articles_issue = array_column($issue_node->field_article->getValue(), 'target_id');

    // Merge array and make sure they are unique ids
    $article_ids = array_unique($articles_issue);

    // Load each article
    $article_nodes_unorder_keys = Node::loadMultiple($article_ids);

    // Reset array index
    $article_nodes = array_values($article_nodes_unorder_keys);

    // Start from 1 instead of 0
    array_unshift($article_nodes, "placeholder");
    unset($article_nodes[0]);

    // Remove unpublished nodes
    $published_articles = [];
    foreach ($article_nodes as $key => $article_node) {
      if ($article_node->isPublished() == FALSE) {
        continue;
      }
      $published_articles[] = $article_node;
    }

    // Issue publication date
    $pub_date = $issue_node->field_publication_date->getString();

    // Timestamp
    $timestamp = \Drupal::time()->getCurrentTime();

    $article_nodes_list = [];
    foreach ($published_articles as $key => $article_node) {
      // DOI value
      $doi = $article_node->field_doi->getString();

      // Path Alias
      $alias = \Drupal::service('path.alias_manager')
        ->getAliasByPath('/node/' . $article_node->id());

      // Site base url
      $base_url = \Drupal::request()->getSchemeAndHttpHost();

      // First page
      $first_page = $article_node->field_first_page->getString();
      if ($first_page[0] !== 'E') {
        $first_page = 'E' . $first_page;
      }

      // Last page
      $last_page = $article_node->field_last_page->getString();

      // Volume
      $volume = $issue_node->field_volume_number->getString();

      // Issue
      $issue = $issue_node->field_issue_number->getString();

      // Issn Number
      $issn_number = $issue_node->field_issn_number->getString();

      // Article node list
      $article_nodes_list[] = [
        'journal_article' => [
          'titles' => [
            'title' => $article_node->getTitle(),
          ],
          'publication_date' => [
            'month' => date('n', strtotime($pub_date)),
            'day' => date('j', strtotime($pub_date)),
            'year' => date('Y', strtotime($pub_date)),
          ],
          'pages' => [
            'first_page' => $first_page,
            'last_page' => $last_page,
          ],
          'publisher_item' => [
            'identifier' => [
              '@id_type' => 'doi',
              $doi,
            ],
          ],
          'doi_data' => [
            'doi' => $doi,
            'timestamp' => $timestamp,
            'resource' => $base_url . $alias,
          ],
        ],
      ];
    }

    // XML File output
    $data['doi_batch'] = [
      '@xmlns' => 'http://www.crossref.org/schema/4.4.0',
      '@xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
      '@version' => '4.4.0',
      '@xsi:schemaLocation' => 'http://www.crossref.org/schema/4.4.0 http://www.crossref.org/schemas/crossref4.4.0.xsd',
      'head' => [
        'doi_batch_id' => 'Russom_Example_' . $volume . '_' . $issue,
        'timestamp' => $timestamp,
        'depositor' => [
          'depositor_name' => 'Russom Example',
          'email_address' => 'example@example.com',
        ],
        'registrant' => 'Russom Example',
      ],
      'body' => [
        'journal' => [
          'journal_metadata' => [
            '@language' => 'en',
            'full_title' => 'Russom Example',
            'issn' => [
              $issn_number,
              '@media_type' => 'electronic',
            ],
          ],
          'journal_issue' => [
            'publication_date' => [
              '@media_type' => "online",
              'month' => date('n', strtotime($pub_date)),
              'day' => date('j', strtotime($pub_date)),
              'year' => date('Y', strtotime($pub_date)),
            ],
            'journal_volume' => [
              'volume' => $volume,
            ],
            'issue' => $issue,
          ],
          $article_nodes_list,
        ],
      ],
    ];

    return $this->serializer->serialize($data, $format, $this->getContext());
  }

  /**
   * Return the context with all fields needed in the normalizer.
   *
   * @return array
   *   The context values.
   */
  private function getContext() {
    return [
      'views_style_plugin' => $this,
      'view_id' => 'russom_example_xml_crossref_serializer',
    ];
  }
}
