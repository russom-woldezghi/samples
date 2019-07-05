<?php

namespace Drupal\russom_example_google_scholar\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'citation_doi' meta tag.
 *
 * @MetatagTag(
 *   id = "citation_doi",
 *   label = @Translation("Citation DOI"),
 *   description = @Translation("Citation DOI"),
 *   name = "citation_doi",
 *   group = "russom_example_google_scholar",
 *   weight = 2,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = TRUE
 * )
 */
class CitationDoi extends MetaNameBase {

  // Nothing here yet. Just a placeholder class for a plugin.
}
