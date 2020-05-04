<?php
/**
 * Created by PhpStorm.
 * User: Luis
 * Date: 3/8/2020
 * Time: 2:28 PM
 */

const SANDBOX = 1;
const PODUCTION = 2;
const SCOPE_PAYMENT = 'enzona_business_payment';
const SCOPE_QR = 'enzona_business_qr';

class enzonaApi {

  protected $client_key, $client_secret, $stage, $access_token;


  function __construct($client_key, $client_secret, $stage = SANDBOX) {
    $this->client_key = $client_key;
    $this->client_secret = $client_secret;
    $this->stage = $stage;
  }


  public function getAccessToken() {
    return $this->access_token;
  }


  public function requestAccessToken() {
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => "https://api.enzona.net/token",
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "grant_type=client_credentials&scope=enzona_business_payment,enzona_business_qr",
      CURLOPT_HTTPHEADER => [
        "authorization: Basic ",
        "cache-control: no-cache",
        "content-type: application/x-www-form-urlencoded",
      ],
    ]);
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    if ($err) {
      return ["access_token" => "Error", "message" => $err];
    }
    else {
      $ob = json_encode($response);
      $this->access_token = $ob->access_token;
      return $response;
    }
  }

  public function generatePayment($token, $total, $descripcion, $items, $return_url,$cancel_url) {
    $curl = curl_init();
    $values = [
      "description" => $descripcion,
      "currency" => "CUP",
      "amount" => [
        "total" => number_format($total,2),
        "details" => [
          "shipping" => number_format(0, 2),
          "tax" => number_format(0, 2),
          "discount" => number_format(0, 2),
          "tip" => number_format(0, 2),
        ],
      ],
      "items" => $items,
      "merchant_op_id" => 123456789123,
      "invoice_number" => 1212,
      "return_url" => $return_url,
      "cancel_url" => $cancel_url,
      "terminal_id" => 12121,
    ];

    curl_setopt_array($curl, [
      CURLOPT_URL => "https://api.enzona.net/payment/v1.0.0/payments",
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => json_encode($values),
      CURLOPT_HTTPHEADER => [
        "accept: application/json",
        "authorization: Bearer $token",
        "cache-control: no-cache",
        "content-type: application/json",
      ],
    ]);
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    if ($err) {
      return ["status" => "error", "message" => $err];
    }
    else {
      $obj = json_decode($response);
      if(isset($obj->transaction_uuid)){
        return ["status" => "ok", "message" => $response];
      }
      else{
        return ["status" => "error", "message" => $err];
      }
    }
  }

  public function acceptPayment($token, $transaction_uuid) {
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => "https://api.enzona.net/payment/v1.0.0/payments/$transaction_uuid/complete",
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "{}",
      CURLOPT_HTTPHEADER => [
        "accept: application/json",
        "authorization: Bearer $token",
        "cache-control: no-cache",
        "content-type: application/json"
      ],
    ]);
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
      return  ["status" => "error", "message" => $err];
    }
    else {
      return ["status" => "ok", "message" => $response];
    }
  }

}