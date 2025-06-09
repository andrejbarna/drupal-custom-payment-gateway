<?php

namespace Drupal\andrejbarna_custom_payment_gateway\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayBase;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_price\Price;
use AndrejBarna\DemoPhpApiClient\ApiClient;

/**
 * Provides the Andrejbarna Custom payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "andrejbarna_custom_payment_gateway",
 *   label = "Andrejbarna Custom Payment Gateway",
 *   display_label = "Andrejbarna Custom Payment",
 *   forms = {
 *     "add-payment-method" = "Drupal\andrejbarna_custom_payment_gateway\PluginForm\AndrejbarnaCustomPaymentMethodAddForm",
 *     "receive-payment" = "Drupal\andrejbarna_custom_payment_gateway\PluginForm\AndrejbarnaCustomPaymentReceiveForm",
 *   },
 *   payment_method_types = {"credit_card"},
 * )
 */
class AndrejbarnaCustomPaymentGateway extends PaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'api_key' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#description' => $this->t('Enter your API key for the payment service.'),
      '#default_value' => $this->configuration['api_key'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValue($form['#parents']);
    $this->configuration['api_key'] = $values['api_key'];
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    $this->assertPaymentState($payment, ['new']);
    
    $order = $payment->getOrder();
    $amount = $payment->getAmount();
    
    // Calculate final amount using the API client
    $converted_amount = $this->calculateFinalAmount($amount);
    
    // Set the converted amount
    $payment->setAmount($converted_amount);
    
    // In a real implementation, you would process the payment here
    // For now, we'll just mark it as completed
    $payment->setState('completed');
    $payment->save();
  }

  /**
   * Calculate the final amount using the php-api-client.
   *
   * @param \Drupal\commerce_price\Price $amount
   *   The original amount.
   *
   * @return \Drupal\commerce_price\Price
   *   The converted amount.
   */
  protected function calculateFinalAmount(Price $amount) {
    try {
      $api_client = new ApiClient($this->configuration['api_key']);
      
      // Convert amount to float for API calculation
      $original_amount = (float) $amount->getNumber();
      
      // Use the API client to calculate the final amount
      // Adjust this method call based on the actual API client implementation
      $converted_amount = $api_client->calculateAmount($original_amount);
      
      // Return the converted amount as a Price object
      return new Price((string) $converted_amount, $amount->getCurrencyCode());
    }
    catch (\Exception $e) {
      // Log the error and return original amount as fallback
      \Drupal::logger('andrejbarna_custom_payment_gateway')->error('API calculation failed: @message', ['@message' => $e->getMessage()]);
      return $amount;
    }
  }

  /**
   * Get the converted amount for display purposes.
   *
   * @param \Drupal\commerce_price\Price $amount
   *   The original amount.
   *
   * @return \Drupal\commerce_price\Price
   *   The converted amount.
   */
  public function getConvertedAmount(Price $amount) {
    return $this->calculateFinalAmount($amount);
  }
}
