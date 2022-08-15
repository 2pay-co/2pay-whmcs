<?php
# Required File Includes
include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");
@require_once ("../2pay/2pay.php");


$all = file_get_contents("php://input");
$all = json_decode($all, true);


if($_GET['vendor'] == 'alipay'){

  $gatewayModule = $_GET['vendor'].'cn_'."2pay";//获取具体支付方式
}else{
  $gatewayModule = $_GET['vendor'].'_'."2pay";//获取具体支付方式
}

$_POST = $all;
$gatewayParams = getGatewayVariables($gatewayModule);

if (!$gatewayParams["type"]) {
    die("Module Not Activated");
}

$signature = $sign = '';
$data = $_POST;
unset($data['verifySign']);
ksort($data, SORT_STRING);
foreach ($data as $key => $value) {
    if ($value) {
        $sign .= $key . '=' . $value . '&';
    }
}

// aa9d1b71a0c1fde5ae0563219a18a058\
$signature = md5($sign . md5(api2pay::$_apiToken));


if (isset($_POST['verifySign']) && $signature == $_POST['verifySign'] && isset($_POST['transactionNo']) && $_POST['transactionNo']) {
    $invoice_id =  explode("-",$_POST['reference'])[0];
    $fee = 0;
    $paymentAmount = $_POST['amount'];
    //获取账单 用户ID
    $userinfo   = \Illuminate\Database\Capsule\Manager::table('tblinvoices')->where('id', $invoice_id)->first();
    //得到用户 货币种类
    $currency = getCurrency( $userinfo->userid );
    
    //获取支付货币种类
    $currencytype   = \Illuminate\Database\Capsule\Manager::table('tblcurrencies')->where('id', $gatewayParams['convertto'])->first();
  
    if ($currencytype->id != $currency["id"]) {
        // 转换货币
        $amount = convertCurrency( $paymentAmount, $currencytype->id, $currency['id'] );
        $systemtotal = $userinfo->total;
        $grossExpected = convertCurrency($systemtotal, $currency["id"], $currencytype->id);
        if (abs($paymentAmount - $grossExpected) < 1) {
            $amount = $systemtotal;
        }
    }
    $transactionId = $_POST['transactionNo'];
    $invoice = \Illuminate\Database\Capsule\Manager::table('tblinvoices')->where('id', $invoice_id)->first();

    if ($invoice->status === 'Paid') {
        die('success');
    }

    $invoice_id = checkCbInvoiceID($invoice_id, $gatewayParams['name']);

    checkCbTransID($transactionId);


    logTransaction($gatewayParams['name'], $arr, $success);
    addInvoicePayment($invoice_id,$transactionId, $amount,$fee,$gatewaymodule);


    // must output: "success", otherwise 2pay will be considered a failure
    die('success');
} else {
    // process of order failed
    die('failed');
}
