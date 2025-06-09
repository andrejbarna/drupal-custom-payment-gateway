<?php

namespace Drupal\andrejbarna_custom_payment_gateway\Plugin\Commerce\PaymentType;

use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeBase;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides the payment_default payment type.
 *
 * @CommercePaymentType(
 *   id = "payment_default",
 *   label = @Translation("Default payment"),
 *   workflow = "payment_default",
 * )
 */
class PaymentDefault extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentInterface $payment) {
    return $this->t('Payment');
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = [];
    return $fields;
  }

} 