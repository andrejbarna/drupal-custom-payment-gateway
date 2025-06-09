<?php

namespace Drupal\andrejbarna_custom_payment_gateway\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides the Off-site payment form.
 */
class OffsiteRedirectForm extends PaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    
    $payment = $this->entity;
    $order = $payment->getOrder();
    $total_price = $order->getTotalPrice();

    // Container for the payment form
    $form['payment_container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['payment-form-container'],
      ],
    ];

    // Original amount display with data attributes
    $form['payment_container']['original_amount'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'js-original-amount',
        'data-amount' => $total_price->getNumber(),
        'data-currency' => $total_price->getCurrencyCode(),
      ],
      'label' => [
        '#type' => 'label',
        '#title' => $this->t('Original amount'),
      ],
      'amount' => [
        '#type' => 'markup',
        '#markup' => $total_price->__toString(),
      ],
    ];

    // Rate button
    $form['payment_container']['get_rate'] = [
      '#type' => 'button',
      '#value' => $this->t('Get Rate'),
      '#attributes' => [
        'id' => 'js-get-rate',
        'class' => ['button', 'button--primary'],
        'type' => 'button',
      ],
      '#executes_submit_callback' => FALSE,
    ];

    // Container for rate display
    $form['payment_container']['rate_display'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'js-rate-display',
        'class' => ['rate-display-container', 'hidden'],
      ],
    ];

    // Submit button - disabled by default
    $form['payment_container']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Pay now'),
      '#attributes' => [
        'id' => 'js-pay-now',
        'class' => ['button', 'button--primary'],
        'disabled' => 'disabled',
      ],
    ];

    // Attach our custom library
    $form['#attached']['library'][] = 'andrejbarna_custom_payment_gateway/payment_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // No validation needed.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $payment = $this->entity;
    $order = $payment->getOrder();
    
    // Redirect to the payment complete page
    $form_state->setRedirectUrl(Url::fromRoute('andrejbarna_custom_payment_gateway.payment_complete', [
      'commerce_order' => $order->id(),
    ]));
  }
} 