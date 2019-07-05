<?php

namespace Drupal\russom_example\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\Filter\FilterAlign;

/**
 * Provides a filter to align elements.
 *
 * @Filter(
 *   id = "russom_example_filter_align",
 *   title = @Translation("Russom Example Align images"),
 *   description = @Translation("Uses a <code>data-align</code> attribute on
 *   <code>&lt;img&gt;</code> tags to align images. Supports 'portrait'
 *   alignment option. Needs to be above 'Align images' filter. "), type =
 *   Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class RussomExampleAlignFilter extends FilterAlign {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    if (stristr($text, 'data-align') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      $classes = [];
      foreach ($xpath->query('//*[@data-align]') as $node) {
        // Read the data-align attribute's value, then delete it.
        $align = $node->getAttribute('data-align');
        $node->removeAttribute('data-align');

        // If one of the allowed alignments, add the corresponding class.
        if (in_array($align, ['left', 'center', 'right'])) {
          $classes = $node->getAttribute('class');
          $classes = (strlen($classes) > 0) ? explode(' ', $classes) : [];
          $classes[] = $align;

          // Add wrapping figure element and set class
          $wrapper = $dom->createElement('figure');
          $wrapper->setAttribute('class', 'russom-example__inline-image russom-example__inline-image--' . $classes[0]);

          $clone = $node->cloneNode();

          // add button element and set class
          $button = $dom->createElement('button');
          $button->setAttribute('class', 'russom-example__inline-image__zoom');

          $wrapper->appendChild($button);

          $wrapper->appendChild($clone);

          // For each image apply custom wrapping
          $node->parentNode->replaceChild($wrapper, $node);

        }
      }

      $result->setProcessedText(Html::serialize($dom));

    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('
      <p>You can align images, videos, blockquotes and so on to the left, right or center. Examples:</p>
      <ul>
      <li>Align an image to the left: <code>&lt;img src="" data-align="left" /&gt;</code></li>
      <li>Align an image to the center: <code>&lt;img src="" data-align="center" /&gt;</code></li>
      <li>Align an image to the right: <code>&lt;img src="" data-align="right" /&gt;</code></li>
      <li>… and you can apply this to other elements as well: <code>&lt;video src="" data-align="center" /&gt;</code></li>
      </ul>');
    }
    else {
      return $this->t('You can align images (<code>data-align="center"</code>), but also videos, blockquotes, and so on.');
    }
  }

}
