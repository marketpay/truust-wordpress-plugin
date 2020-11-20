# woocommerce-truust-v2

## Installation

Copy the woocommerce-truust-v2 folder containing the source code to the wp-content/plugins folder within the WordPress installation.

## Activation

Ensure that you have WooCommerce installed and activated before activating the woocommerce-truust-v2 plugin. Failure to do so will result in the woocommerce-truust-v2 plugin failing to activate and an error message

```
Sorry, Truust requires WooCommerce to be installed and activated first
```

## Usage

Once activated you should see a new menu option in the admin sidebar labelled `Truust` which will take you to the WooCommerce Payment Settings page to configure the settings for the new gateway.

![Woocommerce Truust v2 Plugin Menu](https://raw.githubusercontent.com/truust-io/woocommerce-truust/master/setup/menu.jpg)

![Woocommerce Truust v2 Plugin Settings](https://raw.githubusercontent.com/truust-io/woocommerce-truust/master/setup/settings.jpg)

On the API Key Field, you need to put the API Key of your environment (_Sandbox or Production_).

The Field Description shows the text on the checkout area of WooCoommerce.

The seller information fields (EMAIL - PHONE) are required to do the calling to TRUUST Payment Gateway. There you should put the TRUUST Registered Email and Phone Number.

### Payment flow and shipment

When a order is created and payed succesfully, the orders are created in Truust and WooCommerce Systems.

![Woocommerce Truust v2 Order](https://github.com/truust-io/woocommerce-truust/blob/master/setup/paymentComplete.png?raw=true)

There we could check the Truust Order ID and the WooCommerce ID.

In order to release the item and process the shipment we need to put the status of the order from "Processing" to "Completed". In that way if the seller have shipments available in TRUUST system, then it should change the TRUUST Order to "Shipment Ready" and TRUUST will handle the shipment flow.

If something comes up creating the shipment of the order, an error should pop up and you should communicate with TRUUST via email at hello@truust.io
