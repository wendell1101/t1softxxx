<?php
require_once dirname(__FILE__) . '/abstract_sms_api.php';

/**
 * Config items:
 * * sms_api_vonage_account
 * * sms_api_vonage_password
 */

class Sms_api_vonage extends Abstract_sms_api {
    const SUCCESS_CODE = "0";

    protected function signContent($content) {
        return sprintf("【%s】%s", $this->CI->config->item('sms_from'), $content);
    }

    public function getUrl() {
        return $this->getParam('url') ? $this->getParam('url') : "https://rest.nexmo.com/sms/json";
    }

    public function getFields($mobile, $content, $dialingCode) {

        if(empty($dialingCode)){
            $dialingCode = $this->getParam('country_code');
        }
        $fields = array(
            'type'  => 'unicode',
            'text' => $content,
            'to'    => $dialingCode.$mobile,
            'from'      => $this->getParam('from'),
            'api_key'=> $this->getParam('api_key'),
            'api_secret'=> $this->getParam('api_secret'),
        );
       
        $this->utils->debug_log("===============vonage SMS fields", $fields);
        return $fields;
    }

    public function getErrorMsg($returnQueryString) {
        
        $errorCode = 'Missing state';
        $errorDesc = 'State invalidate';

        if(isset($returnQueryString['messages'][0]['status'])){
            $errorCode = $returnQueryString['messages'][0]['status'];
            $errorDesc = $this->getErrorMsgByErrorCode($errorCode);
        }

        $this->utils->error_log("===============vonage return [$errorCode]: $errorDesc", $returnQueryString);
        return $errorCode.": ".$errorDesc;
    }

    public function isSuccess($returnQueryString) {
        $result = json_decode($returnQueryString, true);
        if($result['messages'][0]['status'] == self::SUCCESS_CODE)
            return true;
        else
            $errorMsg=$this->getErrorMsg($returnQueryString);
            return $errorMsg;
    }

    protected function configCurl($handle, $mobile, $content, $dialingCode) {
		$url = $this->getUrl();
		$content = $this->signContent($content);
		$fields = $this->getFields($mobile, $content, $dialingCode);
		$fields_string = http_build_query($fields);
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER =>array('application/x-www-form-urlencoded'),
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $fields_string,
            CURLOPT_RETURNTRANSFER => 1,
        ];
        curl_setopt_array($handle, $options);

        return $fields;
	}


    protected function getParam($name) {
        return $this->CI->config->item('sms_api_vonage_'.$name);
    }

    protected function getErrorMsgByErrorCode($error_code){
        $errorDesc = '';
        switch ($error_code) {
            case '1':
                $errorDesc = '您发送短信的速度超出帐户限制';
                break;
            case '2':
                $errorDesc = '您的请求缺少必需的参数之一：from，to，api_key，api_secret 或 text';
                break;
            case '3':
                $errorDesc = '一个或多个参数的值无效。';
                break;
            case '4':
                $errorDesc = '您的 API 密钥和/或密码不正确、无效或被禁用。';
                break;
            case '5':
                $errorDesc = '处理此消息时平台中发生了错误。';
                break;
            case '6':
                $errorDesc = '平台无法处理此消息，例如，无法识别的数字前缀。';
                break;
            case '7':
                $errorDesc = '您尝试向其发送消息的号码已停用，可能不会收到。';
                break;
            case '8':
                $errorDesc = '您的 Nexmo 帐户已被暂停。';
                break;
            case '9':
                $errorDesc = '您没有足够的积分来发送消息。请充值并重试。';
                break;
            case '10':
                $errorDesc = '与平台的同时连接数超出了您的帐户分配。';
                break;
            case '11':
                $errorDesc = '该帐户未配置短信 API，您应该改用 SMPP。';
                break;
            case '12':
                $errorDesc = '消息长度超过了允许的最大长度。';
                break;
            case '13':
                $errorDesc = '提供的签名无法验证。';
                break;
            case '14':
                $errorDesc = '您正在 from 字段中使用未经授权的发件人 ID。这是在北美地区最常见的情况。在北美地区，需要使用 Nexmo 长虚拟号码或短代码。';
                break;
            case '15':
                $errorDesc = '提供的网络代码要么无法识别，要么与目标地址的国家/地区不匹配。';
                break;
            case '22':
                $errorDesc = '提供的回调 URL 太长或包含非法字符.';
                break;
            case '29':
                $errorDesc = '您的 Nexmo 帐户仍处于演示模式。在演示模式下，您必须将目标号码添加到列入白名单的目的地列表中。为您的帐户充值以消除此限制。';
                break;
            case '32':
                $errorDesc = '签名的请求也可能不会显示 api_secret。';
                break;
            case '33':
                $errorDesc = '您尝试向其发送消息的号码已停用，可能不会收到。';
                break;
            default:
                $errorDesc = '未知错误';
                break;
        }
        return $errorDesc;
    }

}
