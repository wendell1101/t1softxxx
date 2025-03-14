<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * IFSC RAZORPAY LIBRARY
 *
 * Used to access Razorpay IFSC API
 *
 * @author		Rupert Chen
 * @copyright	tot 2020
 */
class ifsc_razorpay_lib {

	protected $base_url = 'https://ifsc.razorpay.com';

	function __construct() {
		$this->ci = &get_instance();
		$this->ci->load->model('ifsc_cache');
	}

	function query($ifsc_code) {
		$stat = null;

		$ifsc_row = $this->ci->ifsc_cache->get($ifsc_code);
		if (!empty($ifsc_row)) {
			$resp = $ifsc_row['response'];

			$this->success = true;
			$this->from_cache = true;
			$this->full_url = $ifsc_row['source_url'];
		}
		else {
			$full_url = "{$this->base_url}/{$ifsc_code}";

			list($head, $resp, $stat) = $this->ci->utils->callHttp($full_url, 'GET', []);
			$this->success = ($stat == '200');
			$this->from_cache = false;
			$this->full_url = $full_url;
			$this->ci->ifsc_cache->store($ifsc_code, $resp, $full_url);
		}

		$this->ifsc = $ifsc_code;
		$this->http_status = $stat;
		$this->result_raw = $resp;

		if ($this->success) {
			$this->result = empty($resp) ? null : json_decode($resp, 'as_array');
		}
		else {
			$this->result = null;
		}

		// return $this->result;
	}

	/**
	 * Returns branch by given IFSC code
	 * @param	string	$ifsc_code		Example: KARB0000001, CITI0000001
	 * @return	string	name of branch
	 */
	function get_branch($ifsc_code) {
		$this->query($ifsc_code);
		if (!empty($this->result) && isset($this->result['BRANCH'])) {
			return $this->result['BRANCH'];
		}
		return null;
	}

	/**
	 * Returns 'branch, bank' string by given IFSC code
	 * OGP-20556, built for YOURSITE_WITHDRAWAL_PAYMENT_API (5844)
	 *
	 * @param	string	$ifsc_code		Example: KARB0000001, CITI0000001
	 * @see		payment_api_yoursite_withdrawal.php
	 * @return	string	'branch, bank' string
	 */
	function get_branch_bank($ifsc_code) {
		$this->query($ifsc_code);
		if (!empty($this->result) && isset($this->result['BRANCH']) && isset($this->result['BANK'])) {
			return "{$this->result['BRANCH']}, {$this->result['BANK']}";
		}
		return null;
	}

	/**
	 * Returns [ bank, branch, city, addr ] tuple for a given ifsc code
	 * OGP-22715, built for APPAY_WITHDRAWAL_PAYMENT_API (5926)
	 *
	 * @param	string	$ifsc_code		Example: KARB0000001, CITI0000001, ICBC0000001
	 * @see		payment_api_appay_withdrawal.php
	 * @return	array of [ bank, branch, city, addr ]
	 */
	function get_branch_combined_details($ifsc_code) {
		$this->query($ifsc_code);

		if (empty($this->result)) {
			return [ 'bank' => null, 'branch' => null, 'city' => null, 'addr' => null ];
		}

		$ret = [
			'bank'		=> isset($this->result['BANK']) ? $this->result['BANK'] : null ,
			'branch'	=> isset($this->result['BRANCH']) ? $this->result['BRANCH'] : null ,
			'city'		=> isset($this->result['CITY']) ? $this->result['CITY'] : null ,
			'addr'		=> isset($this->result['ADDRESS']) ? $this->result['ADDRESS'] : null ,
		];

		return $ret;
	}

}

/* End of file whitelist_library.php */
/* Location: ./application/libraries/whitelist_library.php */