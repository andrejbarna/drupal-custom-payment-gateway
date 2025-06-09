<?php

namespace Drupal\andrejbarna_custom_payment_gateway\Plugin\Commerce\PaymentGateway;

use AndrejBarna\DemoPhpApiClient\ApiClient;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_price\NumberFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Andrejbarna\DemoPhpApiClient\Client;
use Andrejbarna\DemoPhpApiClient\Exception\ApiException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeManagerInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\commerce_payment\Attribute\CommercePaymentGateway;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsNotificationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;

/**
 * Provides the Andrejbarna payment gateway.
 */
#[CommercePaymentGateway(
  id: "andrejbarna_custom_payment_gateway",
  label: new TranslatableMarkup("Andrejbarna Custom Payment Gateway"),
  display_label: new TranslatableMarkup("Custom Payment"),
  forms: [
    "offsite-payment" => "Drupal\andrejbarna_custom_payment_gateway\PluginForm\OffsiteRedirectForm",
  ],
  modes: [
    "test" => new TranslatableMarkup("Test"),
    "live" => new TranslatableMarkup("Live"),
  ],
  payment_method_types: ["credit_card"],
  credit_card_types: [
    "visa", "mastercard", "amex", "discover"
  ],
  requires_billing_information: TRUE,
)]
class AndrejbarnaCustomPaymentGateway extends OffsitePaymentGatewayBase implements SupportsNotificationsInterface, SupportsRefundsInterface {

  /**
   * The number formatter.
   *
   * @var \Drupal\commerce_price\NumberFormatter
   */
  protected $numberFormatter;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    PaymentTypeManager $payment_type_manager,
    PaymentMethodTypeManager $payment_method_type_manager,
    TimeInterface $time,
    NumberFormatter $number_formatter
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);
    $this->numberFormatter = $number_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time'),
      $container->get('commerce_price.number_formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'api_key' => '',
      'mode' => 'test',
      'display_label' => 'Custom Payment',
      'collect_billing_information' => TRUE,
      'payment_method_types' => ['credit_card'],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Ensure configuration values are set
    $this->configuration = $this->defaultConfiguration() + $this->configuration;

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#description' => $this->t('This is your API key from the payment provider.'),
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
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['api_key'] = $values['api_key'];
      $this->configuration['mode'] = $values['mode'];
      $this->configuration['display_label'] = $values['display_label'];
      $this->configuration['collect_billing_information'] = $values['collect_billing_information'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    // Get the order total price
    $total_price = $order->getTotalPrice();
    
    // Create a new payment
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $payment_storage->create([
      'state' => 'completed',
      'payment_gateway' => $this->parentEntity->id(),
      'order_id' => $order->id(),
      'remote_id' => $request->query->get('payment_id', ''),
      'remote_state' => 'completed',
      'amount' => $order->getTotalPrice(),
      'payment_gateway_mode' => $this->getMode(),
      'payment_gateway' => $this->parentEntity->id(),
    ]);
    
    $payment->save();
    
    // Set the order state to completed
    $order->getState()->applyTransitionById('place');
    $order->save();
  }

  /**
   * Gets the payment rate from the API.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return array
   *   The rate information.
   */
  public function getRate(OrderInterface $order) {
    try {
      $total_price = $order->getTotalPrice();
      $amount = $total_price->getNumber();
      $currency = $total_price->getCurrencyCode();
      
      // Initialize the API client with the API key from configuration
      $client = new ApiClient($this->configuration['api_key']);
      
      // Get the rate from the API
      $rate = $client->getRate($currency, $amount);
      return [
        'rate' => $rate,
        'currency' => $currency,
        'amount' => $amount * $rate,
        'formatted_amount' => $this->numberFormatter->format($amount * $rate) . ' ' . $currency,
      ];
    }
    catch (\Exception $e) {
      \Drupal::logger('andrejbarna_custom_payment_gateway')->error('API Error: @message', ['@message' => $e->getMessage()]);
      throw new PaymentGatewayException($this->t('Unable to get rate. Please try again later.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    // Handle the notification from the payment provider
    // This is called when the payment provider sends a notification about payment status
    try {
      // Get the payment ID from the request
      $payment_id = $request->query->get('payment_id');
      if (empty($payment_id)) {
        throw new PaymentGatewayException('Missing payment ID');
      }

      // Load the payment
      $payment = $this->entityTypeManager->getStorage('commerce_payment')->load($payment_id);
      if (!$payment) {
        throw new PaymentGatewayException('Invalid payment ID');
      }

      // Update the payment status based on the notification
      $remote_status = $request->query->get('status');
      switch ($remote_status) {
        case 'completed':
          $payment->setState('completed');
          break;
        case 'failed':
          $payment->setState('failed');
          break;
        default:
          throw new PaymentGatewayException('Invalid payment status');
      }

      $payment->setRemoteId($request->query->get('remote_id'));
      $payment->save();
    }
    catch (\Exception $e) {
      throw new PaymentGatewayException($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $this->assertRefundAmount($payment, $amount);

    try {
      // Initialize API client
      $client = new ApiClient($this->configuration['api_key']);
      
      // Perform the refund
      $refund_id = $client->refund($payment->getRemoteId(), $amount->getNumber());
      
      $old_refunded_amount = $payment->getRefundedAmount();
      $new_refunded_amount = $old_refunded_amount->add($amount);
      
      if ($new_refunded_amount->lessThan($payment->getAmount())) {
        $payment->setState('partially_refunded');
      }
      else {
        $payment->setState('refunded');
      }

      $payment->setRefundedAmount($new_refunded_amount);
      $payment->save();
    }
    catch (\Exception $e) {
      throw new PaymentGatewayException($e->getMessage());
    }
  }
} 