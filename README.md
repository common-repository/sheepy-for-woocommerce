=== Sheepy - crypto payment gateway ===
Contributors: Sheepy
Donate link: https://www.sheepy.com/demo
Tags: crypto, bitcoin, ethereum
Requires at least: 5.7
Tested up to: 6.5
Stable tag: 1.0.0
Requires PHP: 7.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Start accepting cryptocurrencies (BTC, ETH, XRP, LTC, TRX, USDT and more). Get paid in EUR directly to your bank account.

== Key Benefits ==

* Select your preferred cryptocurrencies for payments and enjoy instant conversion to fiat.
* Enhance your sales potential by establishing an additional revenue channel, allowing new customers to make payments using cryptocurrency.
* We handle the ups and downs of unpredictable cryptocurrencies while ensuring your payment process is super secure. This way, you get exactly what you're expecting.

== Key Features ==

* Create, customize, and send invoices with advanced settings. Add product details, fees, taxes, and other relevant information about your business.
* Automatically convert your cryptocurrency payments into fiat currency or stablecoins, eliminating FX risks.
* Automatically withdraw your balance to the bank account based on your preferences.
* Check your transaction history, statuses, and fees. Export data and reports on the go.
* Enjoy fixed transaction fees, no setup costs, and no minimum monthly commitment. Get an unlimited number of monthly transactions and daily settlements.

== External services use ==

This plugin relies on an external [Sheepy service](https://www.sheepy.com) to inform users about how to connect the plugin and the settings it uses.
Please read the [Privacy policy](https://www.sheepy.com/legal/privacy-policy) before using this plugin and the Sheepy service.

== Installation ==

= Minimum Requirements =

* WordPress 5.7 or greater
* WooCommerce 6.5 or greater
* PHP version 7.2 or greater

= Automatic Installation =

Log into your WordPress account. Navigate to Plugins tab > Add New.

Search for "Sheepy for WooCommerce" and click "Install Now"

= Manual Installation =

Download the plugin from [Sheepy](https://www.sheepy.com/crypto-developers/shopping-cart-plugins). Navigate to Add New Plugins -> Upload Plugin. Select the downloaded file to install it. Additional instructions about manual installation of plugins can be found [here](https://wordpress.org/support/article/managing-plugins/).

= Plugin Setup =

To complete the installation process, you need to configure the Sheepy plugin with relevant API credentials.

Log in to your Sheepy account -> Go to the Integration -> [API Integration](https://www.sheepy.com/integration/api) section and click the "Add New API Token" button. You will be provided with a pair of API Key and Secret Key. Write them down so you don't lose them. The Secret Key is displayed only once when the token is created. Then click the "Save" button. You can configure the remaining parameters of the token later.

In addition, you need to get a Notification Secret Key to sign notifications that will be sent to your store upon receipt of payment. To get it, go to the Integration -> Integration Profile -> Webhooks section, click the "Reveal Secret Key" button at the bottom of the form, and apply the settings by clicking the "Save Settings" button.

In the WordPress admin panel, go to WooCommerce tab -> Settings -> Payments -> Manage. Enter the API access details obtained earlier and click the "Save Changes" button.

== Frequently Asked Questions ==

= What do I need to start using crypto payments through Sheepy for WooCommerce? =

The first thing you have to do is to register on [Sheepy](https://sheepy.com/auth/sign-up). To ensure full compliance with Anti-Money Laundering (AML) policies, we require you to complete a straightforward onboarding process.

Here's a quick overview of our verification process:

* [Log in](https://sheepy.com/auth/sign-in) to your Sheepy account and navigate to the verification section.
* You will be directed to our verification partner to complete the process.
* Provide your company information and upload the required documents to initiate verification.

= Where can I get API Key and API Secret Key to enable Sheepy plugin? =

Once you register and verify on Sheepy, you will have access in your personal account to add a new integration. To get the API Key and Secret Key, click the "Add New API Token" button. When creating a token, you will be shown the API Key and Secret Key. You will also need the Notification Secret Key for the plugin to work. You can find the key by scrolling down the integration settings page to "Webhooks" and clicking the "Reveal Secret Key" button.

= Where can I get general information about Sheepy? =

You can use Sheepy [FAQ](https://www.sheepy.com/faq).
If you have not found the answer to your question, please [contact us](https://www.sheepy.com/contact-us).

== Changelog ==

= 1.0.0 =
Initial release.

== Upgrade Notice ==

= 1.0.0 =
This version is stable and tested on production mode.


== Screenshots ==

1. Customer chooses the cryptocurrency payment method.
2. Customer completes the payment via invoice form.
3. Payment is processed.
4. Payment is completed.
5. Order is received.
6. Order status in admin panel is changed.
