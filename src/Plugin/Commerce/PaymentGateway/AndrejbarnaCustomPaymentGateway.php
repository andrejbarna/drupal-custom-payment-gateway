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
use Drupal\commerce_price\NumberFormatterFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Andrejbarna\DemoPhpApiClient\Client;
use Andrejbarna\DemoPhpApiClient\Exception\ApiException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeManagerInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;

/**
 * Provides the Andrejbarna payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "andrejbarna_custom_payment_gateway",
 *   label = @Translation("Andrejbarna Custom Payment Gateway"),
 *   display_label = @Translation("Custom Payment"),
 *   forms = {
 *     "offsite-payment" = "Drupal\andrejbarna_custom_payment_gateway\PluginForm\OffsiteRedirectForm",
 *   },
 * )
 */
class AndrejbarnaCustomPaymentGateway extends OffsitePaymentGatewayBase {

  /**
   * The number formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\NumberFormatterInterface
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
    NumberFormatterFactoryInterface $number_formatter_factory,
    TimeInterface $time
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);
    $this->numberFormatter = $number_formatter_factory->createInstance();
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('commerce_price.number_formatter_factory'),
      $container->get('datetime.time')
    );
  }

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
 

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

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
    ]);
    
    // Set the amount explicitly using the Price object
    $payment->setAmount(new Price($total_price->getNumber(), $total_price->getCurrencyCode()));
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

} 