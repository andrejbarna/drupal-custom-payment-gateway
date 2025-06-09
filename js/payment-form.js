(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.andrejbarnaCustomPaymentGateway = {
    attach: function (context, settings) {
      // Only run once
      $(once('payment-form', '#js-get-rate', context)).each(function () {
        const $rateButton = $(this);
        const $rateDisplay = $('#js-rate-display');
        const $payButton = $('#js-pay-now');
        const $originalAmount = $('#js-original-amount');

        // Handle rate button click
        $rateButton.on('click', function (e) {
          e.preventDefault();
          
          // Disable the rate button and show loading
          $rateButton.prop('disabled', true).val(Drupal.t('Loading...'));
          
          // Get amount and currency from data attributes
          const amount = $originalAmount.data('amount');
          const currency = $originalAmount.data('currency');

          // Make the AJAX call
          $.ajax({
            url: '/andrejbarna-custom-payment-gateway/get-rate',
            type: 'POST',
            data: {
              amount: amount,
              currency: currency
            },
            dataType: 'json',
            success: function (response) {
              if (response.success) {
                // Show the rate information
                $rateDisplay
                  .removeClass('hidden')
                  .html(
                    '<div class="rate-info">' +
                    '<p><strong>' + Drupal.t('Exchange Rate') + ':</strong> ' + response.rate + '</p>' +
                    '<p><strong>' + Drupal.t('Final Amount') + ':</strong> ' + response.formatted + '</p>' +
                    '</div>'
                  );
                
                // Enable the pay button
                $payButton.prop('disabled', false);
                
                // Reset and enable the rate button
                $rateButton.prop('disabled', false).val(Drupal.t('Get Rate'));
              } else {
                // Show error
                $rateDisplay
                  .removeClass('hidden')
                  .html(
                    '<div class="rate-error">' +
                    '<p class="error">' + Drupal.t('Error') + ': ' + response.error + '</p>' +
                    '</div>'
                  );
                
                // Reset and enable the rate button
                $rateButton.prop('disabled', false).val(Drupal.t('Get Rate'));
              }
            },
            error: function (xhr, status, error) {
              // Show error
              $rateDisplay
                .removeClass('hidden')
                .html(
                  '<div class="rate-error">' +
                  '<p class="error">' + Drupal.t('Error getting rate. Please try again.') + '</p>' +
                  '</div>'
                );
              
              // Reset and enable the rate button
              $rateButton.prop('disabled', false).val(Drupal.t('Get Rate'));
            }
          });
        });
      });
    }
  };

})(jQuery, Drupal); 