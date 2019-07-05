<?php

namespace Drupal\russom_example_xml_format\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\row\RssFields;

/**
 * Renders an RSS item based on fields. Uses default views_view_row_rss theme to
 * output XML results.
 *
 * @ViewsRow(
 *   id = "russom_example_rss_fields",
 *   title = @Translation("Russom Example Fields"),
 *   help = @Translation("Custom RSS feed, display fields as RSS items."),
 *   theme = "views_view_row_rss",
 *   display_types = {"feed"}
 * )
 */
class RussomExampleRssFields extends RssFields {

  /**
   * Does the row plugin support to add fields to its output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['category_field'] = ['default' => ''];
    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $initial_labels = ['' => $this->t('- None -')];
    $view_fields_labels = $this->displayHandler->getFieldLabels();
    $view_fields_labels = array_merge($initial_labels, $view_fields_labels);

    $form['category_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#description' => $this->t('The field that is going to be used as the RSS item category for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['category_field'],
      '#required' => TRUE,
    ];
  }

  public function validate() {
    $errors = parent::validate();
    $required_options = ['category_field'];
    foreach ($required_options as $required_option) {
      if (empty($this->options[$required_option])) {
        $errors[] = $this->t('Row style plugin requires specifying which views fields to use for RSS item.');
        break;
      }
    }
    return $errors;
  }

  /**
   * Prepares the values to be rendered for each row. Additional fields from the
   * parent class are included in rendering the output.
   *
   * @param object $row
   *
   * @return array
   */
  public function render($row) {
    static $row_index;
    if (!isset($row_index)) {
      $row_index = 0;
    }
    if (function_exists('rdf_get_namespaces')) {
      // Merge RDF namespaces in the XML namespaces in case they are used
      // further in the RSS content.
      $xml_rdf_namespaces = [];
      foreach (rdf_get_namespaces() as $prefix => $uri) {
        $xml_rdf_namespaces['xmlns:' . $prefix] = $uri;
      }
      $this->view->style_plugin->namespaces += $xml_rdf_namespaces;
    }

    // Create the RSS item object.
    $item = new \stdClass();
    $item->title = parent::getField($row_index, $this->options['title_field']);
    // @todo Views should expect and store a leading /. See:
    //   https://www.drupal.org/node/2423913
    $item->link = Url::fromUserInput('/' . parent::getField($row_index, $this->options['link_field']))
      ->setAbsolute()
      ->toString();

    $field = parent::getField($row_index, $this->options['description_field']);
    $item->description = is_array($field) ? $field : ['#markup' => $field];


    $field_category = parent::getField($row_index, $this->options['category_field']);
    $item->category = is_array($field_category) ? $field_category : ['#markup' => $field_category];

    $item->elements = [
      [
        'key' => 'pubDate',
        'value' => parent::getField($row_index, $this->options['date_field']),
      ],
      [
        'key' => 'dc:creator',
        'value' => parent::getField($row_index, $this->options['creator_field']),
        'namespace' => ['xmlns:dc' => 'http://purl.org/dc/elements/1.1/'],
      ],
      ['key' => 'category', 'value' => $item->category],
    ];
    $guid_is_permalink_string = 'false';
    $item_guid = parent::getField($row_index, $this->options['guid_field_options']['guid_field']);
    if ($this->options['guid_field_options']['guid_field_is_permalink']) {
      $guid_is_permalink_string = 'true';
      // @todo Enforce GUIDs as system-generated rather than user input? See
      //   https://www.drupal.org/node/2430589.
      $item_guid = Url::fromUserInput('/' . $item_guid)
        ->setAbsolute()
        ->toString();
    }
    $item->elements[] = [
      'key' => 'guid',
      'value' => $item_guid,
      'attributes' => ['isPermaLink' => $guid_is_permalink_string],
    ];

    $row_index++;

    foreach ($item->elements as $element) {
      if (isset($element['namespace'])) {
        $this->view->style_plugin->namespaces = array_merge($this->view->style_plugin->namespaces, $element['namespace']);
      }
    }

    $build = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $item,
      '#field_alias' => isset($this->field_alias) ? $this->field_alias : '',
    ];

    return $build;
  }
}
