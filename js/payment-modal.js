(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.andrejbarnaCustomPaymentGateway = {
    attach: function (context, settings) {
      // Get the settings
      const config = drupalSettings.andrejbarnaCustomPaymentGateway || {};
      
      // Handle the modal submit button click
      once('payment-modal', '.use-ajax', context).forEach(function (element) {
        $(element).on('click', function (e) {
          e.preventDefault();
          
          // Create modal content
          const modalContent = $('<div>', {
            class: 'payment-modal-content',
          }).append(
            $('<div>', {
              class: 'payment-details',
            }).append(
              $('<p>').text('Amount: ' + config.rateInfo.amount + ' ' + config.rateInfo.currency),
              $('<p>').text('Rate: ' + config.rateInfo.rate)
            ),
            $('<button>', {
              text: 'Complete Payment',
              class: 'button button--primary',
              click: function() {
                // Simulate payment completion
                window.location.href = config.returnUrl;
              }
            })
          );

          // Open the modal
          Drupal.dialog(modalContent, {
            title: 'Complete Your Payment',
            width: '50%',
            height: '50%',
            modal: true,
            close: function (event) {
              $(event.target).remove();
            }
          }).showModal();
        });
      });
    }
  };

})(jQuery, Drupal, drupalSettings); 