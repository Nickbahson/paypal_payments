(function ($, Drupal, drupalSettings) {

  const data = drupalSettings.paypal_payments_data

  paypal.Buttons({
    /*style: {
      layout:  'vertical',
      color:   'gold',
      shape:   'rect',
      label:   'paypal'
    },*/
    /*options: {
      clientId: process.env.REACT_APP_PAYPAL_CLIENT_ID,
      currency: 'EUR',
      vault: false,
      intent: 'capture'
    },
    */
    createOrder: function(dt, actions) {
      // This function sets up the details of the transaction, including the amount and line item details.

      return actions.order.create({
        intent: 'CAPTURE',
        purchase_units: [{
          reference_id:  data.nid,
          //description: "",
          custom_id: data.nid,
          //soft_descriptor: "Great description 1",
          amount: {
            value: data.amount,
            currency_code: data.currency,
            breakdown: {
              item_total: {
                currency_code: data.currency,
                value: data.amount
              }
            }
          },
          items: [
            {
              name: data.title,
              description: data.title,
              sku: data.nid,
              unit_amount: {
                currency_code: data.currency,
                value: data.amount
              },
              quantity: 1
            },
          ]
        }]
      });
    },
    onApprove: function(dt, actions) {
      window.location = `?&for_node=${data.nid}&order_id=${dt.orderID }&type=pp_rq`
    }
  }).render('#paypal-button-container');

})(jQuery, Drupal, drupalSettings)
