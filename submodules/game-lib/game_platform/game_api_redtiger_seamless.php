<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_redtiger_seamless extends Abstract_game_api {

	public $casino;
	public $currency;
	public $country;
	public $api_key;
	public $recon_token;
	public $static_token;
	public $test_player;

# Fields in game_logs we want to detect changes for merge, and when redtiger_idr_game_logs.md5_sum is empty
    const MD5_FIELDS_FOR_MERGE=[
        'bet_amount',
        'refund_amount',
        'win_amount',
        'game_key',
        'game_version',
        'round_id',
        'after_balance',
        'start_at',
        'end_at',
        'external_uniqueid'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'bet_amount',
        'refund_amount',
        'win_amount',
        'after_balance'
    ];

	public function __construct() {
		parent::__construct();

		$this->casino 		= $this->getSystemInfo('casino', 'NONE');
		$this->currency 	= $this->getSystemInfo('currency', 'USD');
		$this->country 	 	= $this->getSystemInfo('country', 'CN');
		$this->api_key  	= $this->getSystemInfo('api_key', 'f514e6542da8f6b77d2a44c979d66a20');
		$this->recon_token  = $this->getSystemInfo('recon_token', 'f514e6542da8f6b77d2a44c979d66a20');
		$this->static_token = $this->getSystemInfo('static_token', 'f514e6542da8f6b77d2a44c979d66a20');
		$this->test_player  = $this->getSystemInfo('test_player', 'test002');
		$this->game_launch_url = $this->getSystemInfo('game_launch_url','https://gserver-kinggaming-dev.dopamine-gaming.com/kinggaming/launcher/');

	}

	public function session($params, $response_result_id, &$playerId) {

		$gameUsername = @$params['userId'] ? : $this->convertUsernameToGame($this->test_player);
		$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
		$token = $this->getPlayerToken($playerId);

		$success = TRUE;
		$data = [
			'token' => $token,
			'userId' => $gameUsername,
		];

		return [$success, $data];

	}

	public function auth($params, $response_result_id, &$playerId) {

		# Test auth with invalid token
		$playerInfo = $this->getPlayerInfoByToken($params['token']);

		if (empty($playerInfo)) {
			throw new Exception("Not authorized", 301);
		}

		$playerId = $playerInfo['playerId'];
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerInfo['username']);

		if (empty($gameUsername)) {
			throw new Exception("Not authorized", 301);
		}

		$lang = 'en'; # TODO

		$balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());
		$bonus = 0;

		$success = TRUE;
		$data = [
			'token'    => $this->getPlayerToken($playerId),
			'userId'   => $gameUsername,
			'currency' => $this->currency,
			'country'  => $this->country,
			'language' => $lang,
			'casino'   => $this->casino,
			'balance' => [
				'cash'  => number_format($balance, 2, '.', ''),
				'bonus' => number_format($bonus, 2, '.', ''),
			],
		];

		return [$success, $data];

	}

	public function stake($params, $response_result_id, &$playerId) {
			
		$amount = floatval($params['transaction']['stake']);
		$transaction_id = $params['transaction']['id'];

		$playerInfo = $this->getPlayerInfoByToken($params['token']);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerInfo['username']);
		$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

		# Makes a bet request with an invalid user id && Makes a bet request with recon token
		if (empty($playerId) || $gameUsername != $params['userId']) {
			throw new Exception("Not authorized", 301);
		}

		# Makes a bet request with existing transaction id
		if ($this->CI->db->where('transaction_id', $params['transaction']['id'])->count_all_results('redtigerseamless_game_logs') > 0) {
			throw new Exception("Duplicated transaction", 401);
		}

		# Makes a bet request with a invalid currency
		if ($this->currency != $params['currency']) {
			throw new Exception("Invalid user currency", 305);
		}

		# Makes a bet request with a negative stake && Makes a bet request with stake 0
		if ($amount < 0) {
			throw new Exception("Invalid Input", 200);
		}

		# Makes a bet request with а greater stake than balance
		$balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());
		if (($balance - $amount) < 0) {
			throw new Exception('Insufficient funds', 304);
		}

		$success = $this->subtract_amount($playerId, $amount);

		$balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());
		$bonus = 0;

		$insert_data = [
			'transaction_id' 					=> @$params['transaction']['id']?:NULL,
			'type'								=> 'stake',
			'token' 							=> @$params['token']?:NULL,
			'ip' 								=> @$params['ip']?:NULL,
			'userId' 							=> @$params['userId']?:NULL,
			'casino' 							=> @$params['casino']?:NULL,
			'currency' 							=> @$params['currency']?:NULL,
			'transaction_stake_payout' 			=> @$params['transaction']['stake']?:NULL,
			'transaction_stake_payout_promo' 	=> @$params['transaction']['stakePromo']?:NULL,
			'transaction_details_game' 			=> @$params['transaction']['details']['game']?:NULL,
			'transaction_details_jackpot' 		=> @$params['transaction']['details']['jackpot']?:NULL,
			'transaction_sources_lines' 		=> @$params['transaction']['sources']['lines']?:NULL,
			'transaction_sources_features' 		=> @$params['transaction']['sources']['features']?:NULL,
			'transaction_sources_jackpot' 		=> @$params['transaction']['sources']['jackpot']?:NULL,
			'game_type' 						=> @$params['game']['type']?:NULL,
			'game_key' 							=> @$params['game']['key']?:NULL,
			'game_version' 						=> @$params['game']['version']?:NULL,
			'round_id' 							=> @$params['round']['id']?:NULL,
			'round_starts' 						=> $params['round']['starts'],
			'round_ends' 						=> $params['round']['ends'],
			'promo_type' 						=> @$params['promo']['type']?:NULL,
			'promo_instanceCode' 				=> @$params['promo']['instanceCode']?:NULL,
			'promo_instanceId' 					=> @$params['promo']['instanceId']?:NULL,
			'promo_campaignCode' 				=> @$params['promo']['campaignCode']?:NULL,
			'promo_campaignId' 					=> @$params['promo']['campaignId']?:NULL,
			'retry' 							=> @$params['retry']?:0,
			'jackpot_group' 					=> @$params['jackpot_group']?:NULL,
			'jackpot_contribution' 				=> @$params['jackpot_contribution']?:NULL,
			'jackpot_pots' 						=> @$params['jackpot_pots']?:NULL,
			'player_id'							=> $playerId,
			'after_balance' 					=> $balance,
			'response_result_id' 				=> $response_result_id,
			'created_at' 						=> $this->CI->utils->getNowForMysql(),
		];

		$insert_data = array_map(function($value) {
			return is_array($value) ? json_encode($value) : $value;
		}, $insert_data);

		$this->CI->db->insert('redtigerseamless_game_logs', $insert_data);

		$id = $this->CI->db->insert_id();

		// $gameRecord = $this->getDataForMerging($playerId, $insert_data['round_id'], Game_logs::STATUS_PENDING);

		$data = [
			'token'    => $this->getPlayerToken($playerId),
			'id'   	   => $id,
			'currency' => $this->currency,
			'stake'    => [
				'cash'  => number_format($amount, 2, '.', ''),
				'bonus' => number_format(0, 2, '.', ''),
			],
			'balance' => [
				'cash'  => number_format($balance, 2, '.', ''),
				'bonus' => number_format($bonus, 2, '.', ''),
			],
		];

		return [$success, $data];

	}
	
	public function payout($params, $response_result_id, &$playerId) {
		
		$amount = floatval($params['transaction']['payout']);
		$transaction_id = $params['transaction']['id'];

		$gameUsername = $params['userId'];

		if ($this->recon_token != $params['token']) { # Makes an award request with recon token

			$playerInfo = $this->getPlayerInfoByToken($params['token']);
			$gameUsername = $this->getGameUsernameByPlayerUsername($playerInfo['username']);

			if ($gameUsername != $params['userId']) { # Makes an award request with invalid user id
				throw new Exception("Not authorized", 301);
			}

		}

		$playerId = isset($playerInfo['playerId']) ? $playerInfo['playerId'] : $this->getPlayerIdInGameProviderAuth($gameUsername);

		if (empty($playerId)) {
			throw new Exception("Not authorized", 301);
		}

		# Makes an award request with existing id
		if ($this->CI->db->where('transaction_id', $params['transaction']['id'])->count_all_results('redtigerseamless_game_logs') > 0) {
			throw new Exception("Duplicated transaction", 401);
		}

		# Makes an award request with invalid currency
		if ($this->currency != $params['currency']) {
			throw new Exception("Invalid user currency", 305);
		}

		# Makes an award request with a negative payout
		if ($amount < 0) {
			throw new Exception("Invalid Input", 200);
		}

		$success = $this->add_amount($playerId, $amount);

		$balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());
		$bonus = 0;

		$insert_data = [
			'transaction_id' 					=> @$params['transaction']['id']?:NULL,
			'type'								=> 'payout',
			'token' 							=> @$params['token']?:NULL,
			'ip' 								=> @$params['ip']?:NULL,
			'userId' 							=> @$params['userId']?:NULL,
			'casino' 							=> @$params['casino']?:NULL,
			'currency' 							=> @$params['currency']?:NULL,
			'transaction_stake_payout' 			=> @$params['transaction']['payout']?:NULL,
			'transaction_stake_payout_promo' 	=> @$params['transaction']['payoutPromo']?:NULL,
			'transaction_details_game' 			=> @$params['transaction']['details']['game']?:NULL,
			'transaction_details_jackpot' 		=> @$params['transaction']['details']['jackpot']?:NULL,
			'transaction_sources_lines' 		=> @$params['transaction']['sources']['lines']?:NULL,
			'transaction_sources_features' 		=> @$params['transaction']['sources']['features']?:NULL,
			'transaction_sources_jackpot' 		=> @$params['transaction']['sources']['jackpot']?:NULL,
			'game_type' 						=> @$params['game']['type']?:NULL,
			'game_key' 							=> @$params['game']['key']?:NULL,
			'game_version' 						=> @$params['game']['version']?:NULL,
			'round_id' 							=> @$params['round']['id']?:NULL,
			'round_starts' 						=> $params['round']['starts'],
			'round_ends' 						=> $params['round']['ends'],
			'promo_type' 						=> @$params['promo']['type']?:NULL,
			'promo_instanceCode' 				=> @$params['promo']['instanceCode']?:NULL,
			'promo_instanceId' 					=> @$params['promo']['instanceId']?:NULL,
			'promo_campaignCode' 				=> @$params['promo']['campaignCode']?:NULL,
			'promo_campaignId' 					=> @$params['promo']['campaignId']?:NULL,
			'retry' 							=> @$params['retry']?:0,
			'jackpot_group' 					=> @$params['jackpot']['group']?:NULL,
			'jackpot_contribution' 				=> @$params['jackpot']['contribution']?:NULL,
			'jackpot_pots' 						=> @$params['jackpot']['pots']?:NULL,
			'player_id'							=> $playerId,
			'after_balance' 					=> $balance,
			'response_result_id' 				=> $response_result_id,
			'created_at' 						=> $this->CI->utils->getNowForMysql(),
		];

		$insert_data = array_map(function($value) {
			return is_array($value) ? json_encode($value) : $value;
		}, $insert_data);

		$this->CI->db->insert('redtigerseamless_game_logs', $insert_data);

		$id = $this->CI->db->insert_id();

		// $gameRecord = $this->getDataForMerging($playerId, $insert_data['round_id'], Game_logs::STATUS_SETTLED);

		$data = [
			'token'    => $this->getPlayerToken($playerId),
			'id'   	   => $id,
			'payout'   => [
				'cash'  => number_format($amount, 2, '.', ''),
				'bonus' => number_format(0, 2, '.', ''),
			],
			'currency' => $this->currency,
			'balance' => [
				'cash'  => number_format($balance, 2, '.', ''),
				'bonus' => number_format($bonus, 2, '.', ''),
			],
		];

		return [$success, $data];

	}
	
	public function refund($params, $response_result_id, &$playerId) {
		
		$amount = floatval($params['transaction']['stake']);
		$transaction_id = $params['transaction']['id'];

		$gameUsername = $params['userId'];
		$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

		# Try to refund with an invalid token
		if ($this->recon_token != $params['token']) {
			throw new Exception("Not authorized", 301);
		}

		# Try to refund non-existing transaction
		$refund = $this->CI->db->where('transaction_id', $params['transaction']['id'])->get('redtigerseamless_game_logs')->row_array();
		if (empty($refund)) {
			throw new Exception("Transaction not found", 400);
		}

		# Try to refund buyIn transaction
		if ($refund['type'] == 'promobuyin') {
			throw new Exception("Transaction not found", 400);
		}

		# Try to refund a refunded transaction
		if ($refund['type'] == 'refund') {
			throw new Exception("Duplicated transaction", 401);
		}

		$success = $this->add_amount($playerId, $amount);

		$balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());
		$bonus = 0;

		$insert_data = [
			'transaction_id' 					=> @$params['transaction']['id']?:NULL,
			'type'								=> 'refund',
			'token' 							=> @$params['token']?:NULL,
			'ip' 								=> @$params['ip']?:NULL,
			'userId' 							=> @$params['userId']?:NULL,
			'casino' 							=> @$params['casino']?:NULL,
			'currency' 							=> @$params['currency']?:NULL,
			'transaction_stake_payout' 			=> @$params['transaction']['stake']?:NULL,
			'transaction_stake_payout_promo' 	=> @$params['transaction']['stakePromo']?:NULL,
			'transaction_details_game' 			=> @$params['transaction']['details']['game']?:NULL,
			'transaction_details_jackpot' 		=> @$params['transaction']['details']['jackpot']?:NULL,
			'transaction_sources_lines' 		=> @$params['transaction']['sources']['lines']?:NULL,
			'transaction_sources_features' 		=> @$params['transaction']['sources']['features']?:NULL,
			'transaction_sources_jackpot' 		=> @$params['transaction']['sources']['jackpot']?:NULL,
			'game_type' 						=> @$params['game']['type']?:NULL,
			'game_key' 							=> @$params['game']['key']?:NULL,
			'game_version' 						=> @$params['game']['version']?:NULL,
			'round_id' 							=> @$params['round']['id']?:NULL,
			'round_starts' 						=> $params['round']['starts'],
			'round_ends' 						=> $params['round']['ends'],
			'promo_type' 						=> @$params['promo']['type']?:NULL,
			'promo_instanceCode' 				=> @$params['promo']['instanceCode']?:NULL,
			'promo_instanceId' 					=> @$params['promo']['instanceId']?:NULL,
			'promo_campaignCode' 				=> @$params['promo']['campaignCode']?:NULL,
			'promo_campaignId' 					=> @$params['promo']['campaignId']?:NULL,
			'retry' 							=> @$params['retry']?:0,
			'jackpot_group' 					=> @$params['jackpot']['group']?:NULL,
			'jackpot_contribution' 				=> @$params['jackpot']['contribution']?:NULL,
			'jackpot_pots' 						=> @$params['jackpot']['pots']?:NULL,
			'player_id'							=> $playerId,
			'after_balance' 					=> $balance,
			'response_result_id' 				=> $response_result_id,
			'created_at' 						=> $this->CI->utils->getNowForMysql(),
		];

		$insert_data = array_map(function($value) {
			return is_array($value) ? json_encode($value) : $value;
		}, $insert_data);

		$this->CI->db->insert('redtigerseamless_game_logs', $insert_data);

		$id = $this->CI->db->insert_id();

		// $gameRecord = $this->getDataForMerging($playerId, $insert_data['round_id'], Game_logs::STATUS_REFUND);

		$data = [
			'token'    => $this->getPlayerToken($playerId),
			'id'   	   => $id,
			'stake'   => [
				'cash'  => number_format($amount, 2, '.', ''),
				'bonus' => number_format(0, 2, '.', ''),
			],
			'currency' => $this->currency,
			'balance' => [
				'cash'  => number_format($balance, 2, '.', ''),
				'bonus' => number_format($bonus, 2, '.', ''),
			],
		];

		return [$success, $data];

	}
	
	public function promo_buyin($params, $response_result_id, &$playerId) {
		
		$amount = floatval($params['transaction']['stake']);
		$transaction_id = $params['transaction']['id'];

		$playerInfo = $this->getPlayerInfoByToken($params['token']);

		if (empty($playerInfo)) {
			throw new Exception("Not authorized", 301);
		}

		$playerId = $playerInfo['playerId'];
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerInfo['username']);

		# Try to make buyIn with an invalid token
		if (empty($gameUsername) || $gameUsername != $params['userId']) {
			throw new Exception("Not authorized", 301);
		}

		# Try to make buyIn with a negative stake
		if ($amount < 0) {
			throw new Exception("Invalid Input", 200);
		}

		# Try to make buyIn with used transaction id
		if ($this->CI->db->where('transaction_id', $params['transaction']['id'])->count_all_results('redtigerseamless_game_logs') > 0) {
			throw new Exception("Duplicated transaction", 401);
		}

		# Makes a buyin request with a invalid currency
		if ($this->currency != $params['currency']) {
			throw new Exception("Invalid user currency", 305);
		}

		$success = $this->subtract_amount($playerId, $amount);

		$balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());
		$bonus = 0;

		$insert_data = [
			'transaction_id' 					=> @$params['transaction']['id']?:NULL,
			'type'								=> 'promobuyin',
			'token' 							=> @$params['token']?:NULL,
			'ip' 								=> @$params['ip']?:NULL,
			'userId' 							=> @$params['userId']?:NULL,
			'casino' 							=> @$params['casino']?:NULL,
			'currency' 							=> @$params['currency']?:NULL,
			'transaction_stake_payout' 			=> @$params['transaction']['stake']?:NULL,
			'transaction_stake_payout_promo' 	=> @$params['transaction']['stakePromo']?:NULL,
			'transaction_details_game' 			=> @$params['transaction']['details']['game']?:NULL,
			'transaction_details_jackpot' 		=> @$params['transaction']['details']['jackpot']?:NULL,
			'transaction_sources_lines' 		=> @$params['transaction']['sources']['lines']?:NULL,
			'transaction_sources_features' 		=> @$params['transaction']['sources']['features']?:NULL,
			'transaction_sources_jackpot' 		=> @$params['transaction']['sources']['jackpot']?:NULL,
			'game_type' 						=> @$params['game']['type']?:NULL,
			'game_key' 							=> @$params['game']['key']?:NULL,
			'game_version' 						=> @$params['game']['version']?:NULL,
			'round_id' 							=> @$params['round']['id']?:NULL,
			'round_starts' 						=> $params['round']['starts'],
			'round_ends' 						=> $params['round']['ends'],
			'promo_type' 						=> @$params['promo']['type']?:NULL,
			'promo_instanceCode' 				=> @$params['promo']['instanceCode']?:NULL,
			'promo_instanceId' 					=> @$params['promo']['instanceId']?:NULL,
			'promo_campaignCode' 				=> @$params['promo']['campaignCode']?:NULL,
			'promo_campaignId' 					=> @$params['promo']['campaignId']?:NULL,
			'retry' 							=> @$params['retry']?:0,
			'jackpot_group' 					=> @$params['jackpot_group']?:NULL,
			'jackpot_contribution' 				=> @$params['jackpot_contribution']?:NULL,
			'jackpot_pots' 						=> @$params['jackpot_pots']?:NULL,
			'player_id'							=> $playerId,
			'after_balance' 					=> $balance,
			'response_result_id' 				=> $response_result_id,
			'created_at' 						=> $this->CI->utils->getNowForMysql(),
		];

		$insert_data = array_map(function($value) {
			return is_array($value) ? json_encode($value) : $value;
		}, $insert_data);

		$this->CI->db->insert('redtigerseamless_game_logs', $insert_data);

		$id = $this->CI->db->insert_id();

		$data = [
			'token'    => $this->getPlayerToken($playerId),
			'id'   	   => $id,
			'currency' => $this->currency,
			'stake'    => [
				'cash'  => number_format($amount, 2, '.', ''),
				'bonus' => number_format(0, 2, '.', ''),
			],
			'balance' => [
				'cash'  => number_format($balance, 2, '.', ''),
				'bonus' => number_format($bonus, 2, '.', ''),
			],
		];

		return [$success, $data];

	}
	
	public function promo_settle($params, $response_result_id, &$playerId) {
		
		$amount = floatval($params['transaction']['payout']);
		$transaction_id = $params['transaction']['id'];

		$gameUsername = $params['userId'];

		if ($this->recon_token != $params['token']) { # Test promo settle recon

			$playerInfo = $this->getPlayerInfoByToken($params['token']);
			$gameUsername = $this->getGameUsernameByPlayerUsername($playerInfo['username']);

			if ($gameUsername != $params['userId']) { # Test promo settle with an invalid token
				throw new Exception("Not authorized", 301);
			}

		}

		$playerId = isset($playerInfo['playerId']) ? $playerInfo['playerId'] : $this->getPlayerIdInGameProviderAuth($gameUsername);

		if (empty($playerId)) {
			throw new Exception("Not authorized", 301);
		}

		# Test promo settle with used transaction id
		if ($this->CI->db->where('transaction_id', $params['transaction']['id'])->count_all_results('redtigerseamless_game_logs') > 0) {
			throw new Exception("Duplicated transaction", 401);
		}

		# Test promo settle with an invalid currency
		if ($this->currency != $params['currency']) {
			throw new Exception("Invalid user currency", 305);
		}

		$success = $this->add_amount($playerId, $amount);

		$balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());
		$bonus = 0;

		$insert_data = [
			'transaction_id' 					=> @$params['transaction']['id']?:NULL,
			'type'								=> 'promosettle',
			'token' 							=> @$params['token']?:NULL,
			'ip' 								=> @$params['ip']?:NULL,
			'userId' 							=> @$params['userId']?:NULL,
			'casino' 							=> @$params['casino']?:NULL,
			'currency' 							=> @$params['currency']?:NULL,
			'transaction_stake_payout' 			=> @$params['transaction']['payout']?:NULL,
			'transaction_stake_payout_promo' 	=> @$params['transaction']['payoutPromo']?:NULL,
			'transaction_details_game' 			=> @$params['transaction']['details']['game']?:NULL,
			'transaction_details_jackpot' 		=> @$params['transaction']['details']['jackpot']?:NULL,
			'transaction_sources_lines' 		=> @$params['transaction']['sources']['lines']?:NULL,
			'transaction_sources_features' 		=> @$params['transaction']['sources']['features']?:NULL,
			'transaction_sources_jackpot' 		=> @$params['transaction']['sources']['jackpot']?:NULL,
			'game_type' 						=> @$params['game']['type']?:NULL,
			'game_key' 							=> @$params['game']['key']?:NULL,
			'game_version' 						=> @$params['game']['version']?:NULL,
			'round_id' 							=> @$params['round']['id']?:NULL,
			'round_starts' 						=> $params['round']['starts'],
			'round_ends' 						=> $params['round']['ends'],
			'promo_type' 						=> @$params['promo']['type']?:NULL,
			'promo_instanceCode' 				=> @$params['promo']['instanceCode']?:NULL,
			'promo_instanceId' 					=> @$params['promo']['instanceId']?:NULL,
			'promo_campaignCode' 				=> @$params['promo']['campaignCode']?:NULL,
			'promo_campaignId' 					=> @$params['promo']['campaignId']?:NULL,
			'retry' 							=> @$params['retry']?:0,
			'jackpot_group' 					=> @$params['jackpot']['group']?:NULL,
			'jackpot_contribution' 				=> @$params['jackpot']['contribution']?:NULL,
			'jackpot_pots' 						=> @$params['jackpot']['pots']?:NULL,
			'player_id'							=> $playerId,
			'after_balance' 					=> $balance,
			'response_result_id' 				=> $response_result_id,
			'created_at' 						=> $this->CI->utils->getNowForMysql(),
		];

		$insert_data = array_map(function($value) {
			return is_array($value) ? json_encode($value) : $value;
		}, $insert_data);

		$this->CI->db->insert('redtigerseamless_game_logs', $insert_data);

		$id = $this->CI->db->insert_id();

		$data = [
			'token'    => $this->getPlayerToken($playerId),
			'id'   	   => $id,
			'payout'   => [
				'cash'  => number_format($amount, 2, '.', ''),
				'bonus' => number_format(0, 2, '.', ''),
			],
			'currency' => $this->currency,
			'balance' => [
				'cash'  => number_format($balance, 2, '.', ''),
				'bonus' => number_format($bonus, 2, '.', ''),
			],
		];

		return [$success, $data];

	}
	
	public function promo_refund($params, $response_result_id, &$playerId) {
		
		$amount = floatval($params['transaction']['stake']);
		$transaction_id = $params['transaction']['id'];

		$gameUsername = $params['userId'];
		$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

		# Try to refund buyin with an invalid token
		if ($this->recon_token != $params['token']) {
			throw new Exception("Not authorized", 301);
		}

		# Try to refund non-existing buyIn transaction
		if ($this->CI->db->where('transaction_id', $params['transaction']['id'])->where('type', 'promobuyin')->count_all_results('redtigerseamless_game_logs') == 0) {
			throw new Exception("Transaction not found", 400);
		}

		$success = $this->add_amount($playerId, $amount);

		$balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());
		$bonus = 0;

		$insert_data = [
			'transaction_id' 					=> @$params['transaction']['id']?:NULL,
			'type'								=> 'promorefund',
			'token' 							=> @$params['token']?:NULL,
			'ip' 								=> @$params['ip']?:NULL,
			'userId' 							=> @$params['userId']?:NULL,
			'casino' 							=> @$params['casino']?:NULL,
			'currency' 							=> @$params['currency']?:NULL,
			'transaction_stake_payout' 			=> @$params['transaction']['stake']?:NULL,
			'transaction_stake_payout_promo' 	=> @$params['transaction']['stakePromo']?:NULL,
			'transaction_details_game' 			=> @$params['transaction']['details']['game']?:NULL,
			'transaction_details_jackpot' 		=> @$params['transaction']['details']['jackpot']?:NULL,
			'transaction_sources_lines' 		=> @$params['transaction']['sources']['lines']?:NULL,
			'transaction_sources_features' 		=> @$params['transaction']['sources']['features']?:NULL,
			'transaction_sources_jackpot' 		=> @$params['transaction']['sources']['jackpot']?:NULL,
			'game_type' 						=> @$params['game']['type']?:NULL,
			'game_key' 							=> @$params['game']['key']?:NULL,
			'game_version' 						=> @$params['game']['version']?:NULL,
			'round_id' 							=> @$params['round']['id']?:NULL,
			'round_starts' 						=> $params['round']['starts'],
			'round_ends' 						=> $params['round']['ends'],
			'promo_type' 						=> @$params['promo']['type']?:NULL,
			'promo_instanceCode' 				=> @$params['promo']['instanceCode']?:NULL,
			'promo_instanceId' 					=> @$params['promo']['instanceId']?:NULL,
			'promo_campaignCode' 				=> @$params['promo']['campaignCode']?:NULL,
			'promo_campaignId' 					=> @$params['promo']['campaignId']?:NULL,
			'retry' 							=> @$params['retry']?:0,
			'jackpot_group' 					=> @$params['jackpot_group']?:NULL,
			'jackpot_contribution' 				=> @$params['jackpot_contribution']?:NULL,
			'jackpot_pots' 						=> @$params['jackpot_pots']?:NULL,
			'player_id'							=> $playerId,
			'after_balance' 					=> $balance,
			'response_result_id' 				=> $response_result_id,
			'created_at' 						=> $this->CI->utils->getNowForMysql(),
		];

		$insert_data = array_map(function($value) {
			return is_array($value) ? json_encode($value) : $value;
		}, $insert_data);

		$this->CI->db->insert('redtigerseamless_game_logs', $insert_data);

		$id = $this->CI->db->insert_id();

		$data = [
			'token'    => $this->getPlayerToken($playerId),
			'id'   	   => $id,
			'currency' => $this->currency,
			'stake'    => [
				'cash'  => number_format($amount, 2, '.', ''),
				'bonus' => number_format(0, 2, '.', ''),
			],
			'balance' => [
				'cash'  => number_format($balance, 2, '.', ''),
				'bonus' => number_format($bonus, 2, '.', ''),
			],
		];

		return [$success, $data];

	}

	private function subtract_amount($player_id, $amount) {

		if ($amount == 0) {
			return TRUE;
		}

		$game_platform_id = $this->getPlatformCode();

		$success = $this->CI->wallet_model->lockAndTransForPlayerBalance($player_id, function () use ($game_platform_id, $player_id, $amount) {
			$success = $this->CI->wallet_model->decSubWallet($player_id, $game_platform_id, $amount);
			$this->CI->utils->debug_log('redtiger subtract_amount', 'player_id', $player_id, 'amount', $amount, 'success', $success);
			return $success;
		});

		return $success;

	}

	private function add_amount($player_id, $amount) {

		if ($amount == 0) {
			return TRUE;
		}

		$game_platform_id = $this->getPlatformCode();

		$success = $this->CI->wallet_model->lockAndTransForPlayerBalance($player_id, function () use ($game_platform_id, $player_id, $amount) {
			$success = $this->CI->wallet_model->incSubWallet($player_id, $game_platform_id, $amount);
			$this->CI->utils->debug_log('redtiger add_amount', 'player_id', $player_id, 'amount', $amount, 'success', $success);
			return $success;
		});

		return $success;

	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {

		$success = parent::createPlayer($playerName, $playerId, $password, $email, $extra);

		$message = $success ? "Successfull create account for Red Tiger" : "Unable to create Account for Red Tiger";

		return array("success" => $success, "message" => $message);

	}

	public function getPlatformCode() {
		return REDTIGER_SEAMLESS_API;
	}

	public function queryPlayerInfo($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}

	public function changePassword($playerName, $oldPassword, $newPassword) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}

	public function depositToGame($playerName, $amount, $transfer_secure_id = null) {

		$external_transaction_id = $transfer_secure_id;

	    return array(
	        'success' => true,
	        'external_transaction_id' => $external_transaction_id,
	        'response_result_id ' => NULL,
	        'didnot_insert_game_logs'=>true,
	    );

	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null) {

		$external_transaction_id = $transfer_secure_id;

	    return array(
	        'success' => true,
	        'external_transaction_id' => $external_transaction_id,
	        'response_result_id ' => NULL,
	        'didnot_insert_game_logs'=>true,
	    );

	}

	public function login($gameUsername, $password = null) {
		return $this->returnUnimplemented();
	}
	
	public function logout($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
	}

	public function queryPlayerBalance($playerName) {

		$this->CI->load->model(array('player_model'));

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
		$balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

		return array(
			'success' => true,
			'balance' => $balance
		);

	}

	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}

	public function queryForwardGame($playerName, $extra) {

		$token = $this->getPlayerTokenByUsername($playerName);

		$game_code = $extra['game_code'];
		$playMode = $extra['game_mode'] == 'real' ? 'real' : 'demo';
		$language = $extra['language'];
		$channel = $extra['is_mobile'] ? 'M' : 'D';
		$lobbyURL = $extra['t1_lobby_url'];

		$params = http_build_query([
			'hasHistory' => true,
			'hasRealPlayButton' => false,
			'hasGamble' => true,
			'hasRoundId' => true,
			'fullScreen' => true,
			'hasAutoplayTotalSpins' => true,
			'hasAutoplayLimitLoss' => true,
			'hasAutoplaySingleWinLimit' => true,
			'hasAutoplayStopOnJackpot' => true,
			'hasAutoplayStopOnBonus' => true,
			'token' => $token,
			'playMode' => $playMode,
			'lang' => $language,
			'channel' => $channel,
			'lobbyURL' => $lobbyURL,
			
		]);

		$url = rtrim($this->game_launch_url, '/') . "/{$game_code}?{$params}";

		// return array('success' => TRUE, 'url' => $url, 'is_redirect' => $extra['is_mobile']);
		return array('success' => TRUE, 'url' => $url, 'is_redirect' => FALSE); # FOR RED TIGER TESTING ON MOBILE

	}

	public function syncOriginalGameLogs($token) {
		return $this->returnUnimplemented();
	}

	// This syncMerge is for bulk records which date time can be applied
    public function syncMergeToGameLogs($token)
    {
        $enabled_game_logs_unsettle=true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

   public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time)
    {
        $sqlTime='r.created_at >= ? AND r.created_at <= ?';
        $sql = <<<EOD
SELECT 
	gd.game_type_id AS game_type_id,
    gd.id AS game_description_id,
    gd.game_code AS game_code,
    gt.game_type as game_type,
    gd.game_name AS game_name,
    r.userId AS userId,
    r.player_id AS player_id,
    r.game_type AS game_type_original,
    r.type AS bet_type,
    r.game_key AS game_key,
    r.game_version AS game_version,
    r.round_id AS round_id,
    r.after_balance AS after_balance,
    MIN(r.created_at) AS start_at,
    MAX(r.created_at) AS end_at,
    SUM(IF(r.type = 'stake',
        r.transaction_stake_payout,
        0)) AS bet_amount,
    SUM(IF(r.type = 'refund',
        r.transaction_stake_payout,
        0)) AS refund_amount,
    SUM(IF(r.type = 'payout',
        r.transaction_stake_payout,
        0)) AS win_amount,
    MAX(CONCAT_WS('|', r.id, r.after_balance)) AS after_balance,
    SHA2(MAX(CONCAT_WS('|', r.userId, r.round_id)), 256) AS external_uniqueid,
    r.md5_sum as md5_sum,
    count(*) as transaction_count

FROM
    redtigerseamless_game_logs AS r
LEFT JOIN game_description as gd ON r.game_key = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
WHERE
	r.type NOT IN ('promobuyin', 'promosettle', 'promorefund')
AND
    {$sqlTime}
GROUP BY r.userId , r.player_id , r.game_type , r.game_key , r.game_version , r.round_id
EOD;
        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
	{
		if (isset($row, $row['after_balance'])) {
			$row['after_balance'] = @explode('|', $row['after_balance'])[1] ? : 0;
		}

		$player_username = $this->CI->player_model->getUsernameById($row['player_id']);

		// $external_uniqueid = implode('|', [$row['userId'], $row['round_id']]);
		// $external_uniqueid = hash('sha256', $external_uniqueid);

		// As of the original merging process of seamless if every stake has a payout if the group by query dont have a payout that means that the transaction is pending
		$transaction_count 	 = $row['transaction_count'];

		$trans_amount 		 = $row['bet_amount'];
		$refund_amount 	     = $row['refund_amount'];
		$win_amount 	     = $row['win_amount'];

		$bet_amount 	     = $trans_amount - $refund_amount;
		$result_amount 	     = $win_amount - $bet_amount;

		$has_both_side 	     = $bet_amount >= $result_amount && $result_amount > 0 ? 1 : 0;
		$start_at 		     = $row['start_at'];
		$end_at 		     = $row['end_at'];

		if ($refund_amount > 0 && $refund_amount >= $trans_amount) {
			$status = Game_logs::STATUS_REFUND;
		} else if ($row['transaction_count'] < 2) {
			$status = Game_logs::STATUS_PENDING;
		} else {
			$status = Game_logs::STATUS_SETTLED;
		}

        $extra = [
            'table' =>  $row['round_id'],
        ];

        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }
        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => $row['game_type'],
                'game' => $row['game_name']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $player_username
            ],
            'amount_info' => [
                'bet_amount' => $bet_amount,
                'result_amount' => $result_amount,
                'bet_for_cashback' => $bet_amount,
                'real_betting_amount' => $bet_amount,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance'],
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['start_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $status,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_id'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => null,
                'sync_index' => null,
                'bet_type' => null
            ],
            'bet_details' => [],
            'extra' => $extra,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
            $row['game_name'] = $row['game_key'];
            $row['game_code'] = $row['game_key'];
            $row['game_type'] = self::TAG_CODE_UNKNOWN_GAME;
        }
        $row['status'] = Game_logs::STATUS_SETTLED;
    }

	private function getGameDescriptionInfo($row, $unknownGame)
	{
		$game_description_id = null;
		$game_name = str_replace("알수없음",$row['game_key'],
					 str_replace("不明",$row['game_key'],
					 str_replace("Unknown",$row['game_key'],$unknownGame->game_name)));
		$external_game_id = $row['game_key'];
        $extra = array('game_code' => $external_game_id,'game_name' => $game_name);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$external_game_id, $game_type, $external_game_id, $extra,
			$unknownGame);
	}


	public function generateUrl($apiName, $params) {
	    return NULL;
	}

    public function isSeamLessGame(){
        return TRUE;
    }

	public function getDataForMerging($player_id, $round_id, $status) {

		$success = FALSE;

		try {

			$this->CI->db->select('userId');
			$this->CI->db->select('player_id');
			$this->CI->db->select('game_type');
			$this->CI->db->select('game_key');
			$this->CI->db->select('game_version');
			$this->CI->db->select('round_id');

			$this->CI->db->select_min('created_at','start_at');
			$this->CI->db->select_max('created_at','end_at');
			$this->CI->db->select_sum("IF(type = 'stake', transaction_stake_payout, 0)",'bet_amount');
			$this->CI->db->select_sum("IF(type = 'refund', transaction_stake_payout, 0)",'refund_amount');
			$this->CI->db->select_sum("IF(type = 'payout', transaction_stake_payout, 0)",'win_amount');
			$this->CI->db->select_max('CONCAT_WS(\'|\',id,after_balance)','after_balance');
			$this->CI->db->from('redtigerseamless_game_logs');

			$this->CI->db->where('player_id', $player_id);
			$this->CI->db->where('round_id', $round_id);

			// $this->CI->db->group_by('casino');
			// $this->CI->db->group_by('channel');
			// $this->CI->db->group_by('affiliate');
			// $this->CI->db->group_by('currency');

			$this->CI->db->group_by('userId');
			$this->CI->db->group_by('player_id');
			$this->CI->db->group_by('game_type');
			$this->CI->db->group_by('game_key');
			$this->CI->db->group_by('game_version');
			$this->CI->db->group_by('round_id');

			$query = $this->CI->db->get();

			$row = $query->row_array();

			if (isset($row, $row['after_balance'])) {
				$row['after_balance'] = @explode('|', $row['after_balance'])[1] ? : 0;
			}

			$player_username = $this->CI->player_model->getUsernameById($player_id);

			$external_uniqueid = implode('|', [$row['userId'], $row['round_id']]);
			$external_uniqueid = hash('sha256', $external_uniqueid);

			$trans_amount 		 = $row['bet_amount'];
			$refund_amount 	     = $row['refund_amount'];
			$win_amount 	     = $row['win_amount'];

			$bet_amount 	     = $trans_amount - $refund_amount;
			$result_amount 	     = $win_amount - $bet_amount;

			$has_both_side 	     = $bet_amount >= $result_amount && $result_amount > 0 ? 1 : 0;
			$start_at 		     = $row['start_at'];
			$end_at 		     = $row['end_at'];

			list($game_description_id, $game_type_id) = $this->processUnknownGame(NULL, NULL, $row['game_key'], $row['game_type'], $row['game_version']);

			$extra = [];
			$extra['trans_amount'] = $trans_amount;

			if ($status) {
				$extra['status'] = $status;
			}

			if ($refund_amount > 0 && $refund_amount >= $trans_amount) {
				$extra['status'] = Game_logs::STATUS_REFUND;
			}

			$success = $this->syncGameLogs(
				$game_type_id,  		# game_type_id
				$game_description_id,	# game_description_id
				$row['game_version'], 	# game_code
				$row['game_type'],		# game_type
				$row['game_key'], 		# game
				$player_id, 			# player_id
				$player_username, 		# player_username
				$bet_amount, 			# bet_amount
				$result_amount, 		# result_amount
				null,					# win_amount
				null,					# loss_amount
				$row['after_balance'],	# after_balance
				$has_both_side, 		# has_both_side
				$external_uniqueid, 	# external_uniqueid
				$start_at,				# start_at
				$end_at,				# end_at
				null,					# response_result_id
				Game_logs::FLAG_GAME,	# flag
				$extra					# extra
			);
			
		} catch (Exception $e) {
			$this->CI->utils->error_log($e->getMessage());
		}

		return $success;
	}

	public function mergeToGameLogs($row_game_username) {

		$success = TRUE;


		if ( ! empty($player_id)) {

			$extra = [];
			$extra['trans_amount'] = $trans_amount;

			if ($status) {
				$extra['status'] = $status;
			}

			if ($refund_amount > 0 && $refund_amount >= $trans_amount) {
				$extra['status'] = Game_logs::STATUS_REFUND;
			}

			$success = $this->syncGameLogs(
				$game_type_id,  		# game_type_id
				$game_description_id,	# game_description_id
				$row_gamereference, 	# game_code
				$game_type_id, 			# game_type
				$row_gamereference, 	# game
				$player_id, 			# player_id
				$player_username, 		# player_username
				$bet_amount, 			# bet_amount
				$result_amount, 		# result_amount
				null,					# win_amount
				null,					# loss_amount
				$row_after_balance,		# after_balance
				$has_both_side, 		# has_both_side
				$external_uniqueid, 	# external_uniqueid
				$start_at,				# start_at
				$end_at,				# end_at
				null,					# response_result_id
				Game_logs::FLAG_GAME,	# flag
				$extra					# extra
			);

		}

		 return $success;

	}

}

/*end of file*/