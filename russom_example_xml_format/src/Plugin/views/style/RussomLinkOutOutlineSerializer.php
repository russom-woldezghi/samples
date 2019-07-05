<?php

namespace Drupal\russom_example_xml_format\Plugin\views\style;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\rest\Plugin\views\style\Serializer;


/**
 * Plugin for serialized output formats using XML format.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "russom_example_xml_linkout_outline_serializer",
 *   title = @Translation("Custom LinkOut Outline XML Serializer"),
 *   help = @Translation("Serializes views row data using the Serializer
 *   component for LinkOut Outline."), display_types = {"data"}
 * )
 */
class RussomLinkOutOutlineSerializer extends Serializer implements CacheableDependencyInterface {

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

    // Site base url
    $base_url = \Drupal::request()->getSchemeAndHttpHost();

    // XML File output
    $data['Provider'] = [
      'ProviderId' => '8377',
      [
        'Name' => 'Russom Example',
        'NameAbbr' => 'Russ Ex.',
        // @todo
        'SubjectType' => '',
        'Attribute' => '',
        'Url' => $base_url,
        'Brief' => '',
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
      'view_id' => 'russom_example_xml_linkout_outline_serializer',
    ];
  }

}
