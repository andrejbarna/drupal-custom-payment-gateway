<?php

namespace Drupal\andrejbarna_custom_payment_gateway\Controller;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_payment\PaymentStorageInterface;

/**
 * Controller for payment completion.
 */
class PaymentCompleteController extends ControllerBase {

  /**
   * The payment storage.
   *
   * @var \Drupal\commerce_payment\PaymentStorageInterface
   */
  protected $paymentStorage;

  /**
   * Constructs a new PaymentCompleteController object.
   *
   * @param \Drupal\commerce_payment\PaymentStorageInterface $payment_storage
   *   The payment storage.
   */
  public function __construct(PaymentStorageInterface $payment_storage) {
    $this->paymentStorage = $payment_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('commerce_payment')
    );
  }

  /**
   * Completes the payment process.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The order.
   *
   * @return array
   *   A render array.
   */
  public function complete(OrderInterface $commerce_order) {
    // Get the latest payment for this order
    $payments = $this->paymentStorage->loadByProperties([
      'order_id' => $commerce_order->id(),
    ]);
    $payment = end($payments);

    if ($payment) {
      // Mark the payment as completed
      $payment->setState('completed');
      $payment->save();

      // Set the order state to completed
      $commerce_order->getState()->applyTransitionById('place');
      $commerce_order->save();
    }

    return [
      '#theme' => 'commerce_checkout_complete',
      '#order_entity' => $commerce_order,
    ];
  }

} 