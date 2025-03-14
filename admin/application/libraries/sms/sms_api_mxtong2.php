<?php
require_once dirname(__FILE__) . '/abstract_sms_api.php';

/**
 * mxtong2 SMS API implementation
 * http://www.weiwebs.cn/msg/HttpSendSM
 *
 * Config items:
 * * sms_api_mxtong_account
 * * sms_api_mxtong_password
 */
class Sms_api_mxtong2 extends Abstract_sms_api {
    const SUCCESS_CODE = "0";

    protected function signContent($content) {
        return sprintf("【%s】%s", $this->getParam('sign'), $content);
    }

    public function getUrl() {
        return $this->getParam('url') ? $this->getParam('url') : "http://www.weiwebs.cn/msg/HttpSendSM";
    }

    public function getFields($mobile, $content, $dialingCode) {
        if(empty($dialingCode)){
            $dialingCode = $this->getParam('country_code');
        }
        $fields = array(
            'account' => $this->getParam('account'),
            'pswd' => $this->getParam('pswd'),
            'mobile' => $dialingCode.$mobile,
            'msg' => $content,
            'needstatus' => true,
            'resptype' => 'json',
        );

        $this->utils->debug_log("===============mxtong2 SMS fields", $fields);
        return $fields;
    }

    protected function configCurl($handle, $mobile, $content, $dialingCode) {
        $content = $this->signContent($content);
        $fields = $this->getFields($mobile, $content, $dialingCode);
        $fields_string = http_build_query($fields);
        $url = $this->getUrl();
        $this->utils->debug_log("===============mxtong2 SMS url", $url);

        curl_setopt($handle, CURLOPT_URL,$url);
        curl_setopt($handle, CURLOPT_POSTFIELDS,$fields_string);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($handle, CURLOPT_HTTPHEADER, array('Content-Type:application/x-www-form-urlencoded','charset=utf-8'));
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_TIMEOUT, 10);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        $this->setCurlProxyOptions($handle);

        return $fields;
    }

    public function getErrorMsg($returnQueryString) {
        $result = json_decode($returnQueryString, true);
        $message = 'Unknown error!';
        if(isset($result['result'])){
            $message = self::ERROR_MSG[$result['result']];
        }

        $this->utils->error_log("===============mxtong2 return error", $returnQueryString);
        return $message;
    }

    public function isSuccess($returnQueryString) {
        $result = json_decode($returnQueryString, true);
        if( isset($result['result']) && $result['result'] == self::SUCCESS_CODE)
            return true;
        else
            return false;
    }

    protected function getParam($name) {
        return $this->CI->config->item('sms_api_mxtong2_'.$name);
    }

    const ERROR_MSG = array(
        '0'     => '提交成功',
        '101'   => '无此用户',
        '102'   => '密码错误',
        '103'   => '提交过快（提交速度超过流速限制）',
        '104'   => '系统忙碌（因平台侧原因，暂时无法处理提交的短信）',
        '105'   => '敏感短信（短信内容包含敏感词）',
        '106'   => '消息长度错误',
        '107'   => '包含错误的手机号码',
        '108'   => '手机号码个数错',
        '109'   => '无发送额度',
        '110'   => '不在发送时间内',
        '111'   => '超出该账户当月发送额度限制',
        '112'   => '无此产品',
        '113'   => 'extno格式错',
        '115'   => '自动审核驳回',
        '116'   => '签名不合法，未带签名',
        '117'   => 'IP地址认证错,请求调用的IP地址不是系统登记的IP地址',
        '118'   => '用户没有相应的发送权限',
        '119'   => '用户已过期',
        '120'   => '内容不在白名单模板中',
    );
}
