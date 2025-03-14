<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Abstract_tracking_api
 *
 *
 * @version		1.0.0
 */

class Abstract_telesale_api extends CI_Controller
{
    const API_TYPE = '3rdpartyRegisterTelesale';
    public $_options;
    public $_custom_curl_header = null;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->utils = $this->CI->utils;
        $this->_options = [
            'CURLOPT' => [
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5
            ]
        ];
        $this->init();
    }

    public function init(){
    }

    public function postSaveCustomerData($player_id, $player_details){
    }

    /**
     * getOptions function
     *
     * @param String $key
     * @return mixed
     */
    protected function getOptions($key = null){
        if (empty($key)) {
            return $this->_options;
        } else {
            if (!isset($key)) {
                return null;
            } else {
                if (array_key_exists($key, $this->_options)) {
                    return $this->_options[$key];
                } else {
                    return null;
                }
            }
        }
    }

    protected function getApiURL(){
        return $this->getOptions('api_url');
    }

    public function getDomain($url = null){
        $parts = parse_url($url);
        $domain = isset($parts['host']) ? $parts['host'] : '';
        return $domain;
    }

    public function doPost($post_data, $player_id = null){
        $config = [];
        $config = array_replace_recursive($config, $this->getOptions('CURLOPT'));
        $url = $this->getApiURL();

        $this->utils->debug_log('============post URL============', $url);
        $this->utils->debug_log('============post data============', $post_data);
        list($head, $resp, $stat) = $this->utils->callHttp($url, 'POST', $post_data, $config);
        $post_res['head'] = $head;
        $post_res['resp'] = $resp;
        $post_res['stat'] = $stat;

        $this->CI->load->model(array('response_result'));
        $flag = ($stat == 200) ? Response_result::FLAG_NORMAL:Response_result::FLAG_ERROR;
        // return $this->utils->callHttp($url, 'POST', $post_back_data, $config);
        $resultAll['type']       = 'submit';
        $resultAll['url']        = $url;
        $resultAll['params']     = $post_data;
        $resultAll['content']    = $resp;
        $resultAll['_SERVER']    = $_SERVER;

        // $event = $deposit_order ? '3rdpartyAffiliateNetworkDepositEvent' : '3rdpartyAffiliateNetworkRegisterEvent';
        $response_result_id = $this->CI->response_result->saveResponseResult(
            0, #1
            $flag, #2
            self::API_TYPE, #3
            self::API_TYPE, #4
            json_encode($resultAll), #5
            $stat, #6
            null, #7
            null, #8
            array('player_id' => $player_id));

        return $post_res;
    }

    protected function processCurl($params, $player_id = null, $action) {
        //call http
        $content = null;
        $header = null;
        $statusCode = null;
        $statusText = '';
        $last_url=null;
        $ch = null;
        $config = [];
        $default_http_timeout = config_item('default_http_timeout');
        $default_connect_timeout = config_item('default_connect_timeout');
        $curlOptions = array_replace_recursive($config, $this->getOptions('CURLOPT'));
        $url = $this->getApiURL().'/'.$action;

        $this->utils->debug_log('============post processCurl URL============', $url);
        $this->utils->debug_log('============post processCurl data============', $params);

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            //set timeout
            curl_setopt($ch, CURLOPT_TIMEOUT, $default_http_timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $default_connect_timeout);

            if (!empty($curlOptions)) {
                curl_setopt_array($ch, $curlOptions);
            }

            $response = curl_exec($ch);
            $errCode = curl_errno($ch);
            $error = curl_error($ch);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $content = substr($response, $header_size);

            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $last_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

            $statusText = $errCode . ':' . $error;
            // var_dump($statusText);
            curl_close($ch);

        } catch (Exception $e) {
            $this->error_log('http call url:'.$url.', method:' . 'POST', 'params', $params, 'curlOptions', $curlOptions, 'last_url', $last_url, $e);
        }

        $post_res['head'] = $header;
        $post_res['resp'] = $content;
        $post_res['stat'] = $statusCode;

        $this->utils->debug_log('============post processCurl response============', $post_res);

        $this->CI->load->model(array('response_result'));
        $flag = ($statusCode == 200) ? Response_result::FLAG_NORMAL:Response_result::FLAG_ERROR;
        // return $this->utils->callHttp($url, 'POST', $post_back_data, $config);
        $resultAll['type']       = 'submit';
        $resultAll['url']        = $url;
        $resultAll['params']     = $params;
        $resultAll['content']    = $content;
        $resultAll['_SERVER']    = $_SERVER;

        $this->utils->debug_log('============post processCurl save response result============', $resultAll);
        $response_result_id = $this->CI->response_result->saveResponseResult(
            0, #1
            $flag, #2
            self::API_TYPE, #3
            self::API_TYPE, #4
            json_encode($resultAll), #5
            $statusCode, #6
            null, #7
            null, #8
            array('player_id' => $player_id));

        return $post_res;
    }
}
