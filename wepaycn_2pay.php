<?php

require_once realpath(dirname(__FILE__)) . "/2pay/2pay.php";

function wepaycn_2pay_config() {
	return api2pay::getConfig('WechatPay');
}

function wepaycn_2pay_link($params) {
	return api2pay::getLink($params, 'wechat');
}

function wepaycn_2pay_refund($params) {
	return api2pay::refund($params);
}

?>
