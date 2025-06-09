<?php

namespace Drupal\andrejbarna_custom_payment_gateway\Plugin\WorkflowType;

use Drupal\workflows\State;
use Drupal\workflows\StateInterface;
use Drupal\workflows\WorkflowInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * A state for the payment workflow.
 */
class PaymentState extends State implements StateInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(WorkflowInterface $workflow, $id, $label, $weight = 0) {
    parent::__construct($workflow, $id, new TranslatableMarkup($label), $weight);
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function canTransitionTo($to_state_id) {
    return $this->workflow->hasTransitionFromStateToState($this->id, $to_state_id);
  }

} 