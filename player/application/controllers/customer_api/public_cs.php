<?php
/**
 * Ole777cn chatbot/cs exclusive container, OGP-18764
 * Prototype from generic player center API wrapper, OGP-9053
 *
 * @see		routes		(player/application/config/routes.php)
 * @see		api_common	(player/application/controllers/api_common.php)
 *
 * @author 	Rupert Chen
 */

require_once dirname(__FILE__) . '/t1t_ac_tmpl.php';

class Public_cs extends T1t_ac_tmpl {

	protected $black_list_enabled = false;
	protected $black_list = [];

	protected $white_list_enabled = true;
	protected $white_list = [
		'apiEcho' ,
		'apiPostEcho' ,
		'login' ,
		'logout' ,
		'isPlayerExist' ,
		'getRequestIP' ,
		'getClientIP' ,
		// OGP-9815
		'getPlayerReports' ,
		// OGP-9570
		'listPromos' ,
		'applyPromo' ,
		// OGP-17411, 17412
		'getPlayerTransferStatus' ,
		'summaryPlayerWithdrawalConditions' ,
		// OGP-20942
		'getPlayerDepositStatus' ,
		'getPlayerVipStatus'
	];

	function __construct() {
		parent::__construct();

		$this->verify_sign();
	}

	protected function verify_sign() {
		$sign		= $this->input->post('sign');
		$username	= $this->input->post('username');
		$token		= $this->input->post('token');
		$sign_key	= $this->utils->getConfig('sign_key_public_cs');
		$dateident	= date('YmdH');

		$sign_valid_plain	= $username . $token . $sign_key . $dateident;
		$sign_valid 		= md5($sign_valid_plain);

		$err_ret = null;

		try {
			if (empty($sign_key)) {
				throw new Exception('sign_key_public_cs not set, public_cs not accessible', 0x01);
			}
			if ($sign != $sign_valid) {
				$err_ret = [ 'sign' => $sign, 'sign_valid' => $sign_valid, 'sign_valid_plain' => $sign_valid_plain ];
				throw new Exception('Public_cs sign check failed', 0x02);
			}

			// Point of success
			$this->utils->debug_log(__METHOD__, 'Public_cs sign check successful', [ 'sign_valid_plain' => $sign_valid_plain ]);
			// Set internal api_key to pass Api_common checks
			$_POST['api_key'] = $this->utils->getConfig('internal_player_center_api_key');
		}
		catch (Exception $ex) {
			$this->utils->debug_log(__METHOD__, "({$ex->getCode()}) {$ex->getMessage()}", $err_ret);
			return show_404();
		}

	}

}
