<?php

namespace Drupal\russom_example_google_scholar\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'citation_fulltext_html_url' meta tag.
 *
 * @MetatagTag(
 *   id = "citation_fulltext_html_url",
 *   label = @Translation("Citation Full Text HTML URL"),
 *   description = @Translation("Citation Full Text HTML URL"),
 *   name = "citation_fulltext_html_url",
 *   group = "russom_example_google_scholar",
 *   weight = 1,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = TRUE
 * )
 */
class CitationFullTextHtmlUrl extends MetaNameBase {

  // Nothing here yet. Just a placeholder class for a plugin.
}
