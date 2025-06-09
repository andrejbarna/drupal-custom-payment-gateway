<?php

namespace Drupal\andrejbarna_custom_payment_gateway\Plugin\WorkflowType;

use Drupal\workflows\State;
use Drupal\workflows\StateInterface;
use Drupal\workflows\WorkflowInterface;

/**
 * A state for the payment workflow.
 */
class State extends State implements StateInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(WorkflowInterface $workflow, $id, $label, $weight = 0) {
    parent::__construct($workflow, $id, $label, $weight);
  }

} 