<?php

if ( !defined ( 'DIR_CORE' )) {
    header ( 'Location: static_pages/' );
}

class ControllerResponsesExtensionRemita extends AController{

    public $data = array();

    public function main(){

        $this->data['button_confirm'] = $this->language->get('button_confirm');
        $this->data['button_back'] = $this->language->get('button_back');

        if ($this->config->get('remita_environment') == 'test') {
            $this->data['gateway_url'] = 'https://remitademo.net/payment/v1/remita-pay-inline.bundle.js';
        }
        else {
            $this->data['gateway_url'] = 'https://login.remita.net/payment/v1/remita-pay-inline.bundle.js';
        }

        if($this->config->get('embed_mode')) {
            $this->data['target_parent'] = 'target="_parent"';
        }

        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $uniqueId = uniqid();
        $this->transactionId = $uniqueId. "_" .$this->session->data['order_id'];
        $return_url = $this->html->getSecureURL('extension/remita/callback');
        $formattedUrl = $return_url .'&transactionId=' .$this->transactionId;

        $this->data['amount'] = $this->currency->format($order_info['total'], $order_info['currency'], $order_info['value'], FALSE);
        $this->data['remita_environment'] = $this->config->get('remita_environment');
        $this->data['remita_publickey'] = $this->config->get('remita_publickey');
        $this->data['payment_lastname'] = $order_info['payment_lastname'];
        $this->data['payment_firstname'] = $order_info['payment_firstname'];
        $this->data['email'] = $order_info['email'];
        $this->data['transactionId'] = $this->transactionId;
        $this->data['form_callback'] = $formattedUrl;

        $this->load->library('encryption');
        $encryption = new AEncryption($this->config->get('encryption_key'));
        $this->data['id'] = $encryption->encrypt($this->session->data['order_id']);

        if ($this->request->get['rt'] != 'checkout/guest_step_3') {
            $this->data['back'] = $this->html->getSecureURL('checkout/payment');
        } else {
            $this->data['back'] = $this->html->getSecureURL('checkout/guest_step_2');
        }

        $back = $this->request->get[ 'rt' ] != 'checkout/guest_step_3'
            ? $this->html->getSecureURL('checkout/payment')
            : $this->html->getSecureURL('checkout/guest_step_2');

        $this->view->batchAssign( $this->data );
        $this->processTemplate('responses/remita.tpl');
    }

    function callback($posted){

        if ($this->config->get('remita_environment') == 'test') {

            $query_url = 'https://remitademo.net/payment/v1/payment/query/';

        }
        else {

            $query_url = 'https://login.remita.net/payment/v1/payment/query/';

        }

        $trxref = $_GET [transactionId];
        $url = $query_url . $trxref ;
        $hash_string = $trxref . trim($this->config->get('remita_secretkey'));
        $txnHash = hash('sha512', $hash_string);

        $header = array(
            'Content-Type: application/json',
            'publicKey:' . trim($this->config->get('remita_publickey')),
            'TXN_HASH:' . $txnHash
        );


        //  Initiate curl
        $ch = curl_init();

        // Disable SSL verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // Will return the response, if false it print the response
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


        // Set the url
        curl_setopt($ch, CURLOPT_URL, $url);

        // Set the header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);


        // Execute
        $result = curl_exec($ch);

        // Closing
        curl_close($ch);

        // decode json
        $response = json_decode($result, true);

//        var_dump($response['responseCode']);

        if (($response['responseCode'] == "00")) {

            $this->load->model('checkout/order');
            $this->model_checkout_order->confirm($this->session->data['order_id'], $this->config->get('remita_order_status_id'), 'Payment was successful');
            $this->extensions->hk_UpdateData($this, __FUNCTION__);

            $this->redirect($this->html->getURL('checkout/success'));


        } elseif (($response['responseCode'] == "34")) {

            $this->redirect($this->html->getURL('index/home&ERROR=API HASHING ERROR'));

        }
        else{

            $this->redirect($this->html->getURL('index/home&ERROR='.  $response['responseMsg']));

        }
    }

}
