<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * VNPAY
 *
 * VNPAY_PAYMENT_API, 5814
 * VNPAY_BANKCARD_PAYMENT_API, 5815
 * VNPAY_ZALO_PAYMENT_API, 5816
 * VNPAY_MOMO_PAYMENT_API, 5817
 * VNPAY_WITHDRAWAL_PAYMENT_API, 5818
 *
 * Required Fields:
 * * URL
 * * Key
 * * uid (merchant ID)
 *
 * Field Values:
 * * URL        : https://manager.vnpay.center/pay
 * * Key        : ## Live key ##
 * * uid        : ## merchant ID ##
 *
 * @category Payment
 * @copyright 2022 tot
 */
abstract class Abstract_payment_api_vnpay extends Abstract_payment_api {
    const CHANNEL_DEPOSIT_ONLINEBANK    = 907;
    const CHANNEL_DEPOSIT_BANKCARD      = 908;
    const CHANNEL_DEPOSIT_ZALO          = 921;
    const CHANNEL_DEPOSIT_MOMO          = 923;
    const CHANNEL_WITHDRAWAL_DEFAULT    = 712;

    const RETURN_SUCCESS_CODE = 'success';

    const STATUS_SUCCESSFUL = 10000;

    protected $status_mesgs = [
        '20001' => '发送模式错误' ,
        '20002' => 'header 错误' ,
        '20003' => '没有取得参数' ,
        '20004' => '参数异常' ,
        '20005' => '没有取得商号' ,
        '20041' => '没有取得 sign' ,
        '20042' => 'sign不相符' ,
        '20091' => '币别禁用' ,
        '20092' => '权限禁用' ,
        '20093' => '商户禁用' ,
        '20094' => '交易类型禁用' ,
        '20095' => '平台API线禁用' ,
        '20096' => '商户API线禁用' ,
        '20097' => '商户单点API禁用' ,
        '21011' => '没有取得商户平台订单号' ,
        '21012' => '商户平台订单号长度小于 1 字元' ,
        '21013' => '商户平台订单号长度大于 20 字元' ,
        '21014' => '商户平台订单号重复' ,
        '21016' => '没有取得支付类型' ,
        '21017' => '支付类型不是数字' ,
        '21018' => '支付类型不存在' ,
        '21019' => '支付类型未启用' ,
        '21020' => '支付类型错误' ,
        '21021' => '没有取得回调连结' ,
        '21022' => '回调连结长度大于 100 字元' ,
        '21026' => '没有取得跳转连结' ,
        '21027' => '跳转连结长度大于 100 字元' ,
        '21031' => '没有取得金额' ,
        '21032' => '金额不是数字' ,
        '21033' => '金额小于单笔最小量' ,
        '21034' => '金额大于单笔最大量' ,
        '21035' => '金额大于单日交易最大量' ,
        '21036' => '没有取得客户端IP' ,
        '21037' => '客户端IP长度大于 40 字元' ,
        '21041' => '没有取得时间戳记' ,
        '21042' => '时间戳记不是数字' ,
        '21046' => '没有取得附言' ,
        '21047' => '附言长度大于 100 字元' ,
        '21071' => '起始时间长度大于 19 字元' ,
        '21072' => '起始时间格式错误' ,
        '21076' => '截止时间长度大于 19 字元' ,
        '21077' => '截止时间格式错误' ,
        '21081' => '页数不是数字' ,
        '21101' => '没有取得收款人开户姓名' ,
        '21102' => '收款人开户姓名长度小于 1 字元' ,
        '21103' => '收款人开户姓名长度大于 50 字元' ,
        '21106' => '没有取得收款人银行帐号' ,
        '21107' => '收款人银行帐号长度小于 1 字元' ,
        '21108' => '收款人银行帐号长度大于 20 字元' ,
        '21111' => '没有取得银行编号' ,
        '21112' => '银行编号不是数字' ,
        '21113' => '银行编号不存在' ,
        '21114' => '银行编号未启用' ,
        '21115' => '银行编号错误' ,
        '21116' => '没有取得开户支行' ,
        '21117' => '开户支行长度小于 1 字元' ,
        '21118' => '开户支行长度大于 20 字元' ,
        '21121' => '没有取得开户行所在省份' ,
        '21122' => '开户行所在省份长度小于 1 字元' ,
        '21123' => '开户行所在省份长度大于 20 字元' ,
        '21126' => '没有取得开户行所在城市' ,
        '21127' => '开户行所在城市长度小于 1 字元' ,
        '21128' => '开户行所在城市长度大于 20 字元' ,
        '30001' => '商户不存在' ,
        '30002' => '商户没有开通此交易渠道' ,
        '30003' => '商户没有设定此交易渠道' ,
        '30004' => '商户没有开通此交易类型' ,
        '30011' => '交易单建立失败' ,
        '30020' => '商户余额不足' ,
        '30021' => '平台API没有支援此币别' ,
        '30100' => '支付通道阻塞' ,
        '30901' => '订单失效' ,
        '30906' => '登入失败' ,
        '30907' => '餘額不足' ,
        '30911' => '验证失败' ,
        '30916' => '交易失败' ,
        '30921' => '交易超时' ,
        '90001' => '维护中' ,
        '90091' => 'API IP 跳转过多' ,
        '90092' => 'API IP 在黑名单中' ,
        '90901' => '金流通道回传错误格式' ,
        '90902' => '金流通道没有回传结果' ,
        '90903' => '金流通道回传失败' ,
        '90904' => '金流通道没有回传连结' ,
        '90905' => '金流通道回传连结为空' ,
        '90906' => '金流通道交易失败' ,
        '99999' => '没有此API' ,
    ];


    public function __construct($params = null) {
        parent::__construct($params);
    }

    # Implement these to specify pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        // $params = [
        //     'app_id'            => $this->getSystemInfo('account') ,
        //     'user_id'           => $playerId ,
        //     'customer_order_no' => $order->secure_id ,
        //     'cash'              => $this->convertAmountToCurrency($amount) ,
        //     'callback_url'      => $this->getNotifyUrl($orderId) ,
        //     'timestamp'         => time() ,
        // ];
        $params = [
            'uid'           => $this->getSystemInfo('uid') ,
            'orderid'       => $order->secure_id ,
            'notify_url'    => $this->getNotifyUrl($orderId) ,
            'return_url'    => $this->getReturnUrl($orderId) ,
            'amount'        => $this->convertAmountToCurrency($amount) ,
            'userip'        => $this->getClientIp() ,
            'timestamp'     => gmdate('U') ,
            'custom'        => ''
        ];

        $this->configParams($params, $order->direct_pay_extra_info);

        $params['sign'] = $this->sign($params);

        $this->CI->utils->debug_log(__METHOD__, 'VNPAY generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormRedirect($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['orderid']);
        $this->CI->utils->debug_log(__METHOD__, 'VNPAY processPaymentUrlFormPost response', $response);
        $result = json_decode($response, 'as_array');
        $this->CI->utils->debug_log(__METHOD__, 'VNPAY processPaymentUrlFormPost decoded result', $result);

        if ( !isset($result['status']) || !isset($result['result']) || !isset($result['sign']) ) {
            return [
                'success'   => false ,
                'type'      => self::REDIRECT_TYPE_ERROR ,
                'message'   => lang('Invalid API response')
            ];
        }
        else if (!$this->verify_callback_sign($result)) {
            return [
                'success'   => false ,
                'type'      => self::REDIRECT_TYPE_ERROR ,
                'message'   => lang('Sign does not match')
            ];
        }
        else if ($result['status'] != self::STATUS_SUCCESSFUL) {
            $mesg = isset($this->status_mesgs[$result['status']]) ? $this->status_mesgs[$result['status']] : '(unknown error)';
            return [
                'success'   => false ,
                'type'      => self::REDIRECT_TYPE_ERROR ,
                'message'   => sprintf("Error %d: %s", $result['status'], $mesg)
            ];
        }
        else {
            return [
                'success'   => true ,
                'type'      => self::REDIRECT_TYPE_URL ,
                'url'       => $result['result']['payurl']
            ];
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

    protected function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log(__METHOD__, "VNPAY callbackFrom $source params", $params);

        if ($source == 'server') {
            if (empty($params)) {
                $raw_post_data = file_get_contents('php://input', 'r');
                $this->CI->utils->debug_log(__METHOD__, "VNPAY raw_post_data", $raw_post_data);
                $params = json_decode($raw_post_data, true);
                $this->CI->utils->debug_log(__METHOD__, "VNPAY json_decode params", $params);
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
            $this->CI->sale_order->updateExternalInfo($order->id, $params['orderid'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto browser callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
        } else {
            $result['return_error'] = 'Error';
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    protected function checkCallbackOrder($order, $fields, &$processed = false) {
        // Check required fields
        $requiredFields = [ 'status', 'result', 'sign' ];

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog(__METHOD__, "VNPAY checkCallbackOrder field missing '$f'");
                return false;
            }
        }

        // Check sign
        if (!$this->verify_callback_sign($fields)) {
            $this->writePaymentErrorLog(__METHOD__, 'VNPAY checkCallbackOrder signature Error');
            return false;
        }

        // processed is set to true once the signature verification pass
        $processed = true;

        $cb_result = $fields['result'];
        $cb_status = $fields['status'];

        if ($cb_status != self::RETURN_SUCCESS_CODE) {
            $this->writePaymentErrorLog(__METHOD__, "VNPAY checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        // Check amount (cash)
        if ($cb_result['amount'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog(__METHOD__, 'VNPAY checkCallbackOrder amount mismatch', [ 'expected' => $order->amount, 'returned' => $cb_result['amount'] ], $fields);
            return false;
        }

        // Check order ID (customer_order_no)
        if ($cb_result['orderId'] != $order->secure_id) {
            $this->writePaymentErrorLog(__METHOD__, 'VNPAY checkCallbackOrder order ID mismatch', [ 'expected' => $order->secure_id, 'returned' => $cb_result['orderId'] ], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    protected function sign($params) {
        $plain = $this->sign_plain($params);
        $sign = strtoupper(md5($plain));
        $this->CI->utils->debug_log(__METHOD__, 'VNPAY sign hash', $sign);
        return $sign;
    }

    protected function sign_plain($params) {
        ksort($params);
        $params['key'] = $this->getSystemInfo('key');
        // $plain = http_build_query($params);
        $pairs = [];
        foreach ($params as $key => $val) {
            $pairs[] = "{$key}={$val}";
        }
        $plain = implode('&', $pairs);
        $this->CI->utils->debug_log(__METHOD__, 'VNPAY sign plaintext', $plain);
        return $plain;
    }

    protected function verify_callback_sign($fields) {
        $key = $this->getSystemInfo('key');
        $result = $fields['result'];
        $result_json = json_encode($result);
        $plaintext = "result={$result_json}&status={$fields['status']}&key={$key}";
        $sign_expected = strtoupper(md5($plaintext));

        $match = ($sign_expected == $fields['sign']);

        $this->CI->utils->debug_log(__METHOD__, 'VNPAY sign', [ 'match' => $match, 'sign_expected' => $sign_expected, 'sign' => $fields['sign'], 'plaintext' => $plaintext ]);

        return $match;
    }

    // protected function validate_callback_sign($fields) {
    //     $key = $this->getSystemInfo('key');
    //     $plain = "result={$fields['result']}&status={$fields['status']}&key={$key}";
    //     $sign_expected = strtoupper($md5($plain));

    //     $match = ($sign_expected == $fields['sign']);
    //     return $match;
    // }

    // protected function validateSign($params) {
    //     $sign = $params['data_sign'];
    //     $fields = $params;
    //     unset($fields['data_sign']);
    //     $sign_expected = $this->sign($fields);
    //     $is_match = ($sign == $sign_expected);

    //     return $is_match;
    // }


    # -- Private functions --
    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }
}