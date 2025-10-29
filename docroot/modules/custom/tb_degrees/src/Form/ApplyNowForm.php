<?php

namespace Drupal\tb_degrees\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;

/**
 * Class ApplyNowForm.
 */
class ApplyNowForm extends FormBase {

  /**
   * The application URL.
   *
   * @var string
   */
  private $base_apply_url = 'https://webapp4.asu.edu/dgsadmissions/Index.jsp';

  /**
   * The degree node.
   *
   * @var \Drupal\node\NodeInterface
   */
  private $degree;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'apply_now_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $build_info = $form_state->getBuildInfo();
    $this->degree = $build_info['args'][0];

    $form['#attributes']['target'] = '_blank';

    if ($degree_url = $this->degree->get('field_apply_url')->value) {
      $this->base_apply_url = $degree_url;
    }

    $term_options = [];

    // Get the available terms from the degree and build options list.
    $program_terms = $this->degree->get('field_program_terms')->referencedEntities();
    foreach ($program_terms as $program_term) {
      $term_label = $program_term->label();
      $term_id = $program_term->get('field_term_id')->value;
      $term_session = $program_term->get('field_term_session')->value;

      $term_options[$term_id . ':' . $term_session] = $term_label;
    }

    $conc_options = [];

    // If degree has any Concentrations selected, create options list.
    $conc_terms = $this->degree->get('field_concentrations')->referencedEntities();
    foreach ($conc_terms as $conc_term) {
      $term_label = $conc_term->label();
      $conc_plan_code = $conc_term->get('field_plan_code')->value;

      $conc_options[$conc_plan_code] = $term_label;
    }

    if (!empty($conc_options)) {
      $form['conc_options'] = [
        '#type' => 'select',
        '#empty_option' => $this->t('Select a Concentration'),
        '#options' => $conc_options,
        '#required' => TRUE,
      ];
    }

    $form['term_options'] = [
      '#type' => 'select',
      '#empty_option' => $this->t('Select a Term'),
      '#options' => $term_options,
      '#required' => TRUE,
    ];

    $apply_label = $this->t('Apply now');
    if ($label_text = $this->degree->get('field_cta_button_label')->value) {
      $apply_label = $label_text;
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $apply_label,
      '#attributes' => [
        'class' => ['btn', 'btn-gold'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $apply_info = [
      'program' => 'field_program_code',
      'plan' => 'field_plan_code',
      'subplan' => 'field_sub_plan_code',
      'campus' => 'field_campus_code'
    ];

    $query_params = [];

    // Get values for Apply info and build url params.
    foreach ($apply_info as $param_name => $field_name) {
      $param_value = $this->degree->get($field_name)->value;
      if ($param_value) {
        $query_params[$param_name] = $param_value;
      }
    }

    // If a concentration was chosen, replace the plan code.
    if (isset($values['conc_options']) && $values['conc_options']) {
      $query_params['plan'] = $values['conc_options'];
    }

    // Get selected term.
    $term = $values['term_options'];
    $term_parts = explode(':', $term);
    $query_params['term'] = $term_parts[0];
    $query_params['session'] = $term_parts[1];

    // Redirect to application URL.
    $url = Url::fromUri($this->base_apply_url, [
      'query' => $query_params,
    ]);

    $response = new TrustedRedirectResponse($url->toString());
    $response->getCacheableMetadata()->setCacheMaxAge(0);

    $form_state->setResponse($response);
  }

}
