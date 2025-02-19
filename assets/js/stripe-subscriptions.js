jQuery(document).ready(function($) {
    const stripe = Stripe(ebookStripeVars.publicKey);

    $(document).on('click', '.stripe-sub-pay-btn', function() {
        const $btn = $(this);
        const amount = parseFloat($btn.data('amount'));
        const currency = $btn.data('currency').toLowerCase(); // Kisbetűsítés
        const interval = $btn.data('interval');
        const product = $btn.data('product');

        $.ajax({
            url: ebookStripeVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'process_stripe_subscription',
                nonce: ebookStripeVars.nonce,
                amount: amount,
                currency: currency,
                interval: interval,
                product: product
            },
            success: function(response) {
                if (response.success) {
                    stripe.redirectToCheckout({ sessionId: response.data.id });
                } else {
                    alert('Error: ' + response.data.error);
                }
            }
        });
    });
});