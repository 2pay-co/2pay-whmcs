<?php

require_once realpath(dirname(__FILE__)) . "/2pay/2pay.php";

function alipaycn_2pay_config() {
	return api2pay::getConfig('Alipay');
}

function alipaycn_2pay_link($params) {
	return api2pay::getLink($params, 'alipay');
}

function alipaycn_2pay_refund($params) {
	return api2pay::refund($params);
}

?>
