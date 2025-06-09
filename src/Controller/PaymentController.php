<?php

namespace Drupal\andrejbarna_custom_payment_gateway\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use AndrejBarna\DemoPhpApiClient\ApiClient;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\commerce_payment\Entity\PaymentGateway;

/**
 * Controller for handling payment gateway operations.
 */
class PaymentController extends ControllerBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new PaymentController.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Gets the exchange rate and calculates the final amount.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function getRate(Request $request) {
    try {
      // Get POST data
      $amount = $request->request->get('amount');
      $currency = $request->request->get('currency');

      if (!$amount || !$currency) {
        throw new \InvalidArgumentException('Amount and currency are required.');
      }

      // Load the payment gateway
      $gateways = PaymentGateway::loadMultiple();
      foreach ($gateways as $gateway) {
        if ($gateway->getPluginId() === 'andrejbarna_custom_payment_gateway') {
          $api_key = $gateway->getPluginConfiguration()['api_key'] ?? '';
          if (empty($api_key)) {
            throw new \RuntimeException('API key not configured.');
          }

          // Initialize API client
          $client = new ApiClient($api_key);
          
          // Get rate from API
          $rate = $client->getRate($currency, (float)$amount);
          
          // Calculate final amount
          $final_amount = $amount * $rate;
          
          return new JsonResponse([
            'success' => TRUE,
            'rate' => $rate,
            'original_amount' => $amount,
            'final_amount' => $final_amount,
            'currency' => $currency,
            'formatted' => number_format($final_amount, 2) . ' ' . $currency,
          ]);
        }
      }

      throw new \RuntimeException('Payment gateway not found.');
    }
    catch (\Exception $e) {
      \Drupal::logger('andrejbarna_custom_payment_gateway')->error('Rate calculation error: @message', ['@message' => $e->getMessage()]);
      return new JsonResponse([
        'success' => FALSE,
        'error' => $e->getMessage(),
      ]);
    }
  }
} 