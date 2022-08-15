<?php
# Required File Includes
include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");
@require_once ("../2pay/2pay.php");


if(!empty($_GET['vendor'])){

  if($_GET['vendor'] == 'alipay'){
    $gatewayModule = $_GET['vendor'].'cn_'."2pay";//获取具体支付方式
  }else{
    $gatewayModule = $_GET['vendor'].'_'."2pay";//获取具体支付方式
  }

if (!$gatewayParams["type"]) {
    die("Module Not Activated");
}

$signature = $sign = '';
$data = $_POST;
unset($data['verifySign']);
ksort($data, SORT_STRING);
foreach ($data as $key => $value) {
    $sign .= $key . '=' . $value . '&';
}

$signature = md5($sign . md5(api2pay::$_apiToken));


if (isset($_POST['verifySign']) && $signature == $_POST['verifySign'] && isset($_POST['transactionNo']) && $_POST['transactionNo']) {

    $invoice_id = explode("-",$_POST['reference'])[0];
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
    addInvoicePayment($invoice_id,$transactionId,$amount,$fee,$gatewaymodule);


    // must output: "success", otherwise 2pay will be considered a failure
    die('success');
} else {

    // process of order failed
    die('failed');
}

}



if (isset($_GET['status']) && $_GET['status'] === 'success') {
	$code = "<div class='sa-icon sa-success animate'>
                <span class='sa-line sa-tip animateSuccessTip'></span>
                <span class='sa-line sa-long animateSuccessLong'></span>
                <div class='sa-placeholder'></div>
                <div class='sa-fix'></div>
            </div>";
    $code2 = "<h2>您已成功支付 </h2>";
    $code3 = "<p>我们会将确认资料发送至您的信箱。</p>";
} else {
    //验证失败
    $code = "<div class='sa-icon sa-error animateErrorIcon'>
				<span class='sa-x-mark animateXMark'>
					<span class='sa-line sa-left'></span>
					<span class='sa-line sa-right'></span>
				</span>
			</div>";
	$code2 = "<h2>奥...好像那里出错了</h2>";
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>支付接口返回页面</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="<?php echo $gatewayParams["systemurl"]; ?>/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
	<style>
body {
	padding-top: 60px;
	padding-bottom: 40px;
	font-family: 'HanHei SC', 'PingFang SC', 'Helvetica Neue', 'Helvetica', 'STHeitiSC-Light', 'Arial', sans-serif;
}
.well {
	padding: 0;
	box-shadow: none;
	border-color: #E4E4E4;
	background-color: #FFF;
}
.header {
	padding: 30px 50px;
}
.logo {
	font-size: 28px;
	margin: 0;
	line-height: 30px;
	font-weight: 300;
	font-family: Raleway, sans-serif;
}
.content {
	padding: 30px;
	border-top: 1px solid #E3E3E3;
	border-bottom: 1px solid #E3E3E3;
	background-color: #F8F9FB;
}
.content h2 {
	font-weight: 300;
}
.footer {
	padding: 30px 50px;
}
.footer p {
	color: #222;
	font-size: 16px;
	line-height: 26px;
	font-weight: 300;
}
.footer p span {
	color: #777;
}

.btn {
	color: #444;
	font-size: 16px;
	font-weight: 300;
	margin-top: 30px;
	border-radius: 3px;
	border-color: #E2E7EB;
	background-color: #E2E7EB;
}
.btn:hover {
	color: #777;
	border-color: #E2E7EB;
	background-color: #E2E7EB;
}

.sa-icon.sa-success {
    border-color: #A5DC86;
}
.sa-icon {
    width: 80px;
    height: 80px;
    border: 4px solid gray;
    -webkit-border-radius: 40px;
    border-radius: 40px;
    border-radius: 50%;
    margin: 20px auto;
    padding: 0;
    position: relative;
    box-sizing: content-box;
}
.sa-icon.sa-success .sa-line.sa-tip {
    -ms-transform: rotate(45deg) \9;
}
.sa-icon.sa-success .sa-line.sa-tip {
    width: 25px;
    left: 14px;
    top: 46px;
    -webkit-transform: rotate(45deg);
    transform: rotate(45deg);
}
.sa-icon.sa-success .sa-line.sa-long {
    width: 47px;
    right: 8px;
    top: 38px;
    -webkit-transform: rotate(-45deg);
    transform: rotate(-45deg);
}
.sa-icon.sa-success .sa-line {
    height: 5px;
    background-color: #A5DC86;
    display: block;
    border-radius: 2px;
    position: absolute;
    z-index: 2;
}
.animateSuccessTip {
    -webkit-animation: animateSuccessTip 0.75s;
    animation: animateSuccessTip 0.75s;
}
.animateSuccessLong {
    -webkit-animation: animateSuccessLong 0.75s;
    animation: animateSuccessLong 0.75s;
}
.sa-icon.sa-success .sa-placeholder {
    width: 80px;
    height: 80px;
    border: 4px solid rgba(165, 220, 134, 0.2);
    -webkit-border-radius: 40px;
    border-radius: 40px;
    border-radius: 50%;
    box-sizing: content-box;
    position: absolute;
    left: -4px;
    top: -4px;
    z-index: 2;
}
.sa-icon.sa-error {
    border-color: #F27474;
}
.sa-icon.sa-error .sa-x-mark {
    position: relative;
    display: block;
}
.sa-icon.sa-error .sa-line {
    position: absolute;
    height: 5px;
    width: 47px;
    background-color: #F27474;
    display: block;
    top: 37px;
    border-radius: 2px;
}
.sa-icon.sa-error .sa-line.sa-left {
    -webkit-transform: rotate(45deg);
    transform: rotate(45deg);
    left: 17px;
}
.sa-icon.sa-error .sa-line.sa-right {
    -webkit-transform: rotate(-45deg);
    transform: rotate(-45deg);
    right: 16px;
}
@-webkit-keyframes animateSuccessTip {
    0% {
        width: 0;
        left: 1px;
        top: 19px;
    }
    54% {
        width: 0;
        left: 1px;
        top: 19px;
    }
    70% {
        width: 50px;
        left: -8px;
        top: 37px;
    }
    84% {
        width: 17px;
        left: 21px;
        top: 48px;
    }
    100% {
        width: 25px;
        left: 14px;
        top: 45px;
    }
}
@keyframes animateSuccessTip {
    0% {
        width: 0;
        left: 1px;
        top: 19px;
    }
    54% {
        width: 0;
        left: 1px;
        top: 19px;
    }
    70% {
        width: 50px;
        left: -8px;
        top: 37px;
    }
    84% {
        width: 17px;
        left: 21px;
        top: 48px;
    }
    100% {
        width: 25px;
        left: 14px;
        top: 45px;
    }
}
@-webkit-keyframes animateSuccessLong {
    0% {
        width: 0;
        right: 46px;
        top: 54px;
    }
    65% {
        width: 0;
        right: 46px;
        top: 54px;
    }
    84% {
        width: 55px;
        right: 0px;
        top: 35px;
    }
    100% {
        width: 47px;
        right: 8px;
        top: 38px;
    }
}
@keyframes animateSuccessLong {
    0% {
        width: 0;
        right: 46px;
        top: 54px;
    }
    65% {
        width: 0;
        right: 46px;
        top: 54px;
    }
    84% {
        width: 55px;
        right: 0px;
        top: 35px;
    }
    100% {
        width: 47px;
        right: 8px;
        top: 38px;
    }
}
@-webkit-keyframes animateErrorIcon {
    0% {
        transform: rotateX(100deg);
        -webkit-transform: rotateX(100deg);
        opacity: 0;
    }
    100% {
        transform: rotateX(0deg);
        -webkit-transform: rotateX(0deg);
        opacity: 1;
    }
}
@keyframes animateErrorIcon {
    0% {
        transform: rotateX(100deg);
        -webkit-transform: rotateX(100deg);
        opacity: 0;
    }
    100% {
        transform: rotateX(0deg);
        -webkit-transform: rotateX(0deg);
        opacity: 1;
    }
}
.animateErrorIcon {
    -webkit-animation: animateErrorIcon 0.5s;
    animation: animateErrorIcon 0.5s;
}
@-webkit-keyframes animateXMark {
    0% {
        transform: scale(0.4);
        -webkit-transform: scale(0.4);
        margin-top: 26px;
        opacity: 0;
    }
    50% {
        transform: scale(0.4);
        -webkit-transform: scale(0.4);
        margin-top: 26px;
        opacity: 0;
    }
    80% {
        transform: scale(1.15);
        -webkit-transform: scale(1.15);
        margin-top: -6px;
    }
    100% {
        transform: scale(1);
        -webkit-transform: scale(1);
        margin-top: 0;
        opacity: 1;
    }
}
@keyframes animateXMark {
    0% {
        transform: scale(0.4);
        -webkit-transform: scale(0.4);
        margin-top: 26px;
        opacity: 0;
    }
    50% {
        transform: scale(0.4);
        -webkit-transform: scale(0.4);
        margin-top: 26px;
        opacity: 0;
    }
    80% {
        transform: scale(1.15);
        -webkit-transform: scale(1.15);
        margin-top: -6px;
    }
    100% {
        transform: scale(1);
        -webkit-transform: scale(1);
        margin-top: 0;
        opacity: 1;
    }
}
.animateXMark {
    -webkit-animation: animateXMark 0.5s;
    animation: animateXMark 0.5s;
}
	</style>
</head>
<body>
<div class="container">
	<div class="row">
		<div class="col-xs-10 col-xs-offset-1 col-sm-8 col-sm-offset-2 col-md-6 col-md-offset-3">
			<div class="well">
				<div class="header">
					<h1 class="logo"><?php echo $gatewayParams["companyname"];?></h1>
				</div>
				<div class="content">
					<div class="row">
						<div class="col-sm-12">
							<?php echo $code ?>
						</div>
						<div class="col-sm-12 text-center">
							<?php echo $code2?>
						</div>
					</div>
				</div>
				<div class="footer">
					<?php echo $code3?>
					<a href="<?php echo $gatewayParams["systemurl"]; ?>/clientarea.php" class="btn btn-lg btn-success btn-block">返回用户中心</a>
				</div>
			</div>
		</div>
	</div>
</div>
</body>
</html>
