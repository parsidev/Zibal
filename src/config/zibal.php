<?php

return [
	'apiPurchaseUrl' => 'https://gateway.zibal.ir/v1/request',
    'apiPaymentUrl' => 'https://gateway.zibal.ir/start/',
    'apiVerificationUrl' => 'https://gateway.zibal.ir/v1/verify',
    'apiWalletList' => 'https://api.zibal.ir/v1/wallet/list',
    'apiWalletCreate' => 'https://api.zibal.ir/v1/wallet/create',
    'apiWalletCharge' => 'https://api.zibal.ir/v1/wallet/charge',
    'apiWalletTransfer' => 'https://api.zibal.ir/v1/wallet/transfer',
    'apiWalletBalance' => 'https://api.zibal.ir/v1/wallet/balance',
    'apiWalletCheckout' => 'https://api.zibal.ir/v1/wallet/checkout',
    'apiWalletCheckoutCancel' => 'https://api.zibal.ir/v1/wallet/checkout/cancel',
    'apiReportCheckout' => 'https://api.zibal.ir/v1/report/checkout',
    'apiReportCheckoutQueue' => 'https://api.zibal.ir/v1/report/checkout/queue',
    'apiGatewayReport' => 'https://api.zibal.ir/v1/gateway/report/transaction',
    'apiCodReport' => 'https://api.zibal.ir/v1/cod/report/transaction',
    'apiSubMerchantCreate' => 'https://api.zibal.ir/v1/subMerchant/create',
    'apiSubMerchantList' => 'https://api.zibal.ir/v1/subMerchant/list',
    'apiSubMerchantEdit' => 'https://api.zibal.ir/v1/subMerchant/edit',
    'accessToken' => '',
    'merchantId' => '',
    'unit' => 'toman', //rial or toman
    'mode' => 'normal', // normal or direct
    'callbackUrl' => 'http://yoursite.com/path/to',
    'subMerchantCallbackUrl' => 'http://yoursite.com/path/to',
];
