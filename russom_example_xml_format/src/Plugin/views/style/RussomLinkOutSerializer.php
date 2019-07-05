<?php

namespace Drupal\russom_example_xml_format\Plugin\views\style;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\rest\Plugin\views\style\Serializer;
use Drupal\node\Entity\Node;

/**
 * Plugin for serialized output formats using XML format.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "russom_example_xml_linkout_serializer",
 *   title = @Translation("Custom LinkOut XML Serializer"),
 *   help = @Translation("Serializes views row data using the Serializer
 *   component for LinkOut."), display_types = {"data"}
 * )
 */
class RussomLinkOutSerializer extends Serializer implements CacheableDependencyInterface {

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

    $article_nodes_link = [];
    foreach ($published_articles as $key => $article_node) {
      // path alias of article node
      $alias = \Drupal::service('path.alias_manager')
        ->getAliasByPath('/node/' . $article_node->id());
      $article_nodes_link[] = [
        'Link' => [
          'LinkId' => $key,
          'ProviderId' => '8377',
          'IconUrl' => '&icon.url;',
          'ObjectSelector' => [
            //@todo assumption made about Database, need to confirm
            'Database' => 'PubMed',
            'ObjectList' => [
              'ObjId' => $article_node->field_pubmed_id->value,
            ],
          ],
          'ObjectUrl' => [
            'Base' => '&base.url;',
            'Rule' => $alias,
            'Attribute' => 'full-text online',
          ],
        ],
      ];
    }

    // XML file output
    $data['LinkSet'] = [
      $article_nodes_link,
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
      'view_id' => 'russom_example_xml_linkout_serializer',
    ];
  }
}
