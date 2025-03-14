<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yoursite.php';
/**
 * YOURSITE
 *
 * * YOURSITE_WITHDRAWAL_PAYMENT_API, ID: 5844
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://pay.payment-connect.com/api/payment
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * Notes:
 * 1 This one uses HTTP Basic authentication, see Abstract_payment_api_yoursite
 * 2 This withdrawal API uses no callback
 * 3 Required fields (not documented):
 *     phone
 *     IfscCode (india-only bank identifier, precise to each branch)
 * 4 Minimum amount = 1000
 *
 * @category Payment
 * @copyright 2022 tot
 */
class Payment_api_yoursite_withdrawal extends Abstract_payment_api_yoursite {

    protected $path_wd_status    = 'api/withdrawrequestStatus';
    protected $path_wd_request   = 'api/withdrawrequest';

    const ERRORCODE_WITHDRAWAL_SUCCESS  = 1;
    const ERRORCODE_WITHDRAWAL_FAILED   = 2;

    const STATUSCODE_WITHDRAWAL_CHECK_PENDING       = 'PD';
    const STATUSCODE_WITHDRAWAL_CHECK_APPROVED      = 'AP';
    const STATUSCODE_WITHDRAWAL_CHECK_PROCESSING    = 'PR';
    const STATUSCODE_WITHDRAWAL_CHECK_REJECTED      = 'RJ';

    public function getPlatformCode() {
        return YOURSITE_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'yoursite_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params){}

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {

        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $this->CI->load->library([ 'ifsc_razorpay_lib' ]);
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        $bank_name = $this->findBankName($bank);

        // $bankInfo = $this->getBankInfo();
        // $bank_ifsc = $bankInfo[$bank]['ifsc'];
        $bank_ifsc = $order['bankBranch'];
        $bank_branch = $this->CI->ifsc_razorpay_lib->get_branch_bank($bank_ifsc);

        $player = $this->CI->player_model->getPlayerDetailArrayById($order['playerId']);

        $this->CI->utils->debug_log(__METHOD__, 'YOURSITE_withdrawal basic creds', [ 'accNum' => $accNum, 'name' => $name, 'bank' => $bank, 'bank_name' => $bank_name, 'bank_ifsc' => $bank_ifsc, 'bank_branch' => $bank_branch ]);

        $params = [
            "requestid"         => $transId ,
            "amount"            => $this->convertAmountToCurrency($amount) ,
            "accountnumber"     => $order['bankAccountNumber'] ,
            "bankname"          => $bank_name ,
            // "branchname"        => $order['bankBranch'] ,
            "branchname"        => $bank_branch ,
            "accountholdername" => $order['bankAccountFullName'] ,
            "IfscCode"          => $bank_ifsc ,
            "phone"             => $player['contactNumber']
        ];

        // $params['sign'] = $this->sign($params);

        $this->CI->utils->debug_log(__METHOD__, 'YOURSITE_withdrawal getWithdrawParams params', $params);
        return $params;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log(__METHOD__, $result);
            return $result;
        }

        // $bankInfo = $this->getBankInfo();
        // if(!array_key_exists($bank, $bankInfo)) {
        //     $this->utils->error_log(__METHOD__, "withdrawal bank not supported", [ 'bankTypeId' => $bank ]);
        //     return array('success' => false, 'message' => 'withdrawal bank not supported');
        // }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);

        if (empty($params['IfscCode'])) {
            return [
                'success' => false ,
                'message' => 'IFSC not set, please set IFSC code of your withdrawal account'
            ];
        }

        if (empty($params['branchname'])) {
            return [
                'success' => false ,
                'message' => 'Cannot find the bank branch corresponding to account IFSC code'
            ];
        }

        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, 'as_json', $params['requestid']);
        $this->CI->utils->debug_log(__METHOD__, 'params submit response', $response);

        $result = $this->decodeResult($response);

        $this->CI->utils->debug_log(__METHOD__, 'decoded result', $result);

        return $result;

    }

    public function decodeResult($resp, $queryAPI = false) {
        $result = json_decode($resp, true);
        $this->utils->debug_log(__METHOD__, "YOURSITE_withdrawal json_decode result", $result);

        $expected_fields = [ 'Result', 'ErrorMessage', 'ErrorCode', 'Id' ];

        foreach ($expected_fields as $ef) {
            // if (!isset($result[$ef])) {
            if (!array_key_exists($ef, $result)) {
                return [
                    'success'   => false ,
                    'message'   => sprintf('Invalid API response, missing field: %s', $ef)
                ];
            }
        }

        if ($result['ErrorCode'] == self::ERRORCODE_WITHDRAWAL_SUCCESS) {
            return [
                'success' => true ,
                'message' => sprintf('YOURSITE withdrawal successful, transaction ID: %s', $result['Id'])
            ];
        }
        else {
            return [
                'success'   => false ,
                'message'   => sprintf("Result: %s, Error: %s (%d)", $result['Result'], $result['ErrorMessage'], $result['ErrorCode'] )
            ];
        }

    }

    public function checkWithdrawStatus($transId) {
        $this->CI->load->model(array('wallet_model'));

        $params = [ 'requestid' => $transId ];

        $this->CI->utils->debug_log(__METHOD__, 'YOURSITE_withdrawal checkWithdrawStatus params: ', $params);

        $url = $this->getCheckWithdrawStatusUrl();
        $this->CI->utils->debug_log(__METHOD__, 'YOURSITE_withdrawal checkWithdrawStatus url: ', $url );

        // $response = $this->submitGetForm($url, $param);
        $response = $this->submitPostForm($url, $params, 'as_json', $transId);

        $this->CI->utils->debug_log(__METHOD__, 'YOURSITE_withdrawal checkWithdrawStatus result: ', $response );

        $result = $this->parseWithdrawalCheckResult($response, true);

        if (isset($result['action'])) {
            // Reject withdrawal order
            if ($result['action'] == 'reject') {
                $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $result['message']);
            }
            unset($result['action']);
        }

        return $result;
    }

    public function parseWithdrawalCheckResult($result_str, $queryAPI = false) {
        $res = json_decode($result_str, 'as_array');
        $this->utils->debug_log(__METHOD__, "YOURSITE_withdrawal withdrawal check res", $res);

        if ( !array_key_exists('StatusName', $res) || !array_key_exists('StatusCode', $res) ) {
            return [
                'success' => false ,
                'message' => 'malformed API result'
            ];
        }
        else {
            if ($res['StatusCode'] == self::STATUSCODE_WITHDRAWAL_CHECK_APPROVED) {
                // Return success, approve order
                return [
                    'success'   => true ,
                    'message'   => 'YOURSITE_withdrawal withdrawal check result: APPROVED'
                ];
            }
            else if ($res['StatusCode'] == self::STATUSCODE_WITHDRAWAL_CHECK_REJECTED) {
                // Return failed and additional action to reject order
                 return [
                    'success'   => false ,
                    'message'   => $mesg ,
                    'action'    => 'reject'
                ];
            }
            else {
                // Return failed, no other action taken (stay in processing status)
                $mesg = sprintf('YOURSITE_withdrawal withdrawal check results: Status = %s (%s), ErrorCode = %d (%s)', $res['StatusName'], $res['StatusCode'], $res['ErrorCode'], $res['ErrorMessage']);
                return [
                    'success'   => false ,
                    'message'   => $mesg
                ];
            }
        }

    }

    protected function findBankName($bank_id) {
        $bank_row = $this->CI->banktype->getBankTypeById($bank_id);
        $bank_name = lang($bank_row->bankName);

        return $bank_name;
    }

    protected function getCheckWithdrawStatusUrl() {
        $url = $this->getSystemInfo('url');
        $uparts = parse_url($url);
        $url_wd_status = "{$uparts['scheme']}://{$uparts['host']}/{$this->path_wd_status}";

        return $url_wd_status;
    }

    public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("withdrawal_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
                if(isset($bankInfoItem['name'])){
                    $bankInfo[$system_bank_type_id]['name'] = $bankInfoItem['name'];
                }
                if(isset($bankInfoItem['ifsc'])){
                    $bankInfo[$system_bank_type_id]['ifsc'] = $bankInfoItem['ifsc'];
                }
            }
            $this->utils->debug_log(__METHOD__, "YOURSITE_withdrawal bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = [ ];

            $this->utils->debug_log(__METHOD__, "YOURSITE_withdrawal WARNING: no bankInfo available");
        }
        return $bankInfo;
    }
}