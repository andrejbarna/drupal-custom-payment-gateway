(function ($, Drupal) {
  Drupal.behaviors.andrejbarnaCustomPaymentGateway = {
    attach: function (context, settings) {
      $('#js-get-rate', context).once('get-rate').on('click', function () {
        var amount = $('#js-payment-amount').data('amount');
        var currency = $('#js-payment-amount').data('currency');
        $.ajax({
          url: '/andrejbarna-custom-payment-gateway/get-rate',
          type: 'POST',
          data: { amount: amount, currency: currency },
          dataType: 'json',
          success: function (data) {
            if (data.error) {
              $('#js-rate-display').text('Error: ' + data.error);
              $('#js-pay-now').prop('disabled', true);
            } else {
              $('#js-rate-display').text('Rate: ' + data.formatted);
              $('#js-pay-now').prop('disabled', false);
            }
          },
          error: function () {
            $('#js-rate-display').text('Error getting rate');
            $('#js-pay-now').prop('disabled', true);
          }
        });
      });
    }
  };
})(jQuery, Drupal); 