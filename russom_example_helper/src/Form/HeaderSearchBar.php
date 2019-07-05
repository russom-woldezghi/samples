<?php

namespace Drupal\russom_example\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a header search bar form.
 */
class HeaderSearchBar extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'russom_example_header_search_bar_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get query paramaters
    $search_query = \Drupal::request()->query->all();

    $form['site-search'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => empty($search_query['search']) ? '' : $search_query['search'],
      '#attributes' => [
        'placeholder' => 'Search',
        'id' => 'site-search',
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
    $input = $form_state->getValue('site-search');
    $params['query'] = [
      'search' => $input,
    ];
    $form_state->setRedirectUrl(Url::fromUri('internal:' . '/search', $params));
  }
}
