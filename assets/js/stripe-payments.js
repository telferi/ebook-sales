jQuery(document).ready(function($) {
    const stripe = Stripe(ebookStripeVars.publicKey);

    // Fix összegű fizetés
    $(document).on('click', '.stripe-fix-pay', function() {
        const amount = $(this).data('amount');
        const currency = $(this).data('currency');
        const product = $(this).data('product');
        processPayment(amount, currency, product);
    });

    // Flexibilis fizetés
    $(document).on('click', '.stripe-flex-pay-btn', function() {
        const amount = $('.stripe-amount').val();
        const currency = $('.stripe-currency').val();
        const product = $(this).data('product');
        if (!amount || amount < 1) {
            alert('Please enter a valid amount');
            return;
        }
        processPayment(amount, currency, product);
    });

    // Fizetés feldolgozása
    function processPayment(amount, currency, product) {
        $.ajax({
            url: ebookStripeVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'process_stripe_payment',
                nonce: ebookStripeVars.nonce,
                amount: amount,
                currency: currency,
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
    }
});