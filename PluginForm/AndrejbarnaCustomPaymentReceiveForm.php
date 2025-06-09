<?php

namespace Drupal\andrejbarna_custom_payment_gateway\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentReceiveForm as BasePaymentReceiveForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the payment receive form for Andrejbarna Custom Payment Gateway.
 */
class AndrejbarnaCustomPaymentReceiveForm extends BasePaymentReceiveForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    
    /** @var \Drupal\andrejbarna_custom_payment_gateway\Plugin\Commerce\PaymentGateway\AndrejbarnaCustomPaymentGateway $plugin */
    $plugin = $this->plugin;
    $order = $this->entity->getOrder();
    $total = $order->getTotalPrice();
    
    // Get the converted amount
    $converted_amount = $plugin->getConvertedAmount($total);
    
    // Display the original and converted amounts
    $form['amount_info'] = [
      '#type' => 'item',
      '#title' => $this->t('Payment Information'),
      '#markup' => $this->t('
        <div class="payment-amounts">
          <p><strong>Original Amount:</strong> @original</p>
          <p><strong>Final Amount:</strong> @converted</p>
        </div>
      ', [
        '@original' => $total->__toString(),
        '@converted' => $converted_amount->__toString(),
      ]),
    ];
    
    $form['confirm_payment'] = [
      '#type' => 'submit',
      '#value' => $this->t('Confirm Payment'),
      '#submit' => ['::confirmPayment'],
    ];
    
    return $form;
  }

  /**
   * Submit handler for the confirm payment button.
   */
  public function confirmPayment(array &$form, FormStateInterface $form_state) {
    // Process the payment confirmation
    $this->submitConfigurationForm($form, $form_state);
  }
}
