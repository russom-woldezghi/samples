<?php

namespace Drupal\russom_example\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a search bar form.
 */
class SearchBar extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'russom_example_search_bar_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get query paramaters
    $search_query = \Drupal::request()->query->all();

    $form['keyword_search'] = [
      '#type' => 'textfield',
      '#default_value' => empty($search_query['combine']) ? '' : $search_query['combine'],
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'Search our Journal',
      ],
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Redirect to search page with query parameters
    $input = $form_state->getValue('keyword_search');
    $params['query'] = [
      'combine' => $input,
    ];
    $form_state->setRedirectUrl(Url::fromUri('internal:' . '/search', $params));
  }
}
