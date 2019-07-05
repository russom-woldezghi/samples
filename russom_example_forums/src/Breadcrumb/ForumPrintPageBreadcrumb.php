<?php

namespace Drupal\russom_example_forums\Breadcrumb;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Provides a breadcrumb builder base class for forum printing pages.
 * Because the forum print page has its own controller, we have to
 * replicated the breadcrumb as seen in ForumBreadcrumbBuilderBase to
 * generate a similar breadcrumb.
 */
class ForumPrintPageBreadcrumb implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    // Apply to only forum print page/controller.
    return $route_match->getRouteName() == 'russom_example_forums.print_controller';
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {

    // Get id from url as tid value for loading term object.
    $tid = Url::fromRouteMatch($route_match)->getRouteParameters()['id'];
    $term = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->load($tid);
    $term_id = $term->id();

    // Get parents term.
    $parents = \Drupal::service('entity_type.manager')
      ->getStorage("taxonomy_term")
      ->loadAllParents($term_id);

    // Start building the breadcrumb.
    $breadcrumb = new Breadcrumb();

    // Set cache context, tags and dependencies.
    $breadcrumb->addCacheContexts(["url"]);
    $breadcrumb->addCacheTags(["term:{$tid}"]);
    $breadcrumb->addCacheableDependency($term);

    // Add "Home" and "Forum" breadcrumb link.
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));

    $breadcrumb->addLink(Link::fromTextAndUrl($this->t('Forum'), Url::fromUri('base:forum')));

    // Build out the rest of the parent taxonomy tree, term is currently in.
    if ($parents) {
      foreach (array_reverse($parents) as $parent) {
        if ($parent->id() != $term_id) {
          $breadcrumb->addCacheableDependency($parent);
          $breadcrumb->addLink(Link::createFromRoute($parent->label(), 'forum.page', [
            'taxonomy_term' => $parent->id(),
          ]));
        }
      }
    }
    return $breadcrumb;
  }
}
