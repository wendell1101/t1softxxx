<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yafu.php';

/**
 * Yafu 雅付代付
 * https://www.yafupay.com/
 *
 * YAFU_WECHAT_PAYMENT_API, ID: 95
 *
 *
 * Required Fields:
 *
 * * URL
 * * Key: The user key assigned by Yafu
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yafu_withdrawal extends Abstract_payment_api_yafu {
    const YAFU_WITHDRAWAL_API_VERSION = '3.0';

	/**
	 * detail: get the platform code from the constant
	 *
	 * @return string
	 */
	public function getPlatformCode() {
		return YAFU_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yafu_withdrawal';
	}

	public function getPayType() {
		return NULL;
	}

    public function getYafuWithdrawalBankNo($bank_type_id){
        $bank_type_id_mapping = $this->getSystemInfo('bank_no_mapping');

        if(!is_array($bank_type_id_mapping)){
            return NULL;
        }

        return (isset($bank_type_id_mapping[$bank_type_id])) ? $bank_type_id_mapping[$bank_type_id] : NULL;
    }

    public function signYafuWithdrawalParams($params){
        if(isset($params['sign'])){
            unset($params);
        }

        ksort($params);

        $sign_str = '';
        foreach($params as $key => $value){
            if(empty($value)) continue;

            $sign_str .= $key . '=' . $value . '&';
        }
        $sign_str .= 'key=' . $this->getSystemInfo('secret');

        $params['sign'] = strtoupper(md5($sign_str));

        return $params;
    }

    public function verifyYafuWithdrawalSign($params){
        if(!isset($params['sign'])){
            return FALSE;
        }

        $sign = $params['sign'];
        unset($params['sign']);

        $params = $this->signYafuWithdrawalParams($params);

        return (strtoupper($params['sign']) === strtoupper($sign)) ? TRUE : FALSE;
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId){
		$this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        $province = '无';
        $city = '无';
        $bankBranch = '无';
        $bankSubBranch = '无';
		if(!empty($playerBankDetails)){
			$province = (empty($playerBankDetails['province'])) ? $province : $playerBankDetails['province'];
			$city = (empty($playerBankDetails['city'])) ? $city : $playerBankDetails['city'];
			$bankBranch = (empty($playerBankDetails['branch'])) ? $bankBranch : $playerBankDetails['branch'];
			$bankSubBranch = (empty($playerBankDetails['branch'])) ? $bankSubBranch : $playerBankDetails['branch'];
		}

		$fee = $this->getSystemInfo('fee');
        $fee = (empty($fee)) ? '5.0' : (float)$fee;

		$params = [];
        $params['version'] = static::YAFU_WITHDRAWAL_API_VERSION;
        $params['consumerNo'] = $this->getSystemInfo('account');
        $params['merOrderNo'] = $params['consumerNo'] . '_' . $transId;
        $params['transAmt'] = $this->convertAmountToCurrency($amount + $fee);
        $params['accountName'] = $name;
        $params['accountNo'] = $accNum;
        $params['acountBankNo'] = $this->getYafuWithdrawalBankNo($bank);
        $params['accountCountry'] = $province;
        $params['accountCity'] = $city;

		if(empty($params['accountNo'] )) {
			$this->utils->error_log("========================yafu withdrawal bank whose bankTypeId=[$bank] is not supported by yafu withdrawal");
            return [
                'success' => false,
                'message' => 'Unknown bank no'
            ];
		}

        $params = $this->signYafuWithdrawalParams($params);

        return $params;
    }

    public function getWithdrawUrl(){
        return $this->getSystemInfo('url') . '/yfpay/cs/TX0001.ac';
    }

    public function decodeResult($result){
        if(empty($result)){
			$this->utils->error_log("========================yafu withdrawal api unknown response", $result);
            return [
                'success' => FALSE,
                'message' => 'Unknown response data'
            ];
        }

		$json_data = json_decode($result, true);
        if(!is_array($json_data) || !isset($json_data['code'])){
			$this->utils->error_log("========================yafu withdrawal api unknown response", $result);
            return [
                'success' => FALSE,
                'message' => 'Unknown response data'
            ];
        }

        if(0 !== (int)$json_data['code']){
			$this->utils->error_log("========================yafu withdrawal api response failed", $json_data);
            return [
                'success' => FALSE,
                'message' => (isset($json_data['msg'])) ? sprintf('Code: %s, Msg: %s', $json_data['code'], $json_data['msg']) : 'Yafu withdrawal failed'
            ];
        }

        if(!isset($json_data['consumerNo']) || !isset($json_data['merOrderNo']) || !isset($json_data['orderNo']) || !isset($json_data['sign'])){
			$this->utils->error_log("========================yafu withdrawal api response lost the necessary info.", $json_data);
            return [
                'success' => FALSE,
                'message' => 'Lost the necessary info'
            ];
        }

        if(!$this->verifyYafuWithdrawalSign($json_data)){
			$this->utils->error_log("========================yafu withdrawal api response sign verify failed.", $json_data, $this->signYafuWithdrawalParams($json_data));
            return [
                'success' => FALSE,
                'message' => 'Sign verify failed'
            ];
        }

        return [
            'success' => TRUE,
            'message' => (isset($json_data['msg'])) ? $json_data['msg'] : 'Yafu withdrawal successful'
        ];
    }

	public function checkWithdrawStatus($transId) {
		$this->CI->load->model(array('wallet_model'));
		$walletaccount = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

		$dateTimeString = $walletaccount['dwDateTime'];
		$datetime = new DateTime($dateTimeString);

		# ---- First add bank card entry ----
		$params = array();
        $params['version'] = static::YAFU_WITHDRAWAL_API_VERSION;
        $params['consumerNo'] = $this->getSystemInfo("account");
        $params['merOrderNo'] = $params['consumerNo'] . '_' . $transId;
		$params['batchDate'] = $datetime->format('Ymd');
		$params['batchNo'] = 'batch' . $transId;
		$params['batchVersion'] = "00";
		$params['charset'] = "utf8";
		$params['merchantId'] = $this->getSystemInfo("account");
        $params = $this->signYafuWithdrawalParams($params);


        $response = $this->submitGetForm($this->getSystemInfo('url') . '/yfpay/cs/TX0002.ac', $params);

		return $this->decodeYafuCheckWithdrawStatusResult($response);
	}

    public function decodeYafuCheckWithdrawStatusResult($response){
        if(empty($response)){
            $this->CI->utils->debug_log('======================================yafu checkWithdrawStatus unknown result: ', $response);
            return [
                'success' => FALSE,
                'message' => 'Unknown response data'
            ];
        }

        $json_data = json_decode($response, TRUE);
        if(!is_array($json_data) || !isset($json_data['code'])){
            $this->CI->utils->debug_log('======================================yafu checkWithdrawStatus invalid result: ', $response);
            return [
                'success' => FALSE,
                'message' => 'Unknown response data'
            ];
        }

        if(0 !== (int)$json_data['code']){
			$this->utils->error_log("========================yafu checkWithdrawStatus response failed", $json_data);
            return [
                'success' => FALSE,
                'message' => (isset($json_data['msg'])) ? sprintf('Code: %s, Msg: %s', $json_data['code'], $json_data['msg']) : 'Yafu checkWithdrawStatus failed'
            ];
        }

        if(!isset($json_data['orderStatus']) || !isset($json_data['sign'])){
			$this->utils->error_log("========================yafu checkWithdrawStatus response lost the necessary info.", $json_data);
            return [
                'success' => FALSE,
                'message' => 'Lost the necessary info'
            ];
        }

        if(isset($json_data['transAmt'])){
            $json_data['transAmt'] = $this->convertAmountToCurrency($json_data['transAmt']);
        }

        if(isset($json_data['feeAmt'])){
            $json_data['feeAmt'] = $this->convertAmountToCurrency($json_data['feeAmt']);
        }

        if(isset($json_data['settleAmt'])){
            $json_data['settleAmt'] = $this->convertAmountToCurrency($json_data['settleAmt']);
        }

        if(isset($json_data['transTime'])){
            $json_data['transTime'] = $this->convertTransTimeForSign($json_data['transTime']);
        }

        if(!$this->verifyYafuWithdrawalSign($json_data)){
			$this->utils->error_log("========================yafu checkWithdrawStatus response sign verify failed.", $json_data, $this->signYafuWithdrawalParams($json_data));
            return [
                'success' => FALSE,
                'message' => 'Sign verify failed'
            ];
        }

        $success = FALSE;
        $message = '';
        switch((int)$json_data['orderStatus']){
            case 1:
                $success = TRUE;
                $message = 'Yafi 代付 提现成功。（Withdrawal successful）';
                break;
            case 2:
                $success = FALSE;
                $message = 'Yafi 代付 已取消。（Cancel Order）';
                break;
            case 3:
                $success = FALSE;
                $message = 'Yafi 代付 提现失败。（Withdrawal failed）';
                break;
            case 4:
                $success = FALSE;
                $message = 'Yafi 代付 提现处理中。（Withdrawal Processing）';
                break;
            case 5:
                $success = FALSE;
                $message = 'Yafi 代付 提现部份成功。（Withdrawal Part of the success）';
                break;
            case 0:
            default:
                $success = FALSE;
                $message = 'Yafi 代付 未处理 或 无效订单。（Not processed or Invalid Order）';
                break;
        }

        return [
            'success' => $success,
            'message' => $message
        ];
    }

    public function convertTransTimeForSign($time){
        return date('Y-m-d H:i:s', $time / 1000) . '.0';
    }
}
