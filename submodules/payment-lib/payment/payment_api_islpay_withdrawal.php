<?php
require_once dirname(__FILE__) . '/abstract_payment_api_islpay.php';

/**
 * ISLPAY 速龍支付-出款
 *
 * * ISLPAY_WITHDRAWAL_PAYMENT_API, ID: 719
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.islpay.com/chargebank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_islpay_withdrawal extends Abstract_payment_api_islpay {
	const CALLBACK_STATUS_SUCCESS = 1;

	public function getPlatformCode() {
		return ISLPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'islpay_withdrawal';
	}

	# Implement abstract function but do nothing
	protected function configParams(&$params, $direct_pay_extra_info) {}

	/**
	 * detail: override common API functionsh
	 *
	 * @return void
	 */
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		return $this->returnUnimplemented();
	}

	public function processPaymentUrlForm($params) {
		return $this->returnUnimplemented();
	}

    # Submit
	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
		$result = array('success' => false, 'message' => 'Request failed');

		if(!$this->isAllowWithdraw()) {
			$result['message'] = lang("Withdraw not allowed with this API");
			$this->utils->debug_log($result);
			return $result;
		}

		$params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);

		# Check if successfully got the right $params
		if(isset($params['success'])) {
			if($params['success'] == false) {
				$result['message'] = $params['message'];
				$this->utils->debug_log($result);
				return $result;
			}
		}

		$params["sign_type"] = "RSA-S";
		$params["sign_info"] = $this->sign($params);

		$this->CI->utils->debug_log("======================================islpay submitWithdrawRequest params: ", $params);

		# CURL post the data to Dinpay
		$url = $this->getSystemInfo("url");
		$this->CI->utils->debug_log("======================================islpay process submitWithdrawRequest URL: ", $url);

		$postString = http_build_query($params);
		$curlConn = curl_init($url);
		curl_setopt($curlConn, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curlConn, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curlConn, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlConn, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curlConn, CURLOPT_POSTFIELDS, $postString);

		$this->setCurlProxyOptions($curlConn);

		# Need to specify the referer when doing CURL submit. since we use redirect 2nd url, we can take the HTTP_HOST
		//curl_setopt($curlConn, CURLOPT_REFERER, "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

		$curlResult['result']  = curl_exec($curlConn);
		$curlResult['message'] = curl_error($curlConn);
		$curlResult['success'] = (curl_errno($curlConn) == 0);
        $this->CI->utils->debug_log("======================================islpay withdrawal postString", $postString, "curl result", $curlResult);
        curl_close($curlConn);


		$errorMsg = NULL;
		if($curlResult['success']) {
            $decodedResult = $this->decodeResult($curlResult['result']);
            $this->utils->debug_log("======================================islpay withdrawal decoded Result", $decodedResult);

            return $decodedResult;
		} else {
			# curl error
			$errorMsg = $curlResult['message'];

			return array('success' => false, 'message' => "Curl Error: ".$errorMsg);
		}
	}

    # Note: to avoid breaking current APIs, these abstract methods are not marked abstract
	# APIs with withdraw function need to implement these methods
	## This function returns the URL to submit withdraw request to
	public function getWithdrawUrl() {
		return $this->getSystemInfo('url');
	}

    ## This function returns the params to be submitted to the withdraw URL
	public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
		$this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

        $params = array();
		$params = [
			"interface_version" => "V3.1.0",  #fixed value
			"mer_transfer_no"   => $transId,
			"merchant_no"       => $this->getSystemInfo("account"),
			"tran_code"         => "DMTI",  #fixed value
			"tran_amount"       => $this->convertAmountToCurrency($amount),
			"tran_fee_type"     => "1", #0：从转账金额中扣除 1：从账户余额中扣除
			"tran_type"         => "1", #0：普通 1：加急
			"remark"            => "Withdrawal"
		];

		# look up bank code
		$bankInfo = $this->getIslpayBankInfo();
		if(!array_key_exists($bank, $bankInfo)) {
			$this->utils->error_log("======================================isl withdrawal bank whose bankTypeId=[$bank] is not supported by isl");
			return array('success' => false, 'message' => 'Bank not supported by isl');
			$bank = '无';
		}

		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("======================================Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

		if(!empty($playerBankDetails)){
			$province = $playerBankDetails['province'];	//开户省  卡号的开户省
			$city     = $playerBankDetails['city'];	    //开户市  卡号的开户市
			$this->utils->debug_log("======================================Get playerBankDetails province=[$province] + city=[$city]");
		}

		# look up city code
		$cityInfo = $this->getIslpayCityInfo();
		if(!array_key_exists($city, $cityInfo)) {
			$this->utils->error_log("======================================isl withdrawal bank city whose cityId=[$city] is not supported by isl");
			return array('success' => false, 'message' => 'Provided Bank City not supported by isl');
			$bank = '无';
		}
		# look up if system setted city code
		# 因為速龍會擋很多城市造成出現錯誤:联行号查询失败,但固定送北京(11,1000)的話反而沒問題,也能成功出款
		$system_province_code = $this->getSystemInfo('provice_code');
		$system_city_code     = $this->getSystemInfo('city_code');

		$params["recv_bank_code"] = $bankInfo[$bank]['code'];	//开户行代碼
		$params["recv_accno"]     = $accNum;
		$params["recv_name"]      = $name;
		$params["recv_province"]  = empty($system_province_code) ? $province : $system_province_code;
		$params["recv_city"]      = empty($system_city_code) ? $city : $system_city_code;


		
		return $params;
	}

	## This function takes in the return value of the URL and translate it to the following structure
	## array('success' => false, 'message' => 'Error message')
	public function decodeResult($resultString, $queryAPI = false) {

		# parses return XML result into array
		## Parse xml array
		$xmlResult = $this->parseResultXML($resultString);

		## Flatten the parsed xml array
		$result = $this->flattenResult($xmlResult);

		if($queryAPI) {
			$this->utils->debug_log("======================================islpay checkWithdrawStatus decoded result string", $result);
		} else {
			$this->utils->debug_log("======================================islpay withdrawal decoded result string", $result);
		}

		if($result['result_code'] == '0') {
			$message = "Islpay withdrawal response successful, mer_transfer_no: ". $result['mer_transfer_no'];
			if($queryAPI) {
				$sucess = false;
				if($result['recv_code'] == "0000") {
					$sucess = true;
					$message = "Islpay withdrawal success! mer_transfer_no: ". $result['mer_transfer_no'];
					return array('success' => $sucess, 'message' => $message);
				} else if($result['recv_code'] == "0002" || $result['recv_code'] == "0003"){
					$sucess = false;
					$message = "Islpay withdrawal failed, recv_info: ". $result['recv_info'];
					$this->CI->wallet_model->withdrawalAPIReturnFailure($message);
					return array('success' => $sucess, 'message' => $message);
				}
				else {
					$sucess = false;
					$message = "Islpay withdrawal response status, recv_info: ". $result['recv_info'];
					return array('success' => $sucess, 'message' => $message);
				}
			}
			return array('success' => true, 'message' => $message);
		} else {
			$message = "Islpay withdrawal response failed, recv_info: ". $result['recv_info'];
			return array('success' => false, 'message' => $message);
		}

		return array('success' => false, 'message' => "Decode failed");
	}

	## This function provides a way to manually check withdraw status. Useful when API does not provide a callback.
	## Returns array('success' => false, 'payment_fail' => false, 'message' => 'Error message')
	## 'success' means whether payment is successful, 'payment_fail' means if payment is not successful, shall we mark it as failed or shall we wait
	public function checkWithdrawStatus($transId) {
		$this->CI->load->model(array('wallet_model'));
		$walletaccount = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

		$dateTimeString = $walletaccount['dwDateTime'];
		$datetime = new DateTime($dateTimeString);

		# ---- First add bank card entry ----
        $params = array();
		$params = [
			"interface_version" => "V3.1.0",  #fixed value
			"mer_transfer_no"   => $transId,
			"merchant_no"       => $this->getSystemInfo("account"),
			"tran_code"         => "DMTQ",  #fixed value
		];
		$params["sign_info"] = $this->sign($params);
		$params["sign_type"] = "RSA-S";

		$this->CI->utils->debug_log("======================================islpay checkWithdrawStatus params: ", $params);

		$url = $this->getSystemInfo('url');
		$this->CI->utils->debug_log("======================================islpay checkWithdrawStatus url: ", $url);

		$response = $this->submitPostForm($url, $params);

		$this->CI->utils->debug_log("======================================islpay checkWithdrawStatus result: ", $response);

		$decodedResult = $this->decodeResult($response, true);

		return $decodedResult;
	}


    /*Customized functions*/
	protected function flattenResult($xmlResult) {
		$this->CI->utils->debug_log('======================================islpay xmlResult to be flattened', $xmlResult);
		return $xmlResult["islpay"];
	}

	public function getIslpayBankInfo() {
		$bankInfo = array();
		$bankInfoArr = $this->getSystemInfo("islpay_bank_info");
		if(!empty($bankInfoArr)) {
			foreach($bankInfoArr as $bankInfoItem) {
				$bankInfo[$bankInfoItem[0]] = array('name' => $bankInfoItem[1], 'code' => $bankInfoItem[2]);
			}
			$this->utils->debug_log("======================================getting islpay bank info from extra_info: ", $bankInfo);
		} else {
			$bankInfo = array(
				'4'  => array('name' => '中国农业银行', 'code' => 'ABC'),
				'1'  => array('name' => '中国工商银行', 'code' => 'ICBC'),
				'3'  => array('name' => '中国建设银行', 'code' => 'CCB'),
				'5'  => array('name' => '交通银行', 'code' => 'BCOM'),
                '6'  => array('name' => '中国银行', 'code' => 'BOC'),
				'2'  => array('name' => '招商银行', 'code' => 'CMB'),
				'11' => array('name' => '中国民生银行', 'code' => 'CMBC'),
				'20' => array('name' => '光大银行', 'code' => 'CEBB'),
				'13' => array('name' => '兴业银行', 'code' => 'CIB'),
                '12' => array('name' => '中国邮政储蓄银行', 'code' => 'PSBC'),
                '15' => array('name' => '平安银行', 'code' => 'SPABANK'),
                '10' => array('name' => '中信银行', 'code' => 'ECITIC'),
				'8'  => array('name' => '广东发展银行', 'code' => 'GDB'),
				'14' => array('name' => '华夏银行', 'code' => 'HXB'),
				'24' => array('name' => '浦发银行', 'code' => 'SPDB')
			);
			$this->utils->debug_log("======================================getting islpay bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

	public function getIslpayCityInfo() {
		$province_city_list = '{
			"北京":{"provice_code":"11","city_code":"1000"},
			"天津":{"provice_code":"12","city_code":"1100"},
			"石家庄":{"provice_code":"13","city_code":"1210"},
			"唐山":{"provice_code":"13","city_code":"1240"},
			"秦皇岛":{"provice_code":"13","city_code":"1260"},
			"邯郸":{"provice_code":"13","city_code":"1270"},
			"邢台":{"provice_code":"13","city_code":"1310"},
			"保定":{"provice_code":"13","city_code":"1340"},
			"张家口":{"provice_code":"13","city_code":"1380"},
			"承德":{"provice_code":"13","city_code":"1410"},
			"沧州":{"provice_code":"13","city_code":"1430"},
			"廊坊":{"provice_code":"13","city_code":"1460"},
			"衡水":{"provice_code":"13","city_code":"1480"},
			"太原":{"provice_code":"14","city_code":"1610"},
			"大同":{"provice_code":"14","city_code":"1620"},
			"阳泉":{"provice_code":"14","city_code":"1630"},
			"长治":{"provice_code":"14","city_code":"1640"},
			"晋城":{"provice_code":"14","city_code":"1680"},
			"朔州":{"provice_code":"14","city_code":"1690"},
			"忻州":{"provice_code":"14","city_code":"1710"},
			"吕梁":{"provice_code":"14","city_code":"1730"},
			"晋中":{"provice_code":"14","city_code":"1750"},
			"临汾":{"provice_code":"14","city_code":"1770"},
			"运城":{"provice_code":"14","city_code":"1810"},
			"呼和浩特":{"provice_code":"15","city_code":"1910"},
			"包头":{"provice_code":"15","city_code":"1920"},
			"乌海":{"provice_code":"15","city_code":"1930"},
			"赤峰":{"provice_code":"15","city_code":"1940"},
			"呼伦贝尔":{"provice_code":"15","city_code":"1960"},
			"兴安盟":{"provice_code":"15","city_code":"1980"},
			"通辽":{"provice_code":"15","city_code":"1990"},
			"锡林浩特":{"provice_code":"15","city_code":"2010"},
			"乌兰察布":{"provice_code":"15","city_code":"2030"},
			"鄂尔多斯":{"provice_code":"15","city_code":"2050"},
			"巴彦淖尔":{"provice_code":"15","city_code":"2070"},
			"阿拉善盟":{"provice_code":"15","city_code":"2080"},
			"沈阳":{"provice_code":"21","city_code":"2210"},
			"大连":{"provice_code":"21","city_code":"2220"},
			"鞍山":{"provice_code":"21","city_code":"2230"},
			"抚顺":{"provice_code":"21","city_code":"2240"},
			"本溪":{"provice_code":"21","city_code":"2250"},
			"丹东":{"provice_code":"21","city_code":"2260"},
			"锦州":{"provice_code":"21","city_code":"2270"},
			"葫芦岛":{"provice_code":"21","city_code":"2276"},
			"营口":{"provice_code":"21","city_code":"2280"},
			"阜新":{"provice_code":"21","city_code":"2290"},
			"辽阳":{"provice_code":"21","city_code":"2310"},
			"盘锦":{"provice_code":"21","city_code":"2320"},
			"铁岭":{"provice_code":"21","city_code":"2330"},
			"朝阳":{"provice_code":"21","city_code":"2340"},
			"长春":{"provice_code":"22","city_code":"2410"},
			"吉林":{"provice_code":"22","city_code":"2420"},
			"四平":{"provice_code":"22","city_code":"2430"},
			"辽源":{"provice_code":"22","city_code":"2440"},
			"通化":{"provice_code":"22","city_code":"2450"},
			"白山":{"provice_code":"22","city_code":"2460"},
			"白城":{"provice_code":"22","city_code":"2470"},
			"延边":{"provice_code":"22","city_code":"2490"},
			"松原":{"provice_code":"22","city_code":"2520"},
			"哈尔滨":{"provice_code":"23","city_code":"2610"},
			"齐齐哈尔":{"provice_code":"23","city_code":"2640"},
			"大庆":{"provice_code":"23","city_code":"2650"},
			"鸡西":{"provice_code":"23","city_code":"2660"},
			"鹤岗":{"provice_code":"23","city_code":"2670"},
			"双鸭山":{"provice_code":"23","city_code":"2680"},
			"佳木斯":{"provice_code":"23","city_code":"2690"},
			"伊春":{"provice_code":"23","city_code":"2710"},
			"牡丹江":{"provice_code":"23","city_code":"2720"},
			"七台河":{"provice_code":"23","city_code":"2740"},
			"绥化":{"provice_code":"23","city_code":"2760"},
			"黑河":{"provice_code":"23","city_code":"2780"},
			"大兴安岭":{"provice_code":"23","city_code":"2790"},
			"上海":{"provice_code":"31","city_code":"2900"},
			"南京":{"provice_code":"32","city_code":"3010"},
			"无锡":{"provice_code":"32","city_code":"3020"},
			"徐州":{"provice_code":"32","city_code":"3030"},
			"常州":{"provice_code":"32","city_code":"3040"},
			"苏州":{"provice_code":"32","city_code":"3050"},
			"南通":{"provice_code":"32","city_code":"3060"},
			"连云港":{"provice_code":"32","city_code":"3070"},
			"淮安":{"provice_code":"32","city_code":"3080"},
			"宿迁":{"provice_code":"32","city_code":"3090"},
			"盐城":{"provice_code":"32","city_code":"3110"},
			"扬州":{"provice_code":"32","city_code":"3120"},
			"泰州":{"provice_code":"32","city_code":"3128"},
			"镇江":{"provice_code":"32","city_code":"3140"},
			"杭州":{"provice_code":"33","city_code":"3310"},
			"宁波":{"provice_code":"33","city_code":"3320"},
			"温州":{"provice_code":"33","city_code":"3330"},
			"嘉兴":{"provice_code":"33","city_code":"3350"},
			"湖州":{"provice_code":"33","city_code":"3360"},
			"绍兴":{"provice_code":"33","city_code":"3370"},
			"金华":{"provice_code":"33","city_code":"3380"},
			"衢州":{"provice_code":"33","city_code":"3410"},
			"舟山":{"provice_code":"33","city_code":"3420"},
			"丽水":{"provice_code":"33","city_code":"3430"},
			"台州":{"provice_code":"33","city_code":"3450"},
			"合肥":{"provice_code":"34","city_code":"3610"},
			"芜湖":{"provice_code":"34","city_code":"3620"},
			"蚌埠":{"provice_code":"34","city_code":"3630"},
			"淮南":{"provice_code":"34","city_code":"3640"},
			"马鞍山":{"provice_code":"34","city_code":"3650"},
			"淮北":{"provice_code":"34","city_code":"3660"},
			"铜陵":{"provice_code":"34","city_code":"3670"},
			"安庆":{"provice_code":"34","city_code":"3680"},
			"黄山":{"provice_code":"34","city_code":"3710"},
			"阜阳":{"provice_code":"34","city_code":"3720"},
			"亳州":{"provice_code":"34","city_code":"3722"},
			"宿州":{"provice_code":"34","city_code":"3740"},
			"滁州":{"provice_code":"34","city_code":"3750"},
			"六安":{"provice_code":"34","city_code":"3760"},
			"宣城":{"provice_code":"34","city_code":"3771"},
			"巢湖":{"provice_code":"34","city_code":"3781"},
			"池州":{"provice_code":"34","city_code":"3790"},
			"福州":{"provice_code":"35","city_code":"3910"},
			"厦门":{"provice_code":"35","city_code":"3930"},
			"莆田":{"provice_code":"35","city_code":"3940"},
			"三明":{"provice_code":"35","city_code":"3950"},
			"泉州":{"provice_code":"35","city_code":"3970"},
			"漳州":{"provice_code":"35","city_code":"3990"},
			"南平":{"provice_code":"35","city_code":"4010"},
			"宁德":{"provice_code":"35","city_code":"4030"},
			"龙岩":{"provice_code":"35","city_code":"4050"},
			"南昌":{"provice_code":"36","city_code":"4210"},
			"景德镇":{"provice_code":"36","city_code":"4220"},
			"萍乡":{"provice_code":"36","city_code":"4230"},
			"九江":{"provice_code":"36","city_code":"4240"},
			"新余":{"provice_code":"36","city_code":"4260"},
			"鹰潭":{"provice_code":"36","city_code":"4270"},
			"赣州":{"provice_code":"36","city_code":"4280"},
			"宜春":{"provice_code":"36","city_code":"4310"},
			"上饶":{"provice_code":"36","city_code":"4330"},
			"吉安":{"provice_code":"36","city_code":"4350"},
			"抚州":{"provice_code":"36","city_code":"4370"},
			"济南":{"provice_code":"37","city_code":"4510"},
			"青岛":{"provice_code":"37","city_code":"4520"},
			"淄博":{"provice_code":"37","city_code":"4530"},
			"枣庄":{"provice_code":"37","city_code":"4540"},
			"东营":{"provice_code":"37","city_code":"4550"},
			"烟台":{"provice_code":"37","city_code":"4560"},
			"潍坊":{"provice_code":"37","city_code":"4580"},
			"济宁":{"provice_code":"37","city_code":"4610"},
			"泰安":{"provice_code":"37","city_code":"4630"},
			"莱芜":{"provice_code":"37","city_code":"4634"},
			"威海":{"provice_code":"37","city_code":"4650"},
			"滨州":{"provice_code":"37","city_code":"4660"},
			"德州":{"provice_code":"37","city_code":"4680"},
			"聊城":{"provice_code":"37","city_code":"4710"},
			"临沂":{"provice_code":"37","city_code":"4730"},
			"日照":{"provice_code":"37","city_code":"4732"},
			"菏泽":{"provice_code":"37","city_code":"4750"},
			"郑州":{"provice_code":"41","city_code":"4910"},
			"开封":{"provice_code":"41","city_code":"4920"},
			"洛阳":{"provice_code":"41","city_code":"4930"},
			"平顶山":{"provice_code":"41","city_code":"4950"},
			"安阳":{"provice_code":"41","city_code":"4960"},
			"鹤壁":{"provice_code":"41","city_code":"4970"},
			"新乡":{"provice_code":"41","city_code":"4980"},
			"焦作":{"provice_code":"41","city_code":"5010"},
			"濮阳":{"provice_code":"41","city_code":"5020"},
			"许昌":{"provice_code":"41","city_code":"5030"},
			"漯河":{"provice_code":"41","city_code":"5040"},
			"三门峡":{"provice_code":"41","city_code":"5050"},
			"商丘":{"provice_code":"41","city_code":"5060"},
			"周口":{"provice_code":"41","city_code":"5080"},
			"驻马店":{"provice_code":"41","city_code":"5110"},
			"南阳":{"provice_code":"41","city_code":"5130"},
			"信阳":{"provice_code":"41","city_code":"5150"},
			"武汉":{"provice_code":"42","city_code":"5210"},
			"黄石":{"provice_code":"42","city_code":"5220"},
			"十堰":{"provice_code":"42","city_code":"5230"},
			"宜昌":{"provice_code":"42","city_code":"5260"},
			"襄樊":{"provice_code":"42","city_code":"5280"},
			"随州":{"provice_code":"42","city_code":"5286"},
			"鄂州":{"provice_code":"42","city_code":"5310"},
			"荆门":{"provice_code":"42","city_code":"5320"},
			"黄冈":{"provice_code":"42","city_code":"5330"},
			"孝感":{"provice_code":"42","city_code":"5350"},
			"咸宁":{"provice_code":"42","city_code":"5360"},
			"荆州":{"provice_code":"42","city_code":"5370"},
			"恩施州":{"provice_code":"42","city_code":"5410"},
			"长沙":{"provice_code":"43","city_code":"5510"},
			"株州":{"provice_code":"43","city_code":"5520"},
			"湘潭":{"provice_code":"43","city_code":"5530"},
			"衡阳":{"provice_code":"43","city_code":"5540"},
			"邵阳":{"provice_code":"43","city_code":"5550"},
			"岳阳":{"provice_code":"43","city_code":"5570"},
			"常德":{"provice_code":"43","city_code":"5580"},
			"张家界":{"provice_code":"43","city_code":"5590"},
			"益阳":{"provice_code":"43","city_code":"5610"},
			"娄底":{"provice_code":"43","city_code":"5620"},
			"郴州":{"provice_code":"43","city_code":"5630"},
			"永州":{"provice_code":"43","city_code":"5650"},
			"怀化":{"provice_code":"43","city_code":"5670"},
			"吉首":{"provice_code":"43","city_code":"5690"},
			"广州":{"provice_code":"44","city_code":"5810"},
			"韶关":{"provice_code":"44","city_code":"5820"},
			"深圳":{"provice_code":"44","city_code":"5840"},
			"珠海":{"provice_code":"44","city_code":"5850"},
			"汕头":{"provice_code":"44","city_code":"5860"},
			"揭阳":{"provice_code":"44","city_code":"5865"},
			"潮州":{"provice_code":"44","city_code":"5869"},
			"佛山":{"provice_code":"44","city_code":"5880"},
			"江门":{"provice_code":"44","city_code":"5890"},
			"湛江":{"provice_code":"44","city_code":"5910"},
			"茂名":{"provice_code":"44","city_code":"5920"},
			"肇庆":{"provice_code":"44","city_code":"5930"},
			"云浮":{"provice_code":"44","city_code":"5937"},
			"惠州":{"provice_code":"44","city_code":"5950"},
			"梅州":{"provice_code":"44","city_code":"5960"},
			"汕尾":{"provice_code":"44","city_code":"5970"},
			"河源":{"provice_code":"44","city_code":"5980"},
			"阳江":{"provice_code":"44","city_code":"5990"},
			"清远":{"provice_code":"44","city_code":"6010"},
			"东莞":{"provice_code":"44","city_code":"6020"},
			"中山":{"provice_code":"44","city_code":"6030"},
			"南宁":{"provice_code":"45","city_code":"6110"},
			"崇左":{"provice_code":"45","city_code":"6128"},
			"柳州":{"provice_code":"45","city_code":"6140"},
			"来宾":{"provice_code":"45","city_code":"6155"},
			"桂林":{"provice_code":"45","city_code":"6170"},
			"梧州":{"provice_code":"45","city_code":"6210"},
			"贺州":{"provice_code":"45","city_code":"6225"},
			"北海":{"provice_code":"45","city_code":"6230"},
			"玉林":{"provice_code":"45","city_code":"6240"},
			"贵港":{"provice_code":"45","city_code":"6242"},
			"百色":{"provice_code":"45","city_code":"6261"},
			"河池":{"provice_code":"45","city_code":"6281"},
			"钦州":{"provice_code":"45","city_code":"6311"},
			"防城港":{"provice_code":"45","city_code":"6330"},
			"海口":{"provice_code":"46","city_code":"6410"},
			"三亚":{"provice_code":"46","city_code":"6420"},
			"成都":{"provice_code":"51","city_code":"6510"},
			"重庆":{"provice_code":"50","city_code":"6530"},
			"自贡":{"provice_code":"51","city_code":"6550"},
			"攀枝花":{"provice_code":"51","city_code":"6560"},
			"泸州":{"provice_code":"51","city_code":"6570"},
			"德阳":{"provice_code":"51","city_code":"6580"},
			"绵阳":{"provice_code":"51","city_code":"6590"},
			"广元":{"provice_code":"51","city_code":"6610"},
			"遂宁":{"provice_code":"51","city_code":"6620"},
			"内江":{"provice_code":"51","city_code":"6630"},
			"资阳":{"provice_code":"51","city_code":"6636"},
			"乐山":{"provice_code":"51","city_code":"6650"},
			"眉山":{"provice_code":"51","city_code":"6652"},
			"万州":{"provice_code":"50","city_code":"6670"},
			"涪陵":{"provice_code":"50","city_code":"6690"},
			"宜宾":{"provice_code":"51","city_code":"6710"},
			"南充":{"provice_code":"51","city_code":"6730"},
			"广安":{"provice_code":"51","city_code":"6737"},
			"达州":{"provice_code":"51","city_code":"6750"},
			"巴中":{"provice_code":"51","city_code":"6758"},
			"雅安":{"provice_code":"51","city_code":"6770"},
			"阿坝":{"provice_code":"51","city_code":"6790"},
			"甘孜":{"provice_code":"51","city_code":"6810"},
			"凉山":{"provice_code":"51","city_code":"6840"},
			"黔江":{"provice_code":"50","city_code":"6870"},
			"贵阳":{"provice_code":"52","city_code":"7010"},
			"六盘水":{"provice_code":"52","city_code":"7020"},
			"遵义":{"provice_code":"52","city_code":"7030"},
			"铜仁":{"provice_code":"52","city_code":"7050"},
			"黔西南州":{"provice_code":"52","city_code":"7070"},
			"毕节":{"provice_code":"52","city_code":"7090"},
			"安顺":{"provice_code":"52","city_code":"7110"},
			"黔东南州":{"provice_code":"52","city_code":"7130"},
			"黔南州":{"provice_code":"52","city_code":"7150"},
			"昆明":{"provice_code":"53","city_code":"7310"},
			"昭通":{"provice_code":"53","city_code":"7340"},
			"曲靖":{"provice_code":"53","city_code":"7360"},
			"楚雄":{"provice_code":"53","city_code":"7380"},
			"玉溪":{"provice_code":"53","city_code":"7410"},
			"红河":{"provice_code":"53","city_code":"7430"},
			"文山":{"provice_code":"53","city_code":"7450"},
			"思茅":{"provice_code":"53","city_code":"7470"},
			"西双版纳":{"provice_code":"53","city_code":"7490"},
			"大理":{"provice_code":"53","city_code":"7510"},
			"保山":{"provice_code":"53","city_code":"7530"},
			"德宏":{"provice_code":"53","city_code":"7540"},
			"丽江":{"provice_code":"53","city_code":"7550"},
			"怒江":{"provice_code":"53","city_code":"7560"},
			"迪庆":{"provice_code":"53","city_code":"7570"},
			"临沧":{"provice_code":"53","city_code":"7580"},
			"拉萨":{"provice_code":"54","city_code":"7700"},
			"昌都":{"provice_code":"54","city_code":"7720"},
			"山南":{"provice_code":"54","city_code":"7740"},
			"日喀则":{"provice_code":"54","city_code":"7760"},
			"那曲":{"provice_code":"54","city_code":"7790"},
			"阿里":{"provice_code":"54","city_code":"7811"},
			"林芝":{"provice_code":"54","city_code":"7830"},
			"西安":{"provice_code":"61","city_code":"7910"},
			"铜川":{"provice_code":"61","city_code":"7920"},
			"宝鸡":{"provice_code":"61","city_code":"7930"},
			"咸阳":{"provice_code":"61","city_code":"7950"},
			"渭南":{"provice_code":"61","city_code":"7970"},
			"汉中":{"provice_code":"61","city_code":"7990"},
			"安康":{"provice_code":"61","city_code":"8010"},
			"商洛":{"provice_code":"61","city_code":"8030"},
			"延安":{"provice_code":"61","city_code":"8040"},
			"榆林":{"provice_code":"61","city_code":"8060"},
			"兰州":{"provice_code":"62","city_code":"8210"},
			"嘉峪关":{"provice_code":"62","city_code":"8220"},
			"金昌":{"provice_code":"62","city_code":"8230"},
			"白银":{"provice_code":"62","city_code":"8240"},
			"天水":{"provice_code":"62","city_code":"8250"},
			"酒泉":{"provice_code":"62","city_code":"8260"},
			"张掖":{"provice_code":"62","city_code":"8270"},
			"武威":{"provice_code":"62","city_code":"8280"},
			"定西":{"provice_code":"62","city_code":"8290"},
			"陇南":{"provice_code":"62","city_code":"8310"},
			"平凉":{"provice_code":"62","city_code":"8330"},
			"庆阳":{"provice_code":"62","city_code":"8340"},
			"临夏":{"provice_code":"62","city_code":"8360"},
			"甘南":{"provice_code":"62","city_code":"8380"},
			"西宁":{"provice_code":"63","city_code":"8510"},
			"海东":{"provice_code":"63","city_code":"8520"},
			"海北":{"provice_code":"63","city_code":"8540"},
			"黄南":{"provice_code":"63","city_code":"8550"},
			"海南":{"provice_code":"63","city_code":"8560"},
			"果洛":{"provice_code":"63","city_code":"8570"},
			"玉树":{"provice_code":"63","city_code":"8580"},
			"海西":{"provice_code":"63","city_code":"8590"},
			"银川":{"provice_code":"64","city_code":"8710"},
			"石嘴山":{"provice_code":"64","city_code":"8720"},
			"吴忠":{"provice_code":"64","city_code":"8731"},
			"中卫":{"provice_code":"64","city_code":"8733"},
			"固原":{"provice_code":"64","city_code":"8741"},
			"乌鲁木齐":{"provice_code":"65","city_code":"8810"},
			"克拉玛依":{"provice_code":"65","city_code":"8820"},
			"吐鲁番":{"provice_code":"65","city_code":"8830"},
			"哈密":{"provice_code":"65","city_code":"8840"},
			"阿勒泰":{"provice_code":"65","city_code":"8844"},
			"昌吉":{"provice_code":"65","city_code":"8850"},
			"博尔塔拉":{"provice_code":"65","city_code":"8870"},
			"巴音郭楞":{"provice_code":"65","city_code":"8880"},
			"阿克苏":{"provice_code":"65","city_code":"8910"},
			"克孜勒苏":{"provice_code":"65","city_code":"8930"},
			"喀什":{"provice_code":"65","city_code":"8940"},
			"和田":{"provice_code":"65","city_code":"8960"},
			"伊犁":{"provice_code":"65","city_code":"8980"},
			"塔城":{"provice_code":"65","city_code":"9010"},
			"阿勒泰":{"provice_code":"65","city_code":"9020"},
			"石河子":{"provice_code":"65","city_code":"9028"}
			}';
		$cityInfo = json_decode($province_city_list, true);
		return $cityInfo;
	}
}
