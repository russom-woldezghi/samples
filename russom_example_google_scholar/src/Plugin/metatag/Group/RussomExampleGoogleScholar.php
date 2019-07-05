<?php

namespace Drupal\russom_example_google_scholar\Plugin\metatag\Group;

use Drupal\metatag\Plugin\metatag\Group\GroupBase;

/**
 * The Russom Example Google Scholar group.
 *
 * @MetatagGroup(
 *   id = "russom_example_google_scholar",
 *   label = @Translation("Russom Example Google Scholar"),
 *   description = @Translation("Additional custom meta tags for indexing
 *   scholarly articles by Google Scholar."), weight = 4
 * )
 */
class RussomExampleGoogleScholar extends GroupBase {

  // Inherits everything from Base.
}
