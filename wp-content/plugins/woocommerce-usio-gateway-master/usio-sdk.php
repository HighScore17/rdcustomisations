<?php

class WC_Horizon_USIO_Payment_SDK
{
    private $merchant_id = "";
    private $login = "";
    private $password = "";
    private $merchant_key;
    private $logger = null;

    function __construct($merchant_id, $login, $password, $merchant_key, $logger = false)
    {
        $this->merchant_id = $merchant_id;
        $this->login = $login;
        $this->password = $password;
        $this->merchant_key = $merchant_key;
        $this->logger = $logger ? true : false;
    }

    public function get_brand(){
        $brand = wc_horizon_get_brand_name();
        if ($brand === "amerisano"){
            return "AMS";
        } else if ($brand != "amerisano"){
            return "RCK";
        }
    }
    public function make_request($url, $body)
    {
        return wp_remote_post($url, array(
            "headers" => array(
                "Content-Type" => "application/json"
            ),
            "timeout" => 60,
            "body" => json_encode($body)
        ));
    }

    public function make_single_charge($token, $amount, $billing = array())
    {
        return $this->get_single_charge_response(
            $this->make_request("https://checkout.securepds.com/checkout/checkout.svc/json/SinglePayment", array_merge(
                array(
                    "MerchantKey" => $this->merchant_key,
                    "Token" => $token,
                    "Amount" => $amount,
                    "AdditionalSearch" => '',
                    "AccountCode1" => $this->get_brand(),
                    "AccountCode2" => '',
                    "AccountCode3" => '',
                    "VerStr" => ""
                ),
                $billing
            ))
        );
    }

    public function make_charge_by_confirmation($token, $amount)
    {
        return $this->get_single_charge_response(
            $this->make_request("https://payments.usiopay.com/2.0/payments.svc/JSON/SubmitTokenPayment", array(
                "MerchantID" => $this->merchant_id,
                "Login" => $this->login,
                "Password" => $this->password,
                "Token" => $token,
                "Amount" => $amount,
                "AccountCode1" => $this->get_brand(),
                "AccountCode2" => '',
                "AccountCode3" => '',
            ))
        );
    }

    function log($message)
    {
        if ($this->logger) {
            $logger = wc_get_logger();
            $logger->info($message, ["source" => "usio-gateway"]);
        }
    }

    private function get_single_charge_response($result)
    {
        if (is_wp_error($result)) {
            $this->log(wc_print_r($result, true));
            return new WP_Error("usio_recurrent_payment", "Can't process the payment");
        }

        $body = wp_remote_retrieve_body($result);

        if (empty($body)) {
            $this->log($body);
            return new WP_Error("usio_recurrent_payment", "Can't process the payment");
        }

        $body = json_decode($body, true);

        if (!array_key_exists("Status", $body)) {
            $this->log(wc_print_r($body, true));
            return new WP_Error("usio_recurrent_payment", "Can't confirm the payment");
        }

        if ($body["Status"] !== "success") {
            $this->log(wc_print_r($body, true));
            return new WP_Error("usio_recurrent_payment", $body["Message"]);
        }

        return $body["Confirmation"];
    }
}