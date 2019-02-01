
<form method="post" action="<?php echo $form_callback; ?>" id="checkout_form">
    <input id="publicKey" name="publicKey" value="<?php echo $remita_publickey; ?>" type="hidden"/>
    <input id="payment_firstname" name="payment_firstname" value="<?php echo $payment_firstname; ?>" type="hidden"/>
    <input id="payment_lastname" name="payment_lastname" value="<?php echo $payment_lastname; ?>" type="hidden"/>
    <input id="payerEmail" name="payerEmail" value="<?php echo $email; ?>" type="hidden"/>
    <input id="totalAmount" name="totalAmount" value="<?php echo $amount; ?>" type="hidden"/>
    <input id="transactionId" name="totalAmount" value="<?php echo $transactionId; ?>" type="hidden"/>
    <input id="gateway_url" name="gateway_url" value="<?php echo $gateway_url; ?>" type="hidden"/>


    <div class="buttons">

        <script src="<?php echo $gateway_url; ?>"></script>
        <div class="buttons">
            <div class="pull-right">
                <input type="button"  onclick="makePayment()" value="<?php echo $button_confirm; ?>" class="btn btn-primary" />
            </div>
        </div>
    </div>
</form>

<script>
    function makePayment() {
        var paymentEngine = RmPaymentEngine.init({
            key: "<?php echo $remita_publickey; ?>",
            customerId: "<?php echo $email; ?>",
            firstName: "<?php echo $payment_firstname; ?>",
            lastName: "<?php echo $payment_lastname; ?>",
            transactionId: "<?php echo $transactionId; ?>",
            narration: "bill pay",
            email: "<?php echo $email; ?>",
            amount: "<?php echo $amount; ?>",
            onSuccess: function (response) {
                window.location.href='<?php echo html_entity_decode($form_callback); ?>';

            },
            onError: function (response) {
                console.log('callback Error Response', response);
            },
            onClose: function () {
                console.log("closed");
            }
        });

        paymentEngine.showPaymentWidget();
    }
</script>
