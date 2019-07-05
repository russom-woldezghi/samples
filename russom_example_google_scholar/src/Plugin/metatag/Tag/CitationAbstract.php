<?php

namespace Drupal\russom_example_google_scholar\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'citation_abstract' meta tag.
 *
 * @MetatagTag(
 *   id = "citation_abstract",
 *   label = @Translation("Citation Abstract"),
 *   description = @Translation("Citation Abstract"),
 *   name = "citation_abstract",
 *   group = "russom_example_google_scholar",
 *   weight = 1,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = TRUE
 * )
 */
class CitationAbstract extends MetaNameBase {

  // Nothing here yet. Just a placeholder class for a plugin.
}
