<?php

namespace Drupal\russom_example_xml_format\Plugin\views\style;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\file\Entity\File;
use Drupal\rest\Plugin\views\style\Serializer;
use Drupal\node\Entity\Node;


/**
 * Plugin for serialized output formats using XML format.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "russom_example_xml_pubmed_serializer",
 *   title = @Translation("Custom Pubmed XML Serializer"),
 *   help = @Translation("Serializes views row data using the Serializer
 *   component for Pubmed."), display_types = {"data"}
 * )
 */
class RussomPubmedSerializer extends Serializer implements CacheableDependencyInterface {

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

    // Article node list
    $issue_article_nodes = [];
    foreach ($published_articles as $key => $article_node) {

      // DOI string
      $doi = $article_node->field_doi->getString();

      // PII string, storing value to be injected into the xml file at a later point
      $pii = $article_node->field_pii->getString();

      // Author list
      $author_paragraph = $article_node->field_author->getValue();

      //@todo Suffix field from Pubmed, Middle name from content model, confirm Affliation as bio
      $author_list = [];
      foreach ($author_paragraph as $key => $element) {
        $p = \Drupal\paragraphs\Entity\Paragraph::load($element['target_id']);
        $author_list['Author'][$key]['FirstName'] = $p->field_first_name->value;
        $author_list['Author'][$key]['MiddleName'] = $p->field_middle_name->value;;
        $author_list['Author'][$key]['LastName'] = $p->field_last_name->value;
        $author_list['Author'][$key]['Affiliation'] = $p->field_biography->value;
      }

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

      // Copyright text
      $copyright = 'Copyright ' . date('Y') . ' Russom Example. All Rights Reserved.';

      // ArchiveCopySource
      $archiveCopySource = '';
      if (!$issue_node->field_pdf_download_file->isEmpty()) {
        $fid = $issue_node->field_pdf_download_file->first()
          ->getValue()['target_id'];
        $archiveCopySource = File::load($fid)->url();
      }


      $issue_article_nodes[] = [
        'Article' => [
          'Journal' => [
            'PublisherName' => 'Publisher Name',
            'JournalTitle' => 'Journal Title',
            'Issn' => $issn_number,
            'Volume' => $volume,
            'Issue' => $issue,
            'PubDate' => [
              '@PubStatus' => 'epublish',
              'Year' => date('Y', strtotime($pub_date)),
              'Month' => date('F', strtotime($pub_date)),
              'Day' => date('j', strtotime($pub_date)),
            ],
          ],
          'ArticleTitle' => $article_node->getTitle(),
          'FirstPage' => $first_page,
          'LastPage' => $last_page,
          // Hacky, but solves the problem to have same node elements with different attributes
          // Wrapped in <item> node that Drupal/Syfony will process
          'ELocationID' => [
            '@EIdType' => 'doi',
            $doi,
          ],
          [
            'ELocationID' => [
              '@EIdType' => 'pii',
              $pii,
            ],
          ],
          'Language' => 'EN',
          'AuthorList' => [
            $author_list,
          ],
          'ArticleIdList' => [
            // Hacky, but solves the problem to have same node elements with different attributes
            // Wrapped in <item> node that Drupal/Syfony will process
            [
              'ArticleId' => [
                '@IdType' => 'pii',
                $pii,
              ],
            ],
            'ArticleId' => [
              '@IdType' => 'doi',
              $doi,
            ],
          ],
          'Abstract' => [
            $article_node->field_abstract->value,
          ],
          'CopyrightInformation' => $copyright,
          'CoiStatement' => $article_node->field_coi_statement->value,
          'ArchiveCopySource' => [
            '@DocType' => 'pdf',
            $archiveCopySource,
          ],
        ],
      ];
    }

    $data['ArticleSet'] = [
      $issue_article_nodes,
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
      'view_id' => 'russom_example_xml_pubmed_serializer',
    ];
  }
}
