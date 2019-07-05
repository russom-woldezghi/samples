<?php

namespace Drupal\russom_example_forums\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\votingapi_widgets\Form\BaseRatingForm;

/**
 * Form controller for "flag" plugin forms.
 */
class FlagBaseRatingForm extends BaseRatingForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    // Get entity ids, types and user id to perform query.
    $entity_id = $form_state->getFormObject()->getEntity()->getVotedEntityId();
    $entity_type = $form_state->getFormObject()
      ->getEntity()
      ->getVotedEntityType();
    $user_id = \Drupal::currentUser()->id();

    $submitted_vote = $this->queryUserVoteResults($entity_id, $entity_type, $user_id);

    $form['value']['#attributes']['data-user-submitted-vote'] = (!$submitted_vote) ? 'undefined' : $submitted_vote->value;

    return $form;
  }

  /**
   * Ajax submit handler.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function ajaxSubmit(array $form, FormStateInterface $form_state) {
    // Fetches vote entity value for ajax submission.
    $entity = $this->getEntity();
    $form['value']['#attributes']['data-user-submitted-vote'] = $entity->getValue();
    return $form;
  }

  /**
   * Queries 'votingapi_vote' table for user vote results. Limited by entity id
   * and type.
   *
   * @param $entity_id
   * @param $entity_type
   * @param $user_id
   *
   * @return
   */
  private function queryUserVoteResults($entity_id, $entity_type, $user_id) {
    // Getting user voting record by entity id, type and user uid.
    $result = db_select('votingapi_vote', 'v')
      ->fields('v', ['entity_type', 'entity_id', 'value', 'user_id'])
      ->condition('entity_type', $entity_type)
      ->condition('entity_id', $entity_id)
      ->condition('user_id', $user_id)
      ->execute();

    return $result->fetchObject();
  }

}
