<?php

require_once __DIR__ . '/vendor/autoload.php';

include_once('myutils.php');

use AfricasTalking\SDK\AfricasTalking;

class Sms {
    protected $phone;
    protected $AT;

    public function __construct($phone) {
        $this->phone = $phone;

        $this->AT = new AfricasTalking(
            myutils::$API_USERNAME,
            myutils::$API_KEY
        );
    }

    public function sendSMS($message, $recipients) {
        $sms = $this->AT->sms();

        $result = $sms->send([
            'username' => myutils::$API_USERNAME,
            'to'       => $recipients,
            'message'  => $message,
            'from'     => myutils::$COMPANY_NAME 
        ]);

        return $result;
    }
}
?>
