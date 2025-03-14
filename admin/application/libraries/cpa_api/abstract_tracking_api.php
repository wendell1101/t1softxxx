<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Abstract_tracking_api
 *
 *
 * @version		1.0.0
 * @property Utils $utils
 * @property CI_loader $load
 * @property Player_trackingevent $player_trackingevent
 */

class Abstract_tracking_api extends CI_Controller
{
    const API_TYPE = '3rdpartyAffiliateNetwork';
    public $_options;
    public $post_content;
    public $platform_setting;
    public $identity_content;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->library(['session']);
        $this->utils = $this->CI->utils;
        $this->CI->load->model(['player_model', 'player_trackingevent']);
        // $this->_options = [
        //     'curl' => [
        //         'timeout_second' => 10,
        //         'connect_timeout' => 5,
        //         'skip_ssl_verify' => true,
        //         'is_post' => true
        //     ]
        // ];

        $this->_options = [
            'CURLOPT' => [
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5
            ]
        ];
        $this->post_content = [];
        $this->init();
    }

    public function init()
    {
    }

    /**
     * getOptions function
     *
     * @param string $key
     * @return mixed
     */
    protected function getOptions($key = null)
    {
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

    public function getSpecificAffiliateSetting($aff_id) {
        if($this->getOptions($aff_id)) {
            $this->_options = array_replace_recursive($this->_options, $this->getOptions($aff_id));
        }
    }

    protected function getApiURL()
    {
        return $this->getOptions('api_url');
    }

    public function pageViewPostBack($platform_setting, $tracking_extra_info){}

    public function regPostBack($clickid, $player, $extra_info = null)
    {
        return true;
    }

    public function depositPostBack($clickid, $player, $deposit_order=null)
    {
        return true;
    }

    public function everyTimeDepositPostBack($clickid, $player, $status, $deposit_order=null)
    {
        return true;
    }
    public function withdrawalSuccessPostBack($cpaInfo, $player, $status, $withdrawal_order = null)
    {
        return true;
    }


    public function doPost($post_back_data, $player = null, $related_id2 = null, $post_url = null)
    {
        $config = [];
        $player = (array)$player;
        // $deposit_order = (array)$deposit_order;
        $config = array_replace_recursive($config, $this->getOptions('CURLOPT'));
        if($post_url) {
            $url = $post_url;
        }else{
            $url = $this->getApiURL();
        }
        if (!empty($post_back_data) && is_array($post_back_data)) {
            if (strpos($url, '?') === false) {
                $url .= '?';
            } else {
                $url .= '&';
            }
            $url .= http_build_query($post_back_data);
        }
        $this->utils->debug_log('============postBack URL============', $url);
        $this->utils->debug_log('============postBack data============', $post_back_data);
        list($head, $resp, $stat) = $this->utils->callHttp($url, 'POST', $post_back_data, $config);
        // var_dump($head, $resp, $stat);
        $post_back_res['head'] = $head;
        $post_back_res['resp'] = $resp;
        $post_back_res['stat'] = $stat;

        $this->CI->load->model(array('response_result'));
        $flag = ($stat == 200) ? Response_result::FLAG_NORMAL:Response_result::FLAG_ERROR;
        // return $this->utils->callHttp($url, 'POST', $post_back_data, $config);
        $resultAll['type']       = 'submit';
        $resultAll['url']        = $url;
        $resultAll['params']     = $post_back_data;
        $resultAll['content']    = $resp;
        $resultAll['_SERVER']    = $_SERVER;

        // $event = $deposit_order ? '3rdpartyAffiliateNetworkDepositEvent' : '3rdpartyAffiliateNetworkRegisterEvent';
        $response_result_id = $this->CI->response_result->saveResponseResult(
            0, #1
            $flag, #2
            self::API_TYPE, #3
            $post_back_data, #4
            json_encode($resultAll), #5
            $stat, #6
            null, #7
            null, #8
            array(
                'player_id' => ($player?$player['playerId']:null),
                'related_id1' => ($player?$player['playerId']:null),
                'related_id2' => $related_id2) #9 payment id
        );

        return $post_back_res;
    }
}
