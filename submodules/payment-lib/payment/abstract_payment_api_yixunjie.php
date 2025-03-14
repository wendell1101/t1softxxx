<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * yixunjie 易迅捷 / GUANSHIN 广鑫
 *
 * * YIXUNJIE_PAYMENT_API, ID: 5080
 * * YIXUNJIE_2_PAYMENT_API, ID: 5244
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://47.244.47.216/orderpay.do
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_yixunjie extends Abstract_payment_api {

    const TRADETYPE_ONLINEBANK = '41'; //网关支付

    const RETURN_SUCCESS_CODE = 'success';
    const RETURN_FAILED_CODE = 'FAIL';
    const REQUEST_SUCCESS = '0';
    const PAY_RESULT_SUCCESS = '0';

    # Implement these for specific pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        $params['userId']    = $this->getSystemInfo("account");
        $params['orderNo']   = $order->secure_id;
        $params['payAmt']    = $this->convertAmountToCurrency($amount);
        $params['goodsName'] = $order->secure_id;
        $params['returnUrl'] = $this->getReturnUrl($orderId);
        $params['notifyUrl'] = $this->getNotifyUrl($orderId);

        $this->configParams($params, $order->direct_pay_extra_info);
        $params['sign'] = $this->sign($params);
        $this->CI->utils->debug_log("=====================yixunjie generatePaymentUrlForm", $params);

        return $this->processPaymentUrlForm($params);
    }

    # Submit POST form
    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['orderNo']);
        $decode_data = json_decode($response, true);

        $this->CI->utils->debug_log('=====================yixunjie processPaymentUrlFormPost received response', $response);
        $this->CI->utils->debug_log('=====================yixunjie processPaymentUrlFormPost response json to array', $decode_data);

        if(!empty($decode_data['payUrl']) && ($decode_data['retCode'] == self::REQUEST_SUCCESS)) {
            echo $decode_data['payUrl'];
        }else {
            $msg = lang('Invalidte API response');
            if(!empty($decode_data['retMsg'])) {
                $msg = $decode_data['retMsg'];
            }
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $msg
            );
        }
    }

    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    # $source can be 'server' or 'browser'
    private function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================yixunjie callbackFrom $source params", $params);

        if($source == 'server' ){
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log("=====================yixunjie raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data,true);
                $this->CI->utils->debug_log("=====================yixunjie json_decode params", $params);
            }
            if (!$order || !$this->checkCallbackOrder($order, $params, $processed)) {
                return $result;
            }
        }

        # Update order payment status and balance
        $success = true;

        # Update player balance based on order status
        # if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
        $orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
        if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
            $this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
            if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
                $this->CI->sale_order->setStatusToSettled($orderId);
            }
        } else {
            # update player balance
            $this->CI->sale_order->updateExternalInfo($order->id,
                $params['orderNo'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($success) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
        } else {
            $result['message'] = $processed ? self::RETURN_SUCCESS_CODE : self::RETURN_FAILED_CODE;
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields, &$processed = false) {

        $requiredFields = array('retCode', 'userId','orderNo','transNo','payAmt','sign');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================yixunjie missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=======================yixunjie checkCallbackOrder verify signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['retCode'] != self::PAY_RESULT_SUCCESS) {
            $payStatus = $fields['retCode'];
            $this->writePaymentErrorLog("=====================yixunjie Payment was not successful, payStatus is [$payStatus]", $fields);
            return false;
        }

        if ( $this->convertAmountToCurrency($order->amount) != floatval( $fields['payAmt'] )
        ) {
            $this->writePaymentErrorLog("=====================yixunjie Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['orderNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("========================yixunjie checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    public function getBankListInfoFallback() {
        return array(
            array('label' => '中国工商银行', 'value' => '102'),
            array('label' => '中国农业银行', 'value' => '103'),
            array('label' => '中国银行', 'value' => '104'),
            array('label' => '中国建设银行', 'value' => '105'),
            array('label' => '交通银行', 'value' => '301'),
            array('label' => '中信银行', 'value' => '302'),
            array('label' => '中国光大银行', 'value' => '303'),
            array('label' => '华夏银行', 'value' => '304'),
            array('label' => '中国民生银行', 'value' => '305'),
            array('label' => '广发银行', 'value' => '306'),
            array('label' => '平安银行', 'value' => '307'),
            array('label' => '招商银行', 'value' => '308'),
            array('label' => '兴业银行', 'value' => '309'),
            array('label' => '上海浦东发展银行', 'value' => '310'),
            array('label' => '北京银行', 'value' => '313'),
            array('label' => '恒丰银行', 'value' => '315'),
            array('label' => '浙商银行', 'value' => '316'),
            array('label' => '渤海银行', 'value' => '318'),
            array('label' => '徽商银行', 'value' => '319'),
            array('label' => '重庆三峡银行', 'value' => '321'),
            array('label' => '上海农村商业银行', 'value' => '322'),
            array('label' => '上海银行', 'value' => '401'),
            array('label' => '农村信用社（含北京农村商业银行）', 'value' => '402'),
            array('label' => '中国邮政储蓄银行', 'value' => '403'),
            array('label' => '宁波银行', 'value' => '408'),
            array('label' => '杭州商业银行', 'value' => '423'),
            array('label' => '潍坊银行', 'value' => '458'),
            array('label' => '东亚银行', 'value' => '502'),
            array('label' => '南洋商业银行', 'value' => '503'),
            array('label' => '恒生银行', 'value' => '504'),
            array('label' => '集友银行', 'value' => '506'),
            array('label' => '大众银行', 'value' => '508'),
            array('label' => '永亨银行', 'value' => '510'),
            array('label' => '上海商业银行', 'value' => '511'),
            array('label' => '永隆银行', 'value' => '512'),
            array('label' => '中信嘉华银行', 'value' => '514'),
            array('label' => '宜宾市商业银行', 'value' => '701'),
            array('label' => '南京银行', 'value' => '702'),
            array('label' => '青岛银行', 'value' => '703'),
            array('label' => '天津银行', 'value' => '704'),
            array('label' => '河北银行', 'value' => '705'),
            array('label' => '广州银行', 'value' => '710'),
            array('label' => '成都银行', 'value' => '715'),
            array('label' => '寺库', 'value' => '716'),
            array('label' => '东莞银行', 'value' => '717'),
            array('label' => '齐鲁银行', 'value' => '718'),
            array('label' => '常熟农村商业银行', 'value' => '719'),
            array('label' => '保定银行', 'value' => '720'),
            array('label' => '上海华瑞银行', 'value' => '721'),
            array('label' => '九江银行', 'value' => '722'),
            array('label' => '江西省农村信用社', 'value' => '800'),
            array('label' => '山东省农村信用社', 'value' => '801'),
            array('label' => '广东省农村信用社', 'value' => '802'),
            array('label' => '湖北省农村信用社', 'value' => '803'),
            array('label' => '河南省农村信用社', 'value' => '804'),
            array('label' => '辽宁省农村信用社', 'value' => '805'),
            array('label' => '吉林省农村信用社', 'value' => '806'),
            array('label' => '黑龙江省农村信用社', 'value' => '807'),
            array('label' => '浙江省农村信用社', 'value' => '808'),
            array('label' => '江苏省农村信用社', 'value' => '809'),
            array('label' => '福建省农村信用社', 'value' => '810'),
            array('label' => '安徽省农村信用社', 'value' => '811'),
            array('label' => '湖南省农村信用社', 'value' => '812'),
            array('label' => '河北省农村信用社', 'value' => '813'),
            array('label' => '甘肃省农村信用社', 'value' => '814'),
            array('label' => '山西省农村信用社', 'value' => '815'),
            array('label' => '陕西省农村信用社', 'value' => '816'),
            array('label' => '云南省农村信用社', 'value' => '817'),
            array('label' => '贵州省农村信用社', 'value' => '818'),
            array('label' => '海南省农村信用社', 'value' => '819'),
            array('label' => '内蒙古自治区农村信用社', 'value' => '820'),
            array('label' => '新疆自治区农村信用社', 'value' => '821'),
            array('label' => '四川省农村信用社', 'value' => '822'),
            array('label' => '重庆市农村信用社', 'value' => '823'),
        );
    }

    # -- Private functions --
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

    # -- private helper functions --
    public function sign($params) {
        unset($params['bankId']);
        unset($params['goodsName']);
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
  
        return $sign;
    }

    public function verifySignature($data) {
        $callback_sign = $data['sign'];
        $signStr = $this->createSignStr($data);
        $sign = md5($signStr);
  
        return (strcasecmp($sign, $callback_sign) !== 0) ? false : true;
    }

    private function createSignStr($params) {
        ksort($params);
        $signStr='';
        foreach ($params as $key => $value) {

            if(is_null($value) || $key == 'sign'){
                continue;
            }
            $signStr .= $key."=".$value."&";
        }
        $signStr .= 'key='. $this->getSystemInfo('key');
        return $signStr;
    }
}
