<?php

namespace Drupal\russom_example_forums\Plugin\votingapi_widget;

use Drupal\votingapi_widgets\Plugin\VotingApiWidgetBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Assigns ownership of a node to a user.
 *
 * @VotingApiWidget(
 *   id = "flag",
 *   label = @Translation("Flag"),
 *   values = {
 *    0 = @Translation("None"),
 *    1 = @Translation("Flag"),
 *   },
 * )
 */
class FlagWidget extends VotingApiWidgetBase {

  use StringTranslationTrait;

  /**
   * @inheritdoc
   */
  public function buildForm($entity_type, $entity_bundle, $entity_id, $vote_type, $field_name, $settings) {
    $form = $this->getForm($entity_type, $entity_bundle, $entity_id, $vote_type, $field_name, $settings);
    $build = [
      'rating' => [
        '#theme' => 'container',
        '#attributes' => [
          'class' => [
            'votingapi-widgets',
            'flag',
            ($settings['readonly'] === 1) ? 'read_only' : '',
          ],
        ],
        '#children' => [
          'form' => $form,
        ],
      ],
      '#attached' => [
        'library' => ['russom_example_forums/flag'],
      ],
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getInitialVotingElement(array &$form) {
    $form['value']['#prefix'] = '<div class="votingapi-widgets flag">';
    $form['value']['#attached'] = [
      'library' => ['russom_example_forums/flag'],
    ];
    $form['value']['#suffix'] = '</div>';
  }

  /**
   * {@inheritdoc}
   */
  public function getStyles() {
    return [
      'default' => $this->t('Default'),
    ];
  }

}
