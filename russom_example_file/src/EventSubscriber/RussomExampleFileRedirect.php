<?php

namespace Drupal\russom_example_file\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Url;

/**
 * Allows redirects of deleted files.
 * If a redirect is discovered in the redirect module using the requested path,
 * the request is redirected to the path saved.
 */
class RussomExampleFileRedirect implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public function onRespond(FilterResponseEvent $event) {
    // Check if request is a master and not a sub request.
    if (!$event->isMasterRequest()) {
      return;
    }
    $response = $event->getResponse();

    if (\Drupal::request()->query->has('file') && $response->headers->has('Content-Type')) {
      $target = \Drupal::request()->query->get('file');
      $file_uri = 'private://' . $target;
      $realPath = \Drupal::service('file_system')->realpath($file_uri);

      // If real path in the directory, find any redirects by source path.
      if ($realPath) {
        $uri_request = \Drupal::request()->getRequestUri();
        $trimmed_uri = ltrim($uri_request, '/');
        $redirect = \Drupal::service('redirect.repository')
          ->findBySourcePath($trimmed_uri);

        if ($redirect) {
          // Get first element in array.
          $url = reset($redirect)->getRedirectUrl();
          $this->setResponse($event, $url);
        }
      }
    }
  }


  /**
   * Redirect and cache response.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   * @param \Drupal\Core\Url $url
   */
  protected function setResponse(FilterResponseEvent $event, Url $url) {
    $response = new TrustedRedirectResponse($url->toString(), 301);
    $response->addCacheableDependency(CacheableMetadata::createFromRenderArray([])
      ->addCacheTags(['rendered']));
    $event->setResponse($response);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond', -100];
    return $events;
  }

}
