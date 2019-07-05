<?php

namespace Drupal\russom_example_listing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;

/**
 * Russom Example Directory Listing Controller.
 * Prepares JSON output for Vue application.
 */
class RussomExampleDirectoryController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function get() {
    // Initialize JsonResponse.
    $response = new JsonResponse();

    $data = $this->getData();
    $response->setData($data);

    // Return JSON object.
    return $response;

  }

  public function getData() {
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'company')
      ->condition('status', '1')
      ->execute();
    $nodes = Node::loadMultiple($nids);

    foreach ($nodes as $node) {
      // NID of node.
      $nid = $node->get('nid')->getValue()[0]['value'];

      // Node path.
      $path = \Drupal::service('path.alias_manager')
        ->getAliasByPath('/node/' . $nid);

      // Badges reference.
      $badges = $node->get('field_block_badges')->referencedEntities();

      // Preview image url, load file and style image.
      $preview_image = $node->get('field_preview_image')->referencedEntities();
      if (empty($preview_image)) {
        $preview_image == NULL;
      }
      else {
        // Get first preview image
        $first_preview_image = reset($preview_image)->field_media_image;
        $image_id = $first_preview_image->entity->id();

        $file = File::load($image_id);
        $image_uri = ImageStyle::load('teaser')->buildUrl($file->getFileUri());

        $preview_image = [
          'uri' => file_create_url($image_uri),
          'alt' => $first_preview_image[0]->alt,
        ];

      }

      // Rating from votingapi.
      $rating = 0;
      $voting_service = \Drupal::service('plugin.manager.votingapi.resultfunction');
      if ($voting_service->getResults('node', $node->id())) {
        $rating = $voting_service->getResults('node', $node->id());
      }

      // Members value.
      $members = 0;
      if ($node->get('field_company_members')->getValue()) {
        $members = $node->get('field_company_members')->getValue()[0]['value'];
      }

      // Product references.
      $vocab = NULL;
      if ($product_references = $node->get('field_ref_product')
        ->referencedEntities()
      ) {

        foreach ($product_references as $product) {
          if ($product->isPublished() == FALSE) {
            continue;
          }

          // Terms on product nodes. Gets all terms from all products referenced.
          $fields = [
            'field_taxonomy_commodities',
            'field_taxonomy_issues',
            'field_taxonomy_solutions',
          ];
          foreach ($fields as $field) {
            foreach ($product->{$field} as $item) {
              if ($item->entity) {
                $vocab[$field][$item->entity->id()] = $item->entity->label();
              }
            }
          }
        }
      }

      // State name, get key instead of value.
      $allowed_values = $node->getFieldDefinition('field_company_state')
        ->getFieldStorageDefinition()
        ->getSetting('allowed_values');
      $state_value = $node->get('field_company_state')->value;
      $state_key['state_name'] = $allowed_values[$state_value];

      // Acres value.
      $acres = NULL;
      if ($node->get('field_company_acres')->getValue()) {
        $values = $node->getFieldDefinition('field_company_acres')
          ->getFieldStorageDefinition()
          ->getSetting('allowed_values');
        $acre_value = $node->get('field_company_acres')->value;
        $acres = $values[$acre_value];
      }

      // Year value.
      $year = 0;
      if ($node->get('field_year_founded')->getValue()) {
        $year = $node->get('field_year_founded')->getValue()[0]['value'];
      }

      $data[] = [
        'title' => $node->get('title')->getValue()[0]['value'],
        'url' => $path,
        'city' => $node->get('field_company_city')->getValue()[0]['value'],
        'state' => $state_key['state_name'],
        'state_abbreviation' => $node->get('field_company_state')
          ->getValue()[0]['value'],
        'year' => $year,
        'number_members' => $members,
        'acres' => $acres,
        'badges' => (count((array) $badges)) ? count((array) $badges) : 0,
        'image' => $preview_image,
        'rating' => $rating['vote']['vote_average'],
        'issues' => isset($vocab['field_taxonomy_issues']) ? array_values($vocab['field_taxonomy_issues']) : NULL,
        'solutions' => isset($vocab['field_taxonomy_solutions']) ? array_values($vocab['field_taxonomy_solutions']) : NULL,
        'commodities' => isset($vocab['field_taxonomy_commodities']) ? array_values($vocab['field_taxonomy_commodities']) : NULL,
      ];
    }

    return $data;
  }

  /**
   * Returns a directory page.
   *
   * @return array
   *
   */
  public function directoryPage() {
    return [
      '#theme' => 'russom_example_template',
      '#var' => '',
      '#attached' => [
        'library' => [
          'russom_example_listing/listing',
        ],
        'drupalSettings' => ['russom_example_data' => $this->getData()],
      ],
    ];
  }
}
