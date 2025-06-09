(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.andrejbarnaCustomPaymentGatewayCheckout = {
    attach: function (context, settings) {
      once('andrejbarna-custom-payment-gateway-checkout', '.get-rate-button', context).forEach(function (element) {
        element.addEventListener('click', function (e) {
          e.preventDefault();
          
          // Disable the button while calculating
          const button = this;
          button.disabled = true;
          button.value = Drupal.t('Calculating...');

          // Get the order ID from the URL
          const orderId = window.location.pathname.split('/')[2];

          // Make the AJAX call to get the rate
          $.ajax({
            url: Drupal.url('payment/rate/' + orderId),
            type: 'GET',
            dataType: 'json',
            success: function (response) {
              // Enable the payment button
              $('.payment-button').prop('disabled', false);
              
              // Show success message
              Drupal.drupalSetMessage(Drupal.t('Rate calculated successfully: @amount', {
                '@amount': response.formatted_amount
              }), 'status');
              
              // Reset the get rate button
              button.disabled = false;
              button.value = Drupal.t('Get Rate');
            },
            error: function (xhr, status, error) {
              // Show error message
              Drupal.drupalSetMessage(Drupal.t('Error calculating rate: @error', {
                '@error': error
              }), 'error');
              
              // Reset the get rate button
              button.disabled = false;
              button.value = Drupal.t('Get Rate');
            }
          });
        });
      });
    }
  };
})(jQuery, Drupal); 