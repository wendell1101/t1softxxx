<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/ip.php';

/**
 *
 * - remote addr 认为是直连ip，可能是cdn或proxy，也可能是真实ip
 *   - cdn或proxy只限于：cloudflare, google cdn, 自建proxy，也可能是pod内部ip
 * - x-forwarded-for 是来自于cdn或proxy，保证会把真实ip放在里面
 *
 * - 如果 remote addr 也就是说直连的ip不是来自于cdn或proxy，那么就是认为是真实ip
 * - 如果 remote addr 是来自于cdn或proxy，那么就要检查x-forwarded-for里所有的ip
 *   - 如果有一个ip不是cdn或proxy，那么就认为是存在真实ip
 *   - 如果ip不是cdn或proxy，那么就会去检查白名单列表，发现不在白名单内立刻退出，返回false
 *   - 如果ip是cdn或proxy，并且在白名单内，那么也认为白名单ip存在，并且检查是否允许该ip充当真实ip
 *   - 存在真实ip，并且在白名单内就算是通过检查
 *
 * @see unit_test_white_ip_checker.php
 */
class White_ip_checker extends Ip {

	function __construct() {
		parent::__construct();
	}

	/**
	 * - remote addr is trusted ip
	 * - get ip list from x-forwarded-for
	 * - ip should be white ip and real ip(not trusted ip)
	 */
	function checkWhiteIpForAdmin($overRemoteAddr='', $overIpList=[]) {
		$exists=false;
		$foundRealIp=false;
		$admin_white_ip_list_mode=$this->utils->getConfig('admin_white_ip_list_mode');
		if($admin_white_ip_list_mode==self::MAX_COMPATIBILITY){
			//get ip
			$ip = $this->input->ip_address();
			if(!empty($ip)){
				$exists=$this->inWhiteIp($ip);
				// reset to true. ignore real ip check
				$foundRealIp=true;
				$this->utils->debug_log('MAX_COMPATIBILITY ip in searchWhiteIP', $ip, $exists);
			}
		}else{
			//check remote addr
			$ip=$this->input->getRemoteAddr();
			if(!empty($overRemoteAddr)){
				// if overRemoteAddr is not empty, use it
				$this->utils->debug_log('overRemoteAddr', $overRemoteAddr);
				$ip=$overRemoteAddr;
			}
			$this->utils->debug_log('MIN_COMPATIBILITY ip getRemoteAddr', $ip);
			// remote ip is trusted ip, cdn or proxy or internal ip
			if($this->isTrustedIP($ip)){
				//check forward for
				$ipList=$this->input->getIpListFromXForwardedFor();
				if(!empty($overIpList)){
					// if overIpList is not empty, use it
					$this->utils->debug_log('overIpList', $overIpList);
					$ipList=$overIpList;
				}
				if(!empty($ipList)){
					// should check every ip in x-forwarded-for
					foreach ($ipList as $ipOfXForwardedFor) {
						// print log for debug
						$this->utils->debug_log('processing ipOfXForwardedFor', $ipOfXForwardedFor);
						$isSysIp=$this->isCDNOrProxy($ipOfXForwardedFor);
						if($exists && $isSysIp){
							// found any white ip and now isSysIp, skip it.
							$this->utils->debug_log('found one white ip and now isSysIp', $ipOfXForwardedFor);
						}else{
							$exists=$this->inWhiteIp($ipOfXForwardedFor);
						}

						// $exists=$this->searchWhiteIpOrTrustedIp($ipOfXForwardedFor);
						if(!$foundRealIp){
							// if never found real ip
							$foundRealIp=!$isSysIp;
						}else{
							// if already found real ip and existed white ip, skip it.
							// proxy ip may follow after real ip.
							$this->utils->debug_log('if already found real ip and existed white ip, skip it.', $ipOfXForwardedFor);
						}
						// if it's not real ip but we can treat it as real ip, set it as real ip.
						// this if for ss ip only.
						if(!$foundRealIp && $this->treatIpAsRealIP($ipOfXForwardedFor)){
							$this->utils->debug_log('treatIpAsRealIP before ss ip', $ipOfXForwardedFor);
							$foundRealIp=true;
						}
						if(!$exists && !$isSysIp){
							//not white ip and not sys ip mean failed
							$this->utils->debug_log('not exist ip in getIpListFromXForwardedFor', $ipOfXForwardedFor);
							break;
						}
						// continue if it's sys ip or exists
						$this->utils->debug_log('continue if it\'s sys ip or exists', $ipOfXForwardedFor, $exists, $isSysIp);
					}
				}
				if($exists){
					$this->utils->debug_log('exist ip in getIpListFromXForwardedFor', $ipList, $exists);
				}
			}else{
				//if it's not pod
				// $exists=$this->searchWhiteIpOrTrustedIp($ip);
				$exists=$this->inWhiteIp($ip);
				$isSysIp=$this->isCDNOrProxy($ip);
				// if it's not trusted ip, it's real ip.
				$foundRealIp=!$isSysIp; //!$this->isCDNIp($ip);
				$this->utils->debug_log('ip not in trusted ip', $ip, $exists);
			}
		}

		$this->utils->debug_log('checkWhiteIpForAdmin', ['exists'=>$exists, 'foundRealIp'=>$foundRealIp]);
		return $exists && $foundRealIp;
	}

	public function treatIpAsRealIP($ip){
		$ss_ip_list=$this->utils->getConfig('ss_ip_list');
		return in_array($ip, $ss_ip_list);
	}

	public function isTrustedIP($ip){
		$ip=trim($ip);
		return strpos($ip, '10.') === 0 || $this->isCDNOrProxy($ip);
	}

	public function isCDNOrProxy($ip){
		// return $this->isDefaultWhiteIP($ip);
		return $this->isProxyIp($ip) || $this->isCDNIp($ip);
	}

	public function isCDNIp($ip){
		$exists=false;
		$default_cdn_ip_list=$this->utils->getConfig('default_cdn_ip_list');
		if(!empty($default_cdn_ip_list)){
			foreach ($default_cdn_ip_list as $allowedIp) {
				$exists=$this->utils->compareIP($ip, $allowedIp);
				if($exists){
					$this->utils->debug_log('exist ip in default_cdn_ip_list', $ip, $allowedIp);
					return $exists;
				}
			}
		}

		return $exists;
	}

	public function isProxyIp($ip){
		$exists=false;
		$default_proxy_ip_list=$this->utils->getConfig('default_proxy_ip_list');
		if(!empty($default_proxy_ip_list)){
			foreach ($default_proxy_ip_list as $allowedIp) {
				$exists=$this->utils->compareIP($ip, $allowedIp);
				if($exists){
					$this->utils->debug_log('exist ip in default_proxy_ip_list', $ip, $allowedIp);
					return $exists;
				}
			}
		}

		return $exists;
	}

	// public function searchWhiteIpOrTrustedIp($ip){
	// 	$exists=false;
	// 	$ip=trim($ip);
	// 	if(!empty($ip)){
	// 		$exists=$this->isDefaultWhiteIP($ip);
	// 		if($exists){
	// 			return $exists;
	// 		}
	// 		$exists=$this->inWhiteIp($ip);
	// 	}
	// 	return $exists;
	// }
	public function inWhiteIp($ip){
		$exists=false;
		$ip=trim($ip);
		if(!empty($ip)){
			$this->db->select('ipName')->from('ip')->where('ipName', $ip)->where('status', self::STATUS_ALLOW);
			$exists=$this->runExistsResult();

			if (!$exists) {
				//check ip mask again
				$this->db->select('ipName')->from('ip')->where('ipName like "%/%"', null, false)
				    ->where('status',self::STATUS_ALLOW);
				$rows=$this->runMultipleRowArray();
				if(!empty($rows)){
					foreach ($rows as $row) {
						$allowedIp=$row['ipName'];
						$exists=$this->utils->compareIP($ip, $allowedIp);
						if($exists){
							return $exists;
						}
					}
				}
			}
		}
		return $exists;
	}

	public function isDefaultWhiteIP($ip){
		$exists=false;
		$default_white_ip_list=$this->utils->getConfig('default_white_ip_list');
		if(!empty($default_white_ip_list)){
			foreach ($default_white_ip_list as $allowedIp) {
				$exists=$this->utils->compareIP($ip, $allowedIp);
				if($exists){
					$this->utils->debug_log('exist ip in default_white_ip_list', $ip, $allowedIp);
					return $exists;
				}
			}
		}

		return $exists;
	}


}