<?php

namespace Drupal\russom_example_google_scholar\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'citation_publisher' meta tag.
 *
 * @MetatagTag(
 *   id = "citation_publisher",
 *   label = @Translation("Citation Publisher"),
 *   description = @Translation("Citation Publisher"),
 *   name = "citation_publisher",
 *   group = "russom_example_google_scholar",
 *   weight = 2,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = TRUE
 * )
 */
class CitationPublisher extends MetaNameBase {

  // Nothing here yet. Just a placeholder class for a plugin.
}
