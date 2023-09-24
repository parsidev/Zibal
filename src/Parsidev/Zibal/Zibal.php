<?php namespace Parsidev\Zibal;
    
use Exception;
use Parsidev\Zibal\Enums\Method;
use stdClass;

class Zibal
{
    const BASE = "https://gateway.zibal.ir";

    protected string $callback;
    protected string $merchantId;
    protected string $accessToken;

    public function __construct(string $accessToken, string $merchantId, string $callback)
    {
        $this->accessToken = $accessToken;
        $this->merchantId = $merchantId;
        $this->callback = $callback;
    }

    protected function send(string $path, array $data, Method $method): null|stdClass
    {
        $data_string = json_encode($data);
        $header = [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)
        ];

        $ch = curl_init(sprintf("%s/%s", self::BASE, $path));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method->value);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $result = curl_exec($ch);
        curl_close($ch);

        if (!$result) {
            return null;
        }

        return json_decode($result);
    }

    public function PaymentRequest(int $amount, string $description = "", string $orderId = "", string $mobile = "")
    {
        $data = [
            "merchant" => $this->merchantId,
            "callbackUrl" => $this->callback,
            "amount" => $amount
        ];

        if ($description != "") {
            $data["description"] = $description;
        }

        if ($orderId != "") {
            $data["orderId"] = $orderId;
        }

        if ($mobile != "") {
            $data["mobile"] = $mobile;
        }

        $response = $this->send("v1/request", $data, Method::POST);
        if (is_null($response)) {
            throw new Exception("error");
        }

        return $response;
    }

    public function PaymentVerify(int $trackId)
    {
        $data = [
            "merchant" => $this->merchantId,
            "trackId" => $trackId
        ];

        $response = $this->send("v1/verify", $data, Method::POST);

        if (is_null($response)) {
            throw new Exception("verify error");
        }

        return $response;
    }

    public function Inquery(int $trackId)
    {
        $data = [
            "merchant" => $this->merchantId,
            "trackId" => $trackId
        ];

        $response = $this->send("v1/inquiry", $data, Method::POST);

        if (is_null($response)) {
            throw new Exception("inquiry error");
        }

        return $response;
    }

}

