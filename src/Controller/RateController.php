<?php

namespace Drupal\andrejbarna_custom_payment_gateway\Controller;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for handling payment rate calculations.
 */
class RateController extends ControllerBase {

  /**
   * Gets the payment rate for an order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The order.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function getRate(OrderInterface $commerce_order) {
    try {
      $total_price = $commerce_order->getTotalPrice();
      $amount = $total_price->getNumber();
      $currency = $total_price->getCurrencyCode();
      
      // Initialize the API client
      $client = new \AndrejBarna\DemoPhpApiClient\ApiClient('your-api-key');
      
      // Get the rate from the API
      $rate = $client->getRate($currency, $amount);
      
      return new JsonResponse([
        'rate' => $rate,
        'currency' => $currency,
        'amount' => $amount * $rate,
        'formatted_amount' => number_format($amount * $rate, 2) . ' ' . $currency,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => $e->getMessage(),
      ], 400);
    }
  }

} 