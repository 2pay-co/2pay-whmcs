<?php

class api2pay {
	public static $_merchantNo = 'M1659129181867';
	public static $_apiToken = 'yjmsyi1200sjllsq';

	public static function getPMId($class_name) {
		$index = strpos($class_name, '_');
		return strtolower(substr($class_name, $index + 1));
	}

	public static function getConfig($pm_name) {
		$configarray = array(
		"FriendlyName" => array(
			"Type" => "System",
			"Value" => "2pay $pm_name 支付"
		),);

		return $configarray;
	}

	public static function getLink(&$params, $pm_id) {

		$payparams = array(
				'merchantNo'     => self::$_merchantNo,
				'amount'         => $params["amount"],
				'currency'       => $params["currency"],
				'vendor'         => $pm_id,
				'ipnUrl'         => $params['systemurl']."/modules/gateways/callback/callback_secure_pay_ipn.php?vendor=".$pm_id,
				'callbackUrl'    => $params['systemurl']."/modules/gateways/callback/callback_secure_pay.php?reference={reference}&status={status}",
				'terminal'       => 'ONLINE',
				'reference'      => $params['invoiceid'] . '-' . time(),
				//'reference' => '2-1660210967',
				// 'goodsInfo' => "",
			 //  'description' => "",
				'timeout' => '120'
		);
		ksort($payparams, SORT_STRING);

		$payparams['verifySign'] = api_2pay_sign($payparams,self::$_apiToken);
		$result = api_2pay_curl('online/v1/secure-pay', $payparams);
		
		if ($result['ret_code'] === '000100') {
		    if($pm_id == 'paypal'){

		    $paylink = "<a href='".$result['result']['cashierUrl']."' class='btn btn-danger btn-block'>立刻支付</a>";
		    }else{
				$paylink = "<form id='2paysubmit'  name='2paysubmit' action='".$result['result']['cashierUrl']."' method='get' target='_blank'></form><button type='button' class='btn btn-danger btn-block' onclick='document.forms[\"2paysubmit\"].submit();'>".Lang::trans("makepayment")."</button>";
		    }
						return $paylink;
		}else{
		       // var_dump($result); return;
				$paylink = "请求错误：" .$result['ret_msg'];
		}
		return $paylink;
	}

	public static function refund($params) {

		$payparams = array(
		'merchantNo'     => self::$_merchantNo,
        'amount'   => $params["amount"],
        'settleCurrency' => $params["currency"],
        'reference'  => trim($params['transid']),
    );
    $payparams['verifySign'] = api_2pay_sign($payparams,self::$_apiToken);
    $result = api_2pay_curl('online/v1/refund', $payparams);
    if ($result['ret_code'] === '000100') {
        return array( "status" => "success", "rawdata" => $result, "transid" => '退款流水号:'.$result['result']['refundTransactionNo'] .'|时间:'.$result['result']['refundReference'], "fees" => '0');
    }else{
         return array( "status" => "error", "rawdata" => $result);
    }
	}
}


function api_2pay_curl($url, $data) {
    $api_url = 'https://api.2pay.co/';
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $api_url . $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    $result = curl_exec($curl);
    curl_close($curl);
    return json_decode($result, true);
}

function api_2pay_sign($data,$token) {
    ksort($data, SORT_STRING);

    $sign = '';

    foreach ($data as $key => $value) {
        $sign .= $key . '=' . $value . '&';
    }
    
    $original_sign_str = $sign . md5($token);
    $verifySign = md5($original_sign_str);
    return $verifySign;
}
?>
