<?php
/**
 *
 * @see		routes		(player/application/config/routes.php)
 * @see		api_common	(player/application/controllers/api_common.php)
 *
 */

require_once dirname(__FILE__) . '/t1t_ac_tmpl.php';
require_once dirname(__FILE__) . '/t1t_comapi_module_smash_promo_auth.php';

class Smash extends T1t_ac_tmpl {

	use t1t_comapi_module_smash_promo_auth;

	protected $black_list_enabled = false;
	protected $black_list = [];

	protected $white_list_enabled = true;
	protected $white_list = [
		'apiEcho' ,
		'apiPostEcho' ,
		'generateEventUrlWithToken',
		'useinfo' ,
		'invite_friend',
		'bet_info',
		'bet_amount',
        'fake_mobi_push'
	];

	function __construct() {
		parent::__construct();
		$this->comapi_log(__METHOD__, 'Beginning comapi request');

	}


	/**
     * Check api_key against configured list in system config files
     * Switchable IP matching; overrides Api_common::isValidApiKey()
     *
     * @uses	config item
     *
     * @param  string	$key_str	The api_key to be verified
     * @return boolean
     */
	public function _isValidApiKeyInSmash($sign, $player_token) {

		$rlt_internal_player_center_api_key = $this->_check_internal_player_center_api_key($sign);
		if( $rlt_internal_player_center_api_key){
			return true;
		}


		$apiList = $this->utils->getConfig('api_key_player_center');
		$getIP = $this->_getRequestIp();

		if ($this->utils->getConfig('api_key_player_center_required')) {
			if ( empty($apiList) ) {
                $this->comapi_log(__METHOD__, 'no api_key list defined.');
				return false;
			} else {
				$whiteIpIssue=false;
				foreach ($apiList as $key => $value) {

					/// sign = md5(player_token+ api_key)
					$_md5_source = $player_token. $key;
					// encrypted_password_md5, password
					if ($this->utils->validate_password_md5($sign, $_md5_source )){
						//* means match any ip
						if(in_array('*', $value) || in_array('any', $value)){
							$this->comapi_log(__METHOD__, 'IP * matched', [  'key' => $key, 'req_ip' => $getIP, 'allowed_ips' => $value ]);
							return true;
						}
						if (in_array($getIP, $value)){
							$this->comapi_log(__METHOD__, 'Exact IP matched', [  'key' => $key, 'req_ip' => $getIP, 'allowed_ips' => $value ]);
							return true;
						}
						$whiteIpIssue=true;
						break;
					}
				}
				if($whiteIpIssue){
	                $this->comapi_log(__METHOD__, 'white ip issue', [ 'req_ip' => $getIP, 'api_key-list' => $apiList] );
					return self::CODE_IP_NOT_WHITELISTED;
				}

                $this->comapi_log(__METHOD__, 'invalid', ['req_ip' => $getIP, 'api_key-list' => $apiList] );

                return false;
			}
		}else{
            $this->utils->debug_log('isValidApiKey is not enabled.');
            // OGP-9059: returning true when config item 'api_key_player_center_required' null or false will leave Api_common accessible to any api_key/any IP when no api_key configured.  This is a serious security hole.  Reverting to false here.
            // return true;
            return false;
        }
	} // EOF isValidApiKey

	public function _check_internal_player_center_api_key($api_key){
		$internal_player_center_api_key=$this->utils->getConfig('internal_player_center_api_key');
        if(!empty($internal_player_center_api_key) && $internal_player_center_api_key == $api_key){
            $this->comapi_log(__METHOD__, 'match internal_player_center_api_key');
            return true;
        }
	}

}
