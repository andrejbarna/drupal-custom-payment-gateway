# Andrejbarna Custom Payment Gateway

A custom payment gateway module for Drupal Commerce that integrates with the Andrejbarna payment API.

## Features

- Custom payment gateway integration
- Real-time exchange rate calculation
- Configurable API key
- Seamless checkout experience

## Requirements

- Drupal Commerce 2.x or higher
- PHP 8.1 or higher
- [Andrejbarna Demo PHP API Client](https://packagist.org/packages/andrejbarna/demo-php-api-client)

## Installation

### Using Composer (Recommended)

1. Add the repository to your project's `composer.json`:
```json
{
    "repositories": {
        "andrejbarna_custom_payment_gateway": {
            "type": "vcs",
            "url": "https://github.com/andrejbarna/drupal-custom-payment-gateway.git"
        }
    }
}
```

2. Require the module:
```bash
composer require andrejbarna/custom-payment-gateway
```

3. Enable the module:
```bash
drush en andrejbarna_custom_payment_gateway
```

### Manual Installation (Not Recommended)

1. Download the module from [GitHub](https://github.com/andrejbarna/drupal-custom-payment-gateway)
2. Place it in your `web/modules` directory
3. Run `composer update` to install dependencies
4. Enable the module using Drush or the Drupal UI

## Configuration

1. Navigate to Commerce > Configuration > Payment Gateways (`/admin/commerce/config/payment-gateways`)
2. Click "Add payment gateway"
3. Select "Andrejbarna Custom Payment Gateway" from the plugin dropdown
4. Configure the following settings:
   - Gateway name (for administrative purposes)
   - Mode (Test/Live)
   - API Key (obtain this from your Andrejbarna account)
5. Save the configuration

## Usage

The payment gateway will automatically:
1. Calculate exchange rates during checkout
2. Display the converted amount to customers
3. Process payments through the Andrejbarna API
4. Handle payment completion and order status updates

## Troubleshooting

Common issues and solutions:

1. **"Payment gateway not found" error**
   - Ensure the module is properly enabled
   - Check if the payment gateway is configured in Commerce settings
   - Verify the gateway configuration exists in the database

2. **"API key not configured" error**
   - Verify your API key in the payment gateway configuration
   - Ensure the API key has the correct permissions
   - Check if the API key is properly saved in the configuration

3. **Rate calculation fails**
   - Check your API key permissions
   - Verify the currency code is supported
   - Check the Drupal error logs for detailed information
   - Ensure the API endpoint is accessible from your server

## Support

For issues and support:
- Create an issue in the [GitHub repository](https://github.com/andrejbarna/drupal-custom-payment-gateway/issues)
- Contact your Andrejbarna account manager

## Development

To contribute to the module:
1. Fork the [repository](https://github.com/andrejbarna/drupal-custom-payment-gateway)
2. Create a feature branch
3. Submit a pull request

## License

This module is licensed under GPL-2.0-or-later. 