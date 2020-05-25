<?php namespace Parsidev\Zibal;
    
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

    
class Zibal
{

    /**
     * Zibal Client.
     *
     * @var object
     */
    protected $client;

    /**
     * Zibal settings
     *
     * @var object
     */
    protected $settings;

    public function __construct($setting)
    {
        $this->client = new Client();
        $this->settings = (object) $setting;
    }

    private function convertAmount($amout)
    {

        if (gettype($amout) != 'integer')
            $amout = intval($amout);

        if ($this->settings->unit == "rial") {
            return $amout * 10;
        }

        return $amout;
    }

    private function call($method, $url, $headers, $data)
    {
        $data = ["json" => $data, "http_errors" => false, "headers" => $headers];
        $response = $this->client->request($method, $url, $data);
        return json_decode($response->getBody()->getContents(), false);
    }

    private function authorization()
    {
        return ['Authorization' => "Bearer " . $this->settings->accessToken];
    }

    /**
     * Purchase Invoice.
     *
     *
     * @param $amout
     * @param string $callbackUrl
     * @param string $description
     * @param string $orderId
     * @param string $mobile
     * @param string $allowedCards
     * @return string
     *
     * @throws Exception
     */
    public function purchase($amout, $description = "", $orderId = "", $mobile = "", $allowedCards = "", $callbackUrl = "")
    {

        $amout = $this->convertAmount($amout);

        if (!Str::startsWith($callbackUrl, "http"))
            $callbackUrl = $this->settings->callbackUrl;

        $data = [
            'merchant' => $this->settings->merchantId,
            'amount' => $amout,
            'callbackUrl' => $callbackUrl
        ];

        if (!empty($description))
            $data['description'] = $description;

        if (!empty($orderId))
            $data['orderId'] = $orderId;

        if (!empty($mobile))
            $data['mobile'] = $mobile;

        if (!empty($allowedCards))
            $data['allowedCards'] = $allowedCards;

        $body = $this->call('POST', $this->settings->apiPurchaseUrl, [], $data);

        if ($body->result != 100) {
            // some error has happened
            throw new Exception($body->message);
        }

        return $body->trackId;
    }

    /**
     * Pay the Invoice
     *
     * @param $trackId
     * @param string $mode
     * @return RedirectResponse|mixed
     */
    public function pay($trackId, $mode = "")
    {
        $payUrl = $this->settings->apiPaymentUrl . $trackId;

        if (empty($mode)) {
            $mode = $this->settings->mode;
        }

        if (strtolower($mode) == 'direct') {
            $payUrl .= '/direct';
        }

        return redirect()->to($payUrl);
    }

    /**
     * Verify payment
     *
     * @param $successFlag
     * @param $orderId
     * @param $trackId
     * @return mixed|void
     *
     * @throws Exception
     */
    public function verify($trackId)
    {

        $data = [
            "merchant" => $this->settings->merchantId, //required
            "trackId" => $trackId, //required
        ];
        $body = $this->call('POST', $this->settings->apiVerificationUrl, [], $data);

        if ($body->result != 100) {
            throw new Exception($body->message);
        }

        return $body;
    }

    //---------------------------------------------------------------------------------------------

    /**
     * Get Wallet List
     *
     * @return mixed|void
     *
     * @throws Exception
     */
    public function getWalletList()
    {
        $body = $this->call('GET', $this->settings->apiWalletList, $this->authorization(), []);
        if ($body->result != 1) {
            throw new Exception($body->message);
        }

        return $body->data;
    }

    /**
     * Create New Wallet
     *
     * @param $name
     * @return mixed|void
     *
     * @throws Exception
     */
    public function createNewWallet($name)
    {

        if (empty($name))
            throw new Exception("نام کیف پول وارد نشده است.");

        $data = ["name" => $name];
        $body = $this->call('POST', $this->settings->apiWalletCreate, $this->authorization(), $data);

        if ($body->result != 1) {
            throw new Exception($body->message);
        }

        return $body->data;
    }

    /**
     * Charge Wallet
     *
     * @param $id
     * @param $amount
     * @param $callbackUrl
     * @param $mobile
     * @param $feeMode
     * @return mixed|void
     *
     * @throws Exception
     */
    public function chargeWallet($id, $amount, $callbackUrl, $mobile, $feeMode = 0)
    {

        $amount = $this->convertAmount($amount);

        if (!Str::startsWith($callbackUrl, "http"))
            $callbackUrl = $this->settings->callbackUrl;

        $data = [
            "gatewayMerchant" => $this->settings->merchantId,
            "id" => $id,
            "amount" => $amount,
            "callbackUrl" => $callbackUrl,
            "feeMode" => $feeMode
        ];

        if (!empty($mobile))
            $data['mobile'] = $mobile;


        $body = $this->call('POST', $this->settings->apiWalletCharge, $this->authorization(), $data);

        if ($body->result != 1) {
            throw new Exception($body->message);
        }

        return $body->trackId;
    }

    /**
     * Transfer Between Wallets
     *
     * @param $srcId
     * @param $dstId
     * @param $amount
     *
     * @return mixed|void
     *
     * @throws Exception
     *
     */
    public function transferBetweenWallets($srcId, $dstId, $amount)
    {

        $amount = $this->convertAmount($amount);

        $data = ["srcId" => $srcId, "dstId" => $dstId, "amount" => $amount];

        $body = $this->call('POST', $this->settings->apiWalletTransfer, $this->authorization(), $data);

        if ($body->result != 1) {
            throw new Exception($body->message);
        }

        return $body->message;
    }

    /**
     * Get Wallet Balance
     *
     * @param $id
     * @return mixed|void
     * @throws Exception
     */
    public function getWalletBalance($id)
    {

        if (empty($id))
            throw new Exception("شناسه کیف پول وارد نشده است.");

        $data = ["id" => $id];

        $body = $this->call('POST', $this->settings->apiWalletBalance, $this->authorization(), $data);

        if ($body->result != 1) {
            throw new Exception($body->message);
        }

        return $body->data;
    }

    /**
     * Wallet Checkout
     *
     * @param $amount
     * @param $id
     * @param $submerchantId
     * @param $bankAccount
     * @param $description
     * @return mixed|void
     * @throws Exception
     */
    public function walletCheckout($amount, $id, $submerchantId = null, $bankAccount = null, $description)
    {

        if (empty($id))
            throw new Exception("شناسه کیف پول وارد نشده است.");

        $amount = $this->convertAmount($amount);

        $data = ["amount" => $amount, "id" => $id, "description" => $description];

        if ($submerchantId != null)
            $data["submerchantId"] = $submerchantId;
        else if ($bankAccount != null)
            $data["bankAccount"] = $bankAccount;


        $body = $this->call('POST', $this->settings->apiWalletCheckout, $this->authorization(), $data);

        if ($body->result != 1) {
            throw new Exception($body->message);
        }

        return $body->data;
    }

    /**
     * Wallet Checkout Cancel
     *
     * @param $checkoutQueueId
     * @return mixed|void
     * @throws Exception
     */
    public function walletCheckoutCancel($checkoutQueueId)
    {

        if (empty($checkoutQueueId))
            throw new Exception("شناسه تسویه وارد نشده است.");

        $data = ["checkoutQueueId" => $checkoutQueueId];

        $body = $this->call('POST', $this->settings->apiWalletCheckoutCancel, $this->authorization(), $data);

        if ($body->result != 1) {
            throw new Exception($body->message);
        }

        return $body->data;

    }

    /**
     * Checkout Report
     *
     * @param $fromDate
     * @param $toDate
     * @param int $page
     * @param int $size
     * @param array $subMerchants
     * @param $transactionTrackId
     * @param $transactionZibalId
     * @param bool $verbose
     * @throws Exception
     * @return mixed|void
     */
    public function checkoutReport($fromDate = "", $toDate = "", $page = "", $size = "", $subMerchants = "", $transactionTrackId = "", $transactionZibalId = "", $verbose = "")
    {
        $data = [];

        if (!empty($fromDate))
            $data["fromDate"] = $fromDate;

        if (!empty($toDate))
            $data["toDate"] = $toDate;

        if (!empty($page))
            $data["page"] = $page;

        if (!empty($size))
            $data["size"] = $size;

        if (!empty($subMerchants))
            $data["subMerchants"] = $subMerchants;

        if (!empty($transactionTrackId))
            $data["transactionTrackId"] = $transactionTrackId;

        if (!empty($transactionZibalId))
            $data["transactionZibalId"] = $transactionZibalId;

        if (!empty($verbose))
            $data["verbose"] = $verbose;


        $body = $this->call('POST', $this->settings->apiReportCheckout, $this->authorization(), $data);

        if ($body->result != 1) {
            throw new Exception($body->message);
        }

        return $body;
    }

    /**
     * Checkout Report Queue
     *
     * @param $subMerchants
     * @param $verbose
     * @return mixed|void
     * @throws Exception
     */
    public function checkoutReportQueue($subMerchants = "", $verbose = "")
    {

        $data = [];

        if (!empty($subMerchants))
            $data["subMerchants"] = $subMerchants;

        if (!empty($verbose))
            $data["verbose"] = $verbose;


        $body = $this->call('POST', $this->settings->apiReportCheckoutQueue, $this->authorization(), $data);

        if ($body->result != 1) {
            throw new Exception($body->message);
        }

        return $body->data;

    }

    /**
     * Gateway Report
     *
     * @param $fromDate
     * @param $toDate
     * @param $page
     * @param $size
     * @param $verbose
     * @param $trackId
     * @param $status
     * @param $orderId
     * @param $mobile
     * @param $amount
     * @param $cardNumber
     * @return mixed|void
     * @throws Exception
     */
    public function gatewayReport($fromDate = "", $toDate = "", $page = "", $size = "", $verbose = "", $trackId = "", $status = "", $orderId = "", $mobile = "", $amount = "", $cardNumber = "")
    {

        $data = ['merchant' => $this->settings->merchantId];

        if (!empty($fromDate))
            $data["fromDate"] = $fromDate;

        if (!empty($toDate))
            $data["toDate"] = $toDate;

        if (!empty($page))
            $data["page"] = $page;

        if (!empty($verbose))
            $data["verbose"] = $verbose;

        if (!empty($size))
            $data["size"] = $size;

        if (!empty($trackId))
            $data["trackId"] = $trackId;

        if (!empty($status))
            $data["status"] = $status;

        if (!empty($orderId))
            $data["orderId"] = $orderId;

        if (!empty($mobile))
            $data["mobile"] = $mobile;

        if (!empty($amount))
            $data["amount"] = $amount;

        if (!empty($cardNumber))
            $data["cardNumber"] = $cardNumber;


        $body = $this->call('POST', $this->settings->apiGatewayReport, $this->authorization(), $data);

        if ($body->result != 1) {
            throw new Exception($body->message);
        }

        return $body;

    }

    /**
     * Cod Report
     *
     * @param string $zibalId
     * @param string $fromDate
     * @param string $toDate
     * @param string $page
     * @param string $size
     * @param string $verbose
     * @param string $status
     * @param string $orderId
     * @param string $amount
     * @return mixed|void
     * @throws Exception
     */
    public function codReport($zibalId = "", $fromDate = "", $toDate ="", $page= "", $size="", $verbose="", $status="", $orderId = "", $amount = "")
    {

        $data = [];

        if (!empty($zibalId))
            $data["zibalId"] = $zibalId;

        if (!empty($fromDate))
            $data["fromDate"] = $fromDate;

        if (!empty($toDate))
            $data["toDate"] = $toDate;

        if (!empty($page))
            $data["page"] = $page;

        if (!empty($size))
            $data["size"] = $size;

        if (!empty($verbose))
            $data["verbose"] = $verbose;

        if (!empty($status))
            $data["status"] = $status;

        if (!empty($orderId))
            $data["orderId"] = $orderId;

        if (!empty($amount))
            $data["amount"] = $amount;


        $body = $this->call('POST', $this->settings->apiCodReport, $this->authorization(), $data);

        if ($body->result != 1) {
            throw new Exception($body->message);
        }

        return $body;

    }

    /**
     * Create New SubMerchant
     *
     * @param $bankAccount
     * @param $name
     * @param string $callbackUrl
     * @return mixed|void
     * @throws Exception
     */
    public function createNewSubMerchant($bankAccount, $name, $callbackUrl = "")
    {

        if (!Str::startsWith($callbackUrl, "http"))
            $callbackUrl = $this->settings->subMerchantCallbackUrl;

        $data = ["bankAccount"=> $bankAccount, "name" => $name, "callbackUrl" => $callbackUrl];

        $body = $this->call('POST', $this->settings->apiSubMerchantCreate, $this->authorization(), $data);

        if ($body->result != 1) {
            throw new Exception($body->message);
        }

        return $body->data;

    }

    /**
     * Get SubMerchant List
     *
     * @param string $subMerchant
     * @param string $page
     * @param string $size
     * @return mixed|void
     * @throws Exception
     */
    public function getSubMerchantList($subMerchant = "", $page = "", $size = "")
    {
        $data = [];

        if (!empty($subMerchant))
            $data["subMerchant"] = $subMerchant;

        if (!empty($page))
            $data["page"] = $page;

        if (!empty($size))
            $data["size"] = $size;

        $body = $this->call('POST', $this->settings->apiSubMerchantList, $this->authorization(), $data);

        if ($body->result != 1) {
            throw new Exception($body->message);
        }

        return $body->data;

    }

    /**
     * Edit SubMerchant
     *
     * @param $id
     * @param $bankAccount
     * @param $name
     * @return mixed|void
     * @throws Exception
     */
    public function editSubMerchant($id, $bankAccount, $name)
    {
        $data = ["id" => $id, "bankAccount" => $bankAccount, "name" => $name];

        $body = $this->call('POST', $this->settings->apiSubMerchantEdit, $this->authorization(), $data);

        if ($body->result != 1) {
            throw new Exception($body->message);
        }

        return $body->data;

    }
}
