<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

class auto_risk_dispatch_withdrawal_lib {

    public function __construct() {
		$this->ci = &get_instance();
		$this->ci->load->model([ 'dispatch_withdrawal_results' ]);
		$this->ci->load->library([ 'utils' ]);

        $this->utils = $this->ci->utils;

	}


    public function getLatestResultsByTransCode($transCode){
        $walletAccountId = null;
        $dwDateTime = null;
        // $this->utils->debug_log('TT-5404.20.transCode', $transCode);
        $result_list = $this->ci->dispatch_withdrawal_results->isExistsInByTransCode($transCode, $walletAccountId, $dwDateTime);

        $latestResults = [];
        $latestResults['result_dw_status'] = null;
        $latestResults['result_dw_status_plain_txt'] = null;
        $latestResults['result_dw_status_code'] = null;
        $latestResults['after_status'] = null;
        $latestResults['definition_results'] = null;
        if( ! empty($result_list[0]['result_dw_status']) ){
            $latestResults['result_dw_status'] = $result_list[0]['result_dw_status'];
            $_result_dw_status = $this->utils->json_decode_handleErr($result_list[0]['result_dw_status'], true);
            if( empty($_result_dw_status) ){
                $latestResults['result_dw_status_plain_txt'] = $result_list[0]['result_dw_status'];
            }else{
                $latestResults['result_dw_status_code'] = $_result_dw_status['code'];
                $_result_dw_status = null; // clear for Allowed memory size exhausted
            }
        }
        if( ! empty($result_list[0]['after_status']) ){
            $latestResults['after_status'] = $result_list[0]['after_status'];
        }
        if( ! empty($result_list[0]['definition_results']) ){
            $latestResults['definition_results'] = $this->utils->json_decode_handleErr( $result_list[0]['definition_results'], true);
        }

        $result_list = null; // clear for Allowed memory size exhausted
// $this->utils->debug_log('TT-5404.45.transCode', $transCode);
        return $latestResults;
    } // EOF getLatestResultsByTransCode

    public function getResultsByTransCode($transCode){
        $walletAccountId = null;
        $dwDateTime = null;
        $text = '';
        $result = $this->ci->dispatch_withdrawal_results->isExistsInByTransCode($transCode, $walletAccountId, $dwDateTime);
        $this->ci->load->model(['wallet_model']);
        $text = $this->ci->wallet_model->getWalletAccountClipboardText($transCode);

        $lastQueueResult = null; // default, the last row of queue_results.
        $queueResultList = $this->ci->dispatch_withdrawal_results->isExistsInQueueResultsByWalletAccountId($walletAccountId, $dwDateTime);
        if( ! empty($queueResultList)){
            $lastQueueResult = $queueResultList[0];
            if( ! empty($queueResult['result']) ){ // result clone and convert to array.
                $lastQueueResult['resultJson'] =  $this->utils->json_decode_handleErr($lastQueueResult['result'], true);
                $token = $lastQueueResult['token'];
            }
        }
        // $queueResult.params is null and $queueResult.status = 3 means that had ran the auto-risk first.
        // $queueResult.params is null and $queueResult.status = 2 means that the auto-risk has not been run yet.
        // $queueResult.params has "old_token" means that had ran the auto-risk one more times

        $isDisplayReRun = false;
        $count = 0;
        if( ! empty($result) ){
            $count = count($result);
        }

        if( empty($count) ){
            $isDisplayReRun = true;
        }

        $return = [];
        $return['transCode'] = $transCode;
        $return['count'] = $count;
        $return['lastQueueResult'] = $lastQueueResult; // for debug
        $return['isDisplayReRun'] = $isDisplayReRun;
        $return['text'] = $text;
        if( ! empty($token) ){
            $return['token'] = $token;
        }
        if( ! empty($walletAccountId) ){
            // $return['walletAccountId'] = $walletAccountId; // for debug
        }
        // if( ! empty($dwDateTime) ){
        //     // $return['dwDateTime'] = $dwDateTime; // for debug
        // }
        return $return;
        // return $this->returnJsonResult( $return );
    }
    /**
     * Get the results Count By transactionCode
     *
     * @param string $transCode The F.K to "walletaccount.transactionCode".
     * @return array the reaults as the followings,
     * - $return['transCode'] = $transCode;
     * - $return['count'] = $count;
     * - $return['lastQueueResult'] = $lastQueueResult; // for debug
     * - $return['isDisplayReRun'] = $isDisplayReRun;
     * - $return['token']
     */
    public function getResultsByTransCodeV2($transCode){
        $walletAccountId = null;
        $dwDateTime = null;
        $result_list = $this->ci->dispatch_withdrawal_results->isExistsInByTransCode($transCode, $walletAccountId, $dwDateTime);


        if( !empty($result_list)){
            $result_list_lite = [];
            /// $result_list_lite convertion
            array_walk($result_list, function($_result, $_key) use ( &$result_list_lite ){
                $result_lite = $_result;
                unset($result_lite['definition_results']);
                $_definition_results =  $this->utils->json_decode_handleErr( $_result['definition_results'], true);
                $result_lite['definition_results'] =  $_definition_results;

                $result_list_lite[] = $result_lite;
            });

        }

        $latest_result = (empty($result_list_lite[0])? null: $result_list_lite[0]);

        $lastQueueResult = null; // default, the last row of queue_results.
        $queueResultList = $this->ci->dispatch_withdrawal_results->isExistsInQueueResultsByWalletAccountId($walletAccountId, $dwDateTime);
        $this->utils->debug_log('OGP-25163.38.result_list_lite.0', (empty($result_list_lite[0])? null: $result_list_lite[0]), 'transCode:', $transCode, 'walletAccountId:', $walletAccountId, 'dwDateTime:', $dwDateTime);
        // $this->utils->debug_log('OGP-25163.38.1.queueResultList[0]', $queueResultList[0], 'transCode:', $transCode, 'walletAccountId:', $walletAccountId, 'dwDateTime:', $dwDateTime);
        $this->utils->debug_log('OGP-25163.39.latest_result', $latest_result, 'transCode:', $transCode, 'walletAccountId:', $walletAccountId, 'dwDateTime:', $dwDateTime);
        $this->utils->debug_log('OGP-25163.57.queueResultList', $queueResultList, 'count(queueResultList)', count($queueResultList), 'transCode:', $transCode, 'walletAccountId:', $walletAccountId, 'dwDateTime:', $dwDateTime);

        if( ! empty($queueResultList)){
            $lastQueueResult = $queueResultList[0];
            $this->utils->debug_log('OGP-25163.61.lastQueueResult', $lastQueueResult, 'queueResultList', $queueResultList, 'count(queueResultList)', count($queueResultList), 'transCode:', $transCode, 'walletAccountId:', $walletAccountId, 'dwDateTime:', $dwDateTime);
            if( ! empty($lastQueueResult['result']) ){ // result clone and convert to array.
                $lastQueueResult['resultJson'] = $this->utils->json_decode_handleErr($lastQueueResult['result'], true);
                $token = $lastQueueResult['token'];
            }
        }



        // $queueResult.params is null and $queueResult.status = 3 means that had ran the auto-risk first.
        // $queueResult.params is null and $queueResult.status = 2 means that the auto-risk has not been run yet.
        // $queueResult.params has "old_token" means that had ran the auto-risk one more times

        $isDisplayReRun = false;
        $count = 0;
        if( ! empty($result_list_lite) ){
            $count = count($result_list_lite);
        }

        if( empty($count) ){
            $isDisplayReRun = true;
        }

        $return = [];
        $return['transCode'] = $transCode; // todo
        $return['count'] = $count; // todo
        $return['latestResult'] = $latest_result;
        $return['lastQueueResult'] = $lastQueueResult; // for debug // todo
        $return['isDisplayReRun'] = $isDisplayReRun; // todo
        if( ! empty($token) ){
            $return['token'] = $token;
        }
        if( ! empty($walletAccountId) ){
            // $return['walletAccountId'] = $walletAccountId; // for debug
        }
        // if( ! empty($dwDateTime) ){
        //     // $return['dwDateTime'] = $dwDateTime; // for debug
        // }
        return $return;
    } // EOF getResultsByTransCode

} // EOF auto_risk_dispatch_withdrawal_lib