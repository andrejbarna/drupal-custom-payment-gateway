<?php

namespace Drupal\andrejbarna_custom_payment_gateway\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the payment method add form for Andrejbarna Custom Payment Gateway.
 */
class AndrejbarnaCustomPaymentMethodAddForm extends BasePaymentMethodAddForm {

  /**
   * {@inheritdoc}
   */
  public function buildCreditCardForm(array $element, FormStateInterface $form_state) {
    $element = parent::buildCreditCardForm($element, $form_state);
    
    // Add custom styling or modifications if needed
    $element['#attributes']['class'][] = 'andrejbarna-custom-payment-form';
    
    return $element;
  }
}