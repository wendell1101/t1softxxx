<?php

/**
 * General behaviors include :
 * * Go to game module
 * * Check block game platform setting
 * * Create player on game
 * * Prepare go to game
 * * Check if active single game
 *
 * @category Player Management
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
trait gotogame_module {
    public $redirection_iframe = 'iframe';
    public $redirection_newtab = 'newtab';

	/**
	 * overview : go to ag game
	 *
	 * @param string	$siteName
	 * @param string	$gameType
	 */
	public function goto_aggame($siteName, $game_type = null, $is_mobile = null, $mode = 'real') {
		# NOTE: PARAMETER IS PASSED BUT WAS NOT USED
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'operatorglobalsettings'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = AG_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('AG');
            return;
        }

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# GET LOGGED-IN PLAYER
		switch ($mode) {
			case 'trial':
			case 'fun':
				$player_info = $api->getTrialPlayer();
				if (empty($player_info)) {
					die(lang('goto_game.blocked'));
				}
				$player_id = $player_info->playerId;
				$player_name = $player_info->username;
				break;
			case 'real':
				$player_id = $this->authentication->getPlayerId();
				$player_name = $this->authentication->getUsername();
				//if not login
				if (!$this->authentication->isLoggedIn()) {
					$this->goPlayerLogin();
				}
				break;
			default:
		}
        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }
        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }


		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$currency = $this->config->item('default_currency');
			$result = $api->queryForwardGame($player_name, array(
				'language' => $this->language_function->getCurrentLanguage(),
				'game_type' => $game_type,
				'currency' => $currency,
				'is_mobile' => $is_mobile,
			));
			$this->CI->utils->debug_log("AG URL >-------------------------------------> ", $result);
			if($is_mobile){
				redirect($result['url']);
			}else{
				$platformName = $this->external_system->getNameById($game_platform_id);
				$this->load->view('iframe/player/goto_aggame', array('url' => $url, 'platformName' => $platformName));
			}
			return;
		}
	}

	/**
	 * overview : go to agin game
	 *
	 * @param string	$siteName
	 * @param string	$gameType
	 */
	public function goto_agingame($siteName, $game_code = 8, $is_mobile = '_null', $mode = 'real', $app = 'false', $target = 'redirect') {
		$this->utils->debug_log("goto_agingame get is_mobile", $is_mobile);
		$app = $app =='true';

		if($is_mobile == '_null'){
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

		# NOTE: PARAMETER IS PASSED BUT WAS NOT USED

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'operatorglobalsettings'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = AGIN_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('AGIN');
            return;
        }

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# GET LOGGED-IN PLAYER
		$extra = array();
		switch ($mode) {
			case 'trial':
				$extra = array(
					"game_mode" => "demo"
				);
				$player_id = $this->authentication->getPlayerId();
				$player_name = $this->authentication->getUsername();
				//if not login
				if (!$this->authentication->isLoggedIn()) {
					$this->goPlayerLogin();
				}
				break;
			case 'fun':
				$player_info = $api->getTrialPlayer();
				if (empty($player_info)) {
					die(lang('goto_game.blocked'));
				}
				$player_id = $player_info->playerId;
				$player_name = $player_info->username;
				break;
			case 'real':
			default:
				$player_id = $this->authentication->getPlayerId();
				$player_name = $this->authentication->getUsername();
				//if not login
				if (!$this->authentication->isLoggedIn()) {
					$this->goPlayerLogin();
				}
				break;
			// default:
		}

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name,$extra);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api,$extra);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked || (FALSE === $player['success'])) {
            $this->goBlock();
		} else {
			$_transferResult = $this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$currency = $this->config->item('default_currency');
			$result = $api->queryForwardGame($player_name, array(
				'language' => $this->language_function->getCurrentLanguage(),
				'game_code' => $game_code,
				'currency' => $currency,
				'is_mobile' => $is_mobile,
				'game_mode' => $mode,
				'app' => $app
			));
			$this->playerTrackingEventPlayNow($game_platform_id, $result, ['game_type' => "live_dealer"], $_transferResult);
			$this->CI->utils->debug_log("AGIN URL >-------------------------------------> ", $result);
			$platformName = $this->external_system->getNameById($game_platform_id);
			if($result['success'] && !empty($result['url'])){
				if($app){
					$login_text = lang('Click icon to login');
					$download_text = lang('Click to download app');
					$image_url = $this->utils->getSystemUrl('m', '/includes/images/ag_icon.png');
					return $this->load->view('iframe/player/goto_agapp', array(
						'url' => $result['url'],
						'image_url' =>$image_url,
						'platformName' => $platformName,
						'login_text' => $login_text,
						'download_text'=> $download_text
						)
					);
				}

				// if (!$is_mobile && $target == 'iframe' && strpos($result['url'], 'https://') !== false) {
				if (!$is_mobile && $target == 'iframe') {
					$this->load->view('iframe/game_iframe', array('url' => $result['url'], 'platformName' => $platformName));
					return;
				} else {
					redirect($result['url']);
				}
			}

			return $this->goto_maintenance('agin');
		}
	}

	/**
	 * overview : go to agbbin game
	 *
	 * @param string	$siteName
	 * @param string	$gameType
	 */
	public function goto_agbbingame($siteName, $game_type = null, $is_mobile = null) {
		# NOTE: PARAMETER IS PASSED BUT WAS NOT USED
		$this->utils->debug_log("goto_agingame get is_mobile", $is_mobile);

		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'operatorglobalsettings'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = AGBBIN_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('AGBBIN');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);
		$this->CI->utils->debug_log("AGBBIN ISPLAYER EXIST >-------------------------------------> ", $player);
		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			// $currency = $this->config->item('default_currency');
			$result = $api->queryForwardGame($player_name, array(
				'language' => $this->language_function->getCurrentLanguage(),
				'game_type' => $game_type,
				'is_mobile' => $is_mobile,
			));
			$this->CI->utils->debug_log("AGBBIN URL >-------------------------------------> ", $result);

            #OGP-5970
            redirect($result['url']);
			return;
		}
	}

	/**
	 * overview : go to agbbin game
	 *
	 * @param string	$siteName
	 * @param string	$gameType
	 */
	public function goto_agmggame($siteName, $game_type = null, $is_mobile = null) {
		# NOTE: PARAMETER IS PASSED BUT WAS NOT USED
		$this->utils->debug_log("goto_agmggame get is_mobile", $is_mobile);

		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'operatorglobalsettings'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = AGMG_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('AGMG');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);
		$this->CI->utils->debug_log("AGMG ISPLAYER EXIST >-------------------------------------> ", $player);
		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			// $currency = $this->config->item('default_currency');
			$result = $api->queryForwardGame($player_name, array(
				'language' => $this->language_function->getCurrentLanguage(),
				'game_type' => $game_type,
				'is_mobile' => $is_mobile,
			));
			$this->CI->utils->debug_log("AGMG URL >-------------------------------------> ", $result);

            #OGP-5970
            redirect($result['url']);
			return;
		}
	}

	/**
	 * overview : go to ag hg game
	 *
	 * @param string	$siteName
	 * @param string	$gameType
	 */
	public function goto_aghggame($siteName, $game_type = null, $is_mobile = null) {
		# NOTE: PARAMETER IS PASSED BUT WAS NOT USED
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'operatorglobalsettings'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = AGHG_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('AGHG');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$currency = $this->config->item('default_currency');
			$result = $api->queryForwardGame($player_name, array(
				'language' => $this->language_function->getCurrentLanguage(),
				'game_type' => $game_type,
				'currency' => $currency,
				'is_mobile' => $is_mobile,
			));
			$this->CI->utils->debug_log("AGHG URL >-------------------------------------> ", $result);
			if($is_mobile){
				redirect($result['url']);
			}else{
				$platformName = $this->external_system->getNameById($game_platform_id);
				$this->load->view('iframe/player/goto_aggame', array('url' => $url, 'platformName' => $platformName));
			}
			return;
		}
	}

	/**
	 * overview : go to agpt game
	 *
	 * @param string	$siteName
	 * @param string	$gameType
	 */
	public function goto_agptgame($siteName, $game_type = null, $is_mobile = null) {
		# NOTE: PARAMETER IS PASSED BUT WAS NOT USED
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'operatorglobalsettings'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = AGPT_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('AGPT');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$currency = $this->config->item('default_currency');
			$result = $api->queryForwardGame($player_name, array(
				'language' => $this->language_function->getCurrentLanguage(),
				'game_type' => $game_type,
				'currency' => $currency,
				'is_mobile' => $is_mobile,
			));
			$this->CI->utils->debug_log("AGPT URL >-------------------------------------> ", $result);
			if($is_mobile){
				redirect($result['url']);
			}else{
				$platformName = $this->external_system->getNameById($game_platform_id);
				$this->load->view('iframe/player/goto_aggame', array('url' => $url, 'platformName' => $platformName));
			}
			return;
		}
	}

	/**
	 * overview : go to agashaba game
	 *
	 * @param $siteName
	 * @param null $gameType
	 */
	public function goto_agshabagame($siteName, $game_type = null, $is_mobile = null, $mode='real') {
		# NOTE: PARAMETER IS PASSED BUT WAS NOT USED

		$this->utils->debug_log("goto_agshabagame get is_mobile", $is_mobile);

		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'operatorglobalsettings'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = AGSHABA_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('AG SHABA');
            return;
        }

        # LOAD GAME API
        $api = $this->utils->loadExternalSystemLibObject($game_platform_id);

        # GET LOGGED-IN PLAYER
        switch ($mode) {
            case 'trial':
                $extra = array(
                    "game_mode" => "demo"
                );
                $player_id = $this->authentication->getPlayerId();
                $player_name = $this->authentication->getUsername();
                //if not login
                if (!$this->authentication->isLoggedIn()) {
                    $this->goPlayerLogin();
                }
                break;
            case 'fun':
                $player_info = $api->getTrialPlayer();
                if (empty($player_info)) {
                    die(lang('goto_game.blocked'));
                }
                $player_id = $player_info->playerId;
                $player_name = $player_info->username;
                break;
            case 'real':
                $player_id = $this->authentication->getPlayerId();
                $player_name = $this->authentication->getUsername();
                //if not login
                if (!$this->authentication->isLoggedIn()) {
                    $this->goPlayerLogin();
                }
                break;
            default:
        }


        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name,array('game_platform' => AGSHABA_API));
        $this->CI->utils->debug_log("isPlayerExist AGSHABA_API >-------------------------------------> ", $player);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$currency = $this->config->item('default_currency');
			$result = $api->queryForwardGame($player_name, array(
				'language' => $this->language_function->getCurrentLanguage(),
				'game_type' => $game_type,
				'currency' => $currency,
				'is_mobile' => $is_mobile,
			));

			$this->CI->utils->debug_log("AGSHABA URL >-------------------------------------> ", $result);
			if($is_mobile){
				redirect($result['url']);
			}else{
				$bg_image = $this->utils->getConfig('agshaba_background_image');
				$bg_color = !empty($this->utils->getConfig('agshaba_background_color')) ? $this->utils->getConfig('agshaba_background_color') : "#000000";
				// $image_url = $this->utils->getSystemUrl('www') . '/includes/img/' . $bg_image;
				$image_url = $this->utils->getSystemUrl('www', '/includes/img/' . $bg_image);
				$platformName = $this->external_system->getNameById($game_platform_id);
				$this->load->view('iframe/player/goto_agshabagame', array(
					'url' => $result['url'],
					'platformName' => $platformName,
					'image_url' => $image_url,
					'bg_color'  => $bg_color
				));

			}
			return;
		}
	}

	/**
	 * overview : go to gsag game
	 *
	 * @param string $gameType
	 */
	public function goto_gsaggame($gameType = null, $is_mobile = null) {

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'operatorglobalsettings'));

		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = GSAG_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('GSAG');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();


        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$lang = $this->language_function->getCurrentLanguage() == Language_function::INT_LANG_ENGLISH ? 3 : 1; # 3 is english, 1 is chinese
			$url = $api->queryForwardGame($player_name, array(
				'gameType' => $gameType,
				'lang' => $lang,
				'is_mobile' => $is_mobile,
			))['url'];
			$platformName = $this->external_system->getNameById($game_platform_id);
			$this->load->view('iframe/player/goto_aggame', array('url' => $url, 'platformName' => $platformName));
			return;
		}
	}

	/**
     * overview : go to ab game
     */
    public function goto_abgame($is_mobile = null) {
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'operatorglobalsettings'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = AB_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('AB');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if($player['exists'] !== null){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$_transferResult = $this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$lang = $this->language_function->getCurrentLanguage();
			$extra['language'] = $lang;
			$extra['is_mobile'] = $is_mobile;
			$result = $api->queryForwardGame($player_name, $extra);
            $this->playerTrackingEventPlayNow($game_platform_id, $result, ['game_type' => 'live_dealer'], $_transferResult);
			$this->CI->utils->debug_log("AllBet queryForwardGame: ", $result);

			if($this->authentication->isLoggedIn() && isset($url)){
				$this->game_provider_auth->setPlayerStatusOnline($game_platform_id, $player_id);
			}

            if (!empty($result['forward_url'])) {
                return redirect($result['forward_url']);
            }

            if(!empty($result) && !empty($result['url'])){
	            if($is_mobile){
	            	redirect($result['url']);
	            	return;
	            }else{
	            	if($result['is_redirect'] == true) {
	            		redirect($result['url']);
	            		return;
	            	} else {
						$platformName = $this->external_system->getNameById($game_platform_id);
						$this->load->view('iframe/player/goto_abgame', array('url' => $result['url'], 'platformName' => $platformName));
						return;
	            	}
	            }
            }
		}

		$this->goto_maintenance('allbet');
	}

	/**
	 * overview : go to asialong game
	 */
	public function goto_asialonggame() {
		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'operatorglobalsettings'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = ASIALONG_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('ASIALONG');
            return;
        }

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# Check login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
			return;
		}
		$player_id = $this->authentication->getPlayerId();
		$player_username = $this->authentication->getUsername();

        # User login blocked
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# CHECK PLAYER IF EXIST
		$player_exists = $api->isPlayerExist($player_username);

		# IF NOT CREATE PLAYER
		if ($player_exists['success'] == true && !$player_exists['exists']) {
			$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_username);

		if ($blocked || (FALSE === $player_exists['success'])) {
			die(lang('goto_game.blocked'));
		} else {
			$this->_transferAllWallet($player_id, $player_username, $game_platform_id);
			$url = $api->queryForwardGame($player_username);
			$this->CI->utils->debug_log("ASIALONG queryForwardGame", $url);
			if(!empty($url)) {
				redirect($url);
				return;
			}
			return $this->goto_maintenance('asialong');
		}
	}

	/**
	 * overview : go to beteast game
	 */
	public function goto_beteastgame($is_mobile = null) {
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'operatorglobalsettings'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = BETEAST_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('BETEAST');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			switch ($this->language_function->getCurrentLanguage()) {
                case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                    $lang = 'zh-CHS';
                    break;
                case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
                    $lang = 'ko-KR';
                    break;
                default:
                    $lang = 'en-US';
                    break;
            }
            $extra['language'] = $lang;
            $extra['is_mobile'] = $is_mobile;
            $url = $api->queryForwardGame($player_name, $extra)['url'] . '&locale=' . $lang;
            $platformName = $this->external_system->getNameById($game_platform_id);
            $this->load->view('iframe/player/goto_beteastgame', array('url' => $url, 'platformName' => $platformName));
            return;
		}
	}

	/**
	 * overview : go to ezugi game
	 */
	public function goto_ezugigame($provider = 1, $is_mobile = null, $lobby = null) {
        if($lobby == '_null') {
            $lobby = null;
        }

		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'operatorglobalsettings'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = EZUGI_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('EZUGI');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$extra['language'] = $this->language_function->getCurrentLanguage();
			$extra['is_mobile'] = $is_mobile;
			$extra['game_code'] = $provider;
            $extra['lobby'] = $lobby;
			$url = $api->queryForwardGame($player_name,$extra);
			$platformName = $this->external_system->getNameById($game_platform_id);
			$this->utils->debug_log('goto ezugi game=>', $url['url']);
			$this->load->view('iframe/game_iframe', array('url' => $url['url'], 'platformName' => $platformName));
			return;
		}
	}

	/**
	 * overview : go to DT game
	 */
	public function goto_dtgame($game_code, $game_mode = "real", $is_mobile = null) {

		$is_mobile = $this->utils->is_mobile();
		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'operatorglobalsettings'));

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = DT_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('DT');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();


        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

		//if fun game
		if ($game_mode == "fun" ||$game_mode == "demo" || $game_mode == "trial") {
			$fun_game_url = json_decode($api->SYSTEM_INFO['extra_info'], true)['fun_game_url'];
			$language = $this->language_function->getCurrentLanguage();
			$url = $fun_game_url . "?gameCode=" . $game_code . "&isfun=1&type=dt&language=" . $language;
			if($is_mobile){
				redirect($url);
			}else{
				$this->load->view('iframe/game_iframe', array('url' => $url, 'platformName' => $platformName));
			}
			return;
		}
		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

        $this->checkGameIfLaunchable($game_platform_id,$game_code);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$_transferResult = $this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			//language only chinese for game
			$extra['language'] = $this->language_function->getCurrentLanguage();
			$extra['game_code'] = $game_code;
			$extra['game_mode'] = $game_mode;
			$extra['is_mobile'] = $is_mobile;
			$url = $api->queryForwardGame($player_name,$extra);
			$this->playerTrackingEventPlayNow($game_platform_id, $url, $extra, $_transferResult);
			$this->utils->debug_log('goto dt game=>', $url['url']);
			if($is_mobile){
				redirect($url['url']);
			}else{
				$this->load->view('iframe/game_iframe', array('url' => $url['url'], 'platformName' => $platformName));
			}
			return;
		}
	}

	/**
	 * overview : go to SA GAMING game
	 */
	public function goto_sagaminggame($gamecode = null, $target = "iframe") {
		$is_mobile = $this->utils->is_mobile();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'operatorglobalsettings'));

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = SA_GAMING_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('SAGAMING');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();


        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

        $this->checkGameIfLaunchable($game_platform_id,$gamecode);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists']) {
			$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$_transferResult = $this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$lang = $this->language_function->getCurrentLanguage();
			$extra['language'] = $lang;
			$extra['game_code'] = $gamecode;
			$extra['is_mobile'] = $is_mobile==1?"true":"false";

			$data = $api->queryForwardGame($player_name, $extra);
			$this->playerTrackingEventPlayNow($game_platform_id, $data, $extra, $_transferResult);
			$game_favicon = $this->utils->getSystemUrl('www', 'sa-favicon.ico');
			$data['is_iframe'] = strtolower($target) == 'iframe' && !$is_mobile;
			$data['platformName'] = $platformName;
			$data['game_favicon'] = $game_favicon;
			$data['is_mobile'] = $is_mobile;
			$data['allow_fullscreen'] = true;
			if (isset($data['success']) && $data['success']) {
				$this->CI->utils->debug_log('redirect SA GAMING URL ==========================>', $data['url']);

		        $is_redirect = $api->getSystemInfo('is_redirect', false);

		        if ($is_redirect) {
					redirect($data['url']);
					return;
		        }

                // redirect($data['url']);
                if($data['is_mobile']){
                    redirect($data['url']);
                }else{
                    $this->load->view('iframe/game_iframe', $data);
                }
            } else {
                die(lang('goto_game.error'));
            }
			return;
		}
	}



    /**
     * overview : go to Pragmatic play game
     * mode: real or fun
     */
    public function goto_pragmaticplaygame($game_code = null,$mode = "real",$game_platform_id=null) {
        $is_mobile = $this->utils->is_mobile();

        # LOAD MODEL AND LIBRARIES
        $this->load->model(array('game_provider_auth', 'external_system', 'operatorglobalsettings'));

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

        # DECLARE WHICH GAME PLATFORM TO USE
        $game_platform_id = is_null($game_platform_id) ? PRAGMATICPLAY_API : $game_platform_id;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('PRAGMATICPLAY');
            return;
        }

        # GET LOGGED-IN PLAYER
        $player_id = $this->authentication->getPlayerId();
        $player_name = $this->authentication->getUsername();


        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

        # LOAD GAME API
        $api = $this->utils->loadExternalSystemLibObject($game_platform_id);
        $platformName = $this->external_system->getNameById($game_platform_id);

        //if not login
        if (!$this->authentication->isLoggedIn() && (!$api->required_login_on_launching_trial && $mode == 'real')) {
            $this->goPlayerLogin();
        }

        $this->checkGameIfLaunchable($game_platform_id,$game_code);

        $blocked = false;
        if($this->authentication->isLoggedIn()) {
            # CHECK PLAYER IF EXIST
            $player = $api->isPlayerExist($player_name);

            # IF NOT CREATE PLAYER
            if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
                if(!is_null($player['exists'])){
                    $this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
                }
            }

            # CHECK IF LOGGED-IN PLAYER IS BLOCKED
            $blocked = $api->isBlocked($player_name);
        }

        # CHECK IF GAMEPLATFORM SETTING IS BLOCKED
        $this->checkBlockGamePlatformSetting($game_platform_id);


        if ($blocked) {
            $this->goBlock();
        } else {

        	$_transferResult = [];
            if($this->authentication->isLoggedIn()) { //transfer all only if loggedin
                $_transferResult = $this->_transferAllWallet($player_id, $player_name, $game_platform_id);
            }

            $language = $this->language_function->getCurrentLanguage();

            $extra['game_code'] = $game_code;
            $extra['language'] = $language;
            $extra['is_mobile'] = $is_mobile;
            $extra['game_mode'] = $mode;
            $extra['cashierURL'] = base_url().'iframe_module/iframe_viewMiniCashier';

            $data = $api->queryForwardGame($player_name, $extra);
            $this->playerTrackingEventPlayNow($game_platform_id, $data, $extra, $_transferResult);
            if (isset($data['success']) && $data['success']) {
                $this->CI->utils->debug_log('redirect PP URL ==========================>', $data['url']);
                // redirect($data['url']);
                $result = array(
                    'url' => $data['url'],
                    'platformName' => $platformName,
                );
                $is_redirect = $api->getSystemInfo('is_redirect', false);
                if($is_mobile || $is_redirect){
                    redirect($result['url']);
                }else{
                    $this->load->view('iframe/game_iframe', $result);
                }
            } else {
                die(lang('goto_game.error'));
            }
            return;
        }
    }

	/**
	 * overview : go to Pragmatic play game
	 * mode: real or fun
	 */
	public function goto_pragmaticplaygame_common($game_platform_id = null, $game_code = null,$mode = "real") {
		$is_mobile = $this->utils->is_mobile();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'operatorglobalsettings', 'game_description_model'));

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('PRAGMATICPLAY');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();


        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->game_description_model->getGameNameByCurrentLang($game_code, $game_platform_id);
		$favicon_brand = $api->getSystemInfo('favicon', false);

		//if not login
        if (!$this->authentication->isLoggedIn() && strtolower($mode) != 'fun') {
			$this->goPlayerLogin();
		}

        $this->checkGameIfLaunchable($game_platform_id,$game_code);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
            $this->goBlock();
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$language = $this->language_function->getCurrentLanguage();

			$extra['game_code'] = $game_code;
			$extra['language'] = $language;
			$extra['is_mobile'] = $is_mobile;
			$extra['game_mode'] = $mode;
			$extra['cashierURL'] = base_url().'iframe_module/iframe_viewMiniCashier';

			$data = $api->queryForwardGame($player_name, $extra);
			if (isset($data['success']) && $data['success']) {
				$this->CI->utils->debug_log('redirect PP URL ==========================>', $data['url']);
				// redirect($data['url']);
				$result = array(
					'url' => $data['url'],
					'platformName' => $platformName,
					'favicon_brand' => $favicon_brand,
				);
                $is_redirect = $api->getSystemInfo('is_redirect', false);
                if($is_mobile || $is_redirect){
					redirect($result['url']);
				}else{
					$this->load->view('iframe/game_iframe', $result);
				}
			} else {
				die(lang('goto_game.error'));
			}
			return;
		}
	}

	/**
	 * overview : go to PNG game
	 * mode: real or fun
	 */
	public function goto_pnggame($game_code = null,$mode = "real") {
		$is_mobile = $this->utils->is_mobile();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'operatorglobalsettings'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = PNG_API;
        $this->checkGameIfLaunchable($game_platform_id,null,$game_code,null,true);

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('PNG');
            return;
        }

        # LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$api_allowed_demo_without_login = $api->getSystemInfo('api_allowed_demo_without_login', false);
		$this->utils->debug_log('<==== PNG GAME DEMO api_allowed_demo_without_login ====>', $api_allowed_demo_without_login);

		// FOR CLIENT THAT ALLOW LAUNCHING DEMO GAMES WITHOUT LOGIN IN PLAYER CENTER
        if ($api_allowed_demo_without_login == true && $mode != 'real') {
			$this->utils->debug_log('<==== PNG GAME DEMO SUCCESS IF ====>');

			$protocol = $this->utils->ishttps()?"https://":"http://";
			$extra['game_code'] = $game_code;
			$extra['language'] = $this->language_function->getCurrentLanguage();
			$extra['is_mobile'] = $is_mobile;
			$extra['game_mode'] = $mode;
			$extra['httphost'] = $protocol.$this->utils->getHttpHost();

			$data = $api->queryForwardGame(null, $extra);
			if (isset($data['success']) && $data['success']) {
				if($is_mobile){
					redirect($data['script_inc']);
				}else{
					$this->load->view('common_games/goto_pnggame', $data);
				}
			} else {
				die(lang('goto_game.error'));
			}
			return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# LOAD GAME API
		$platformName = $this->external_system->getNameById($game_platform_id);

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$protocol = $this->utils->ishttps()?"https://":"http://";
			$extra['game_code'] = $game_code;
			$extra['language'] = $this->language_function->getCurrentLanguage();
			$extra['is_mobile'] = $is_mobile;
			$extra['game_mode'] = $mode;
			$extra['httphost'] = $protocol.$this->utils->getHttpHost();

			$data = $api->queryForwardGame($player_name, $extra);
			if (isset($data['success']) && $data['success']) {
				if($is_mobile){
					redirect($data['script_inc']);
				}else{
					$this->load->view('common_games/goto_pnggame', $data);
				}
			} else {
				die(lang('goto_game.error'));
			}
			return;
		}
	}

	/**
	 * overview : go to IDN game
	 */
	public function goto_idngame($is_mobile = null) {
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'operatorglobalsettings'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = IDN_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('IDN');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
            $this->goBlock();
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$extra['language'] = $this->language_function->getCurrentLanguage();
			$extra['is_mobile'] = $is_mobile;
			$url = $api->queryForwardGame($player_name, $extra);

			$platformName = $this->external_system->getNameById($game_platform_id);
			$this->utils->debug_log('goto IDN API game=>', $url['url']);
			$this->load->view('iframe/game_iframe', array('url' => $url['url'], 'platformName' => $platformName));
			return;
		}
	}

	/**
	 * overview : go to Yoplay game
	 * mode: real or fun
	 */
	public function goto_yoplaygame($gamecode = null) {
		$is_mobile = $this->utils->is_mobile();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'operatorglobalsettings'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = YOPLAY_API;

        $this->checkGameIfLaunchable($game_platform_id,null,$gamecode,null,true);

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('YOPLAY');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

		#BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists']) {
			$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
		}

	   # CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$extra['game_code'] = $gamecode;
			$extra['language'] = $this->language_function->getCurrentLanguage();
			$extra['is_mobile'] = $is_mobile;
			// $extra['mode'] = $mode;
			$data = $api->queryForwardGame($player_name, $extra);

			if (isset($data['success']) && $data['success']) {
				if($is_mobile){
					redirect($data['url']);
				}else{
					$this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName));
				}
			} else {
				die(lang('goto_game.error'));
			}
			return;
		}
	}

	/**
	 * overview : go to Suncity game
	 * mode: real or fun
	 */
	public function goto_suncity($gprovidercode = null, $gamecode = 'lobby', $mode = 'real') {
		$is_mobile = $this->utils->is_mobile();

		$extra = array(
			'is_demo_flag' => false
		);

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'operatorglobalsettings'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = SUNCITY_API;

		$maintenance_string = 'SUNCITY';

		if ($gprovidercode == 'AG') {
			$game_platform_id = TGP_AG_API;
			$maintenance_string = 'AsiaGaming';
		}

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance($maintenance_string);
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

		#BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

		# demo
		if($mode != 'real'){
			$extra['is_demo_flag'] = true;
		}

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name,$extra);


		# IF NOT CREATE PLAYER
		if($mode != 'real'){
			$password = $api->getPassword($player_name);
			if(is_null($player)){
				$createPlayer = $api->createPlayer($player_name, $player_id, $password,null, $extra);
			}
		}else{
			if(isset($player['exists']) && !$player['exists'] && $player['success']==true) {
				if(!is_null($player['exists'])){
					$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
				}
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

            $extra['game_type'] = $gprovidercode;
			$extra['game_code'] = $gamecode;
			$extra['language'] = $this->language_function->getCurrentLanguage();
			$extra['is_mobile'] = $is_mobile;


			$data = $api->queryForwardGame($player_name, $extra);

			if (isset($data['success']) && $data['success']) {
				if($is_mobile){
					redirect($data['url']);
				}else{
					$this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName));
				}
			} else {
				die(lang('goto_game.error'));
			}
			return;
		}
	}

	public function goto_asia_gaming($gprovidercode = null, $gamecode = 'lobby', $mode = 'real')
	{
		$this->goto_suncity($gprovidercode, $gamecode, $mode);
	}

	/**
	 * overview : goto RTG game
	 * gameid : game id
	 * machid : machine id
	 * mode: real or fun
	 */
	public function goto_rtg($gameid = null, $machid = null, $mode = 'real') {
		$is_mobile = $this->utils->is_mobile();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = RTG_API;
        $this->checkGameIfLaunchable($game_platform_id,$gameid,$machid,null,true);

		$language = $this->language_function->getCurrentLanguage();
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('RTG');
            return;
        }

		# IF FUN/DEMO MODE Launc
		if(strtolower($mode) == 'fun' || strtolower($mode) == 'demo'){
			$game_url = $api->getSystemInfo('game_launcher_url');
			$params = array(
				'cdkModule' => 'gameLauncher',
				'skinid' => $api->getLauncherLanguage($language),
				'forReal' => 'false',
				'gameId' => $gameid,
				'machId' => $machid,
				'betDenomination' => 0,
				'numOfHands' => 0,
				'width' => 'auto',
				'height' => 'auto',
				'returnurl' => '',
			);
			$url = $game_url.'?'.http_build_query($params);
			if($is_mobile){
				redirect($url);
			}else{
				return $this->load->view('iframe/game_iframe', array('url' => $url, 'platformName' => $platformName));
			}
		}

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

		#BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists']) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$extra['game_code'] = $gameid;
			$extra['machid'] = $machid;
			$extra['language'] = $language;
			$extra['is_mobile'] = $is_mobile;
			$extra['is_demo_flag'] = $mode == 'real'?'true':'false';

			$data = $api->queryForwardGame($player_name, $extra);

			if ($data['success']) {
				if($is_mobile){
					redirect($data['url']);
				}else{
					$this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName));
				}
			} else {
				die(lang('goto_game.error'));
			}
			return;
		}
	}

	/**
	 * overview : goto RTG game
	 * gameid : game id
	 * machid : machine id
	 * mode: real or fun
	 */
	public function goto_rtgmaster($game_code = null, $game_mode = 'real') {
		$is_mobile = $this->utils->is_mobile();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = RTG_MASTER_API;
		$language = $this->language_function->getCurrentLanguage();
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('RTG MASTER');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

		#BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}
		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists']) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$lobbyurl = $is_mobile ? $this->utils->getSystemUrl('m').$this->utils->getConfig('lobby_extension') : $this->utils->getSystemUrl('player');

			$extra = array(
				'game_code' => $game_code,
				'game_mode' => $game_mode,
				'language' => $language,
				'is_mobile' => $is_mobile,
				'lobby_url' => $lobbyurl
			);

			$data = $api->queryForwardGame($player_name, $extra);

			if ($data['success']) {
				if($is_mobile){
					redirect($data['url']);
				}else{
					$this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName));
				}
			} else {
				die(lang('goto_game.error'));
			}
			return;
		}
	}

	/**
	 * overview : goto KYCARD game
	 * game_code : game code
	 */
	public function goto_kycard($game_code = null) {
		$is_mobile = $this->utils->is_mobile();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = KYCARD_API;
		$language = $this->language_function->getCurrentLanguage();
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('KYCARD');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

		#BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists']) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$extra['game_code'] = $game_code;
			$extra['language'] = $language;
			$extra['is_mobile'] = $is_mobile;

			$data = $api->queryForwardGame($player_name, $extra);

			if ($data['success']) {
				if($is_mobile){
					redirect($data['url']);
				}else{
					$this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName));
				}
			} else {
				die(lang('goto_game.error'));
			}
			return;
		}
	}

	/**
	 * overview : goto PGSOFT game
	 * game_code : game code
	 */
	public function goto_pgsoft($game_code = 0,$game_mode = "real") {
		$is_mobile = $this->utils->is_mobile();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = PGSOFT_API;
		$language = $this->language_function->getCurrentLanguage();
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('PGSOFT');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();
		$blocked = false;

		#BLOCK LOGIN GAME BY USER STATUS
		if($game_mode == 'real'){

	        if($this->CI->utils->blockLoginGame($player_id)){
	            $this->goBlock();
	            return;
	        }

			//if not login
			if (!$this->authentication->isLoggedIn()) {
				$this->goPlayerLogin();
			}

			# CHECK PLAYER IF EXIST
			$player = $api->isPlayerExist($player_name);

			# IF NOT CREATE PLAYER
			if (isset($player['exists']) && !$player['exists']) {
				if(!is_null($player['exists'])){
					$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
				}
			}

			# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
			$this->checkBlockGamePlatformSetting($game_platform_id);

			# CHECK IF LOGGED-IN PLAYER IS BLOCKED
			$blocked = $api->isBlocked($player_name);

	    }

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$_transferResult = $this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$extra['game_code'] = $game_code;
			$extra['game_mode'] = $game_mode;
			$extra['language'] = $language;
			$extra['is_mobile'] = $is_mobile;
			$extra['home_url'] = $this->get_lobby_url($api->getSystemInfo('return_slot_url',null));
			// $extra['home_url'] = $this->utils->getSystemUrl('www');

			$data = $api->queryForwardGame($player_name, $extra);
			$this->playerTrackingEventPlayNow($game_platform_id, $data, $extra, $_transferResult);
			if ($data['success']) {
				if($api->getSystemInfo('enabled_new_queryforward') && isset($data['is_html']) && $data['is_html'] == true){
					$this->load->view(
						'iframe/pgsoft_game_iframe', 
						array(
							'platformName' => $platformName,
							'html' => $data['html']
						)
					);
					return;
				} else {
					if($is_mobile){
					redirect($data['url']);
					}else{
						$this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName));
					}
				}
			} else {
				die(lang('goto_game.error'));
			}
			return;
		}
	}


	/**
	 * overview : goto PGSOFT game
	 * game_code : game code
	 */
	public function goto_goldenf_pgsoft($game_code = null,$game_mode = "real") {
		$is_mobile = $this->utils->is_mobile();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = GOLDENF_PGSOFT_API;
		$language = $this->language_function->getCurrentLanguage();
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('GOLDENF_PGSOFT');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

		#BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		//if not login
		if($game_mode == 'real'){
			if (!$this->authentication->isLoggedIn()) {
				$this->goPlayerLogin();
			}
		}

		if($game_mode == 'real'){
			# CHECK PLAYER IF EXIST
			$player = $api->isPlayerExist($player_name);

			# IF NOT CREATE PLAYER
			if (isset($player['exists']) && !$player['exists'] ) {
				if(!is_null($player['exists'])){
					$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
				}
			}
		}

		$blocked = false;
		if($game_mode == 'real'){
			# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
			$this->checkBlockGamePlatformSetting($game_platform_id);

			# CHECK IF LOGGED-IN PLAYER IS BLOCKED
			$blocked = $api->isBlocked($player_name);
		}

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$extra['game_code'] = $game_code;
			$extra['game_mode'] = $game_mode;
			$extra['language'] = $language;
			$extra['is_mobile'] = $is_mobile;
			$data = $api->queryForwardGame($player_name, $extra);

			if ($data['success']) {
				if($is_mobile){
					redirect($data['url']);
				}else{
					$this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName));
				}
			} else {
				die(lang('goto_game.error'));
			}
			return;
		}
	}

	/**
	 * overview : goto CQ9 game
	 * game_code : game code
	 */
	public function goto_cq9($game_code=null, $game_mode = "real"){
		$is_mobile = $this->utils->is_mobile();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = CQ9_API;
		$language = $this->language_function->getCurrentLanguage();
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('CQ9');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

		#BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

    	// For demo mode
    	if ($game_mode == 'demo' || $game_mode == 'trial' || $game_mode == false) {

			$demo_url = $api->getSystemInfo('demo_url', 'https://demo.cqgame.games/');
			// $demo_token = $api->getSystemInfo('demo_token', 'guest123');
			// $demo_language = $api->getLauncherLanguage($language);
			// $demo_url .= '/'.$game_code.'?';
			// $demo_url .= 'language='.$demo_language;
			// $demo_url .= '&token='.$demo_token;
    		if($is_mobile){
				redirect($demo_url);
			}else{
				$this->load->view('iframe/game_iframe', array('url' => $demo_url, 'platformName' => $platformName));
			}
			return;
    	}

        $this->checkGameIfLaunchable($game_platform_id,$game_code);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists']) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$_transferResult = $this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$extra['game_code'] = $game_code;
			$extra['language'] = $language;
			$extra['is_mobile'] = $is_mobile;
			$extra['gamehall'] = $api->getSystemInfo('gamehall', 'cq9');;
			$extra['game_mode'] = $game_mode;

			$data = $api->queryForwardGame($player_name, $extra);
			$this->playerTrackingEventPlayNow($game_platform_id, $data, $extra, $_transferResult);
			if ($data['success']) {
				if($is_mobile){
					redirect($data['url']);
				}else{
					$this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName));
				}
			} else {
				die(lang('goto_game.error'));
			}
			return;
		}
	}



	public function goto_yuxing($game_mode = 'real', $redirection = 'iframe'){
		$is_mobile = $this->utils->is_mobile();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = YUXING_CQ9_GAME_API;
		$language = $this->language_function->getCurrentLanguage();
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('YUXING CQ9');
            return;
        }


		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

		#BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

    	// For demo mode
    	if ($game_mode == 'demo' || $game_mode == 'trial' || $game_mode == false) {

			$response = $api->getYuxingTrialGameList();
    		if($is_mobile || $redirection != 'iframe'){
				redirect($response['url']);
			}else{
				$this->load->view('iframe/game_iframe', array('url' => $response['url'], 'platformName' => $platformName));
			}
			return;
    	}
		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists']) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$extra['language'] = $language;
			$extra['is_mobile'] = $is_mobile;
			$extra['game_mode'] = $game_mode;

			$data = $api->queryForwardGame($player_name, $extra);

			if ($data['success']) {
				if($is_mobile || $redirection != 'iframe'){
					redirect($data['url']);
				}else{
					$this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName));
				}
			} else {
				die(lang('goto_game.error'));
			}
			return;
		}
	}

	/**
	 * overview : go to mwg game
	 * mode: real or fun
	 */
	public function goto_mwg($game_code = 'lobby') {
		$is_mobile = $this->utils->is_mobile();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = MWG_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('MWG');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

		#BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

		# CHECK PLAYER IF EXIST
		$extra['language'] = $this->language_function->getCurrentLanguage();
		$player = $api->isPlayerExist($player_name, $extra);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists']) {
			$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$extra['game_code'] = $game_code;
			$extra['is_mobile'] = $is_mobile;

			$data = $api->queryForwardGame($player_name, $extra);

			if (isset($data['success']) && $data['success']) {
				if($is_mobile){
					redirect($data['url']);
				}else{
					if(isset($data['jumpType'])&&$data['jumpType']==3){
						$this->load->view('iframe/player/goto_mwg', $data);
					}else{
						$this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName));
					}
				}
			} else {
				die(lang('goto_game.error'));
			}
			return;
		}
	}

	/**
	 * overview : check block game platform setting
	 *
	 * @param int	$game_platform_id
	 */
	private function checkBlockGamePlatformSetting($game_platform_id) {
		$this->load->model('operatorglobalsettings');
		$blockedGame = json_decode($this->operatorglobalsettings->getSettingValue('blocked_game_setting'), true); // caulse problem , fix it later
		if (isset($blockedGame[$game_platform_id]) && $blockedGame[$game_platform_id]) {
			#die(lang('goto_game.sysMaintenance'));
			$this->goto_maintenance('blocked');
			return;
		}
	}

	/**
	 * overview : go to pt game
	 * @param string	$siteName
	 * @param string	$gameCode
	 * @param string 	$gameMode
	 * @param string 	$mobile
	 */
	public function goto_ptgame($siteName, $gameCode, $gameMode = 'real', $mobile = '',$support = false, $merchant_code = null) {
		$is_mobile = $this->utils->is_mobile();
		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'static_site'));

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = PT_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('PT');
            return;
        }

        $this->checkGameIfLaunchable($game_platform_id,$gameCode);

		# Load the config for the game platform api
		$apiPT = $this->utils->loadExternalSystemLibObject($game_platform_id);

		// $this->CI->utils->debug_log('SYSTEM_INFO', $apiPT->SYSTEM_INFO, 'API_PLAY_PT', $apiPT->getSystemInfo('API_PLAY_PT'));

		$api_play_pt = $apiPT->getSystemInfo('API_PLAY_PT');
		$required_login_on_launching_trial = $apiPT->getSystemInfo('required_login_on_launching_trial', false);
		// $api_play_pt = 'http://cache.download.banner.greatfortune88.com/casinoclient.html';

		// $this->CI->utils->debug_log('API_PLAY_PT_URL', $api_play_pt);
		$currentLang = $this->language_function->getCurrentLanguage();
		if ($gameMode == 'trial') {
			# will redirect to login page when required login on trial game
			if($required_login_on_launching_trial && !$this->authentication->isLoggedIn()){
				$this->goPlayerLogin();
			}
			$ptLang = ($currentLang == '1') ? 'en' : 'zh-cn';
			$url = $api_play_pt . "/casinoclient.html?language=" . $ptLang . "&nolobby=1&game=" . $gameCode . '&mode=offline';

			$this->utils->debug_log('-------------PT DEMO GAME-----------', $url);

			$platformName = $this->external_system->getNameById($game_platform_id);

			$this->load->view('iframe/game_iframe', array('platformName' => $platformName, 'url' => $url, 'iframeName' => $platformName));
			return;
		}

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();
		$session_id = $this->session->userdata('session_id');

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# goto_ptgame is excluded in auth hook, so need to check manually
		if (empty($player_name) && $gameMode != 'trial') {
			$this->goPlayerLogin();
		}

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			if ($this->utils->getConfig('logout_pt_before_login')) {
				//logout first
				$api->logout($player_name);

				//sync password
				$password = $this->player_model->getPasswordByUsername($player_name);

				if (!empty($password)) {
					$api->changePassword($player_name, $password, $password);
				}
			}

			$_transferResult = $this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			// $language = $this->language_function->getCurrentLanguage();
			$ip_address = $this->input->ip_address();
			// $ptLang = $language == '1' ? 'EN' : 'ZH-CN';
			$extra = array(
				'game_mode' => $gameMode,
				'game_code' => $gameCode,
				'language' => $currentLang,
				'ip_address' => $ip_address,
				'is_mobile' => $is_mobile,
				'extra' => array(
					't1_merchant_code' =>$merchant_code
				)
			);

			$result = $api->queryForwardGame($player_name, $extra);
			$this->playerTrackingEventPlayNow($game_platform_id, $result, $extra, $_transferResult);
			if($support){
				return $this->load->view('iframe/player/goto_support', $result);
			}
			if ($result['success']) {
				# Do not kick out other sessions when use game white domain
				# OGP-14173 (start)
				$gameApisAllowedMultipleLoginArr = $this->utils->getConfig('game_allowed_multiple_login');
				if (!empty($result['forward_url']) && !empty($gameApisAllowedMultipleLoginArr)) {
			        if(in_array($game_platform_id, $gameApisAllowedMultipleLoginArr)){
			        	$forward_url = $result['forward_url']."&game_platform_id=".$game_platform_id;
			            return redirect($forward_url);
			        }
					return redirect($result['forward_url']);
				}
				# OGP-14173 (end)

				if ($result['launch_game_on_player']) {

					$this->utils->debug_log('run pt on player', $result);
					return $this->load->view('iframe/player/goto_ptgame', $result);

				} else {

					$this->CI->utils->debug_log('PTLOADER_URL', $site . '/ptgame.html?' . http_build_query($result));
					redirect($this->utils->getSystemUrl('www') . '/ptgame.html?' . http_build_query($result));

				}

			} else {
				$this->CI->utils->error_log('generate url failed');
				return $this->returnBadRequest();
			}

		}
	}

	/**
	 * overview : go to pt game v2
	 * @param string	$siteName
	 * @param string	$gameCode
	 * @param string 	$gameMode
	 * @param string 	$mobile
	 */
	public function goto_ptv2game($siteName, $gameCode, $gameMode = 'real', $mobile = '',$support = false, $merchant_code = null) {
		$is_mobile = $this->utils->is_mobile();
		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'static_site'));

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = PT_V2_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('PT_V2');
            return;
        }

        $this->checkGameIfLaunchable($game_platform_id,$gameCode);

		# Load the config for the game platform api
		$apiPT = $this->utils->loadExternalSystemLibObject($game_platform_id);

		$api_play_pt = $apiPT->getSystemInfo('API_PLAY_PT');
		$required_login_on_launching_trial = $apiPT->getSystemInfo('required_login_on_launching_trial', false);

		
		$goto_page = $apiPT->getSystemInfo('goto_page', null);

		$currentLang = $this->language_function->getCurrentLanguage();
		if ($gameMode == 'trial') {
			# will redirect to login page when required login on trial game
			if($required_login_on_launching_trial && !$this->authentication->isLoggedIn()){
				$this->goPlayerLogin();
			}

			$ptLang=$apiPT->getLauncherLanguage($currentLang);
			$url = $api_play_pt . "/casinoclient.html?language=" . $ptLang . "&nolobby=1&game=" . $gameCode . '&mode=offline';

			$this->utils->debug_log('-------------PT DEMO GAME-----------', $url);

			$platformName = $this->external_system->getNameById($game_platform_id);

			$this->load->view('iframe/game_iframe', array('platformName' => $platformName, 'url' => $url, 'iframeName' => $platformName));
			return;
		}

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();
		$session_id = $this->session->userdata('session_id');

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# goto_ptgamev2 is excluded in auth hook, so need to check manually
		if (empty($player_name) && $gameMode != 'trial') {
			$this->goPlayerLogin();
		}

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists']) {
			$this->utils->debug_log("PT_V2: (goto_ptv2game) playerNotExist:createPlayerOnGamePlatform", $player);
			$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
		}
		$this->utils->debug_log("PT_V2: (goto_ptv2game) playerNotExist:afterIsPlayerCheck", $player);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			/*$api->logout($player_name);
			$password = $this->player_model->getPasswordByUsername($player_name);

			if (!empty($password)) {
				$api->changePassword($player_name, $password, $password);
			}*/

			$_transferResult = $this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$ip_address = $this->input->ip_address();

			$extra = array(
				'game_mode' => $gameMode,
				'game_code' => $gameCode,
				'language' => $currentLang,
				'ip_address' => $ip_address,
				'is_mobile' => $is_mobile,
				'extra' => array(
					't1_merchant_code' =>$merchant_code
				)
			);

			$this->CI->utils->debug_log('PT_V2 (goto_ptv2game) extra:', $extra);

			$result = $api->queryForwardGame($player_name, $extra);
			$this->playerTrackingEventPlayNow($game_platform_id, $result, $extra, $_transferResult);
			$this->CI->utils->debug_log('PT_V2 (goto_ptv2game) result:', $result);

			if($support){
				return $this->load->view('iframe/player/goto_support', $result);
			}
			if ($result['success']) {
				$gameApisAllowedMultipleLoginArr = $this->utils->getConfig('game_allowed_multiple_login');
				if (!empty($result['forward_url']) && !empty($gameApisAllowedMultipleLoginArr)) {
			        if(in_array($game_platform_id, $gameApisAllowedMultipleLoginArr)){
			        	$forward_url = $result['forward_url']."&game_platform_id=".$game_platform_id;
			            return redirect($forward_url);
			        }
					return redirect($result['forward_url']);
				}

				if ($result['launch_game_on_player']) {
					$this->utils->debug_log('PT_V2 run on player', $result);
					if(!empty($goto_page)){
						return $this->load->view('iframe/player/'.$goto_page, $result);
					}else{
						return $this->load->view('iframe/player/goto_ptv2game', $result);
					}

				} else {

					$this->CI->utils->debug_log('PTLOADER_URL', $site . '/ptv2game.html?' . http_build_query($result));
					redirect($this->utils->getSystemUrl('www') . '/ptv2game.html?' . http_build_query($result));

				}

			} else {
				$this->CI->utils->error_log('PT_V2: generate url failed');
				return $this->returnBadRequest();
			}

		}
	}

    public function goto_ptv3game($game_platform_id, $game_code = null, $game_mode = 'real', $game_type = null, $language = null, $is_mobile = null)
    {
        # LOAD MODEL AND LIBRARIES
        $this->load->model(array('game_provider_auth', 'external_system', 'game_description_model'));

        # DECLARE WHICH GAME PLATFORM TO USE
        $game_platform_id = PT_V3_API;

        $is_mobile = $this->utils->is_mobile();
        if(is_null($language))
        {
           $language = $this->language_function->getCurrentLanguage();
        }

        # LOAD GAME API
        $api = $this->utils->loadExternalSystemLibObject($game_platform_id);

        if(empty($api))
        {
        	return show_error('Invalid game api', 400);
        }

		if($game_type == null){
			$game_type='live_dealer';
		}
		if($game_code == null){
			$game_code=$api->getSystemInfo('default_game_code_for_live_dealer', 'abl');
		}

		# game API that under maintenance to allow for game launch
		$allowed_maintenance_game_api_to_game_launch = $this->utils->getConfig('allowed_maintenance_game_api_to_game_launch');

		if(!is_array($allowed_maintenance_game_api_to_game_launch))
        {
			$allowed_maintenance_game_api_to_game_launch = [];
		}

		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE, if game API is in config allowed_maintenance_game_api_to_game_launch, allow it, even it's maintenance
		if(!in_array($game_platform_id, $allowed_maintenance_game_api_to_game_launch))
        {
			if($this->utils->setNotActiveOrMaintenance($game_platform_id))
            {
				$this->goto_maintenance($game_platform_id);
				return;
			}
		}

        # CHECK IF GAMEPLATFORM SETTING IS BLOCKED
        $this->checkBlockGamePlatformSetting($game_platform_id);

        # GET LOGGED-IN PLAYER
        $player_id = $this->authentication->getPlayerId();
        $player_name = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id))
        {
            $this->goBlock();
            return;
        }

        $isPlayerLoggedin = $this->authentication->isLoggedIn();

        //check if not login
        if(!$isPlayerLoggedin)
        {
           $this->goPlayerLogin();
        }

        # CHECK PLAYER IF EXIST
        $player = $api->isPlayerExist($player_name);

        # IF NOT CREATE PLAYER
        if(isset($player['exists']) && !$player['exists'])
        {
            if(!is_null($player['exists']))
            {
               $this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
            }
        }

        # CHECK IF LOGGED-IN PLAYER IS BLOCKED
        $blocked = $api->isBlocked($player_name);

        //exist main account
        $sub_game_provider_to_main_game_provider = $this->utils->getConfig('sub_game_provider_to_main_game_provider');

        if(!empty($sub_game_provider_to_main_game_provider) && array_key_exists($game_platform_id, $sub_game_provider_to_main_game_provider))
        {
            //create main game account
            $mainApiId = $sub_game_provider_to_main_game_provider[$game_platform_id];
            $mainApi = $this->utils->loadExternalSystemLibObject($mainApiId);

            if(!empty($mainApi))
            {
                $mainExistResult=$this->checkExistOnApiAndUpdateRegisterFlag($mainApi, $player_id, $player_name);
                $this->utils->debug_log('checkExistOnApiAndUpdateRegisterFlag for main api', $mainExistResult);
            }else{
                $this->utils->error_log('load main class failed', $mainApiId, $game_platform_id);
            }
        }

		if($blocked)
        {
			die(lang('goto_game.blocked'));
		}else{
			$extra =[];
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
    		$extra['game_code'] = $game_code;
    		$extra['game_mode'] = $game_mode;
    		$extra['game_type'] = $game_type;
    		$extra['language'] = $language;
    		$extra['is_mobile'] = $is_mobile;

			$data = $api->queryForwardGame($player_name, $extra);

	        $is_redirect = $api->getSystemInfo('is_redirect', false);

	        if($is_redirect && $data['success'])
            {
	        	if(isset($data['url']))
                {
					redirect($data['url']);
		        }else{
					die(lang('goto_game.error'));
				}

				return;
	        }

            if(array_key_exists('forward_url', $data))
            {
                return redirect($data['forward_url']);
            }

			if($data['success'])
            {
				if($is_mobile || (isset($data['redirect']) && $data['redirect']))
                {
					$this->load->view('iframe/player/goto_ptv3game', $data);
				}else{
                    $this->load->view('iframe/player/goto_ptv3game', $data);
				}
			}else{
				die(lang('goto_game.error'));
			}

            //$this->utils->debug_log('-------------PT V3 GAME-----------', 'goto_ptv3game', $data);

			return;
		}
	}

	/**
	 * overview : go to impt game
	 *
	 * @param string	$siteName
	 * @param string	$gameCode
	 * @param string	$gameMode
	 */
	public function goto_imptgame($siteName, $gameCode, $gameMode = 'real', $mobile = '') {

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'static_site'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = IMPT_API;

		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		if (empty($player_name) && $gameMode != 'trial') {
			$this->goPlayerLogin();
			return;
		}

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('IMPT');
            return;
        }

		# LOAD THE CONFIG FOR THE GAME PLATFORM API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		$api_play_pt = $api->getSystemInfo('game_url');
		$currentLang = $this->language_function->getCurrentLanguage();
		switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                $ptLang = 'zh-cn';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
                $ptLang = 'ko';
                break;
            default:
                $ptLang = 'en';
                break;
        }

		$platformName = $this->external_system->getNameById($game_platform_id);

		if ($gameMode == 'trial') {
			$url = $api_play_pt . "/casinoclient.html?language=" . $ptLang . "&nolobby=1&game=" . $gameCode . '&mode=offline';
			$this->load->view('iframe/game_iframe', array('platformName' => $platformName, 'url' => $url));
			return;
		}

		$success = $this->prepareGotoGame($player_name, $player_id, $game_platform_id);

		if ($success) {

			if ($this->utils->getConfig('logout_pt_before_login')) {
				//logout first
				$api->logout($player_name);

				//sync password
				$password = $this->player_model->getPasswordByUsername($player_name);

				if (!empty($password)) {
					$api->changePassword($player_name, $password, $password);
				}
			}

			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			// $language = $this->language_function->getCurrentLanguage();
			$ip_address = $this->input->ip_address();
			// $ptLang = $language == '1' ? 'EN' : 'ZH-CN';
			if (empty($mobile)) {
				$mobile = $this->utils->is_mobile() ? 'mobile' : '';
			}
			$extra = array(
				'gamemode' => $gameMode,
				'gamecode' => $gameCode,
				'language' => $ptLang,
				'ip_address' => $ip_address,
				'mobile' => $mobile,
			);

			$result = $api->queryForwardGame($player_name, $extra);

			if ($result['success']) {
				//will redirect to forward url
				if (!empty($result['forward_url'])) {
					return redirect($result['forward_url']);
				}

				$launch_game_on_player = $result['launch_game_on_player'];
				if ($launch_game_on_player) {
					$data = $result;
					if ($data['mobile'] == 'true') {
						$data['mobile'] = 'mobile';
					}

					$this->utils->debug_log('run pt on player', $data);
					return $this->load->view('iframe/player/goto_imptgame', $data);
				} else {
					$this->utils->debug_log('run pt on www', $data);
					return redirect($this->utils->getSystemUrl('www') . '/imptgame.html?' . http_build_query($data));
				}
			} else {
				$this->CI->utils->error_log('generate url failed');
				return $this->returnBadRequest();
			}

		} else {
			$this->returnBadRequest();
		}

	}

	/**
	 * overview : go to imslots
	 *
	 * @param $gameMode
	 * @param $gameCode
	 */
	public function goto_imslots($gameMode, $gameCode, $is_mobile = null) {
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'static_site'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = IMSLOTS_API;

		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('IMSLOTS');
            return;
        }
        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		if (empty($player_name) && $gameMode != 'trial') {
			$this->goPlayerLogin();
			return;
		}

		# LOAD THE CONFIG FOR THE GAME PLATFORM API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		$currentLang = $this->language_function->getCurrentLanguage();
		$language = ($currentLang == '1') ? 'EN' : 'ZH';

		$platformName = $this->external_system->getNameById($game_platform_id);

		if ($gameMode == 'trial') {

			$rlt = $api->queryForwardGame(NULL, array(
				'gameMode' => $gameMode,
				'gamecode' => $gameCode,
				'language' => $language,
				'is_mobile' => $is_mobile,
			));

		} else {

			$success = $this->prepareGotoGame($player_name, $player_id, $game_platform_id);

			if ($success) {
				$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

				$rlt = $api->queryForwardGame($player_name, array(
					'gamecode' => $gameCode,
					'language' => $language,
					'is_mobile' => $is_mobile,
				));

			}

		}

		if ($rlt['success']) {
			$this->utils->debug_log('goto imslot game=>', $rlt['GameUrl']);
			$platformName = $this->external_system->getNameById($game_platform_id);
			$this->load->view('iframe/game_iframe', array('url' => $rlt['GameUrl'], 'platformName' => $platformName));
		} else {
			return show_error(lang('goto_game.error'), 400);
		}

	}

	/**
	 * overview : go to ebet
	 *
	 * @param $gameMode
	 * @param $gameCode
	 */
	public function goto_ebetgame($gameMode = 'real', $is_mobile = null, $gameType = null) {
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'static_site'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = EBET_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('EBET');
            return;
        }

		# LOAD THE CONFIG FOR THE GAME PLATFORM API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);



		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

		#BLOCK LOGIN GAME BY USER STATUS
		if($this->CI->utils->blockLoginGame($player_id)){
			$this->goBlock();
			return;
		}

		if (empty($player_name)) {
			$this->goPlayerLogin();
			return;
		}

		$success = $this->prepareGotoGame($player_name, $player_id, $game_platform_id);

		$extra = array(
			'is_mobile' => $is_mobile
		);
		if(!empty($gameType)){
			$extra['gameType'] = $gameType;
		}
		if ($success) {
			# CHECK PLAYER IF EXIST
			$player = $api->isPlayerExist($player_name);
			$this->utils->info_log("EBET isPlayerExist ========>", $player);


			# IF NOT CREATE PLAYER
			if (isset($player['exist']) && !$player['exist']) {
				if(!is_null($player['exist'])){
					$this->utils->debug_log("Player does not exist!",$player['exist']);
					$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
				}
			}

			$extra['language'] = $this->language_function->getCurrentLanguage();

			$_transferResult = $this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$rlt = $api->queryForwardGame($player_name, $extra);
			$this->playerTrackingEventPlayNow($game_platform_id, $rlt, $extra, $_transferResult);
			$this->utils->info_log("EBET queryForwardGame ========>", $rlt);
		}



		if (isset($rlt['success']) && $rlt['success']) {
			$this->utils->debug_log('goto ebet game=>', $rlt['url']);
			if($is_mobile || $api->redirect){
				redirect($rlt['url']);
			}else{
				$this->load->view('iframe/game_iframe', array('url' => $rlt['url'], 'platformName' => $platformName));
			}
		} else {
			return show_error(lang('goto_game.error'), 400);
		}

	}


	/**
	 * overview : go to ebet
	 *
	 * @param $gameMode
	 * @param $gameCode
	 */
	public function goto_ebetgame_th($gameMode = 'real', $is_mobile = null) {
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'static_site'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = EBET_THB_API;

		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
		if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
			$this->goto_maintenance('EBET');
			return;
		}

		# LOAD THE CONFIG FOR THE GAME PLATFORM API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

		if ($gameMode == 'trial') {
			$rlt = $api->queryForwardGame();
		} else {

			$player_id = $this->authentication->getPlayerId();
			$player_name = $this->authentication->getUsername();

			#BLOCK LOGIN GAME BY USER STATUS
			if($this->CI->utils->blockLoginGame($player_id)){
				$this->goBlock();
				return;
			}

			if (empty($player_name)) {
				$this->goPlayerLogin();
				return;
			}

			$success = $this->prepareGotoGame($player_name, $player_id, $game_platform_id);

			if ($success) {
				$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
				$rlt = $api->queryForwardGame($player_name, ['is_mobile' => $is_mobile]);
			}

		}

		if (isset($rlt['success']) && $rlt['success']) {
			$this->utils->debug_log('goto ebet game=>', $rlt['url']);
			if($is_mobile){
				redirect($rlt['url']);
			}else{
				$this->load->view('iframe/game_iframe', array('url' => $rlt['url'], 'platformName' => $platformName));
			}
		} else {
			return show_error(lang('goto_game.error'), 400);
		}

	}


	/**
	 * overview : go to mg game
	 *
	 * @param string	$gameType
	 * @param string	$gameName
	 * @param string 	$language
	 */
	public function goto_mggame($game_type, $game_code = null,$is_mobile = null, $game_mode = 'real', $language = 'zh-cn') {
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = MG_API;

        $this->checkGameIfLaunchable($game_platform_id,$game_code);

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('MG');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_name = $this->authentication->getUsername();
		$player_id = $this->authentication->getPlayerId();
		$platformName = $this->external_system->getNameById($game_platform_id);

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		if($game_mode == "fun"){
			$fun_params = array(
				'game_type' => $game_type,
				'is_mobile' => $is_mobile,
				'language' => $language,
				'game_code' => $game_code,
				'game_mode' => $game_mode
			);
			$rlt = $api->queryForwardGame($player_name, $fun_params);
			if($is_mobile){
				redirect($rlt['url']);
			}
			$this->load->view('iframe/game_iframe', array('url' => $rlt['url'], 'platformName' => $platformName));
		}

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

		if($api->set_player_language){
        	$language = $this->language_function->getCurrentLanguage();
        }
		$extra = array(
			'game_type' => $game_type,
			'is_mobile' => $is_mobile,
			'language' => $language,
			'game_code' => $game_code
		);
		$rlt = $api->queryForwardGame($player_name, $extra);

		if ($rlt && $rlt['success']) {
			$new_rng_redirection =  $api->getSystemInfo('new_rng_redirection', false);
			if($new_rng_redirection && $is_mobile){
				redirect($rlt['url']);
			}

			if (isset($rlt['form_html'])) {
				$data = array('form_html' => $rlt['form_html'], 'form_id' => $rlt['form_id']);
				$this->utils->debug_log('redirect type form', $data);
				$this->load->view('player/redirect', $data);
				return;
			} else {
                $platformName = $this->external_system->getNameById($game_platform_id);
                $gameProviderInfo = $this->game_provider_auth->getByPlayerIdGamePlatformId($player_id, $game_platform_id);
                $getPlayerGameHistoryURL = $api->queryBetDetailLink($gameProviderInfo['login_name'], null, Array('password'=> $gameProviderInfo['password']));
                // $this->utils->debug_log('====================== game history url ========================', $getPlayerGameHistoryURL);
                if ($getPlayerGameHistoryURL['success'] && isset($getPlayerGameHistoryURL['url']) && !empty($getPlayerGameHistoryURL['url'])) {
                    $this->load->view('iframe/game_iframe', array('url' => $rlt['url'], 'platformName' => $platformName, 'getPlayerGameHistoryURL' => $getPlayerGameHistoryURL['url']));
                } else {
                    $this->load->view('iframe/game_iframe', array('url' => $rlt['url'], 'platformName' => $platformName));
                }
			}
		} else {
			if (isset($rlt['message'])) {
				$message = $rlt['message'];
			} else {
				$message = 'goto_game.error';
			}
			die(lang($message));
		}

	}


	/**
	 * overview : go to bbin game
	 *
	 * @param string $gameType
	 * @param string $language
	 */
	public function goto_bbingame($gameType = null, $language = null, $is_mobile = null,$mode = 'real', $game_code = null) {

		if(is_null($language) || $language == "null"){
           $language = $this->language_function->getCurrentLanguage();
        }

		if (empty($is_mobile) || $is_mobile == "null") {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = BBIN_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('BBIN');
            return;
        }

        $success = false;
        if ($mode == 'real') {

            # GET LOGGED-IN PLAYER
            $player_name = $this->authentication->getUsername();
            $player_id = $this->authentication->getPlayerId();
            if (!$this->authentication->isLoggedIn()) {
				$this->goPlayerLogin();
				return;
			}

            #BLOCK LOGIN GAME BY USER STATUS
            if($this->CI->utils->blockLoginGame($player_id)){
                $this->goBlock();
                return;
            }

            $success = $this->prepareGotoGame($player_name, $player_id, $game_platform_id);

        }

		$platformName = $this->external_system->getNameById($game_platform_id);

		$data = array('platformName' => $platformName);
        $api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		if ($success) {

			$_transferResult = $this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$this->_refreshBalanceFromApiAndUpdateSubWallet($game_platform_id, $api, $player_name, $player_id);

			$params = array(
				'language' => $language,
				'game_type' => $gameType,
				'is_mobile' => $is_mobile,
				'game_mode'=>$mode,
				'game_code' => $game_code
			);
			$rlt = $api->queryForwardGame($player_name,$params);
			$this->playerTrackingEventPlayNow($game_platform_id, $rlt, $params, $_transferResult);
			$is_redirect = $api->getSystemInfo('is_redirect');
			if (isset($rlt['result']) && $rlt['result'] == 1){
				 redirect($rlt['message']);
			} else if ($rlt['success']) {
				// $data['full_html'] = isset($rlt['html']) ? $rlt['html'] : '';
				if($is_redirect) {

					redirect($rlt['url']);
					return;

				} else {

					$data['url'] = $rlt['url'];

				}
			} else if (isset($rlt['message'])) {
				$data['error_message'] = $rlt['message'];
			} else if (isset($rlt['message_lang'])) {
				$data['error_message_lang'] = $rlt['message_lang'];
			} else {
				$data['error_message_lang'] = 'goto_game.error';
			}

            if ($mode == 'real') {
                if (isset($rlt['message']) && strpos($rlt['message'], 'not complete')) {
                    $this->goPlayerLogin();
                }
            }
		}

        if ($mode != "real") {
            $rlt = $api->queryForwardGame(null, array('language' => $language, 'gameType' => $gameType,
                'is_mobile' => $is_mobile,'mode'=>$mode));

            redirect($rlt['url']);
        }

		$this->load->view('share/goto_game_iframe', $data);

	}

	/**
	 * overview : go to lb game
	 *
	 * @param string $gameType
	 * @param string $language
	 */
	public function goto_lbgame($gameType = null, $language = 'zh-cn', $is_mobile = null) {
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}
		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = LB_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('LB');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_name = $this->authentication->getUsername();
		$player_id = $this->authentication->getPlayerId();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			return show_error(lang('goto_game.blocked'), 400);
		} else {

			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$rlt = $api->queryForwardGame($player_name, ['is_mobile' => $is_mobile]);
			if ($rlt['success']) {

				$this->utils->debug_log('goto lb game=>', $rlt['url']);

				$platformName = $this->external_system->getNameById($game_platform_id);

				$this->load->view('iframe/game_iframe', array('url' => $rlt['url'], 'platformName' => $platformName));
				return;
			}
		}
		return show_error(lang('goto_game.error'), 400);
	}

	/**
	 * overview : go to one88 game
	 *
	 * @param string $gameType
	 * @param string $language
	 */
	public function goto_one88game($gameType = null, $language = 'zh-cn', $is_mobile = null) {
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}
		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = ONE88_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('ONE88');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_name = $this->authentication->getUsername();
		$player_id = $this->authentication->getPlayerId();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$loginInfo = $this->game_provider_auth->getLoginInfoByPlayerId($player_id, $game_platform_id);

			if ($loginInfo) {
				$now = new DateTime();
				$token = $loginInfo->password;
				$iv = $this->utils->getIV1();
				$iv = random_string('alpha', 16);
				$key = $this->config->item('one88_merchant_key');
				$merchantId = $this->config->item('one88_merchant_id');
				$lobbyUrl = $this->config->item('one88_lobby_url');
				$encryptedXML = $iv . $this->CI->utils->aes128_cbc_encrypt($key, $merchantId, $iv);

				$url = $lobbyUrl . '/Launch?t=' . $token . '&l=' . $player_name . '&g=ENG&tz=GMT-04:00&mid=' . $encryptedXML;
				$this->utils->debug_log('goto 188 game=>', $url);

				$platformName = $this->external_system->getNameById($game_platform_id);

				$this->load->view('iframe/game_iframe', array('url' => $url, 'platformName' => $platformName));
				return;
			} else {
				die(lang('goto_game.error'));
			}
		}
	}

	/**
	 * overview : go to ones game
	 *
	 * @param string $gameName
	 * @param string $gameType
	 * @param string $language
	 */
	public function goto_onesgame($gameName = null, $gameType = null, $language = 'zh-cn', $is_mobile = null) {
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = ONESGAME_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('ONES');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_name = $this->authentication->getUsername();
		$player_id = $this->authentication->getPlayerId();


        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
			$this->checkBlockGamePlatformSetting($game_platform_id);

			$loginInfo = $this->game_provider_auth->getLoginInfoByPlayerId($player_id, $game_platform_id);

			if ($loginInfo) {
				$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
				$rlt = $api->queryForwardGame($player_name, array('gameName' => $gameName,
					'language' => $language, 'password' => $loginInfo->password, 'is_mobile' => $is_mobile));

				if ($rlt && $rlt['success']) {
					$platformName = $this->external_system->getNameById($game_platform_id);
					if($is_mobile){
						redirect($rlt['url']);
					}else{
						$this->load->view('iframe/game_iframe', array('url' => $rlt['url'], 'platformName' => $platformName));
					}
				} else {
					if (isset($rlt['message'])) {
						$message = $rlt['message'];
					} else {
						$message = 'goto_game.error';
					}
					die(lang($message));
				}
			} else {
				die(lang('goto_game.error'));
			}
		}
	}

	/**
	 * overview : go to cxtm
	 *
	 * @param $game_code
	 */
	public function goto_cxtm($game_code) {
		$this->utils->debug_log('GOTO CXTM!');
		$this->gotogame(GAMEPLAY_API, $game_code, 'real', null, null, 'zh-cn', 'cxtm');
	}

	/**
	 * overview : go to ipm
	 *
	 * @param bool|false $mobile
	 */
	public function goto_ipm($mobile = false) {

		$this->utils->debug_log('GOTO IPM!');

		$lang = $this->language_function->getCurrentLanguage();

		switch ($lang) {
		case 1:
			$lang = "en";
			break;
		case 2:
			$lang = "chs";
			break;
		case 3:
			//for korean to be confirm
			$lang = "en";
			break;
		default:
			// code...
			$lang = "en";
			break;
		}

		$data = $this->gotogame(SPORTSBOOK_API, null, 'real', null, null, $lang, array('mobile' => $mobile));

		if ($mobile) {
			redirect($data['url']);
		}

	}

	/**
	 * overview : go to sb tech
	 */
	public function goto_sbtech() {
		$this->utils->debug_log('GOTO SBTECH!');
		$this->gotogame(GAMEPLAY_API, null, 'real', null, null, 'zh-cn', 'sbtech');
	}

	/**
	 * overview : go to gp game
	 *
	 * @param $game_platform_id
	 * @param string $game_type
	 * @param string $game_code
	 * @param string $platform
	 * @param int $game_mode
	 */
	public function goto_gpgame($game_platform_id, $game_type = null, $game_code = null, $platform = null, $game_mode = 0, $is_mobile = null, $game_name = null) {
		if (empty($is_mobile) || $is_mobile == 'null' || $is_mobile == '_null') {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);


		$this->utils->debug_log('GOTO GP!');
		$lang = $this->language_function->getCurrentLanguage();
		$extra = array("game_mode" => $game_mode, "platform" => $platform, 'game_name' => $game_name, 'is_mobile' => $is_mobile, "language"=>$lang, 'game_code' => $game_code, 'game_type' => $game_type);
		if ($game_mode) {
			$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
			$rlt = $api->queryForwardGame(
				null,
				array(
					'game_code' => $game_code,
					'game_mode' => $game_mode,
					'game_type' => $game_type,
					'game_name' => $game_name,
					'language' => $lang,
					'extra' => $extra,
					'is_mobile' => $is_mobile,
				)
			);
			$this->playerTrackingEventPlayNow($game_platform_id, $rlt, $extra);
			$this->utils->debug_log('GOTODEMOGAME RESULT: ', $rlt);
			if (isset($rlt['success']) && $rlt['success']) {
				$data = array(
					'url' => $rlt['url'],
					'platformName' => "GAMEPLAY",
					'iframeName' => $rlt['iframeName'],
				);
				$this->load->view('iframe/game_iframe', $data);
				return;
			} else {
				// echo 'goto error';
				die(lang('goto_game.error'));
			}
		} else {
			if (!$this->authentication->isLoggedIn()) {
				$this->goPlayerLogin();
			}

			$this->gotogame($game_platform_id, $game_code, 'real', $game_type, null, $lang, $extra);
		}
	}

	/**
	 * overview : go to ent wine
	 * @param $game_platform_id
	 * @param $extra
	 */
	public function goto_entwine($game_platform_id, $extra = null, $is_mobile = null) {
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}
		$this->utils->debug_log('GOTO ENTWINE!');

		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$is_redirect = $api->getSystemInfo('entwine_is_redirect');
		$currentLang = $this->language_function->getCurrentLanguage();
		$language = ($currentLang == '1') ? '3' : '1';
		$this->gotogame($game_platform_id, null, 'real', null, null, $language, $extra, $is_redirect);
	}

	/**
	 * overview : go to vivo live
	 *
	 * @param int	 $game_platform_id
	 * @param string $platform
	 */
	public function goto_vivo_live($game_platform_id, $platform = null, $is_mobile = null) {
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}
		$this->utils->debug_log('GOTO VIVO!');
		$lang = $this->language_function->getCurrentLanguage();
		switch ($lang) {
		case 1:
			$lang = "EN";
			break;
		case 2:
			$lang = "ZH";
			break;
		case 3:
			$lang = "KO";
			break;
		default:
			// code...
			break;
		}

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('VIVO');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		//$this->utils->debug_log('----------------nt seesion info ++++++', $session_id, $player_id, $playerName, $this->session->all_userdata());
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);

		$platformName = $this->external_system->getNameById($game_platform_id);

		if (!$blocked) {
			$this->_transferAllWallet($player_id, $playerName, $game_platform_id);

			$extra = array();
			$extra['language'] = $lang;
			$extra['platform'] = $platform;
			$extra['is_mobile'] = $is_mobile;
			$params = $api->queryForwardGame($playerName, $extra);

			if (isset($params['success']) && $params['success']) {
				$this->load->view('iframe/player/goto_vivo_live', $params);
				//echo "<pre>";print_r($extra);exit;
				//$this->gotogame($game_platform_id, null, null, null, null, null, $extra);
				return;
			} else {
				// echo 'goto error';
				die(lang('goto_game.error'));
			}

		} else {
			die(lang('goto_game.blocked'));
		}
	}

	/**
	 * overview : go to ibc
	 *
	 * @param string	$gameType
	 * @param string 	$language
	 */
	public function goto_ibc($gameType = null, $language = 'cs', $is_mobile = null) {
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}
		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = IBC_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('IBC');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_name = $this->authentication->getUsername();
		$player_id = $this->authentication->getPlayerId();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		if($is_mobile && $this->utils->getConfig('go_player_login_if_not_login_when_launch_sports_on_mobile')){
			//if not login when launching on mobile
	        if (!$this->authentication->isLoggedIn()) {
	            $this->goPlayerLogin();
	        }
		}
		# NOT LOGIN, GOTO DEMO GAME MOBILE
		if (!$player_name && !$player_id && $is_mobile) {
			$result = $api->queryForwardGame(null, array('language' => $language, 'gameType' => $gameType, 'is_mobile' => $is_mobile));
			return redirect($result['url']);
		}

		# NOT LOGIN, GOTO DEMO GAME
		if (!$player_name && !$player_id) {
			return $this->goto_demo_game($game_platform_id, $gameType, $language);
		}

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
			$this->checkBlockGamePlatformSetting($game_platform_id);

			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$result = $api->queryForwardGame($player_name, array('language' => $language, 'gameType' => $gameType, 'is_mobile' => $is_mobile));
			$loginInfo = $this->game_provider_auth->getLoginInfoByPlayerId($player_id, $game_platform_id);

			if ($loginInfo) {
				$this->utils->debug_log('goto ibc game=>', $result);
				if ($result['success']) {

					if (!empty($result['forward_url'])) {
						return redirect($result['forward_url']);
					}

					if($is_mobile){
						$mobile_url  = "http://".$result['url'];
						$this->utils->debug_log('goto ibc mobile_url=>', $mobile_url);
						// header('Location: '.$mobile_url);
						// die();

						return redirect($mobile_url);
					}else{
						$host=str_replace("player", "", $_SERVER['HTTP_HOST']);
						$this->load->helper('cookie');
						$this->input->set_cookie('g', $result['sessionToken'], 3600, $host);
						$this->utils->debug_log("gotoibc host => ", $host);

						$platformName = $this->external_system->getNameById($game_platform_id);
						return redirect($result['url']);
					}
				} else {
					// $message = 'goto_game.error';
					return $this->goto_maintenance('IBC');
				}
			} else {
				return $this->goto_maintenance('IBC');
				// die(lang('goto_game.error'));
			}
		}
	}

	private function checkExistOnApiAndUpdateRegisterFlag($api,$player_id=null,$player_name=null){
		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);
		if(isset($player['exists']) && $player['exists']){
	        $api->updateRegisterFlag($player_id, Abstract_game_api::FLAG_TRUE);
	    }
		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$game_platform_id = $api->getPlatformCode();
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}
		return $player;
	}

	/**
	 * overview : go to oneworks game
	 *
	 * @param string		$gameType
	 * @param bool|false 	$isRedirect
	 * @param string 		$platform
	 */
	public function goto_oneworks_game($gameType = null, $isRedirect = false, $platform = 'web', $is_mobile = null, $static_language = null) {
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		$this->utils->debug_log('<-----------------------= GOTO ONEWORKS GAME =---------------------->');
		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = ONEWORKS_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('ONEWORKS');
            return;
		}

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

		#REDIRECT TO LOGIN IF NOT LOGGED IN
		$game_platform_ids = $this->utils->getConfig('go_player_login_if_not_login_when_launch_game');
		if (!$this->authentication->isLoggedIn()
		&& is_array($game_platform_ids)
		&& in_array($game_platform_id, $game_platform_ids)) {
			$this->goPlayerLogin();
		}

		# GET LOGGED-IN PLAYER
		$player_name = $this->authentication->getUsername();
		$player_id = $this->authentication->getPlayerId();

		if($is_mobile && $this->utils->getConfig('go_player_login_if_not_login_when_launch_sports_on_mobile')){
			//if not login when launching on mobile
	        if (!$this->authentication->isLoggedIn()) {
	            $this->goPlayerLogin();
	        }
		}

		# NOT LOGIN, GOTO DEMO GAME
		if (!$player_name && !$player_id) {
			return $this->goto_demo_game($game_platform_id, $gameType, $static_language);
		}

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
		}

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		$language = $this->language_function->getCurrentLanguage();
		// $language = ($currentLang == '1') ? 'en' : 'cs';
		if(!empty($static_language)){
			$language = $static_language;
		}
		$this->utils->debug_log('ONEWORKS GAME =----------------------> lang: ', $language, ' player_name: ', $player_name, 'player_id: ', $player_id);

		# NOT LOGIN, GOTO DEMO GAME
		if (!$player_name && !$player_id) {
			// $this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$this->utils->debug_log('oneworks, player not login, will redirect to demo game');

			$extra = array(
				'language' => $language,
				'is_mobile' => $is_mobile
			);

			$result = $api->queryForwardGame(null, $extra);
			$this->utils->debug_log('ONEWORKS GAME =----------------------> demo url: ', @$result['url']);
			$data = array(
				'url' => $result['url'],
				'platformName' => 'Oneworks',
				'iframeName' => "Oneworks Sportsbook",
			);

			// $this->load->view('iframe/sportsbook/game_iframe', $data);
			redirect($result['url']);
			return;
		}

		# CHECK PLAYER IF EXIST
		// $player = $api->isPlayerExist($player_name);

		// # IF NOT CREATE PLAYER
		// if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
		// 	if(!is_null($player['exists'])){
		// 		$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
		// 	}
		// }
		$player = $this->checkExistOnApiAndUpdateRegisterFlag($api,$player_id,$player_name);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
            $this->goBlock();
		} else {
			# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
			$this->checkBlockGamePlatformSetting($game_platform_id);

			$loginInfo = $this->game_provider_auth->getLoginInfoByPlayerId($player_id, $game_platform_id);

			if ($loginInfo) {
				$_transferResult = $this->_transferAllWallet($player_id, $player_name, $game_platform_id);
				$this->_refreshBalanceFromApiAndUpdateSubWallet($game_platform_id, $api, $player_name, $player_id);

				$host = $_SERVER['HTTP_HOST'];
				//remove first subdomain
				//www.webet88.com => webet88.com
				//add subdomain like mkt, sb or testing domain
				$extra = array(
					'language' => $language,
					'game_type' => $gameType,
					'platform' => $platform,
					'host' => $host,
					'is_mobile' => $is_mobile
				);

				$result = $api->queryForwardGame($player_name, $extra);
				$this->playerTrackingEventPlayNow($game_platform_id, $result, $extra, $_transferResult);
				$this->utils->debug_log('ONEWORKS GAME RESULT =----------------------> ', json_encode($result));
				if ($result['success']) {
					if (!empty($result['forward_url'])) {
				        if(in_array($game_platform_id, $this->utils->getConfig('game_allowed_multiple_login'))){
				        	$forward_url = $result['forward_url']."&game_platform_id=".$game_platform_id;
				            return redirect($forward_url);
				        }
						return redirect($result['forward_url']);
					}

					$this->load->helper('cookie');

					if (isset($result['domains']) && !empty($result['domains'])) {
						foreach ($result['domains'] as $key) {
							$this->input->set_cookie('g', $result['sessionToken'], 3600, $key . "." . $this->utils->stripSubdomain($_SERVER['HTTP_HOST']));
							$this->utils->debug_log('ONEWORKS SUBDOMAIN =----------------------> ', $key . "." . $this->utils->stripSubdomain($_SERVER['HTTP_HOST']));

						}
						$this->input->set_cookie('g', $result['sessionToken'], 3600, $this->utils->stripSubdomain($_SERVER['HTTP_HOST']));
						$this->input->set_cookie('g', $result['sessionToken'], 3600, $_SERVER['HTTP_HOST']);
					}

					// if ($platform == 'web') {
						$url = $result['url'];
					// } else {
						// $url = urlencode($result['url']);
					// }

					$this->utils->debug_log('ONEWORKS GAME URL =----------------------> ', $url, 'HOST =---------->', $_SERVER['HTTP_HOST']);
					$data = array(
						'url' => $url,
						'platformName' => $result['system_code'],
						'iframeName' => "Oneworks Sportsbook",
					);

					if ($is_mobile) {
						redirect($url);
					} else {
						redirect($url);
						// $this->load->view('iframe/game_iframe', $data);
					}
				} else {
					$message = 'goto_game.error';
					die(lang($message));
				}
			} else {
				die(lang('goto_game.error'));
			}
		}
	}

	/**
	 * overview : go to demo game
	 *
	 * @param int	 $gamePlatformId
	 * @param string $gameType
	 * @param string $language
	 * @param string $extra
	 */
	public function goto_demo_game($gamePlatformId, $gameType = null, $language = 'cs', $extra = null) {
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
		$platformName = $this->external_system->getNameById($gamePlatformId);
        $on_error_redirect = $this->input->get('on_error_redirect');
        //OGP-32458 send post message 
		$post_message_on_error = $this->input->get('post_message_on_error');

		switch ($gamePlatformId) {
		case IBC_API:
		case ONEWORKS_API:
			$result = $api->queryForwardGame(null, array('game_type' => $gameType, 'language' => $language, 'is_mobile' => $this->utils->is_mobile()));
			$this->utils->debug_log('goto ibc demo game=>', $result);
			if(isset($result['sessionToken']) && $result['sessionToken']){
				$this->load->helper('cookie');
				$this->input->set_cookie('g', $result['sessionToken'], 3600, str_replace("player", "", $_SERVER['HTTP_HOST']));
			}
			if ($result) {
				if(isset($result['is_mobile']) && $result['is_mobile']){
					$this->goPlayerLogin();
				}
				redirect($result['url']);
			} else {
				$message = 'goto_game.error';
				die(lang($message));
			}
			break;

		case GAMEPLAY_API:
			$result = $api->queryForwardGame(null, array('gameType' => $gameType, 'language' => $language));
			$this->utils->debug_log('goto ibc demo game=>', $result);
			$this->load->helper('cookie');
			$this->input->set_cookie('g', $result['sessionToken'], 3600, str_replace("player", "", $_SERVER['HTTP_HOST']));
			if ($result) {
				redirect($result['url']);
			} else {
				$message = 'goto_game.error';
				die(lang($message));
			}
			break;

		case WFT_API:
			$rlt = $api->queryForwardGame(null, array('lang' => $language));
			if (isset($rlt['success']) && $rlt['success']) {
				$data = array(
					'url' => $rlt['url'],
					'platformName' => $platformName,
					'iframeName' => $rlt['iframeName'],
				);
				$this->load->view('iframe/game_iframe', $data);
				return;
			} else {
				die(lang('goto_game.error'));
			}
			break;

		case CROWN_API:
			$rlt = $api->queryForwardGame(null, array());
			if (isset($rlt['success']) && $rlt['success']) {
				$data = array(
					'url' => $rlt['url'],
					'platformName' => $platformName,
					'iframeName' => $rlt['iframeName'],
				);
				$this->load->view('iframe/game_iframe', $data);
				return;
			} else {
				die(lang('goto_game.error'));
			}
			break;

		case FG_API:
			$rlt = $api->queryForwardGame(null, $extra);
			$this->utils->debug_log('goto FG_API demo game=>', $rlt['url']);
			if (isset($rlt['success']) && $rlt['success']) {

				$data = array(
					'url' => $rlt['url'],
					'platformName' => $platformName,
					'iframeName' => $rlt['iframeName'],
				);

				$isRedirect = @$rlt['is_redirect'];

				if (!$isRedirect) {
					$this->load->view('iframe/game_iframe', $data);
				} else {
					if (!empty($rlt['url'])) {
						redirect($rlt['url']);
					}
				}

			} else {
				die(lang('goto_game.error'));
			}
			break;

		case LAPIS_API:
			$rlt = $api->queryForwardGame(null, $extra);
			$this->utils->debug_log('goto GSMS_API demo game=>', $rlt['url']);
			if (isset($rlt['success']) && $rlt['success']) {
				$data = array(
					'url' => $rlt['url'],
					'platformName' => $platformName,
					'iframeName' => $rlt['iframeName'],
				);
				$this->load->view('iframe/game_iframe', $data);
				return;
			} else {
				die(lang('goto_game.error'));
			}
			break;

		case GSMG_API:
			$extra['game_type'] = $gameType;
			$rlt = $api->queryForwardGame(null, $extra);
			$this->utils->debug_log('goto FG_API demo game=>', $rlt['url']);
			if (isset($rlt['success']) && $rlt['success']) {
				$data = array(
					'url' => $rlt['url'],
					'platformName' => $platformName,
					'iframeName' => $rlt['iframeName'],
				);
				$this->load->view('iframe/game_iframe', $data);
				return;
			} else {
				die(lang('goto_game.error'));
			}
			break;

		case PLAYSTAR_API:
			$extra['language'] = $language;
			$rlt = $api->queryForwardGame($extra['player_name'], $extra);
			$this->utils->debug_log('goto PLAYSTAR_API demo game=>', $rlt['url']);
			if (isset($rlt['success']) && $rlt['success']) {
				$data = array(
					'url' => $rlt['url'],
					'platformName' => $platformName?:"PLAYSTAR",
					'iframeName' => isset($rlt['iframeName'])?$rlt['iframeName']:"PLAYSTAR",
				);

				$this->load->view('iframe/game_iframe', $data);
				return;
			} else {
				die(lang('goto_game.error'));
			}
			break;
		case QT_API:
			$extra['language'] = $language;
			$rlt = $api->queryForwardGame($extra['player_name'], $extra);
			$this->utils->debug_log('goto QT_API demo game=>', isset($rlt['url']) ? $rlt['url'] : null);
			if (isset($rlt['success']) && $rlt['success']) {
				$data = array(
					'url' => $rlt['url'],
					'platformName' => $platformName?:"PLAYSTAR",
					'iframeName' => isset($rlt['iframeName'])?$rlt['iframeName']:"PLAYSTAR",
				);

				$this->load->view('iframe/game_iframe', $data);
				return;
			} else {
				die(lang('goto_game.error'));
			}
			break;
		case REDTIGER_API:
			$extra['language'] = $language;
			$extra['game_type'] = $gameType;
			$extra['game_code'] = $extra['game_code'];
			$extra['player_name'] = isset($extra['player_name']) ? $extra['player_name'] : null;
			$rlt = $api->queryForwardGame($extra['player_name'], $extra);
			$this->utils->debug_log('goto REDTIGER_API demo game=>', isset($rlt['url']) ? $rlt['url'] : null);
			if (isset($rlt['success']) && $rlt['success']) {
				$data = array(
					'url' => $rlt['url'],
					'platformName' => $platformName ? : "REDTIGER",
					'iframeName' => isset($rlt['iframeName']) ? $rlt['iframeName'] : "REDTIGER",
				);

				$this->load->view('iframe/game_iframe', $data);
				return;
			} else {
				die(lang('goto_game.error'));
			}
			break;

		case VIVOGAMING_SEAMLESS_API:
		case VIVOGAMING_SEAMLESS_IDR1_API:
		case VIVOGAMING_SEAMLESS_CNY1_API:
		case VIVOGAMING_SEAMLESS_THB1_API:
		case VIVOGAMING_SEAMLESS_USD1_API:
		case VIVOGAMING_SEAMLESS_VND1_API:
		case VIVOGAMING_SEAMLESS_MYR1_API:
		case VIVOGAMING_API:
		case VIVOGAMING_IDR_B1_API:
        case T1_VIVOGAMING_SEAMLESS_API:
			$extra['language'] = $language;
			$rlt = $api->queryForwardGame($extra['player_name'], $extra);
			$this->utils->debug_log('goto VIVOGAMING demo game=>', isset($rlt['url']) ? $rlt['url'] : null);
			if (isset($rlt['success']) && $rlt['success']) {
				$data = array(
					'url' => $rlt['url'],
					'platformName' => $platformName?:"VIVOGAMING",
					'allow_fullscreen' => true
				);

				$this->load->view('iframe/game_iframe', $data);
				return;
			} else {
				die(lang('goto_game.error'));
			}
			break;
		case HUB88_API:
			$extra['language'] = $language;
			$extra['is_mobile'] = $this->utils->is_mobile();
			$rlt = $api->queryForwardGame(null, $extra);
			$this->utils->debug_log('goto HUB88 demo game=>', isset($rlt['url']) ? $rlt['url'] : null, $extra);
			if (isset($rlt['success']) && $rlt['success']) {
				$data = array(
					'url' => $rlt['url'],
					'platformName' => $platformName?:"HUB88",
					'iframeName' => isset($rlt['iframeName'])?$rlt['iframeName']:"HUB88",
				);

				$this->load->view('iframe/game_iframe', $data);
				return;
			} else {
				die(lang('goto_game.error'));
			}
			break;


		case IMESB_API:
			$rlt = $api->queryForwardGame(null, $extra);
			$this->utils->debug_log('goto IMESB demo game=>', isset($rlt['url']) ? $rlt['url'] : null, $extra);
			if (isset($rlt['success']) && $rlt['success']) {
				$data = array(
					'url' => $rlt['url'],
					'platformName' => $platformName?:"IMESB",
					'iframeName' => isset($rlt['iframeName'])?$rlt['iframeName']:"IMESB",
				);

				$this->load->view('iframe/game_iframe', $data);
				return;
			} else {
				die(lang('goto_game.error'));
			}
			break;
			break;

		default:
            if($api->isSupportsDemo()){
            	$extra['game_type'] = $gameType;
                $extra['language'] = $language;
                $extra['player_name'] = isset($extra['player_name'])?$extra['player_name']:null;
                $extra['is_mobile'] = (isset($extra['platform']) && $extra['platform']=='mobile' ?true:$this->utils->is_mobile() );

                $this->utils->error_log(__METHOD__. ' extra', $extra);
    
                $rlt = $api->queryForwardGame($extra['player_name'], $extra);              

                $this->utils->error_log(__METHOD__. ' rlt', $rlt);


                
                $this->utils->debug_log('goto default demo game=>', $rlt);
                if (isset($rlt['success']) && $rlt['success'] && isset($rlt['url']) && !empty($rlt['url'])) {
                    $data = array(
                        'url' => $rlt['url'],
                        'platformName' => $platformName,
                        'iframeName' => isset($rlt['iframeName'])?$rlt['iframeName']:"PLAYSTAR",
                    );

                    if(!empty($extra['is_redirect'])){
                        return redirect($rlt['url']);
                    }
    
                    $this->load->view('iframe/game_iframe', $data);
                    return;
                } else {
                	if($post_message_on_error){
                		$post_message['error_message'] = lang("Launching demo fail.");
		        		return $this->load->view('iframe/player/view_post_message_closed', $post_message);
		        	}
                    if(!empty($on_error_redirect)){
                        return redirect($on_error_redirect);
                    }
                    die(lang('goto_game.error'));
                }
            }else{
            	if($post_message_on_error){
            		$post_message['error_message'] = lang("Doesn't support demo.");
	        		return $this->load->view('iframe/player/view_post_message_closed', $post_message);
	        	}
                if(!empty($on_error_redirect)){
                    return redirect($on_error_redirect);
                }
                die(lang('goto_game.error'));
            }
            
			break;
		}

	}

	/**
	 * overview : go to crown
	 *
	 * @param string	$game_code
	 */
	public function goto_crown($game_code) {
		$this->utils->debug_log('GOTO CROWN!');
		$this->gotogame(CROWN_API);
	}

	/**
	 * overview : go to xhtd lotterry
	 */
	public function goto_xhtdlottery() {
		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = XHTDLOTTERY_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('XHTD LOTTERY');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_name = $this->authentication->getUsername();
		$player_id = $this->authentication->getPlayerId();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
			$this->checkBlockGamePlatformSetting($game_platform_id);

			$loginInfo = $this->game_provider_auth->getLoginInfoByPlayerId($player_id, $game_platform_id);

			if ($loginInfo) {
				$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

				$result = $api->queryForwardGame($player_name, array('password' => $loginInfo->password));

				if ($result) {
					$platformName = $this->external_system->getNameById($game_platform_id);
					$key = $api->getSystemInfo('key');
					$signstr = md5($player_name . $key . $loginInfo->password);
					$this->load->view('iframe/player/goto_xlcodgame', array(
						'url' => $result['url'],
						'redirect_url' => isset($result['redirect_url']) ? $result['redirect_url'] : null,
						'platformName' => $platformName,
						'passwd' => $loginInfo->password,
						'userno' => $player_name,
						'key' => $key,
						'signstr' => $signstr,
					));
				} else {
					$message = 'goto_game.error';
					die(lang($message));
				}
			} else {
				die(lang('goto_game.error'));
			}
		}
	}

	/**
	 * overview : go to opus
	 */
	public function goto_opus($gameType = null, $gamePlatform = null, $gameId = null, $gameMode = 'real', $language = 'id-ID', $is_mobile = null) {
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		$params = array();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		switch ($gameType) {
		case 'sportsbook':
			$game_platform_id = OPUS_SPORTSBOOK_API;
			break;

		case 'keno':
			$game_platform_id = OPUS_KENO_API;
			break;

		case 'slots':
			$params['gamePlatform'] = $gamePlatform;
			$params['gameId'] = $gameId;
			$params['gameMode'] = $gameMode;

		default:
			$game_platform_id = OPUS_API;
			break;
		}

		# CHECK IF GAME API IS ACTIVE STATUS
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
			$data = [];
			$data['empty'] = true;

			$this->load->view('iframe/game_iframe', $data);
			return;
		}

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# LOAD THE CONFIG FOR THE GAME PLATFORM API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);
		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}
		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);
		if (!$blocked) {
			if ($gameMode == 'trial') {
				$params['gameMode'] = $gameMode;
				$rlt = $api->queryForwardGame(NULL, $params);
			} else {

				# GET LOGGED-IN PLAYER
				$player_id = $this->authentication->getPlayerId();
				$player_name = $this->authentication->getUsername();

				if (empty($player_name)) {
					$this->goPlayerLogin();
					return;
				}

				$success = $this->prepareGotoGame($player_name, $player_id, $game_platform_id);

				if ($success) {
					$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

					$loginInfo = $this->game_provider_auth->getLoginInfoByPlayerId($player_id, $game_platform_id);

					$params['language'] = $language;
					$params['password'] = $loginInfo->password;
					$params['is_mobile'] = $is_mobile;
					$rlt = $api->queryForwardGame($player_name, $params);
				}

			}
			if (isset($rlt['success']) && $rlt['success']) {

				if (!empty($rlt['forward_url'])) {
					return redirect($rlt['forward_url']);
				}

				$this->utils->debug_log('goto opus game=>', $rlt['url']);
				$this->load->view('iframe/game_iframe', array('url' => $rlt['url'], 'platformName' => $platformName));
			} else {
				return show_error(lang('goto_game.error'), 400);
			}
		}
		else{
            $this->goBlock();
		}
	}

	/**
	 * overview : go to bbtech game
	 *
	 * @param string	 $gameName
	 * @param bool|false $isDemoMode
	 */
	public function goto_bbtechgame($gameName, $isDemoMode = false, $is_mobile = false) {
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		$currentLang = $this->language_function->getCurrentLanguage();
		$language = ($currentLang == '1') ? 'en' : 'cn';

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = NT_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('BBTECH');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		//$this->utils->debug_log('----------------nt seesion info ++++++', $session_id, $player_id, $playerName, $this->session->all_userdata());

		if (empty($playerName) && $gameMode != 'trial') {
			$this->goPlayerLogin();
		}

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);

		$platformName = $this->external_system->getNameById($game_platform_id);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$rlt = $api->login($playerName);
			if ($rlt && $rlt['success']) {
				$key = $rlt['key'];
				//?
				//http://www.vazagaming.com/lobby/game_lobby_live.php?token=ICE2016-1&language=CH&operatorID=1453&homeUrl=http://www.vivogaming.com&serverID=3649143
				$url = 'http://www.vazagaming.com/lobby/game_lobby_live.php?token=' . $token . '&language=' . $language . '&operatorID=' . $operatorID . '&homeUrl=http://www.vivogaming.com&serverID=' . $serverID;
				$this->load->view('iframe/game_iframe', array('url' => $url, 'platformName' => $platformName));
				return;
			} else {
				die(lang('goto_game.error'));
			}
		}
	}

	/**
	 * overview : goto_nt
	 *
	 * @param $gameName
	 * @param string $gameMode
	 */
	public function goto_ntgame($gameName, $gameMode = 'real', $is_mobile = null) {
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}
		$currentLang = $this->language_function->getCurrentLanguage();
		$language = ($currentLang == '1') ? 'en' : 'cn';

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = NT_API;

		if ($gameMode == 'trial') {
			$platformName = $this->external_system->getNameById($game_platform_id);
			$url = 'http://load.sdjdlc.com/nt/demo.html?language=' . $language . '&game=' . $gameName;
			$this->load->view('iframe/game_iframe', array('url' => $url, 'platformName' => $platformName));
			return;
		}

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('NT');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		//$this->utils->debug_log('----------------nt seesion info ++++++', $session_id, $player_id, $playerName, $this->session->all_userdata());

		if (empty($playerName) && $gameMode != 'trial') {
			$this->goPlayerLogin();
		}

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);

		$platformName = $this->external_system->getNameById($game_platform_id);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$rlt = $api->login($playerName);
			if ($rlt && $rlt['success']) {
				$key = $rlt['key'];
				$url = 'http://load.sdjdlc.com/nt/?language=' . $language . '&game=' . $gameName . '&key=' . $key;
				$this->load->view('iframe/game_iframe', array('url' => $url, 'platformName' => $platformName));
				return;
			} else {
				die(lang('goto_game.error'));
			}
		}
	}

	/**
	 * overview : go to inte play game
	 * @param $game_id
	 */
	public function goto_inteplaygame($game_id, $is_mobile = null) {
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}
		$currentLang = $this->language_function->getCurrentLanguage();
		$language = ($currentLang == '1') ? 'en' : 'cn';

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = INTEPLAY_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('INTEPLAY');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		//$this->utils->debug_log('----------------nt seesion info ++++++', $session_id, $player_id, $playerName, $this->session->all_userdata());

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);

		$platformName = $this->external_system->getNameById($game_platform_id);

		if (!$blocked) {
			$this->_transferAllWallet($player_id, $playerName, $game_platform_id);

			$rlt = $api->queryForwardGame($playerName, array(
				'game_id' => $game_id,
				'language' => $language,
				'is_mobile' => $is_mobile,
			));

			if (isset($rlt['success']) && $rlt['success']) {
				$data = array('url' => $rlt['url'] . '?' . http_build_query($rlt['params']), 'platformName' => $platformName);
				$this->load->view('iframe/game_iframe', $data);
				return;
			} else {
				die(lang('goto_game.error'));
			}

		} else {
			die(lang('goto_game.blocked'));
		}

	}

	/**
	 * overview : go to gspt game
	 *
	 * @param $game_id
	 */
	public function goto_gsptgame($game_id, $is_mobile = null) {
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}
		$currentLang = $this->language_function->getCurrentLanguage();
		$language = ($currentLang == '1') ? 'en' : 'zh-cn';

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = GSPT_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('GSPT');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		//$this->utils->debug_log('----------------nt seesion info ++++++', $session_id, $player_id, $playerName, $this->session->all_userdata());

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);

		$platformName = $this->external_system->getNameById($game_platform_id);

		if (!$blocked) {
			$this->_transferAllWallet($player_id, $playerName, $game_platform_id);

			$rlt = $api->queryForwardGame($playerName, array(
				'username' => $playerName,
				// 'password' =>$password,
				'language' => $language,
				'game_code' => $game_id,
				'is_mobile' => $is_mobile,
			));

			if (isset($rlt['success']) && $rlt['success']) {
				if($is_mobile){
					redirect($rlt['url']);
				} else {
					$data = array(
							'url' => $rlt['url'],
							'platformName' => $platformName,
					);
					$this->load->view('iframe/game_iframe', $data);
					return;
				}

			} else {
				die(lang('goto_game.error'));
			}

		} else {
			die(lang('goto_game.blocked'));
		}

	}

	/**
	 * overview : go to keno game name
	 */
	public function goto_kenogamegame($is_mobile = null) {
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		$currentLang = $this->language_function->getCurrentLanguage();
		$language = ($currentLang == '1') ? 'en' : 'sc';
		// $trial = ($isTrial == 'trial') ? '1' : '0';

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = KENOGAME_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('KENO');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();


        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		//$this->utils->debug_log('----------------nt seesion info ++++++', $session_id, $player_id, $playerName, $this->session->all_userdata());

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);

		$platformName = $this->external_system->getNameById($game_platform_id);

		if (!$blocked) {
			$this->_transferAllWallet($player_id, $playerName, $game_platform_id);

			$rlt = $api->queryForwardGame($playerName, ['is_mobile' => $is_mobile]);

			if (isset($rlt['success']) && $rlt['success']) {
				$data = array(
					'url' => $rlt['url'],
					'platformName' => $platformName,
				);
				$this->load->view('iframe/game_iframe', $data);
				return;
			} else {
				// echo 'goto error';
				die(lang('goto_game.error'));
			}

		} else {
			die(lang('goto_game.blocked'));
		}

	}

	/**
	 * overview : go to wft game
	 * @param string $language
	 */
	public function goto_wftgame($language = 'ZH-CN', $is_mobile = null) {
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = WFT_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('WFT');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_name = $this->authentication->getUsername();
		$player_id = $this->authentication->getPlayerId();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# NOT LOGIN, GOTO DEMO GAME
		if (!$player_name && !$player_id) {
			$this->utils->debug_log('Not Login');
			$this->goto_demo_game($game_platform_id, null, $language);
			return;
		}

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists']) {
			$rlt = $this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			if (!$rlt || !$rlt['success']) {
				//create player failed
				die(lang('goto_game.error'));
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
		$rlt = $api->queryForwardGame($player_name, array('lang' => $language, 'is_mobile' => $is_mobile));
		if ($rlt && $rlt['success']) {
			if($is_mobile){
				redirect($rlt['url']);
			}
			$platformName = $this->external_system->getNameById($game_platform_id);
			$this->load->view('iframe/game_iframe', array('url' => $rlt['url'], 'platformName' => $platformName, 'iframeName' => 'sportFrame'));
		} else {
			if (isset($rlt['message'])) {
				$message = $rlt['message'];
			} else {
				$message = 'goto_game.error';
			}
			die(lang($message));
		}
	}

	/**
	 * overview : go to haba game
	 *
	 * @param string	$game_platform_id
	 * @param int		$game_type
	 * @param string 	$platform
	 */
	public function goto_fishinggame($game_platform_id, $game_type = null, $platform = null, $is_mobile = null) {
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}
		$this->utils->debug_log('GOTO FISNING GAME!');
		$lang = $this->language_function->getCurrentLanguage();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('FISHING');
            return;
        }
		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);
		$platformName = $this->external_system->getNameById($game_platform_id);

		if (!$blocked) {
			$this->_transferAllWallet($player_id, $playerName, $game_platform_id);

			$extra = array();
			$extra['language'] = $lang;
			$extra['platform'] = $platform;
			$extra['game_type'] = $game_type;
			$extra['is_mobile'] = $is_mobile;
			$params = $api->queryForwardGame($playerName, $extra);
			if (isset($params['success']) && $params['success']) {
				redirect($params['url']);
				return;
			} else {
				die(lang('goto_game.error'));
			}

		} else {
			die(lang('goto_game.blocked'));
		}
	}


	/**
	 * overview : go to haba game
	 *
	 * @param string	$mode
	 * @param int		$gameId
	 * @param string 	$language
	 */
	public function goto_habagame_common($game_platform_id, $mode, $gameId, $language = 'zh-CN', $redirection = "iframe") {
		$is_mobile = $this->utils->is_mobile();
		$lobbyurl = $is_mobile ? $this->utils->getSystemUrl('m').$this->utils->getConfig('lobby_extension') : $this->utils->getSystemUrl('player').$this->utils->getConfig('lobby_extension');
		$currentLang = $this->language_function->getCurrentLanguage();

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'game_description_model'));
        $this->checkGameIfLaunchable($game_platform_id,$gameId);

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            return $this->goto_maintenance('HABA');
        }
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$required_login_on_launching_trial = $api->getSystemInfo('required_login_on_launching_trial', false);
		$favicon_brand = $api->getSystemInfo('favicon', false);
		$platformName = $this->game_description_model->getGameNameByCurrentLang($gameId, $game_platform_id);

		# added demo modes
		$demo_modes = ['fun','demo','trial'];
		if (in_array(strtolower($mode),$demo_modes)) {
			# will redirect to login page when required login on trial game
			if($required_login_on_launching_trial && !$this->authentication->isLoggedIn()){
				$this->goPlayerLogin();
			}

			$params = array(
				'game_mode' => $mode,
				'game_code' => $gameId,
				'language' => $currentLang,
				'lobby_url' => $lobbyurl,
				'is_mobile' => $is_mobile,
				'redirection' => $redirection,
			);

			$data = $api->queryForwardGame($playerName,$params);
			if (isset($data['success']) && $data['success']) {
	            if($is_mobile || $redirection!='iframe'){
					return redirect($data['url']);
	            }else{
	                return $this->load->view('iframe/game_iframe', array('platformName' => $platformName, 'url' => $data['url'], 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => $api->allow_fullscreen));
	            }
	        }
		}

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);

		if (!$blocked) {
			$this->_transferAllWallet($player_id, $playerName, $game_platform_id);

			$params = array(
				'game_mode' => $mode,
				'game_code' => $gameId,
				'language' => $currentLang,
				'is_mobile' => $is_mobile,
				'lobby_url' => $lobbyurl,
				'redirection' => $redirection,
			);

			$data = $api->queryForwardGame($playerName,$params);

			if (isset($data['success']) && $data['success']) {
				if($is_mobile || $redirection!='iframe'){
					return redirect($data['url']);
				}else{
					return $this->load->view('iframe/game_iframe', array('platformName' => $platformName, 'url' => $data['url'], 'favicon_brand' => $favicon_brand));
				}
			} else {
				die(lang('goto_game.error'));
			}
		} else {
            $this->goBlock();
		}
	}

	/**
	 * overview : go to haba game
	 *
	 * @param string	$mode
	 * @param int		$gameId
	 * @param string 	$language
	 */
	public function goto_habagame($mode, $gameId, $language = null, $redirection = "iframe") {
		$is_mobile = $this->utils->is_mobile();
		$lobbyurl = $is_mobile ? $this->utils->getSystemUrl('m').$this->utils->getConfig('lobby_extension') : $this->utils->getSystemUrl('player').$this->utils->getConfig('lobby_extension');
		if(isset($language) && !empty($language)) {
			$currentLang = $language;
		} else {
			$currentLang = $this->language_function->getCurrentLanguage();
		}

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = HB_API;
        $this->checkGameIfLaunchable($game_platform_id,$gameId);

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            return $this->goto_maintenance('HABA');
        }
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$required_login_on_launching_trial = $api->getSystemInfo('required_login_on_launching_trial', false);
		$platformName = $this->external_system->getNameById($game_platform_id);

		$this->checkGameFlag($api, $game_platform_id, $gameId);

		# added demo modes
		$demo_modes = ['fun','demo','trial'];
		if (in_array(strtolower($mode),$demo_modes)) {
			# will redirect to login page when required login on trial game
			if($required_login_on_launching_trial && !$this->authentication->isLoggedIn()){
				$this->goPlayerLogin();
			}

			$params = array(
				'game_mode' => $mode,
				'game_code' => $gameId,
				'language' => $currentLang,
				'lobby_url' => $lobbyurl,
				'is_mobile' => $is_mobile,
				'redirection' => $redirection,
			);

			$data = $api->queryForwardGame($playerName,$params);
			if (isset($data['success']) && $data['success']) {
	            if($is_mobile || $redirection!='iframe'){
					return redirect($data['url']);
	            }else{
	                return $this->load->view('iframe/game_iframe', array('platformName' => $platformName, 'url' => $data['url'], 'allow_fullscreen' => $api->allow_fullscreen));
	            }
	        }
		}

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);

		if (!$blocked) {
			$_transferResult = $this->_transferAllWallet($player_id, $playerName, $game_platform_id);

			$params = array(
				'game_mode' => $mode,
				'game_code' => $gameId,
				'language' => $currentLang,
				'is_mobile' => $is_mobile,
				'lobby_url' => $lobbyurl,
				'redirection' => $redirection,
			);

			$data = $api->queryForwardGame($playerName,$params);
			$this->playerTrackingEventPlayNow($game_platform_id, $data, $params, $_transferResult);
			if (isset($data['success']) && $data['success']) {
				if($is_mobile || $redirection!='iframe'){
					return redirect($data['url']);
				}else{
					return $this->load->view('iframe/game_iframe', array('platformName' => $platformName, 'url' => $data['url']));
				}
			} else {
				die(lang('goto_game.error'));
			}
		} else {
            $this->goBlock();
		}

	}

	/**
	 * overview : go to uc game
	 *
	 * @param string	$mode
	 * @param int		$gameId
	 * @param string	$platform
	 * @param string 	$language
	 */
	public function goto_ucgame($game_mode="real", $game_code) {
		$is_mobile = $this->utils->is_mobile();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = UC_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('UC');
            return;
        }
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);
		if ($mode == 'fun') {
			$params = array(
				'game_mode' => $game_mode, //fun or real
				'game_code' => $extra['game_code'],
				'language' => $this->language_function->getCurrentLanguage(),
				'is_mobile' => $is_mobile,
			);
			$data = $api->queryForwardGame($player_name, $params);
			if (isset($data['success']) && $data['success']) {
				$this->load->view('iframe/player/goto_ucgame', array('url' => $data['Url'], 'platformName' => $platformName));
				return;
			} else {
				die(lang('goto_game.error'));
			}
			return;
		}

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();


        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }
		//$this->utils->debug_log('----------------nt seesion info ++++++', $session_id, $player_id, $playerName, $this->session->all_userdata());
		# LOAD GAME API

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);

		if (!$blocked) {
			$this->_transferAllWallet($player_id, $playerName, $game_platform_id);

			$params = array(
				'mode' => $mode, //fun or real
				'game_code' => $gameId,
				'lang' => $language,
				'platform_id' => $platform,
				'is_mobile' => $is_mobile,
			);
			$data = $api->queryForwardGame($playerName, $params);
			if (isset($data['success']) && $data['success']) {
				$this->load->view('iframe/player/goto_ucgame', array('url' => $data['Url'], 'platformName' => $platformName));
				return;
			} else {
				die(lang('goto_game.error'));
			}
		} else {
			die(lang('goto_game.blocked'));
		}

	}

	public function goto_ebetbbingame() {
		$currentLang = $this->language_function->getCurrentLanguage();
		$language = ($currentLang == '1') ? 'en-us' : 'zh-cn';

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = EBET_BBIN_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('EBETBBIN');
            return;
        }
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);

		if (!$blocked) {
			$this->_transferAllWallet($player_id, $playerName, $game_platform_id);

			$params = array(
				'lang' => $language,
			);
			$data = $api->queryForwardGame($playerName, $params);
			$this->utils->debug_log("queryForwardGame", var_export($data, true));
			if (isset($data['success']) && $data['success']) {
				$this->load->view('iframe/game_iframe', array('url' => $data['result'], 'platformName' => $platformName));
				return;
			} else {
				die(lang('goto_game.error'));
			}
		} else {
			die(lang('goto_game.blocked'));
		}
	}

	/**
	 * overview : go to KUMA game
	 *
	 * @param string	$mode
	 * @param int		$gameId
	 * @param string	$platform
	 * @param string 	$language
	 */
	public function goto_kumagame($gameId, $trial = null, $language = 'zh-cn', $is_mobile) {

		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		$currentLang = $this->language_function->getCurrentLanguage();
		$language = ($currentLang == '1') ? 'en-us' : "zh-cn";
		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = KUMA_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('KUMA');
            return;
        }
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

		if ($trial == "fun") {
			$params['trial'] = true;
			$data = $api->queryForwardGame(null, $gameId, $params);
			$this->load->view('iframe/player/goto_kumagame', array('url' => $data['url'], 'platformName' => $platformName));
			return;
		}

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# LOAD GAME API

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);

		if (!$blocked) {
			$this->_transferAllWallet($player_id, $playerName, $game_platform_id);

			$params = array(
				'lang' => $language,
				'trial' => false,
				'is_mobile' => $is_mobile,
			);
			$data = $api->queryForwardGame($playerName, $gameId, $params);
			if (isset($data['success']) && $data['success']) {
				$this->load->view('iframe/player/goto_kumagame', array('url' => $data['url'], 'platformName' => $platformName));
				return;
			} else {
				die(lang('goto_game.error'));
			}
		} else {
			die(lang('goto_game.blocked'));
		}

	}

	/**
	 * overview : go to EBET KUMA game
	 *
	 * @param int		$gameId
	 *
	 */
	public function goto_ebetkumagame($gameId) {
		$is_mobile = $this->utils->is_mobile();

		$language = $this->language_function->getCurrentLanguage();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = EBET_KUMA_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('EBET KUMA');
            return;
        }

		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }
		# LOAD GAME API

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);

		if (!$blocked) {
			$this->_transferAllWallet($player_id, $playerName, $game_platform_id);

			$params = array(
				'lang' => $language,
				'gameId' => $gameId,
				'is_mobile' => $is_mobile,
			);

			$data = $api->queryForwardGame($playerName, $params);
			if (isset($data['success']) && $data['success']) {
				$this->load->view('iframe/game_iframe', array('platformName' => $platformName, 'url' => $data['url']));
				return;
			} else {
				die(lang('goto_game.error'));
			}
		} else {
			die(lang('goto_game.blocked'));
		}

	}

	/**
	 * overview : go to EBET GG FISHING game
	 *
	 * @param int		$gameCode
	 *
	 */
	public function goto_ebetmwgfishing($gameCode='imfishing10002') {
		$is_mobile = $this->utils->is_mobile();

		$language = $this->language_function->getCurrentLanguage();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = EBET_GGFISHING_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('EBET MWG');
            return;
        }

		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# LOAD GAME API

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);

		if (!$blocked) {
			$this->_transferAllWallet($player_id, $playerName, $game_platform_id);

			$params = array(
				'language' => $language,
				'gameCode' => $gameCode,
				'is_mobile' => $is_mobile,
			);

			$data = $api->queryForwardGame($playerName, $params);
			if (isset($data['success']) && $data['success']) {
				if($is_mobile){
					redirect($data['url']);
				}else{
					$this->load->view('iframe/game_iframe', array('platformName' => $platformName, 'url' => $data['url']));
					return;
				}
			} else {
				die(lang('goto_game.error'));
			}
		} else {
			die(lang('goto_game.blocked'));
		}

	}

	/**
	 * overview : go to qt game
	 *
	 * @param int		 $game_platform_id
	 * @param string	 $game_code
	 * @param string	 $game_mode
	 * @param string	 $game_type
	 */
	public function goto_qtgame($game_platform_id, $game_code = null, $game_mode = null, $game_type = null) {
        $language = $this->language_function->getCurrentLanguage();
        $is_mobile = $this->utils->is_mobile();
        $this->gotogame($game_platform_id, $game_code, $game_mode, $game_type,null,$language,null,$is_mobile);
    }

	/**
	 * overview : go to ttg game
	 *
	 * @param int		 $game_id
	 * @param string	 $game_code
	 * @param int		 $game_type
	 * @param string	 $game_mode
	 */
	public function goto_ttggame($game_id, $game_code, $game_type = 0, $game_mode = 'real', $is_mobile = null, $target = 'redirect') {

		$this->load->model('external_system');

		$isMobile = $this->utils->is_mobile();
		$language = $this->language_function->getCurrentLanguage();

        $game_platform_id = TTG_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('TTG');
            return;
        }
		if ($game_mode != 'real') {

			if ($this->external_system->isGameApiActive($game_platform_id)) {

                $this->checkGameIfLaunchable($game_platform_id,$game_code,$game_id);

				$platformName = $this->external_system->getNameById($game_platform_id);

				$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
				$result = $api->queryForwardGame(null, array(
					'game_id' => $game_id,
					'game_code' => $game_code,
					'game_type' => $game_type,
					'language' => $language,
					'is_mobile' => $is_mobile,
					'game_mode' => $game_mode,
				));

				if ($result['success']) {
					if($isMobile){
						redirect($result['url']);
					}else{
						if($target == 'redirect'){
							redirect($result['url']);
						}else{
							$this->load->view('iframe/game_iframe', array('platformName' => $platformName, 'url' => $result['url']));
						}
					}
				} else {
					$this->returnBadRequest();
				}

			} else {
				$this->goto_maintenance('ttg');
			return;
			}

		} else {
			if ($this->authentication->isLoggedIn()) {
				$extra = array();
				$extra["is_mobile"] = $isMobile;
				$extra["game_id"] = $game_id;
				$this->gotogame(TTG_API, $game_code, $game_mode, $game_type, null, $language,$extra,true);
			} else {
				$this->goPlayerLogin();
			}
		}
		return;
	}

	/**
	 * overview : go to gd game
	 *
	 * @param string	 $game_code
	 * @param string	 $is_mobile [true/false]
	 * @param string	 $game_type [slots/live]
	 * @param string	 $game_mode [real/fun/demo]
	 */
	public function goto_gdgame($game_code = null,$game_type = "live", $game_mode = "real") {
		$is_mobile = $this->utils->is_mobile();
		//http://player.og.local/iframe_module/goto_gdgame/RNG14408
		$language = $this->language_function->getCurrentLanguage();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = GD_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('GD');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }
		//$this->utils->debug_log('----------------nt seesion info ++++++', $session_id, $player_id, $playerName, $this->session->all_userdata());

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
		$this->checkBlockGamePlatformSetting($game_platform_id);

        $this->checkGameIfLaunchable($game_platform_id,$game_code);
		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists']) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);

		$platformName = $this->external_system->getNameById($game_platform_id);

		if (!$blocked) {
			$this->_transferAllWallet($player_id, $playerName, $game_platform_id);

			$extra = array(
				'language' => $language,
				'game_code' => $game_code,
				'game_mode' => $game_mode,
				'is_mobile' => $is_mobile,
				'game_type' => $game_type
			);

			$rlt = $api->queryForwardGame($playerName, $extra);

			if (isset($rlt['success']) && $rlt['success']) {
				if ($is_mobile) {
            		return redirect($rlt['url']);
				}else{
					$data = array(
						'url' => $rlt['url'],
						'platformName' => $platformName,
					);
					$this->load->view('iframe/game_iframe', $data);
				}
				return;
			} else {
				// echo 'goto error';
				die(lang('goto_game.error'));
			}

		} else {
			die(lang('goto_game.blocked'));
		}

	}

	/**
	 * overview : go to gamesos game
	 *
	 * @param string	$game_code
	 * @param string 	$gamemode
	 * @param string 	$mobile
	 */
	public function goto_gamesosgame($game_code = null, $gamemode = 'fun', $mobile = 'false') {

		$currentLang = $this->language_function->getCurrentLanguage();
		$language = ($currentLang == '1') ? 'EN' : 'ZH';

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = GAMESOS_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('GAMESOS');
            return;
        }

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$mobile = strtolower($mobile);

		if ($gamemode == 'fun') {

			if ($mobile == 'true') {

				$extra = array(
					'language' => $language,
					'code' => $game_code,
					'mode' => $gamemode,
					'mobile' => $mobile,
				);
			} else {
				$extra = array(
					'language' => $language,
					'game_code' => $game_code,
					'playmode' => $gamemode,
					'mobile' => $mobile,
				);

			}

			$rlt = $api->queryForwardGame('TestFunplayer', $extra);

			if (isset($rlt['success']) && $rlt['success']) {
				$data = array(
					'url' => $rlt['url'],
					'platformName' => $platformName,
				);
				$this->load->view('iframe/game_iframe', $data);
				return;
			} else {
				// echo 'goto error';
				die(lang('goto_game.error'));
			}

			return;

		}

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();


        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }
		//$this->utils->debug_log('----------------nt seesion info ++++++', $session_id, $player_id, $playerName, $this->session->all_userdata());

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);

		$platformName = $this->external_system->getNameById($game_platform_id);

		if (!$blocked) {

			//$extra = array('game_lang' => $language, 'game_code' => $game_code);

			if ($mobile == 'true') {

				$extra = array(
					'language' => $language,
					'code' => $game_code,
					'mode' => $gamemode,
					'mobile' => $mobile,
				);
			} else {
				$extra = array(
					'language' => $language,
					'game_code' => $game_code,
					'playmode' => $gamemode,
					'mobile' => $mobile,
				);

			}

			$this->_transferAllWallet($player_id, $playerName, $game_platform_id);
			$rlt = $api->queryForwardGame($playerName, $extra);

			if (isset($rlt['success']) && $rlt['success']) {
				$data = array(
					'url' => $rlt['url'],
					'platformName' => $platformName,
				);
				$this->load->view('iframe/game_iframe', $data);
				return;
			} else {
				// echo 'goto error';
				die(lang('goto_game.error'));
			}

		} else {
			die(lang('goto_game.blocked'));
		}

	}

	/**
	 * overview : go to spade game
	 *
	 * @param string	$game_code
	 * @param string 	$fun
	 */
	public function goto_spadegame($game_code = null, $game = "real") {
		if ($game == "fun") {
			$extra = array(
					'game' => $game_code,
					'fun' => 'true',
			);
		}else{
			$extra = array(
					'game' => $game_code,
			);
		}

		$game_platform_id = SPADE_GAMING_API;
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = SPADE_GAMING_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('SPADE');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
		$this->checkBlockGamePlatformSetting($game_platform_id);

        $this->checkGameIfLaunchable($game_platform_id,$game_code);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);

		$platformName = $this->external_system->getNameById($game_platform_id);
		if (!$blocked) {
			$this->_transferAllWallet($player_id, $playerName, $game_platform_id);
			$extra['is_mobile'] = $is_mobile = $this->utils->is_mobile();
			$rlt = $api->queryForwardGame($playerName, $extra);

			if (isset($rlt['success']) && $rlt['success']) {
				if($is_mobile){
					redirect($rlt['url']);
				}
				$data = array(
					'url' => $rlt['url'],
					'platformName' => $platformName,
					'customGameJs' => array( $this->utils->processAnyUrl('/png_fullscreen.js', '/resources/png_game') )
				);
				$this->utils->debug_log(' Spade goto url data - =================================================> ' . json_encode($data,true));
				$this->load->helper('load_game_js_helper');
				$this->load->view('iframe/game_iframe', $data);
				return;
			} else {
				// echo 'goto error';
				die(lang('goto_game.error'));
			}
		}
		else{
			die(lang('goto_game.blocked'));
		}


	}

	/**
	 * overview : go to sbobet game
	 *
	 * @param string	$casino
	 */
	public function goto_sbobetgame($game = "sportsbook", $gpid = 'default', $gameid = 'default') {
        $is_mobile = $this->utils->is_mobile();

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

        // $extra['profolio'] = ($game == "casino") ? 'casino' : 'sportsbook';
        $extra['portfolio'] = strtolower($game);
		$extra['mobile'] = $is_mobile;
		$extra['gpid'] = $gpid;
		$extra['gameid'] = $gameid;

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = SBOBET_API;
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('SBOBET');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);

		$platformName = $this->external_system->getNameById($game_platform_id);
		if (!$blocked) {
			$this->_transferAllWallet($player_id, $playerName, $game_platform_id);
			$rlt = $api->queryForwardGame($playerName, $extra);
			$is_redirect = $api->getSystemInfo('is_redirect', false);

			if (isset($rlt['success']) && $rlt['success']) {
                if ($is_mobile) {
                    redirect($rlt['url']);
                } else if ($is_redirect) {
                    redirect($rlt['url']);
                } else {

                    $data = array(
                        'url' => $rlt['url'],
                        'platformName' => $platformName,
                    );
                    $this->load->view('iframe/game_iframe', $data);
                    return;
                }
			} else {
				die(lang('goto_game.error'));
			}
		}
		else{
			die(lang('goto_game.blocked'));
		}
	}

	/**
	 * overview : go to EBET BBTECH game
	 *
	 * @param string	$game_code
	 * @param string 	$fun
	 */
	public function goto_ebetbbtech($game_code = null, $game = "real") {
		if ($game == "fun") {
			$extra = array(
					'game_name' => $game_code,
			);
		}else{
			$extra = array(
					'game_name' => $game_code,
			);
		}

		$game_platform_id = EBET_BBTECH_API;
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = EBET_BBTECH_API;

		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('EBETBBTECH');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}
		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);

		$platformName = $this->external_system->getNameById($game_platform_id);
		if (!$blocked) {
			$this->_transferAllWallet($player_id, $playerName, $game_platform_id);
			$rlt = $api->queryForwardGame($playerName, $extra);

			if (isset($rlt['success']) && $rlt['success']) {
				$data = array(
					'url' => $rlt['url'],//'http://api.egame.staging.sgplay.net/okada/auth/?acctId=t1testj&language=zh_CN&token=5aedff1625be6409cde181ddcf1cca67&game=S-DG03',
					'platformName' => $platformName,
				);
				$this->utils->debug_log(' EBETBBTECH goto url data - =================================================> ' . json_encode($data,true));
				$this->load->view('iframe/game_iframe', $data);
				return;
			} else {
				// echo 'goto error';
				die(lang('goto_game.error'));
			}
		}
		else{
			die(lang('goto_game.blocked'));
		}


	}

	/**
	 * overview : go to EBET IMPT game
	 *
	 * @param string	$game_code
	 * @param string 	$fun
	 */
	public function goto_ebetimpt($game_code = null, $game = "real", $type = null) {

		if ($game == "fun" || $game=="trial") {
			$extra = array(
				"mode"		=> "fun",
				'game_name' => $game_code,
			);
		}else{
			$extra = array(
				"mode"		=> $game,
				'game_name' => $game_code,
				'type'		=> $type
			);
		}

		$game_platform_id = EBET_IMPT_API;
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = EBET_IMPT_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('EBETIMPT');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}
		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);

		$platformName = $this->external_system->getNameById($game_platform_id);
		if (!$blocked) {
			$this->_transferAllWallet($player_id, $playerName, $game_platform_id);

			$currentLang = $this->language_function->getCurrentLanguage();
			$language = ($currentLang == '1') ? 'EN' : 'ZH-CN';
			$extra['language']=$language;

			$rlt = $api->queryForwardGame($playerName, $extra);
			// echo"<pre>";print_r($rlt);exit();
			if (isset($rlt['success']) && $rlt['success']) {
				$data = array(
					'url' => $rlt['url'],//'http://api.egame.staging.sgplay.net/okada/auth/?acctId=t1testj&language=zh_CN&token=5aedff1625be6409cde181ddcf1cca67&game=S-DG03',
					'platformName' => $platformName,
				);
				$this->utils->debug_log(' EBETIMPT goto url data - =================================================> ' . json_encode($data,true));
				$this->load->view('iframe/game_iframe', $data);
				return;
			} else {
				// echo 'goto error';
				die(lang('goto_game.error'));
			}
		}
		else{
			die(lang('goto_game.blocked'));
		}
	}

	/**
	 * overview : go to Gameplay Sbtech game
	 *
	 * @param string	$sportsbook
	 */
	public function goto_gameplaySbtech($sportsbook = "AsianSportsbook") {
		$extra = array(
				'game' => $sportsbook
		);

		$game_platform_id = GAMEPLAY_SBTECH_API;
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = GAMEPLAY_SBTECH_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('GAMEPLAY SBTECH');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName);

		# IF NOT CREATE PLAYER
        if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
            if(!is_null($player['exists'])){
                $this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
            }
        }
		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);

		$platformName = $this->external_system->getNameById($game_platform_id);
		if (!$blocked) {
			$this->_transferAllWallet($player_id, $playerName, $game_platform_id);
			$rlt = $api->queryForwardGame($playerName, $extra);

			if (isset($rlt['success']) && $rlt['success']) {
				$data = array(
					'url' => $rlt['url'],//'http://api.egame.staging.sgplay.net/okada/auth/?acctId=t1testj&language=zh_CN&token=5aedff1625be6409cde181ddcf1cca67&game=S-DG03',
					'platformName' => $platformName,
				);
				$this->utils->debug_log(' Gameplay SBTECH goto url data - =================================================> ' . json_encode($data,true));
				$this->load->view('iframe/game_iframe', $data);
				return;
			} else {
				die(lang('goto_game.error'));
			}
		}
		else{
			die(lang('goto_game.blocked'));
		}
	}


	public function goto_gsbbingame($gameType = null, $language = 'zh-cn', $is_mobile = null) {

		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		$this->load->model(array('game_provider_auth', 'external_system'));

		$game_platform_id = GSBBIN_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('GSBBIN');
            return;
        }

		$player_name = $this->authentication->getUsername();
		$player_id = $this->authentication->getPlayerId();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		$platformName = $this->external_system->getNameById($game_platform_id);

		$success = $this->prepareGotoGame($player_name, $player_id, $game_platform_id);
		$data = array('platformName' => $platformName);

		if ($success) {
			$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$rlt = $api->queryForwardGame($player_name, array('language' => $language, 'gameType' => $gameType,
					'is_mobile' => $is_mobile));

			if ($rlt['success']) {
				$data['full_html'] = $rlt['html'];
			} else if (isset($rlt['message'])) {
				$data['error_message'] = $rlt['message'];
			} else if (isset($rlt['message_lang'])) {
				$data['error_message_lang'] = $rlt['message_lang'];
			} else {
				$data['error_message_lang'] = 'goto_game.error';
			}
		}

		$this->load->view('share/goto_game_iframe', $data);
	}

	public function goto_finance_game() {

		$this->load->model(array('external_system', 'game_provider_auth'));

		$game_platform_id = FINANCE_API;

		$player_name = $this->authentication->getUsername();
		$player_id = $this->authentication->getPlayerId();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('FINANCE');
            return;
        }

		$is_mobile = $this->utils->is_mobile();

		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$player = $api->isPlayerExist($player_name);

		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		$blocked = $api->isBlocked($player_name);
		if (!$blocked) {

			$extra = array();
			$extra['language'] = $this->language_function->getCurrentLanguage();
			$extra['is_mobile'] = $is_mobile;

			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$rlt = $api->queryForwardGame($player_name, $extra);

			$this->CI->utils->debug_log("goto finance gaming", $rlt['url']);
			$platformName = $this->external_system->getNameById($game_platform_id);

			$rlt['platformName'] = $platformName;
			$rlt['is_mobile'] = $is_mobile;
			$rlt['url'] = $rlt['url'].'?'.http_build_query(array('ptype' => $rlt['ptype'], 'mchId' => $rlt['mchId'], 'token' => $rlt['token']));
			$this->load->view('iframe/player/goto_finance_game', $rlt);

		}  else {
			die(lang('goto_game.blocked'));
		}

	}

	public function goto_hg_game($game_type = null) {

		$this->load->model(array('external_system', 'game_provider_auth'));

		$game_platform_id = HG_API;

		$player_name = $this->authentication->getUsername();
		$player_id = $this->authentication->getPlayerId();


        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }
        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('HG');
            return;
        }

		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$player = $api->isPlayerExist($player_name);

		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		$blocked = $api->isBlocked($player_name);
		if (!$blocked) {

			$extra = array();
			$extra['language'] = $this->language_function->getCurrentLanguage();
			if(!empty($game_type)) {
				$extra['game_type'] = $game_type;
			}

			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$rlt = $api->queryForwardGame($player_name, $extra);

			$this->CI->utils->debug_log("goto evolution gaming", $rlt['url']);
			$platformName = $this->external_system->getNameById($game_platform_id);

			$data = array('url' => $rlt['url'], 'platformName' => $platformName,);

			$this->load->view('iframe/game_iframe', $data);

		}  else {
			die(lang('goto_game.blocked'));
		}

	}

	public function goto_ig_game($game_type='lottery') {

		$this->load->model(array('external_system', 'game_provider_auth'));

		$game_platform_id = IG_API;

		$player_name = $this->authentication->getUsername();
		$player_id = $this->authentication->getPlayerId();


		#BLOCK LOGIN GAME BY USER STATUS
		if($this->CI->utils->blockLoginGame($player_id)){
			$this->goBlock();
			return;
		}
        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('IG');
            return;
        }

		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$player = $api->isPlayerExist($player_name);

		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		$is_mobile = $this->utils->is_mobile();

		$blocked = $api->isBlocked($player_name);
		if (!$blocked) {

			$extra = array();
			$extra['language'] = $this->language_function->getCurrentLanguage();
			$extra['is_mobile'] = $is_mobile;
			$extra['game_type'] = $game_type;

			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$rlt = $api->queryForwardGame($player_name, $extra);

			$this->CI->utils->debug_log("goto ig game", $rlt['url']);
			$platformName = $this->external_system->getNameById($game_platform_id);

			$data = array('url' => $rlt['url'], 'platformName' => $platformName,);

			if($is_mobile){
				redirect($data['url']);
			}else{
				$this->load->view('iframe/game_iframe', $data);
			}

		}  else {
			die(lang('goto_game.blocked'));
		}

	}

	public function goto_ole777sports_game($game_type=null) {
		return $this->goto_sbtech_game($game_type);
	}

	public function goto_sbtech_game($game_type=null) {

		$this->load->model(array('external_system', 'game_provider_auth'));

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

		$game_platform_id = SBTECH_API;

		$player_name = $this->authentication->getUsername();
		$player_id = $this->authentication->getPlayerId();


		#BLOCK LOGIN GAME BY USER STATUS
		if($this->CI->utils->blockLoginGame($player_id)){
			$this->goBlock();
			return;
		}
        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('SBTECH');
            return;
        }

		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$player = $api->isPlayerExist($player_name);

		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		$is_mobile = $this->utils->is_mobile();

		$blocked = $api->isBlocked($player_name);
		if (!$blocked) {

			$extra = array();
			$extra['language'] = $this->language_function->getCurrentLanguage();
			$extra['is_mobile'] = $is_mobile;
			$extra['game_type'] = $game_type;

			$_transferResult = $this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$rlt = $api->queryForwardGame($player_name, $extra);
			$this->playerTrackingEventPlayNow($game_platform_id, $rlt, $extra, $_transferResult);
			$this->CI->utils->debug_log("goto ig game", $rlt['url']);
			$platformName = $this->external_system->getNameById($game_platform_id);

			$data = array('url' => $rlt['url'], 'platformName' => $platformName,
					'set_min_width' =>  $rlt['set_min_width'], 'default_frame_min_width' =>  $rlt['default_frame_min_width']);

			if($is_mobile || !$rlt['use_iframe_in_web_launch']){
				redirect($data['url']);
			}else{
				$this->load->view('iframe/game_iframe', $data);
			}

		}  else {
			die(lang('goto_game.blocked'));
		}

	}

	public function goto_sbtech_bti_game($game_code=null) {

		$this->load->model(array('external_system', 'game_provider_auth'));

		$game_platform_id = SBTECH_BTI_API;

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$is_mobile = $this->utils->is_mobile();
		$player_name = $this->authentication->getUsername();
		$player_id = $this->authentication->getPlayerId();
		$extra = array();
		$extra['language'] = $this->language_function->getCurrentLanguage();
		$extra['is_mobile'] = $is_mobile;
		$extra['game_code'] = $game_code;
		#NO LOGIN PLAYER
		if (empty($player_name)) {
			$isPlayerNotLogin = $this->authentication->isLoggedIn();

            //check if not login
            if (!$isPlayerNotLogin && $api->is_redirect_to_login) {
               redirect($this->goPlayerLogin());
            }

			$rlt = $api->queryForwardGame($player_name, $extra);

			$platformName = $this->external_system->getNameById($game_platform_id);

			$data = array('url' => $rlt['url'], 'platformName' => $platformName,
					'set_min_width' =>  $rlt['set_min_width'], 'default_frame_min_width' =>  $rlt['default_frame_min_width']);
			if($is_mobile || !$rlt['use_iframe_in_web_launch']){
				redirect($data['url']);
			}else{
				return $this->load->view('iframe/game_iframe', $data);
			}
		}

		#BLOCK LOGIN GAME BY USER STATUS
		if($this->CI->utils->blockLoginGame($player_id)){
			$this->goBlock();
			return;
		}
        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('SBTECH_BTI_API');
            return;
        }

		$player = $this->checkExistOnApiAndUpdateRegisterFlag($api,$player_id,$player_name);
		$blocked = $api->isBlocked($player_name);

		if (!$blocked) {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$rlt = $api->queryForwardGame($player_name, $extra);

			$this->CI->utils->debug_log("goto SBTECH BTI game", $rlt['url']);
			$platformName = $this->external_system->getNameById($game_platform_id);

			$data = array('url' => $rlt['url'], 'platformName' => $platformName,
					'set_min_width' =>  $rlt['set_min_width'], 'default_frame_min_width' =>  $rlt['default_frame_min_width']);

			if($is_mobile || !$rlt['use_iframe_in_web_launch']){
				redirect($data['url']);
			}else{
				$this->load->view('iframe/game_iframe', $data);
			}

		}  else {
			die(lang('goto_game.blocked'));
		}

	}

	public function goto_ebet_ag($game_type = null) {

		$this->load->model(array('external_system', 'game_provider_auth'));

		$game_platform_id = EBET_AG_API;

		$player_name = $this->authentication->getUsername();
		$player_id = $this->authentication->getPlayerId();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('EBET AG');
            return;
        }

		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$player = $api->isPlayerExist($player_name);

		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		$is_mobile = $this->utils->is_mobile();

		$blocked = $api->isBlocked($player_name);
		if (!$blocked) {

			$extra = array();
			$extra['language'] = $this->language_function->getCurrentLanguage();
			if(!empty($game_type)) {
				$extra['game_type'] = $game_type;
			}
			$extra['is_mobile'] = $is_mobile;

			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$rlt = $api->queryForwardGame($player_name, $extra);

			$this->CI->utils->debug_log("goto ebet ag gaming", $rlt['url']);
			$platformName = $this->external_system->getNameById($game_platform_id);

			$data = array('url' => $rlt['url'], 'platformName' => $platformName,);

			$this->load->view('iframe/game_iframe', $data);

		}  else {
			die(lang('goto_game.blocked'));
		}

	}

	/**
	 * overview : go to XYZ BLUE MINI GAMES
	 *
	 * @param string	$game_name
	 * @param integer	$soundInfo
	 */
	public function goto_xyzBlueMinigames($game_name = 'football', $sound_info = 0, $is_mobile =false, $mode ='real') {
		$extra = array(
				"game_name" => $game_name,
 				"sound_info" => $sound_info,
 				"is_mobile"	=> (!empty($is_mobile)) ? $is_mobile : $this->utils->is_mobile(),
 				"mode"	=> $mode
		);

		$game_platform_id = XYZBLUE_API;
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = XYZBLUE_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('XYZ BLUE');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName,$extra);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api, $extra);
			}
		}
		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);

		$platformName = $this->external_system->getNameById($game_platform_id);
		if (!$blocked) {
			// $this->_transferAllWallet($player_id, $playerName, $game_platform_id);
			$rlt = $api->queryForwardGame($playerName, $extra);
			// echo"<pre>";print_r($rlt);exit();
			if (isset($rlt['success']) && $rlt['success']) {
				$data = array(
					'url' => $rlt['url'],//'http://api.egame.staging.sgplay.net/okada/auth/?acctId=t1testj&language=zh_CN&token=5aedff1625be6409cde181ddcf1cca67&game=S-DG03',
					'platformName' => $platformName,
				);
				$this->utils->debug_log(' XYZBLUE_API goto url data - =================================================> ' . json_encode($data,true));

				if($is_mobile){
					redirect($data['url']);
				}else{
					$this->load->view('iframe/game_iframe', $data);
				}

				return;
			} else {
				// echo 'goto error';
				die(lang('goto_game.error'));
			}
		}
		else{
			die(lang('goto_game.blocked'));
		}
	}

	/**
	 * overview : go to Extreme live gaming
	 * @param string 	$game
	 * @param string	$mode
	 * @param boolean	$is_mobile
	 */
	public function goto_extreme($game = "blackjack", $is_mobile = false, $mode ="real") {
		$currentLang = $this->language_function->getCurrentLanguage();
		$extra = array(
				"game" => $game,
				"is_mobile"	=> (!empty($is_mobile)) ? $is_mobile : $this->utils->is_mobile(),
				"language" => $currentLang,
		);
		$game_platform_id = EXTREME_LIVE_GAMING_API;
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('EXREME LIVE');
            return;
        }
		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }
		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
		$this->checkBlockGamePlatformSetting($game_platform_id);
		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName,$extra);
		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api, $extra);
			}
		}
		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);
		$platformName = $this->external_system->getNameById($game_platform_id);
		if (!$blocked) {
			$rlt = $api->queryForwardGame($playerName, $extra);

			if (isset($rlt['success']) && $rlt['success']) {
				$data = array(
					'url' => $rlt['url'],//'https://integration.extremelivegaming.uk/api/secure/ClientAuthentication?cCode=ACC&caID=o0r9fppakkiwqmsdshak2v68piayjs37&caID_pass=Beteast1&caUserID=testelg&sessionID=98724b46ad3e4626d99427a76c24dbdf&output=0&clienttype=flash&page=105 sample url',
					'platformName' => $platformName,
				);
				$this->utils->debug_log('Extreme goto url data - =================================================> ' . json_encode($data,true));
				if($is_mobile){
					redirect($data['url']);
				}else{
					$this->load->view('iframe/game_iframe', $data);
				}
				return;
			} else {
				die(lang('goto_game.error'));
			}
		}
		else{
			die(lang('goto_game.blocked'));
		}
	}

	/**
	 * overview : go to GG POKER
	 */
	public function goto_ggpoker($landing_page = 'download', $language = "zh-cn") {
		// $currentLang = $this->language_function->getCurrentLanguage();
		$available_pages = array("web","download");
		if (!in_array($landing_page, $available_pages))
		{
			die(lang('goto_game.error'));
		}
		$is_mobile = $this->utils->is_mobile();
		$extra = array(
				"is_mobile"	=> $is_mobile,
				"language" => $language,
				"landing_page" => $landing_page,
		);
		$game_platform_id = GGPOKER_GAME_API;
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('GG POKER');
            return;
        }
		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }
		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
		$this->checkBlockGamePlatformSetting($game_platform_id);
		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName,$extra);
		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api, $extra);
			}
		}
		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);
		$platformName = $this->external_system->getNameById($game_platform_id);
		if (!$blocked) {
			$rlt = $api->queryForwardGame($playerName, $extra);

			if (isset($rlt['success']) && $rlt['success']) {
				$data = array(
					'url' => $rlt['url'],//'http://contents.sdock.net/leaflet/ggpokersite?lang=zh-cn&btag1=tot&btag2=juwang',
					'platformName' => $platformName,
				);
				$this->utils->debug_log('GGPOKER goto url data - =================================================> ' . json_encode($data,true));
				if($is_mobile || $landing_page == "web"){
					redirect($data['url']);
				}else{
					$this->load->view('iframe/game_iframe', $data);
				}
				return;
			} else {
				die(lang('goto_game.error'));
			}
		}
		else{
			die(lang('goto_game.blocked'));
		}
	}

	/**
	 * overview : go to Genesis m4
	 */
	public function goto_genesism4_entaplay($game_name, $mode = 'real', $type='slots') {
		$this->goto_genesism4($game_name, $mode, $type);
	}

	public function goto_genesism4($game_name, $mode = 'real', $type='slots') {
		$currentLang = $this->language_function->getCurrentLanguage();
		$is_mobile = $this->utils->is_mobile();
		if($mode == "fun" || $mode == "demo" || $mode == "play"){
			$mode = "trial";
		}
		$extra = array(
     			"type" => $type,
				'game_name' => $game_name,
				'mode' => $mode,
				'language' => $currentLang,
				'is_mobile' => $is_mobile
		);
		$game_platform_id = GENESISM4_GAME_API;
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));
        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('GENESIS M4');
            return;
        }
		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();

		# CHECK IF USER IS USING TRIAL
		if(empty($player_id) && $mode == 'trial'){
			$extra['extra']['t1_lobby_url'] = $this->get_lobby_url($api->getSystemInfo('return_slot_url',null));
			$rlt = $api->queryForwardGame(null, $extra);

			if (isset($rlt['success']) && $rlt['success']) {
				$data = array(
					'url' => $rlt['url'],//'http://contents.sdock.net/leaflet/ggpokersite?lang=zh-cn&btag1=tot&btag2=juwang',
					'platformName' => $platformName,
				);
				$this->utils->debug_log('GENESIS M4 goto url data - =================================================> ' . json_encode($data,true));
				if($is_mobile){
					redirect($data['url']);
				}else{
					$this->load->view('iframe/game_iframe', $data);
				}
				return;
			} else {
				die(lang('goto_game.error'));
			}
		}

		# IF NOT LOGIN
		if (!$this->authentication->isLoggedIn() && $mode == 'real') {
                $this->goPlayerLogin();
        }

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

        # CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
		$this->checkBlockGamePlatformSetting($game_platform_id);
		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName,$extra);
		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api, $extra);
			}
		}

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);
		$platformName = $this->external_system->getNameById($game_platform_id);
		if (!$blocked) {
			$extra['extra']['t1_lobby_url'] = $this->get_lobby_url($api->getSystemInfo('return_slot_url',null));
			$rlt = $api->queryForwardGame($playerName, $extra);

			if (isset($rlt['success']) && $rlt['success']) {
				$data = array(
					'url' => $rlt['url'],//'http://contents.sdock.net/leaflet/ggpokersite?lang=zh-cn&btag1=tot&btag2=juwang',
					'platformName' => $platformName,
				);
				$this->utils->debug_log('GENESIS M4 goto url data - =================================================> ' . json_encode($data,true));
				if($is_mobile){
					redirect($data['url']);
				}else{
					$this->load->view('iframe/game_iframe', $data);
				}
				return;
			} else {
				die(lang('goto_game.error'));
			}
		}
		else{
			die(lang('goto_game.blocked'));
		}
	}

	/**
	 * overview : create player on game platform
	 *
	 * @param int	$game_platform_id
	 * @param int	$playerId
	 * @param $api
	 */
	protected function createPlayerOnGamePlatform($game_platform_id, $playerId, $api, $extra= null) {
        $this->utils->debug_log('CREATEPLAYERONGAMEPLATFORM PLAYER =====================>', $game_platform_id);

		# LOAD MODEL AND LIBRARIES
		$this->load->model('player_model');
		$this->load->library('salt');

		# GET PLAYER
		$player = $this->player_model->getPlayer(array('playerId' => $playerId));
		# DECRYPT PASSWORD
		$decryptedPwd = $this->salt->decrypt($player['password'], $this->getDeskeyOG());
		if(empty($extra)){
			$extra=[];
		}
		$extra['ip']=$this->utils->getIP();
		# CREATE PLAYER
		$player = $api->createPlayer($player['username'], $playerId, $decryptedPwd, NULL, $extra);

		$this->utils->debug_log('CREATEPLAYERONGAMEPLATFORM PLAYER =====================>['.$game_platform_id.']:',$player);

		if ($player['success']) {
			$api->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		}

	}

	/**
	 * overview : prepare go to game
	 *
	 * @param $player_name
	 * @param $player_id
	 * @param $game_platform_id
	 * @return bool
	 */
	protected function prepareGotoGame($player_name, $player_id, $game_platform_id) {

		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$success = false;
		if ($api && $api->isActive()) {

			# CHECK PLAYER IF EXIST
			// $player = $api->isPlayerExist($player_name);

			// # IF NOT CREATE PLAYER
			// if (isset($player['exists']) && !$player['exists']) {
			// 	//TODO return result
			// 	if(!is_null($player['exists'])){
			// 		$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			// 	}
			// }
			$player = $this->checkExistOnApiAndUpdateRegisterFlag($api,$player_id,$player_name);

			# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
			$this->checkBlockGamePlatformSetting($game_platform_id);

			# CHECK IF LOGGED-IN PLAYER IS BLOCKED
			$blocked = $api->isBlocked($player_name);

			if ($blocked) {
                $this->goBlock();
			}

			$success = !$blocked;
		}
		return $success;
	}

	/**
	 * overview : go to fg game
	 *
	 * @param $game_platform
	 * @param string $game_code
	 * @param string $game_mode
	 * @param string $is_mobile_flag
	 * @param string $language
	 */
	public function goto_fg($game_platform, $game_code = null, $game_mode = 'true', $is_mobile_flag = 'false', $language = null) {
		$this->utils->debug_log('GOTO FG!');
		$is_mobile_flag = $this->utils->is_mobile();
		$language = !empty($language) ? $language : $this->language_function->getCurrentLanguage();

		$extra = array(
			"is_mobile_flag" => $this->utils->is_mobile(),
			"game_platform" => $game_platform,
			"language" => $language,
		);

		$this->gotogame(FG_API,
			$game_code,
			$game_mode,
			null, #game_type
			null, #side_game_api
			$language, #language
			$extra,
			$is_mobile_flag
		);
	}

	/**
	 * overview : go to isb game
	 *
	 * @param string 	 $game_code
	 * @param string	 $game_mode
	 * @param bool|false $is_mobile_flag
	 * @param string	 $language
	 */
	public function goto_isb_game($game_code = null, $game_mode = 'real', $is_mobile_flag = false, $language = null) {
		$this->utils->debug_log('GOTO ISB!');
		if (is_null($language)) {
			$language = $this->language_function->getCurrentLanguage();
		}
        $game_platform_id = ISB_API;

        $api = $this->utils->loadExternalSystemLibObject($game_platform_id);
        $platformName = $this->external_system->getNameById($game_platform_id);

        # GET LOGGED-IN PLAYER
        $player_id = $this->authentication->getPlayerId();
        $session_id = $this->session->userdata('session_id');
        $playerName = $this->authentication->getUsername();

        $is_mobile_flag = (empty($is_mobile_flag) || $is_mobile_flag != "false") ? $this->utils->is_mobile(): $is_mobile_flag;
        $extra = array("is_mobile_flag" => $is_mobile_flag);

        if($game_mode != 'real'){
        	$param = array();
        	$param['extra']['is_mobile_flag'] = $is_mobile_flag;
        	$param['language']= $language;
        	$param['game_mode']= $game_mode;
        	$param['game_code']= $game_code;

        	$data = $api->queryForwardGame($playerName,$param);
        	// echo "<pre>";print_r($data);exit;
        	if($is_mobile_flag){
				redirect($data['url']);
			}else{
				return $this->load->view('iframe/game_iframe', $data);
			}
        }

        //if not login
        if (!$this->authentication->isLoggedIn()) {
            $this->goPlayerLogin();
        }

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }
        # LOAD GAME API

        # CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
        $this->checkBlockGamePlatformSetting($game_platform_id);

        $this->checkGameIfLaunchable($game_platform_id,$game_code);

        # CHECK PLAYER IF EXIST
        $player = $api->isPlayerExist($playerName);

        # IF NOT CREATE PLAYER
        if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {

            if(!is_null($player['exists'])){
                $this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
            }
        }

		$this->gotogame(ISB_API,
			$game_code,
			$game_mode,
			null, #game_type
			null, #side_game_api
			$language,
			$extra,
			true);
	}

	public function goto_isbseamless_game($game_code = null, $game_mode = 'real') {
		$is_mobile = $this->utils->is_mobile();
		$language = $this->language_function->getCurrentLanguage();
		$extra = array("is_mobile" => $is_mobile);

        $game_platform_id = ISB_SEAMLESS_API;

        $api = $this->utils->loadExternalSystemLibObject($game_platform_id);
        $platformName = $this->external_system->getNameById($game_platform_id);

        # GET LOGGED-IN PLAYER
        $player_id = $this->authentication->getPlayerId();
        $session_id = $this->session->userdata('session_id');
        $playerName = $this->authentication->getUsername();

        if($game_mode != 'real'){
        	$param = array();
        	$param['extra']['is_mobile_flag'] = $is_mobile_flag;
        	$param['language']= $language;
        	$param['game_mode']= $game_mode;
        	$param['game_code']= $game_code;

        	$data = $api->queryForwardGame($playerName,$param);
        	// echo "<pre>";print_r($data);exit;
        	if($is_mobile){
				redirect($data['url']);
			}else{
				return $this->load->view('iframe/game_iframe', $data);
			}
        }

        //if not login
        if (!$this->authentication->isLoggedIn()) {
            $this->goPlayerLogin();
        }

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }
        # LOAD GAME API

        # CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
        $this->checkBlockGamePlatformSetting($game_platform_id);

        # CHECK PLAYER IF EXIST
        $player = $api->isPlayerExist($playerName);

        # IF NOT CREATE PLAYER
        if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {

            if(!is_null($player['exists'])){
                $this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
            }
        }

		$this->gotogame($game_platform_id,
			$game_code,
			$game_mode,
			null, #game_type
			null, #side_game_api
			$language,
			$extra,
			true);
	}

	/**
	 * overview : go to gsmg game
	 *
	 * @param string $game_type
	 * @param string $game_code
	 * @param string $game_mode
	 * @param int 	 $side_game_api
	 * @param string $language
	 */
	public function goto_gsmg_game($game_type = null, $game_code = null, $game_mode = 'real', $side_game_api = null, $language = null) {
		$this->utils->debug_log('GOTO GSMG!');
		if (!$language) {
			$currentLang = $this->language_function->getCurrentLanguage();
			$language = ($currentLang == '1') ? 'en' : 'zh_cn';
		}

		if ($game_type == '1') {
			$game_type = 'live';
		}

		if ($game_type == '2') {
			$game_type = 'slots';
		}

		if ($game_type == '3') {
			$game_type = 'mobile';
		}

		$this->gotogame(GSMG_API,
			$game_code,
			$game_mode,
			$game_type, #game_type
			$side_game_api, #1=slots,2=live
			$language,
			null,
			true);
	}

	/**
	 * overview : go to seven77 game
	 *
	 * @param string $game_type
	 * @param string $game_code
	 * @param string $game_mode
	 * @param string $language
	 */
	public function goto_seven77_game($game_type = null, $game_code = null, $game_mode = 'real', $language = null) {
		$this->utils->debug_log('GOTO 777!');
		if (!$language) {
			$currentLang = $this->language_function->getCurrentLanguage();
			$language = ($currentLang == '1') ? 'en' : 'zh_cn';
		}

		$this->gotogame(SEVEN77_API,
			$game_code,
			$game_mode,
			$game_type, #game_type
			null, #side_game_api
			$language);
	}

	/**
	 * overview : go to hrcc game
	 *
	 * @param string $game_type
	 * @param string $game_code
	 * @param string $game_mode
	 * @param string $language
	 */
	public function goto_hrcc_game($game_type = null, $game_code = null, $game_mode = 'real', $language = null) {
		$this->utils->debug_log('GOTO HRCC!');
		if (!$language) {
			$currentLang = $this->language_function->getCurrentLanguage();
			$language = ($currentLang == '1') ? 'en' : 'zh_cn';
		}

		$this->gotogame(HRCC_API,
			$game_code,
			$game_mode,
			$game_type, #game_type
			null, #side_game_api
			$language);
	}

	/**
	 * overview : go to mglapis game
	 *
	 * @param null $game_code
	 * @param string $game_mode
	 * @param string $game_platform
	 * @param bool|false $is_mobile
	 */
	public function goto_mglapis_game($game_code = null, $game_mode = 'real', $game_platform = 'web', $is_mobile = null) {
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}
		$extra = array(
			"is_mobile" =>$is_mobile,
			"game_platform" => $game_platform
		);

		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();
		$game_platform_id = LAPIS_API;
		$success = $this->prepareGotoGame($player_name, $player_id, $game_platform_id);
		if($success){
			$this->gotogame(LAPIS_API,
				$game_code,
				$game_mode,
				null, #game_type
				null, #side_game_api
				null, #language
				$extra
			);
		}else{
			die(lang('goto_game.error'));
		}

	}

	/**
	 * overview : go to KUMA game
	 *
	 * @param string	$mode
	 * @param int		$gameId
	 * @param string	$platform
	 * @param string 	$language
	 */
	public function goto_oggame($redirection = "newtab") {

		$is_mobile = $this->utils->is_mobile();
		$language = $this->language_function->getCurrentLanguage();
		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = OG_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('OG');
            return;
        }
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$player_name = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# LOAD GAME API

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		// # CHECK PLAYER IF EXIST
		// $player = $api->isPlayerExist($playerName);

		// # IF NOT CREATE PLAYER
		// if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
		// 	if(!is_null($player['exists'])){
		// 		$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
		// 	}
		// }
		$player = $this->checkExistOnApiAndUpdateRegisterFlag($api,$player_id,$player_name);
		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if (!$blocked) {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$this->_refreshBalanceFromApiAndUpdateSubWallet($game_platform_id, $api, $player_name, $player_id);

			$params = array(
				'language' => $language,
				'is_mobile' => $is_mobile,
			);

			$data = $api->queryForwardGame($player_name, $params);
			$this->utils->debug_log("queryForwardGame", var_export($data, true));

			if (isset($data['success']) && $data['success'] && $data['url']) {
                if ($redirection == "newtab") {
                    redirect($data['url']);
                }else{
                    $this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName));
                }
				return;
			} else {
				die(lang('goto_game.error'));
			}
		} else {
			die(lang('goto_game.blocked'));
		}
	}

    /**
     * overview : go to KUMA game
     *
     * @param string    $mode
     * @param int       $gameId
     * @param string    $platform
     * @param string    $language
     */
    public function goto_jumbogame($game_type='slots',$game_code=null,$game_mode = 'real') {

        $is_mobile = $this->utils->is_mobile();
        $currentLang = $this->language_function->getCurrentLanguage();
        # LOAD MODEL AND LIBRARIES
        $this->load->model(array('game_provider_auth', 'external_system'));

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

        # DECLARE WHICH GAME PLATFORM TO USE
        $game_platform_id = JUMB_GAMING_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('JUMBO');
            return;
        }
        $api = $this->utils->loadExternalSystemLibObject($game_platform_id);
        $platformName = $this->external_system->getNameById($game_platform_id);

        //if not login
        if (!$this->authentication->isLoggedIn() && $game_mode == 'real') {
            $this->goPlayerLogin();
        }

        # GET LOGGED-IN PLAYER
        $player_id = $this->authentication->getPlayerId();
        $session_id = $this->session->userdata('session_id');
        $playerName = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

        $this->checkGameIfLaunchable($game_platform_id,$game_code);

        # LOAD GAME API

        # CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
        $this->checkBlockGamePlatformSetting($game_platform_id);

        if(!empty($playerName)){
	        # CHECK PLAYER IF EXIST
	        $player = $api->isPlayerExist($playerName);

	        # IF NOT CREATE PLAYER
	        if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
				if(!is_null($player['exists'])){
					$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
				}
			}
		}

        # CHECK IF LOGGED-IN PLAYER IS BLOCKED
        $blocked = $api->isBlocked($playerName);

        if (!$blocked) {
            $this->_transferAllWallet($player_id, $playerName, $game_platform_id);
            $params = array(
                'language' => $currentLang,
                'is_mobile' => $is_mobile,
                'game_type' => $game_type,
                'game_code' => $game_code,
                'game_mode' => $game_mode,
            );
            $data = $api->queryForwardGame($playerName, $params);
            $this->utils->debug_log("queryForwardGame", $data);
            if (isset($data['success']) && $data['success'] && isset($data['url']) && !empty($data['url'])) {
            	if($is_mobile){
            		return redirect($data['url']);
            	}else{
	                $this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName));
	                return;
            	}
            } else {
                die(lang('goto_game.error'));
            }
        } else {
            die(lang('goto_game.blocked'));
        }

	}

	public function goto_jdbgame($game_type='slots',$game_code=null,$game_mode = 'real') {
		$this->goto_jumbogame($game_type,$game_code,$game_mode);
	}

	public function goto_mgquickfire_game($game_launch_code, $game_mode = 'real', $device_type = 'desktop', $game_code, $module_id='', $client_id='', $language = 'en') {

		$this->load->model(array('external_system', 'game_provider_auth'));

		$game_platform_id = MG_QUICKFIRE_API;
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();
		$isRedirect = false;

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('MG_QUICKFIRE');
            return;
        }

		$success = $this->prepareGotoGame($player_name, $player_id, $game_platform_id);

		$loginInfo = $this->game_provider_auth->getLoginInfoByPlayerId($player_id, $game_platform_id);

		if ($success && $loginInfo) {

			$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$params = array(
				'game_launch_code' => $game_launch_code,
				'game_code' => $game_code,
				'game_mode' => $game_mode,
				'device_type' => $device_type,
				'language' => $language,
				'module_id' => $module_id,
				'client_id' => $client_id
			);

			if ($get_params = $this->input->get()) {
				$params = array_merge($params, $get_params);
			}

			$rlt = $api->queryForwardGame($player_name, $params);

			$this->utils->debug_log("talqueryForwardGame", $rlt);

			if (isset($rlt['success']) && $rlt['success']) {

				$platformName = $this->external_system->getNameById($game_platform_id);

				$iframeName = isset($rlt['iframeName']) ? $rlt['iframeName'] : "";

				$data = array(
					'url' => $rlt['url'],
					'platformName' => $platformName,
					'iframeName' => $iframeName,
				);

				if (isset($rlt['is_redirect'])) {
					$isRedirect = $rlt['is_redirect'];
				}

				if ( ! $isRedirect) {
					$this->load->view($this->utils->getPlayerCenterTemplate() . '/game_iframe', $data);
				} else {
					if ( ! empty($rlt['url'])) {
						redirect($rlt['url']);
					}
				}

				return $data;
			} else {
				die(lang('goto_game.error'));
			}

		} else {
			$this->returnBadRequest();
		}

	}

	public function goto_redtigerseamless_game($game_code = NULL, $game_mode = 'real', $language = '') {

		if (empty($language)) {
	        $language = $this->language_function->getCurrentLanguage() == '1' ? 'en' : "zh";
		}

		$this->load->model(array('external_system', 'game_provider_auth'));

		$game_platform_id = REDTIGER_SEAMLESS_API;
		$player_id 		= $this->authentication->getPlayerId();
		$player_name 	= $this->authentication->getUsername();
		$is_mobile 		= $this->utils->is_mobile();
		$isRedirect 	= false;

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        // if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
        //     $this->goto_maintenance('MG_QUICKFIRE');
        //     return;
        // }

		$success = $this->prepareGotoGame($player_name, $player_id, $game_platform_id);

		$loginInfo = $this->game_provider_auth->getLoginInfoByPlayerId($player_id, $game_platform_id);

		if ($success && $loginInfo) {

			$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$params = array(
				'game_code' => $game_code,
				'game_mode' => $game_mode,
				'is_mobile' => $is_mobile,
				'language'  => $language,
				't1_lobby_url' => $this->get_lobby_url(),
			);

			if ($get_params = $this->input->get()) {
				$params = array_merge($params, $get_params);
			}

			$rlt = $api->queryForwardGame($player_name, $params);

			if (isset($rlt['success']) && $rlt['success']) {

				$platformName = $this->external_system->getNameById($game_platform_id);

				$iframeName = isset($rlt['iframeName']) ? $rlt['iframeName'] : "";

				$data = array(
					'url' => $rlt['url'],
					'platformName' => $platformName,
					'iframeName' => $iframeName,
					'isAllowFullScreen' => 'allowfullscreen="true"',
				);

				if (isset($rlt['is_redirect'])) {
					$isRedirect = $rlt['is_redirect'];
				}

				if ( ! $isRedirect) {
					$this->load->view($this->utils->getPlayerCenterTemplate() . '/game_iframe', $data);
				} else {
					if ( ! empty($rlt['url'])) {
						redirect($rlt['url']);
					}
				}

				return $data;
			} else {
				die(lang('goto_game.error'));
			}

		} else {
			$this->returnBadRequest();
		}

	}

	public function goto_ebet_mg_game($game_type,$game_code=null, $game_mode = 'real',$category = 'flash',$language=null) {
		$is_mobile = $this->utils->is_mobile();
		if(empty($language)){
			$language = $this->language_function->getCurrentLanguage();
		}

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('external_system', 'game_provider_auth'));

		$game_platform_id = EBET_MG_API;
        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('EBET_MG');
            return;
        }

		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();
		$platformName = $this->external_system->getNameById($game_platform_id);
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# demo game
		if($game_mode == "fun"||$game_mode == "trial"||$game_mode == "demo"){
			$fun_params = array(
				'game_type' => $game_type,
				'game_code' => $game_code,
				'game_mode' => $game_mode,
				'language' => $language,
				'is_mobile' => $is_mobile,
				'category' => $category
			);

			$rlt = $api->queryForwardGame($player_name, $fun_params);
			$this->load->view('iframe/game_iframe', array('url' => $rlt['url'], 'platformName' => $platformName));
		}

		$success = $this->prepareGotoGame($player_name, $player_id, $game_platform_id);

		if ($success) {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$params = array(
				'game_type' => $game_type,
				'game_code' => $game_code,
				'game_mode' => $game_mode,
				'language' => $language,
				'is_mobile' => $is_mobile,
				'category' => $category
			);

			$rlt = $api->queryForwardGame($player_name, $params);
			if($rlt['success']){
				$this->load->view('iframe/game_iframe', array('url' => $rlt['url'], 'platformName' => $platformName));
			}
		} else {
			$this->returnBadRequest();
		}

	}

	public function goto_ebet_qt_game($gameId, $device = 'desktop', $mode = 'real', $lang = null) {

		$this->load->model(array('external_system', 'game_provider_auth'));

		$game_platform_id = EBET_QT_API;
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('EBET_QT');
            return;
        }

		$success = $this->prepareGotoGame($player_name, $player_id, $game_platform_id);

		$loginInfo = $this->game_provider_auth->getLoginInfoByPlayerId($player_id, $game_platform_id);

		if ($success && $loginInfo) {

			$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

			$rlt = $api->queryForwardGame($player_name, array(
				'gameId' => $gameId,
				'device' => $device,
				'mode' => $mode,
				'lang' => $lang,
			));

			if (isset($rlt['success']) && $rlt['success']) {

				$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
				$platformName = $this->external_system->getNameById($game_platform_id);

				$iframeName = isset($rlt['iframeName']) ? $rlt['iframeName'] : '';

				$data = array(
					'url' => $rlt['url'],
					'platformName' => $platformName,
					'iframeName' => $iframeName,
				);

				if (isset($rlt['is_redirect'])) {
					$isRedirect = $rlt['is_redirect'];
				}

				if ( ! $isRedirect) {
					$this->load->view($this->utils->getPlayerCenterTemplate() . '/game_iframe', $data);
				} else {
					if ( ! empty($rlt['url'])) {
						redirect($rlt['url']);
					}
				}

				return $data;
			} else {
				die(lang('goto_game.error'));
			}

		} else {
			$this->returnBadRequest();
		}

	}

    /**
     * overview : go to VR game
     *
     * game_code: 1, 2, 11, 12, 13, 0=lobby
     */
    public function goto_vrgame($game_code='0') {
        $is_mobile = $this->utils->is_mobile();

        $currentLang = $this->language_function->getCurrentLanguage();
        $language = ($currentLang == '1') ? 'en' : "zh";
        # LOAD MODEL AND LIBRARIES
        $this->load->model(array('game_provider_auth', 'external_system'));

        # DECLARE WHICH GAME PLATFORM TO USE
        $game_platform_id = VR_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('vr');
            return;
        }
        $api = $this->utils->loadExternalSystemLibObject($game_platform_id);
        $platformName = $this->external_system->getNameById($game_platform_id);

        //if not login
        if (!$this->authentication->isLoggedIn()) {
            $this->goPlayerLogin();
        }

        # GET LOGGED-IN PLAYER
        $player_id = $this->authentication->getPlayerId();
        $session_id = $this->session->userdata('session_id');
        $playerName = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

        # LOAD GAME API

        # CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
        $this->checkBlockGamePlatformSetting($game_platform_id);

        # CHECK PLAYER IF EXIST
        $player = $api->isPlayerExist($playerName);

        # IF NOT CREATE PLAYER
       if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

        # CHECK IF LOGGED-IN PLAYER IS BLOCKED
        $blocked = $api->isBlocked($playerName);

        if (!$blocked) {
            $this->_transferAllWallet($player_id, $playerName, $game_platform_id);

            $params = array(
                'lang' => $language,
                'is_mobile' => $is_mobile,
                'game_code'=>$game_code,
                'departureUrl'=>$api->getSystemInfo('departureUrl'),
            );
            $data = $api->queryForwardGame($playerName, $params);

            $this->utils->debug_log("queryForwardGame", $data);
            if (isset($data['success']) && $data['success']) {
            	if($is_mobile){
            		redirect($data['url']);
            	}else{
	                $this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName));
            	}

                return;
            } else {
                die(lang('goto_game.error'));
            }
        } else {
            die(lang('goto_game.blocked'));
        }
    }

    public function goto_ultraplay_game($deviceType = 'desktop', $gameMode = 'real', $language = 'en-US') {

		$this->load->model(array('external_system', 'game_provider_auth'));

		$game_platform_id = ULTRAPLAY_API;
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('ultraplay');
            return;
        }

		$success = $this->prepareGotoGame($player_name, $player_id, $game_platform_id);

		$loginInfo = $this->game_provider_auth->getLoginInfoByPlayerId($player_id, $game_platform_id);

		if ($success && $loginInfo) {

			$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$rlt = $api->queryForwardGame($player_name, array(
				'gameMode' => $gameMode,
				'deviceType' => $deviceType,
				'lang' => $language,
			));

			if (isset($rlt['success']) && $rlt['success']) {

				$platformName = $this->external_system->getNameById($game_platform_id);

				$iframeName = isset($rlt['iframeName']) ? $rlt['iframeName'] : "";

				$data = array(
					'url' => $rlt['url'],
					'platformName' => $platformName,
					'iframeName' => $iframeName,
				);

				if (isset($rlt['is_redirect'])) {
					$isRedirect = $rlt['is_redirect'];
				}

				if ( ! $isRedirect) {
					$this->load->view($this->utils->getPlayerCenterTemplate() . '/game_iframe', $data);
				} else {
					if ( ! empty($rlt['url'])) {
						redirect($rlt['url']);
					}
				}

				return $data;
			} else {
				die(lang('goto_game.error'));
			}

		} else {
			$this->returnBadRequest();
		}

	}

	public function goto_evolution_game($game_type=null, $game_code=null) {

		$this->load->model(array('external_system', 'game_provider_auth'));

		$game_platform_id = EVOLUTION_GAMING_API;

		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }
		$is_mobile = $this->utils->is_mobile();

		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('EVOLUTION');
            return;
        }

		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		$player = $api->isPlayerExist($player_name);

		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		$blocked = $api->isBlocked($player_name);

		if (!$blocked) {
			$extra = array();
			$extra['game_type'] = $game_type;
			$extra['game_code'] = $game_code;
			$extra['is_mobile'] = $is_mobile;
			$extra['language'] = $this->language_function->getCurrentLanguage();

			$rlt = $api->queryForwardGame($player_name, $extra);

			$this->CI->utils->debug_log("goto evolution gaming", $rlt['url']);

			$platformName = $this->external_system->getNameById($game_platform_id);
			$data = array(
					'url' => $rlt['url'],
					'platformName' => $platformName,
			);

			if($is_mobile){
				redirect($data['url']);
			}else{
				$this->load->view('iframe/player/goto_evolution_gaming', $data);
			}
		}  else {
			die(lang('goto_game.blocked'));
		}

    }

    public function goto_dggame_seamless($game_mode="real") {
        #auto detect if mobile
        $is_mobile = $this->utils->is_mobile();

        # LOAD MODEL AND LIBRARIES
        $this->load->model(array('game_provider_auth', 'external_system', 'operatorglobalsettings'));

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

        # DECLARE WHICH GAME PLATFORM TO USE
        $game_platform_id = DG_SEAMLESS_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance($game_platform_id);
            return;
        }

        # GET LOGGED-IN PLAYER
        $player_id = $this->authentication->getPlayerId();
        $player_name = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }
        //if not login
        if (!$this->authentication->isLoggedIn()) {
            $this->goPlayerLogin();
        }

        # LOAD GAME API
        $api = $this->utils->loadExternalSystemLibObject($game_platform_id);

        # CHECK PLAYER IF EXIST
        $player = $api->isPlayerExist($player_name);

        # IF NOT CREATE PLAYER
        if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
            if(!is_null($player['exists'])){
                $this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
            }
        }

        # CHECK IF GAMEPLATFORM SETTING IS BLOCKED
        $this->checkBlockGamePlatformSetting($game_platform_id);

        # CHECK IF LOGGED-IN PLAYER IS BLOCKED
        $blocked = $api->isBlocked($player_name);

        if ($blocked) {
            die(lang('goto_game.blocked'));
        } else {
            $this->_transferAllWallet($player_id, $player_name, $game_platform_id);
            $lang = $this->language_function->getCurrentLanguage();
            $extra['language'] = $lang;
            $extra['is_mobile'] = $is_mobile;
            $extra['game_mode'] = $game_mode;
            $url = $api->queryForwardGame($player_name,$extra);
            if($url['success']){
                if($is_mobile){
                    redirect($url['url']);
                }else{
                    $platformName = $this->external_system->getNameById($game_platform_id);
                    $this->utils->debug_log('goto DG API game=>', $url['url']);
                    $this->load->view('iframe/game_iframe', array('url' => $url['url'], 'platformName' => $platformName));
                    return;
                }
            }else{
                die(lang('goto_game.error'));
            }
        }
    }

	/**
	 * overview : go to DG game
	 */
	public function goto_dggame($game_mode="real") {
		#auto detect if mobile
		$is_mobile = $this->utils->is_mobile();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'operatorglobalsettings'));


		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = DG_API;

		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('dg');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }
		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$_transferResult = $this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$lang = $this->language_function->getCurrentLanguage();
			$extra['language'] = $lang;
			$extra['is_mobile'] = $is_mobile;
			$extra['game_mode'] = $game_mode;
			$url = $api->queryForwardGame($player_name,$extra);
			$this->playerTrackingEventPlayNow($game_platform_id, $url, $extra, $_transferResult);
			if($url['success']){
				if($is_mobile){
					redirect($url['url']);
				}else{
					$platformName = $this->external_system->getNameById($game_platform_id);
					$this->utils->debug_log('goto DG API game=>', $url['url']);
					$this->load->view('iframe/game_iframe', array('url' => $url['url'], 'platformName' => $platformName));
					return;
				}
			}else{
				die(lang('goto_game.error'));
			}
		}
	}

    public function goto_ebet_spadegame($game_code=null,$game_mode="real",$is_mobile = 'false') {

        $this->load->model(array('external_system', 'game_provider_auth'));

        $game_platform_id = EBET_SPADE_GAMING_API;

		$player_id = $this->authentication->getPlayerId();
        $player_name = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('EBET_SPADE_GAMING_API');
            return;
        }

        $api = $this->utils->loadExternalSystemLibObject($game_platform_id);

        $extra = array();

        if($game_mode == "fun"){
            $extra['fun']  = "true";
        }

        $extra['language']  = $this->language_function->getCurrentLanguage();
        $extra['minigame']  = 'false';
        $extra['game']      = $game_code;
        $extra['mobile']    = $is_mobile;
        $extra['menumode']  = "on";

        $this->CI->utils->debug_log("Ebet spade Gaming Extrainfo =================================================>", $extra);

        # CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

        $rlt = $api->queryForwardGame($player_name, $extra);
        if (!empty($rlt['success']) && !empty($rlt['url'])) {
            $result = $this->_transferAllWallet($player_id, $player_name, $game_platform_id);
            $this->CI->utils->debug_log("Ebet spade Gaming _transferAllWallet =================================================>", $result);
        }

        $this->CI->utils->debug_log("goto Ebet spade gaming", $rlt['url']);

        $platformName = $this->external_system->getNameById($game_platform_id);
        $data = array(
            'url'           => $rlt['url'],
            'platformName'  => $platformName,
        );

        $this->load->view('iframe/game_iframe', $data);
    }

    public function goto_lebo_game($game_code=null,$game_mode="real",$is_mobile = 'false') {
        $this->load->model(array('external_system', 'game_provider_auth'));

		# DECLARE WHICH GAME PLATFORM TO USE
        $game_platform_id = LEBO_GAME_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('EBET_OPUS_API');
            return;
        }

		# LOAD GAME API
        $api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# GET LOGGED-IN PLAYER
		switch ($game_mode) {
			case 'real':
				$player_id = $this->authentication->getPlayerId();
				$player_name = $this->authentication->getUsername();
				//if not login
				if (!$this->authentication->isLoggedIn()) {
					$this->goPlayerLogin();
				}
				break;
			default:
		}

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }
		# CHECK PLAYER IF EXIST
		$existRlt = $api->isPlayerExist($player_name);

		if(!$existRlt['success']){
            return $this->goto_maintenance('LEBO_GAME_API');
		}

		# IF NOT CREATE PLAYER
		if (isset($existRlt['exists']) && !$existRlt['exists'] && $existRlt['success']==true) {
			if(!is_null($existRlt['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

            $extra = array();
            $extra['language']  = $this->language_function->getCurrentLanguage();
            $extra['game']      = $game_code;
            $extra['game_code'] = $game_code;
            $extra['mobile']    = $is_mobile;

            $this->CI->utils->debug_log("LEBO Game Extrainfo ====================>", $extra);

            $rlt = $api->queryForwardGame($player_name, $extra);

            $this->CI->utils->debug_log("goto lebo game", $rlt['url']);

            $platformName = $this->external_system->getNameById($game_platform_id);
            $data = array(
                'url'          => $rlt['url'],
                'platformName' => $platformName,
                'uno'          => $rlt['uno'],
                'pw'           => $rlt['pw'],
                'refurl'       => $rlt['refurl'],
                'signstr'      => $rlt['signstr'],
            );

            $is_mobile = $this->utils->is_mobile();
            if( $is_mobile ){
                $this->load->view('iframe/player/goto_lebogame', $data);
            }
            else{
            redirect($data['url']); // use direct here, and designer can handle it outside in another iframe
            }
        }
    }

    public function goto_yungu_game($game_code="default",$game_mode="real",$is_mobile = 'auto') {
        $this->load->model(array('external_system', 'game_provider_auth'));

		# DECLARE WHICH GAME PLATFORM TO USE
        $game_platform_id = YUNGU_GAME_API;

		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('YUNGU_GAME_API');
            return;
        }

		# LOAD GAME API
        $api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# GET LOGGED-IN PLAYER
		switch ($game_mode) {
			case 'trial':
            {
                $extra = array();
                    if ( $is_mobile == "auto" || empty($is_mobile) ){
                        $is_mobile = $this->utils->is_mobile();
                    }
                    $extra['language']  = $this->language_function->getCurrentLanguage();
                    $extra['game_code'] = ($game_code == "default") ? "" : $game_code ;
                    $extra['game_mode'] = $game_mode;
                    $extra['mobile']    = $is_mobile;

                    $rlt = $api->queryForwardGame($player_name, $extra);
                    $platformName = $this->external_system->getNameById($game_platform_id);
                    $data = array(
                            'url'           => $rlt['url'],
                            'platformName'  => $platformName,
                            );

                    redirect($data['url']);
                    return;
            } break;

			case 'real':
				$player_id = $this->authentication->getPlayerId();
				$player_name = $this->authentication->getUsername();
				//if not login
				if (!$this->authentication->isLoggedIn()) {
					$this->goPlayerLogin();
				}
				break;
			default:
		}

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }
		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

            if ( $is_mobile == "auto" || empty($is_mobile) ){
                $is_mobile = $this->utils->is_mobile();
            }
            $extra = array();
            $extra['language']  = $this->language_function->getCurrentLanguage();
            $extra['game_code']      = ($game_code == "default") ? "" : $game_code ;
            $extra['game_mode']      = $game_mode;
            $extra['mobile']    = $is_mobile;

            $this->CI->utils->debug_log("YUNGU Game Extrainfo ====================>", $extra);

            $rlt = $api->queryForwardGame($player_name, $extra);

            $this->CI->utils->debug_log("goto yungu game", $rlt);

            $platformName = $this->external_system->getNameById($game_platform_id);
            $data = array(
                'url'           => $rlt['url'],
                'platformName'  => $platformName,
            );

            if($rlt['success']){
	            redirect($data['url']);
            }else{
            	$this->goto_maintenance('YUNGU_GAME_API');
            }

        }
    }

    /**
	 * overview : go to rwb game
	 * mode: real or fun
	 */
	public function goto_rwb($setType = "href") {
		$is_mobile = $this->utils->is_mobile();
		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = RWB_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('rwb');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

		#BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

		//rwb other data
		$data['platformName'] = $platformName;
		$data['setBackUrl'] = ($is_mobile ? $this->utils->getSystemUrl('m') : $this->utils->getSystemUrl('www')).$this->utils->getConfig('sports_lobby_extension');
		$data['setType'] = $setType;
		$data['bgImage'] = $this->utils->getConfig('rwb_background_image');
		$extra['language'] = $this->language_function->getCurrentLanguage();
		$extra['is_mobile'] = $is_mobile;

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$api_data = $api->queryForwardGame($player_name, $extra);
			$data = array_merge($data,$api_data);
			return $this->load->view('iframe/player/goto_rwb', $data);
			// $this->goPlayerLogin();
		}

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists']) {
			$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);
		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$api_data = $api->queryForwardGame($player_name, $extra);
			$data = array_merge($data,$api_data);
			if (isset($data['success']) && $data['success']) {
				$this->load->view('iframe/player/goto_rwb', $data);
			} else {
				die(lang('goto_game.error'));
			}
			return;
		}
	}

    /**
     * overview : go to le gaming game
     *
     * @param string    $game_code
     */
    public function goto_legaming_game($game_code = null, $mode = 'real', $is_mobile = null) {
        if (empty($is_mobile)) {
            $is_mobile = $this->utils->is_mobile();
        } else {
            $is_mobile = $is_mobile == 'true';
        }

        # LOAD MODEL AND LIBRARIES
        $this->load->model(array('game_provider_auth', 'external_system'));

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

        # DECLARE WHICH GAME PLATFORM TO USE
        $game_platform_id = LE_GAMING_API;

        // $this->checkGameIfLaunchable($game_platform_id,$game_code);

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('MG');
            return;
        }

        # GET LOGGED-IN PLAYER
        $player_name = $this->authentication->getUsername();
        $player_id = $this->authentication->getPlayerId();
        $platformName = $this->external_system->getNameById($game_platform_id);

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

        # LOAD GAME API
        $api = $this->utils->loadExternalSystemLibObject($game_platform_id);

        # CHECK PLAYER IF EXIST
        $player = $api->isPlayerExist($player_name);
		$this->utils->info_log("LE GAMING isPlayerExist RESULT ==========>",$player);

        # IF NOT CREATE PLAYER
        if (isset($player['exists']) && !$player['exists'] && $player['success'] == true) {
            if(!is_null($player['exists'])){
                $this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
            }
        }

        # CHECK IF GAMEPLATFORM SETTING IS BLOCKED
        $this->checkBlockGamePlatformSetting($game_platform_id);

        $this->_transferAllWallet($player_id, $player_name, $game_platform_id);

        $extra = array('game_code' => $game_code, 'game_mode' => $mode);
        $rlt = $api->queryForwardGame($player_name, $extra);
        $this->utils->info_log("LE GAMING queryForwardGame RESULT ==========>",$rlt);

        if ($rlt && $rlt['success']) {
            $platformName = $this->external_system->getNameById($game_platform_id);
            if ($is_mobile) {
                redirect($rlt['url']);
            }else{
                $this->load->view('iframe/game_iframe', array('url' => $rlt['url'], 'platformName' => $platformName));
            }
        } else {
            if (isset($rlt['message'])) {
                $message = $rlt['message'];
            } else {
                $message = 'goto_game.error';
            }
            die(lang($message));
        }

    }
	/**
	 * overview : check if active single game
	 *
	 * @param $gameDescId
	 * @return bool
	 */
	public function isActiveSingleGame($gameDescId) {
		$this->load->model(['game_description_model']);

		$gameDesc = $this->game_description_model->getGameDescription($gameDescId);

		return $gameDesc->status == Game_description_model::STATUS_NORMAL;

	}

	public function launch_game_with_token($token, $game_platform_id, $game_code = '_null', $language = 'zh-cn',
		$game_mode = 'real', $platform = '_null', $game_type = '_null', $merchant_code = '_null',$redirection = "_null",$t1_extra = "_null") {
		//validate token
		$this->load->model(['common_token']);
		$playerInfo = $this->common_token->getPlayerInfoByToken($token);

		//OGP-21732 redirect to url sent on error
		$on_error_redirect = $this->input->get('on_error_redirect');
		//OGP-32458 send post message 
		$post_message_on_error = $this->input->get('post_message_on_error');

        if(empty($playerInfo)){
        	if($post_message_on_error){
        		$post_message['error_message'] = lang("Invalid player token.");
        		return $this->load->view('iframe/player/view_post_message_closed', $post_message);
        	}
			//OGP-21732 redirect to url sent on error
			if(!empty($on_error_redirect)){
				return redirect($on_error_redirect);
			}

            $this->utils->flash_message(FLASH_MESSAGE_TYPE_DANGER, 'invalid token');
            return redirect($this->goPlayerHome());
        }
        $username=$playerInfo['username'];
        $this->utils->debug_log('get playerInfo', $username);
        $this->load->library(['player_library']);

		$allow_clear_session = $this->config->item('allow_clear_session_when_launch_game');
		$this->utils->debug_log(__METHOD__, 'allow_clear_session_when_launch_game', $allow_clear_session);
        //compare with username or ignore
        //lock player login
		/** @var array<string, boolean|array> */
        $login_result=['success'=>false];
        $result=$this->localLockOnlyWithResult(Utils::LOCAL_LOCK_ACTION_PLAYER_LOGIN,
			$username, function(&$error) use(&$login_result, $playerInfo, $allow_clear_session){
			$success=true;
			$login_result = $this->player_library->login_by_player_info($playerInfo, false, $allow_clear_session);
			return $success;
        });
        if(!$login_result['success']){
        	$err=lang('Login error');
        	if(!empty($login_result['errors'])){
        		if(is_array($login_result['errors'])){
        			$err=end($login_result['errors']);
        		}else{
        			$err=$login_result['errors'];
        		}
        	}

        	if($post_message_on_error){
        		$post_message['error_message'] = $err;
        		return $this->load->view('iframe/player/view_post_message_closed', $post_message);
        	}

			//OGP-21732 redirect to url sent on error
			if(!empty($on_error_redirect)){
            	return redirect($on_error_redirect);
            }
            $this->utils->flash_message(FLASH_MESSAGE_TYPE_DANGER, $err);
            return redirect($this->goPlayerHome());
		}

		// record recent
		if (!empty($playerInfo['player_id'])) {
			$this->load->model(['player_recent_game_model', 'game_description_model']);

			$game = $this->game_description_model->getGameDetailsByExternalGameIdAndGamePlatform($game_platform_id, $game_code);

			if($game){
				$succ = $this->player_recent_game_model->addRecentGame($playerInfo['player_id'], $game->game_description_id);
				if (!$succ) {
					$this->utils->error_log('add recent game failed', $playerInfo['player_id'], $game->game_description_id);
				}
			}
		}

		$this->launch_game($game_platform_id, $game_code, $language,
			$game_mode, $platform, $game_type, $merchant_code,$redirection,$t1_extra);
	}

	public function launch_game_demo($token, $game_platform_id, $game_code = '_null', $language = 'zh-cn', $platform = '_null', $game_type = '_null', $merchant_code = '_null',$redirection = "_null",$t1_extra = "_null") {
		$this->launch_game($game_platform_id, $game_code, $language,
			'demo', $platform, $game_type, $merchant_code,$redirection,$t1_extra);
	}

	public function launch_game($game_platform_id, $game_code = '_null', $language = 'zh-cn',
		$game_mode = 'real', $platform = '_null', $game_type = '_null', $merchant_code = '_null',$redirection = "_null",$t1_extra = "_null") {
            
        $this->utils->debug_log('LAUNCH_GAME PARAMS ===>', $game_platform_id, $game_code, $language,
		$game_mode, $platform, $game_type, $merchant_code,$redirection,$t1_extra);

        if($game_code=='_null'){
            $game_code='';
        }
        if($game_type=='_null'){
            $game_type='';
        }
        if($language=='_null'){
            $language='';
        }
        if($platform=='_null'){
            $platform='';
        }
        if($merchant_code=='_null'){
            $merchant_code='';
        }
        if($redirection=='_null'){
            $redirection='';
        }
        if($t1_extra=='_null'){
            $t1_extra='';
        }

        if (empty($platform)) {
            $is_mobile = $this->utils->is_mobile();
        } else {
            $is_mobile = $platform == 'mobile';
        }

        $HB_APIS = $this->config->item('hb_common_apis');

		//OGP-21732 redirect to url sent on error
		$on_error_redirect = $this->input->get('on_error_redirect');
		//OGP-32458 send post message 
		$post_message_on_error = $this->input->get('post_message_on_error');

        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
        	if($post_message_on_error){
        		$post_message['error_message'] = lang("Game not active or on maintenance.");
        		return $this->load->view('iframe/player/view_post_message_closed', $post_message);
        	}

			//OGP-21732 redirect to url sent on error
			if(!empty($on_error_redirect)){
            	return redirect($on_error_redirect);
            }

            $this->goto_maintenance($game_platform_id);
            return;
        }

        if($game_mode=='trial' || $game_mode=='fun' || $game_mode=='demo'){
			$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
			$platformName = $this->external_system->getNameById($game_platform_id);

            $extra['game_code'] = $game_code;
            $extra['language'] = $language;
            $extra['game_mode'] = $game_mode;
            $extra['game_type'] = $game_type;
            $data = $api->queryForwardGame(null, $extra);

			if($game_platform_id == BETBY_SEAMLESS_GAME_API){
	        	$extra['game_mode'] = "notlogin";
	            $extra['language'] = $this->language_function->getCurrentLanguage();
	            $data = $api->queryForwardGame(null,$extra);
				$this->load->view('iframe/player/goto_betby', $data);
				return;
			}

        	if($game_platform_id==IMPT_API){

				$api_play_pt = $api->getSystemInfo('game_url');

				$data['url'] = $api_play_pt . "/casinoclient.html?language=" . $language . "&nolobby=1&game=" . $game_code . '&mode=offline';

				$title_key='game_platform_name_'.$game_platform_id;

				$data['title']=$this->utils->getConfig('company_name').'-'.lang($title_key); //should be agent name

				$this->load->view($this->utils->getPlayerCenterTemplate() . '/game_iframe', $data);
				return;
			}

			if($game_platform_id == PT_API||$game_platform_id == PT_KRW_API){
				$api_play_pt = $api->getSystemInfo('API_PLAY_PT');
				$ptLang = $api->getLauncherLanguage($language);
				$url = $api_play_pt . "/casinoclient.html?language=" . $ptLang . "&nolobby=1&game=" . $game_code . '&mode=offline';
				$this->load->view('iframe/game_iframe', array('platformName' => $platformName, 'url' => $url, 'iframeName' => $platformName));
				return;
			}

			if($game_platform_id == DT_API){
				$fun_game_url = $api->getSystemInfo('fun_game_url');
				$language = $api->getLauncherLanguage($language);
				$url = $fun_game_url . "?gameCode=" . $game_code . "&isfun=1&type=dt&language=".$language;
				if($is_mobile){
	                redirect($url);
	            }else{
					$this->load->view('iframe/game_iframe', array('url' => $url, 'platformName' => $platformName));
	            }
				return;
			}

            if ($game_platform_id == BETGAMES_SEAMLESS_GAME_API) {
                return $this->load->view('iframe/player/goto_betgames_seamless_game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => false, 'allow_fullscreen' => true, 'game_platform_name' => 'BETGAMES_SEAMLESS_GAME_API', 'params' => $data['params']));
            }

            if ($game_platform_id == TWAIN_SEAMLESS_GAME_API) {
                return $this->load->view('iframe/player/goto_twain_seamless_game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => false, 'allow_fullscreen' => true, 'game_platform_name' => 'TWAIN_SEAMLESS_GAME_API', 'params' => $data['params']));
            }
        }

        // $logged=false;
        // $this->load->model(['common_token']);
        // $playerInfo = $this->common_token->getPlayerInfoByToken($token);

        // if(empty($playerInfo)){
        //     $this->utils->flash_message(FLASH_MESSAGE_TYPE_DANGER, 'invalid token');
        //     return redirect($this->goPlayerHome());
        // }

        // $this->utils->debug_log('get playerInfo', @$playerInfo['username']);
        // $this->load->library(['player_library']);
        // //compare with username or ignore

        // $login_result = $this->player_library->login_by_player_info($playerInfo);
        // if(!$login_result['success']){
        //     $this->utils->flash_message(FLASH_MESSAGE_TYPE_DANGER, (is_array($login_result['errors']) && !empty($login_result['errors'])) ? end($login_result['errors']) : $login_result['errors']);
        //     return redirect($this->goPlayerHome());
        // }

        $extra=$this->getInputGetAndPost();
        if(!empty($t1_extra)){
        	$extra = array_merge($extra,$t1_extra);
        }
        $player_id = $this->authentication->getPlayerId();
        $player_name = $this->authentication->getUsername();
        $this->utils->debug_log('GOTOGAME PLAYER ID: ', $player_id, "PLAYER NAME: ", $player_name, "extra", $extra);

        if (!$player_name && !$player_id) {
            
            $extra['game_code'] = $game_code;
            $extra['game_mode'] = $game_mode;
            $extra['platform'] = $platform;

            if ($game_mode == 'false' || $game_mode == 'demo' || $game_mode == 'trial' || $game_mode == 'fun' || !$game_mode) {
                $this->goto_demo_game($game_platform_id, $game_type, $language, $extra);
                return;
            } else {
                $loginUrl = $this->utils->getSystemUrl('player', '/iframe/auth/login', false);
                
                redirect($loginUrl);
            }
        }
        #login player, but game_mode is set to false, it will redirect to demo mode
        if ($game_mode == 'false' || !$game_mode) {
            $this->utils->debug_log('DEMO GAME EXTRA =----------------> ', $extra);
            $extra['game_code'] = $game_code;
            $extra['game_mode'] = $game_mode;
            $this->goto_demo_game($game_platform_id, $game_type, $language, $extra);
            return;
        }
        $this->load->model(array('external_system', 'game_provider_auth'));
        $success = $this->prepareGotoGame($player_name, $player_id, $game_platform_id);
        $this->utils->debug_log('GOTOGAME SUCCESS: ', $success);

        $loginInfo = $this->game_provider_auth->getLoginInfoByPlayerId($player_id, $game_platform_id);

        if ($success && $loginInfo) {

            $api = $this->utils->loadExternalSystemLibObject($game_platform_id);

			// =============================================================================
            # CHECK PLAYER IF EXIST
            $player = $api->isPlayerExist($player_name);

            # IF NOT CREATE PLAYER
            if (isset($player['exists']) && !$player['exists']) {
                if(!is_null($player['exists'])){
                   $this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
                }
            }
			// =============================================================================

            $this->_transferAllWallet($player_id, $player_name, $game_platform_id);
            $this->utils->debug_log('GOTOGAME INFO: gc: ', $game_code, " gm: ", $game_mode, " gt: ", $game_type, " lang: ", $language, " extra: ", $extra);

            if($game_platform_id == PNG_API){
				$token = $api->getPlayerTokenByUsername($player_name);
            	$protocol = $this->utils->ishttps()?"https://":"http://";
            	$extra['httphost'] = $protocol.$this->utils->getHttpHost();
            	$extra['token'] = $token;// OGP-20021
            }

            if($game_platform_id == FG_ENTAPLAY_API){
            	$extra['game_platform'] = $game_type;
            	$extra['merchant_code'] = $merchant_code;
            	$extra['is_mobile_flag'] = $is_mobile;

            	if (strtolower($language) == "en-us") {
            		$language = "en";
            	}
            }

            if($game_platform_id == PT_API || $game_platform_id == PT_KRW_API){
            	$extra['t1_merchant_code'] = $merchant_code;
			}

			if($game_platform_id == SA_GAMING_API || $game_platform_id == T1SA_GAMING_API){
				$extra["game_launch_with_token"] = true;
			}

			// OGP-23571 add option to use config topup page
			if(!isset($extra['cashier_link']) || empty($extra['cashier_link'])){
				$cashier_link = $api->getCashierLink();
				if(!empty($cashier_link)){
					$append_target_db=$this->input->get('append_target_db');
					$append_target_db=$append_target_db=='true' || $append_target_db=='1';
					if($this->CI->utils->isEnabledMDB() && $append_target_db){
						$cashier_link .= '?'.Multiple_db::__OG_TARGET_DB.'='.$this->CI->utils->getActiveTargetDB();
					}
					$extra['cashier_link'] = $cashier_link;
				}
			}

            $rlt = $api->queryForwardGame(
                $player_name,
                array(
                    'game_code' => $game_code,
                    'game_mode' => $game_mode,
                    'game_type' => $game_type,
                    'game_name' => isset($extra['game_name']) ? $extra['game_name'] : '',
                    'password' => $loginInfo->password,
                    'language' => $language,
                    'side_game_api' => @$extra['side_game_api'],
                    'is_mobile' => $is_mobile,
                    'home_link' => @$extra['home_link'],
                    'cashier_link' => @$extra['cashier_link'],
					'extra' => $extra,
					'external_category' => @$extra['external_category'],
                )
            );

            if($game_platform_id == BETBY_SEAMLESS_GAME_API){
				$this->load->view('iframe/player/goto_betby', $rlt);
				return;
			}

            if($game_platform_id == DIGITAIN_SEAMLESS_API){
            	$rlt['title'] = $this->external_system->getNameById($game_platform_id);
            	if ($is_mobile) {
            		$this->load->view('iframe/player/goto_digitain_mobile_view', $rlt);
					return;
            	}
            	if(isset($rlt['view']) && $rlt['view'] == "asian"){
            		$this->load->view('iframe/player/goto_digitain_asian_view', $rlt);
					return;
            	}
				$this->load->view('iframe/player/goto_digitain', $rlt);
				return;
			}

			/* if ($game_platform_id == PNG_SEAMLESS_GAME_API) {
                $this->load->view('iframe/player/goto_png_seamless_game_iframe', array('url' => $rlt['url'], 'platformName' => $platformName, 'favicon_brand' => false, 'allow_fullscreen' => true, 'show_low_balance_prompt' => false, 'game_platform_name' => 'PNG_SEAMLESS_GAME_API', 'params' => $rlt['params'], 'api_domain' => $rlt['api_domain'], 'lobby_url' => $rlt['lobby_url']));
                return;
            }

            if ($game_platform_id == T1_PNG_SEAMLESS_API) {
                $this->load->view('iframe/player/goto_png_seamless_game_iframe', array('url' => $rlt['url'], 'platformName' => $platformName, 'favicon_brand' => false, 'allow_fullscreen' => true, 'show_low_balance_prompt' => false, 'game_platform_name' => 'T1_PNG_SEAMLESS_API', 'params' => $rlt['params'], 'api_domain' => $rlt['api_domain'], 'lobby_url' => $rlt['lobby_url']));
                return;
            } */

            if ($game_platform_id == PINNACLE_SEAMLESS_GAME_API || $game_platform_id == T1_PINNACLE_SEAMLESS_GAME_API) {
                $this->load->view('iframe/player/goto_pinnacle_game_iframe', array('url' => $rlt['url'], 'platformName' => $platformName, 'favicon_brand' => false, 'allow_fullscreen' => true, 'no_scrolling' => true, 'origin' => $rlt['origin']));
                return;
            }

            if ($rlt['success'] && ($game_platform_id == SA_GAMING_API || $game_platform_id == T1SA_GAMING_API)) {
		    	$sa_gaming_redirect_merchants = $this->config->item('T1SA_GAMING_API_REDIRECT_MERCHANTS');
		    	if (isset($sa_gaming_redirect_merchants) && !empty($sa_gaming_redirect_merchants) && in_array($merchant_code, $sa_gaming_redirect_merchants)) {
		    		redirect($rlt['url']);
		    		return;
		    	}

            }

			if ($game_platform_id == PGSOFT_SEAMLESS_API || $game_platform_id == PGSOFT2_SEAMLESS_API || $game_platform_id == PGSOFT3_SEAMLESS_API || $game_platform_id == PGSOFT3_API || $game_platform_id == IDN_PGSOFT_SEAMLESS_API) {
				if($api->getSystemInfo('enabled_new_queryforward') && isset($rlt['is_html']) && $rlt['is_html'] == true){
					return $this->load->view(
						'iframe/pgsoft_game_iframe', 
						array(
							'platformName' => $platformName,
							'html' => $rlt['html']
						)
					);
				}
			}

            if ($game_platform_id == BETGAMES_SEAMLESS_GAME_API) {
                return $this->load->view('iframe/player/goto_betgames_seamless_game_iframe', array('url' => $rlt['url'], 'platformName' => $platformName, 'favicon_brand' => false, 'allow_fullscreen' => true, 'game_platform_name' => 'BETGAMES_SEAMLESS_GAME_API', 'params' => $rlt['params']));
            }

            if ($game_platform_id == TWAIN_SEAMLESS_GAME_API) {
                return $this->load->view('iframe/player/goto_twain_seamless_game_iframe', array('url' => $rlt['url'], 'platformName' => $platformName, 'favicon_brand' => false, 'allow_fullscreen' => true, 'game_platform_name' => 'TWAIN_SEAMLESS_GAME_API', 'params' => $rlt['params']));
            }

			if ($game_platform_id == AVIATRIX_SEAMLESS_GAME_API) {
				if($is_mobile){
					return redirect($rlt['url']);
				}
				return $this->load->view('iframe/player/goto_aviatrix_seamless_game_iframe', array('url' => $rlt['url'], 'platformName' => $platformName, 'favicon_brand' => false, 'allow_fullscreen' => true, 'game_platform_name' => 'AVIATRIX_SEAMLESS_GAME_API'));
			}

            $this->utils->debug_log('GOTOGAME RLT >---------------------->  ', $rlt, $extra);
            //bbin redirection
            if($game_platform_id == BBIN_API){
            	$this->utils->debug_log('GOTOGAME T1BBIN RLT >---------------------->  ', $rlt);
            	$data = array();

            	if (isset($rlt['success']) && $rlt['success']) {
					$data['full_html'] = $rlt['html'];
				} else if (isset($rlt['message'])) {
					$data['error_message'] = $rlt['message'];
				} else if (isset($rlt['message_lang'])) {
					$data['error_message_lang'] = $rlt['message_lang'];
				} else {
					$data['error_message_lang'] = 'goto_game.error';
				}

	            if ($game_mode == 'real') {
	                if (isset($rlt['message']) && strpos($rlt['message'], 'not complete')) {
	                    $this->goPlayerLogin();
	                }
	            }
	            if ($game_mode != "real") {
            		$rlt = $api->queryForwardGame(null, array('language' => $language, 'gameType' => $game_type,'is_mobile' => $is_mobile,'game_mode'=>$game_mode));
            		redirect($rlt['url']);
            		return;
            	}

            	// if(strtolower($redirection) == 'newtab'){
            	// 	redirect($rlt['url']);
            	// 	return;
            	// }

            	$this->utils->debug_log('GOTOGAME T1BBIN DATA >---------------------->  ', $data);
            	$this->load->view('share/goto_game_iframe', $data);
            	return;
            }
            if($game_platform_id == IMPT_API){
        		if ($rlt['success']) {
					//will redirect to forward url
					if (!empty($rlt['forward_url'])) {
						return redirect($rlt['forward_url']);
					}

					$launch_game_on_player = $rlt['launch_game_on_player'];

					$data = $rlt;
					if ($launch_game_on_player) {
						if ($data['mobile'] == 'true' || $data['mobile']===true) {
							$data['mobile'] = 'mobile';
						}
						$title_key='game_platform_name_'.$game_platform_id;

						$data['title']=$this->utils->getConfig('company_name').'-'.lang($title_key); //should be agent name

						$this->utils->debug_log('run pt on player', $data);
						return $this->load->view('iframe/player/goto_imptgame', $data);
					} else {
						$this->utils->debug_log('run pt on www', $data);
						return redirect($this->utils->getSystemUrl('www') . '/imptgame.html?' . http_build_query($data));
					}
				} else {

					//OGP-21732 redirect to url sent on error
					if(!empty($on_error_redirect)){
						if($post_message_on_error){
							$post_message['error_message'] = lang("Launch game encounter error.");
							if(isset($rlt['error_message'])){
								$post_message['error_message'] = $rlt['error_message'];
							}
			        		return $this->load->view('iframe/player/view_post_message_closed', $post_message);
			        	}
						return redirect($on_error_redirect);
					}
					$this->CI->utils->error_log('generate url failed');
					return $this->returnBadRequest();
				}
        	}

        	if($game_platform_id == PNG_API){
        		if($platform != 'pc' && $is_mobile){
					redirect($rlt['script_inc']);
				}else{
					return $this->load->view('common_games/goto_pnggame', $rlt);
				}
        	}

        	if($game_platform_id == PT_API || $game_platform_id == PT_KRW_API){
        		if ($rlt['success']) {
					//will redirect to forward url
					if (!empty($rlt['forward_url'])) {
						return redirect($rlt['forward_url']);
					}

					$launch_game_on_player = $rlt['launch_game_on_player'];

					if ($rlt['launch_game_on_player']) {
						$this->utils->debug_log('run pt on player', $rlt);
						$this->utils->debug_log('PT GAME HTML =====> '. $this->utils->getPlayerCenterTemplate(false) . '/player/goto_ptgame');
						// return $this->load->view($this->utils->getPlayerCenterTemplate(false) . '/player/goto_ptgame', $rlt);
						return $this->load->view('iframe/player/goto_ptgame', $rlt);
					} else {
						$this->CI->utils->debug_log('PTLOADER_URL', $site . '/ptgame.html?' . http_build_query($rlt));
						redirect($this->utils->getSystemUrl('www') . '/ptgame.html?' . http_build_query($rlt));
					}
				} else {
					if($post_message_on_error){
						$post_message['error_message'] = lang("Launch game encounter error.");
						if(isset($rlt['error_message'])){
							$post_message['error_message'] = $rlt['error_message'];
						}
		        		return $this->load->view('iframe/player/view_post_message_closed', $post_message);
		        	}

					//OGP-21732 redirect to url sent on error
					if(!empty($on_error_redirect)){
						return redirect($on_error_redirect);
					}

					$this->CI->utils->error_log('generate url failed');
					return $this->returnBadRequest();
				}
        	}

            if($game_platform_id == PT_V3_API)
            {
        		if($rlt['success'])
                {
					//will redirect to forward url
					if (!empty($rlt['forward_url']))
                    {
						return redirect($rlt['forward_url']);
					}

					return $this->load->view('iframe/player/goto_ptv3game', $rlt);
				}else{
					if($post_message_on_error){
						$post_message['error_message'] = lang("Launch game encounter error.");
						if(isset($rlt['error_message'])){
							$post_message['error_message'] = $rlt['error_message'];
						}
		        		return $this->load->view('iframe/player/view_post_message_closed', $post_message);
		        	}

					//OGP-21732 redirect to url sent on error
					if(!empty($on_error_redirect))
                    {
						return redirect($on_error_redirect);
					}

					$this->CI->utils->error_log('generate url failed');

					return $this->returnBadRequest();
				}

                $this->utils->debug_log('<---------- launch_game ---------->', 'launch_game_result', $rlt);
        	}

        	if($game_platform_id == SA_GAMING_API || $game_platform_id == T1SA_GAMING_API){
				$platformName = $this->external_system->getNameById($game_platform_id);
				$rlt["mobile"] = $is_mobile;
        		$this->launch_t1sagaming($game_code, $platformName, $redirection, $rlt);
				return;
        	}

            if (isset($rlt['success']) && $rlt['success']) {

            	# add cookies for oneworks game
				if($game_platform_id == ONEWORKS_API){
					$this->load->helper('cookie');

					if (isset($rlt['domains']) && !empty($rlt['domains'])) {
						foreach ($rlt['domains'] as $key) {
							$this->input->set_cookie('g', $rlt['sessionToken'], 3600, $key . "." . $this->utils->stripSubdomain($_SERVER['HTTP_HOST']));
							$this->utils->debug_log('ONEWORKS SUBDOMAIN =----------------------> ', $key . "." . $this->utils->stripSubdomain($_SERVER['HTTP_HOST']));

						}
						$this->input->set_cookie('g', $rlt['sessionToken'], 3600, $this->utils->stripSubdomain($_SERVER['HTTP_HOST']));
						$this->input->set_cookie('g', $rlt['sessionToken'], 3600, $_SERVER['HTTP_HOST']);
					}
				}

                $this->load->model('game_description_model');
                $platformName = $this->game_description_model->getGameNameByCurrentLang($game_code, $game_platform_id);
				$this->utils->debug_log('FAVICON ERROR ====>', $game_platform_id);
                // $api = $this->utils->loadExternalSystemLibObject($game_platform_id);
				$favicon_brand = $api->getSystemInfo('favicon', false);

                if (@$extra['side_game_api']) {
                    $platformName = @$extra['side_game_api'];
                }
                $iframeName = isset($rlt['iframeName']) ? $rlt['iframeName'] : "";
                $data = array(
                    'url' => $rlt['url'],
                    'platformName' => $platformName,
                    'iframeName' => $iframeName,
                    'favicon_brand' => $favicon_brand,
                );

                if (isset($rlt['is_redirect'])) {
                    //overwrite
                    $extra['is_redirect'] = $rlt['is_redirect'];
                }
                if (!@$extra['is_redirect']) {

                	$title_key='game_platform_name_'.$game_platform_id;
                	if($game_platform_id==AGIN_API && $game_code=='6'){
						$title_key.='_'.$game_code;
					}

                	$data['title']=$this->utils->getConfig('company_name').'-'.lang($title_key); //should be agent name
					$data['full_html'] = @$rlt['html'];

					if($game_platform_id == ION_GAMING_API || $game_platform_id == ION_GAMING_IDR1_API || $game_platform_id == IONGAMING_SEAMLESS_GAME_API || $game_platform_id == IONGAMING_SEAMLESS_IDR1_GAME_API || $game_platform_id == IONGAMING_SEAMLESS_IDR2_GAME_API){

						$data['payload'] = $rlt['payload'];

						// if(strtolower($redirection) == 'newtab'){
						// 	redirect($rlt['url'].'?'.http_build_query(['payload'=>$rlt['payload']]));
	     //        			// redirect($this->load->view('iframe/player/goto_iongaming', $data));
	     //        			return;
						// }
						$data['redirection']=empty($redirection) ? 'iframe' : $redirection;

	            		$this->processAdditionalIframeAttributesPerGame($data,$game_platform_id);

	            		$this->load->view('iframe/player/goto_iongaming', $data);
	            		return;

					}

					//make default allow fullscreen
					$data['allowfullscreen'] = true;
					$data['isAllowFullScreen'] = "allowfullscreen=\"true\"";

					if($game_platform_id == VIVOGAMING_API ||
					$game_platform_id == VIVOGAMING_IDR_B1_API ||
					$game_platform_id == VIVOGAMING_IDR_B1_ALADIN_API ||
					$game_platform_id == VIVOGAMING_SEAMLESS_API ||
					$game_platform_id == VIVOGAMING_SEAMLESS_IDR1_API ||
					$game_platform_id == VIVOGAMING_SEAMLESS_CNY1_API ||
					$game_platform_id == VIVOGAMING_SEAMLESS_THB1_API ||
					$game_platform_id == VIVOGAMING_SEAMLESS_USD1_API ||
					$game_platform_id == VIVOGAMING_SEAMLESS_VND1_API ||
					$game_platform_id == VIVOGAMING_SEAMLESS_MYR1_API ||
					$game_platform_id == T1_VIVOGAMING_SEAMLESS_API){
						$this->load->model('game_description_model');

						$unique_id = "game_code";

						# Roulette
						if($game_type == "roulette"){
							#american
							if($game_code == 182){
								$unique_id = "external_game_id";
								$game_type = 5;
							}else{
								#European
								$unique_id = "external_game_id";
								$game_type = 1;
							}
						}

						$platformName = $this->game_description_model->getGameNameByCurrentLang($game_type, $game_platform_id,$unique_id);

						$data['allowfullscreen'] = true;
						$data['isAllowFullScreen'] = "allowfullscreen=\"true\"";
						$data['platformName'] = $platformName?$platformName:'VIVO GAMING';

						#REDIRECT TO NEW TAB
						if(strtolower($redirection) == 'newtab'){
							redirect($rlt['url']);
						}

						$this->load->view($this->utils->getPlayerCenterTemplate() . '/game_iframe', $data);
						return;
					}

                    if($game_platform_id ==  PRAGMATICPLAY_LIVEDEALER_IDR1_API || $game_platform_id ==  PRAGMATICPLAY_LIVEDEALER_CNY1_API ||
                       $game_platform_id ==  PRAGMATICPLAY_LIVEDEALER_THB1_API || $game_platform_id ==  PRAGMATICPLAY_LIVEDEALER_MYR1_API ||
                       $game_platform_id ==  PRAGMATICPLAY_LIVEDEALER_VND1_API || $game_platform_id == PRAGMATICPLAY_LIVEDEALER_USD1_API){

                      $data['allowfullscreen'] = true;
                      $data['isAllowFullScreen'] = "allowfullscreen=\"true\"";
					}

					if($game_platform_id == SPADE_GAMING_API){
						$data['customGameJs'] = array( $this->utils->processAnyUrl('/png_fullscreen.js', '/resources/png_game') );
					}

					if( isset($data['customGameJs']) && !empty($data['customGameJs']) ){
						$this->load->helper('load_game_js_helper');
					}

                    #REDIRECT TO NEW TAB
	            	if(strtolower($redirection) == 'newtab'){
	            		redirect($rlt['url']);
	            	}

                    $this->processAdditionalIframeAttributesPerGame($data,$game_platform_id, $game_code);

                    $this->load->view($this->utils->getPlayerCenterTemplate() . '/game_iframe', $data);
                } else {
                    if (!empty($rlt['url'])) {
                        redirect($rlt['url']);
                    }
                }

                return $data;
            } else {
                // echo 'goto error';

				//OGP-21732 redirect to url sent on error

				$disable_home_link = isset($extra['disable_home_link']) ? $extra['disable_home_link'] : false;

				if($post_message_on_error){
					$post_message['error_message'] = lang("Launch game encounter error.");
					if(isset($rlt['error_message'])){
						$post_message['error_message'] = $rlt['error_message'];
					}
	        		return $this->load->view('iframe/player/view_post_message_closed', $post_message);
	        	}
				
				if(!empty($on_error_redirect) && !$disable_home_link){
					return redirect($on_error_redirect);		
				}
                die(lang('goto_game.error'));
            }

        } else {
        	if($post_message_on_error){
        		$post_message['error_message'] = lang("Login info is empty.");
        		return $this->load->view('iframe/player/view_post_message_closed', $post_message);
        	}

			//OGP-21732 redirect to url sent on error
			if(!empty($on_error_redirect)){
            	return redirect($on_error_redirect);
            }
            $this->returnBadRequest();
        }
    }

    private function processAdditionalIframeAttributesPerGame(&$data,$gamePlatformId,$gameCode=null){
    	switch ($gamePlatformId) {
    		case HB_API:
    			$data['isAllowFullScreen'] = "allowfullscreen allow=\"fullscreen\"";
		    	$data['isResponsive'] = $this->utils->getConfig('isResponsive') ?: true;
		    	$data['isScrolling'] = $this->utils->getConfig('isScrolling') ?: true;
		    	$data['isOverflowAuto'] = $this->utils->getConfig('isOverflowAuto') ?: true;
				break;
			case ISB_INR1_API:
    		case ISB_API:
    			$data['isAllowFullScreen'] = "allowfullscreen=\"true\"";
    			break;
    		case REDTIGER_API:
    			$this->load->model('game_description_model');
    			$platforName = $this->game_description_model->getGameNameByCurrentLang($gameCode, $gamePlatformId);
    			$data['platformName'] = $platforName;
    			break;
    		case HOGAMING_API:
    		case HOGAMING_IDR_B1_API:
    			$data['isAllowFullScreen'] = "allowfullscreen=\"allowfullscreen\" mozallowfullscreen=\"mozallowfullscreen\" msallowfullscreen=\"msallowfullscreen\" oallowfullscreen=\"oallowfullscreen\" webkitallowfullscreen=\"webkitallowfullscreen\"";
    			break;
    		default:
    			if(in_array($gamePlatformId,$this->utils->getConfig('hb_common_apis'))){
					$data['isAllowFullScreen'] = "allowfullscreen allow=\"fullscreen\"";
			    	$data['isResponsive'] = $this->utils->getConfig('isResponsive') ?: true;
			    	$data['isScrolling'] = $this->utils->getConfig('isScrolling') ?: true;
			    	$data['isOverflowAuto'] = $this->utils->getConfig('isOverflowAuto') ?: true;
				}elseif(in_array($gamePlatformId,$this->utils->getConfig('isb_common_apis'))){
					$data['isAllowFullScreen'] = "allowfullscreen=\"true\"";
				}

                $this->load->model(['game_description_model', 'external_system']);
                $platformName = $this->game_description_model->getGameNameByCurrentLang($gameCode, $gamePlatformId);
                $platformapi = $this->external_system->getSystemName($gamePlatformId);
                $data['platformName'] = !empty($platformName) ? $platformName : $platformapi;
    			break;
    	}
    }

    public function goto_hggame($game_platform_id,
    						    $game_type_id,
    						    $language,
    						    $table_id=null,
    						    $game_mode='real',
    						    $bet_limit=null,
    						    $is_mobile=null,
    						    $version=null,
    						    $skin_id=null){
    	$extra = [
    		      'bet_limit' => $bet_limit,
    			  'version' => $version,
    			  'skin_id' => $skin_id,
    			 ];
    	$this->gotogame($game_platform_id,$table_id,$game_mode,$game_type_id,null,$language,$extra,null,$is_mobile);
	}

	public function goto_vivogame($game_platform_id,$game_type,$language,$table_id=null,$game_mode='real',$is_mobile = null)
	{

		$this->gotogame($game_platform_id,$table_id,$game_mode,$game_type,null,$language,null,true,$is_mobile);
	}

    public function goto_isin4d($game_platform_id,$language="en"){
    	$this->gotogame($game_platform_id,null,"real",null,null,$language);
    }

    public function goto_qqgame($game_platform_id,$game_code="qqthai",$language="en"){
    	$this->gotogame($game_platform_id,$game_code,"real",null,null,$language);
    }

    public function goto_nttech_game($game_platform_id,$language="en",$is_mobile=null){
    	$this->gotogame($game_platform_id,null,"real",null,null,$language,null,false,$is_mobile);
    }

    public function goto_nttechv2_game($game_platform_id,$game_type="LIVE",$language=null,$is_mobile=null,$isRedirect="false"){
    	$this->gotogame($game_platform_id,null,"real",$game_type,null,$language,null,$isRedirect,$is_mobile);
	}

	public function goto_kingmaker_game($game_platform_id,$game_code,$language=null,$is_mobile=null,$isRedirect="false",$game_type="TABLE"){
    	$this->gotogame($game_platform_id,$game_code,"real",$game_type,null,$language,null,$isRedirect,$is_mobile);
    }

    public function goto_sv388_game($game_platform_id,$game_code,$language=null,$is_mobile=null,$isRedirect="false",$game_type="LIVE"){
        $this->gotogame($game_platform_id,$game_code,"real",$game_type,null,$language,null,$isRedirect,$is_mobile);
    }

    public function goto_24tech_game($game_platform_id,$language="en",$is_mobile=null,$skin_id=null){
    	$this->gotogame($game_platform_id,null,"real",null,null,$language,['skincolor'=>$skin_id],false,$is_mobile);
    }

    public function goto_onebook_game($game_platform_id,$game_type=null,$language=null,$is_mobile=null,$skin_id=null,$oddstype=null,$isRedirect="false"){
    	$this->gotogame($game_platform_id,
    					null,
    					"real",
    					$game_type,
    					null,
    					$language,
    					['skincolor'=>$skin_id,'oddstype'=>$oddstype],
    					$isRedirect,
    					$is_mobile);
    }

    public function goto_sbobet_game($game_platform_id,$language="en",$is_mobile=null,$theme=null,$oddstyle=null,$isRedirect="false"){
    	$this->gotogame($game_platform_id,
    					null,
    					"real",
    					null,
    					null,
    					$language,
    					['theme'=>$theme,'oddstyle'=>$oddstyle],
    					$isRedirect,
    					$is_mobile);
    }


    public function goto_imesb_game($game_platform_id=IMESB_API,$language=null,$is_mobile=null,$gameMode="real",$isRedirect="false"){
    	$this->gotogame($game_platform_id,
    					null,
    					$gameMode,
    					null,
    					null,
    					$language,
    					null,
    					$isRedirect,
    					$is_mobile);
    }

	/**
	 * overview : go to game
	 *
	 * @param int	 $game_platform_id
	 * @param string $game_code
	 * @param string $game_mode
	 * @param string $game_type
	 * @param string $side_game_api
	 * @param string $language
	 * @param null $extra
	 * @param bool|false $isRedirect
	 * @return array|void
	 */
	public function gotogame($game_platform_id, $game_code = null, $game_mode = 'real', $game_type = null, $side_game_api = null, $language = 'zh-cn', $extra = null, $isRedirect = "false", $is_mobile = null) {

		# Set $isRedirect from string type to boolean type
		$isRedirect = filter_var($isRedirect, FILTER_VALIDATE_BOOLEAN);

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();
		$this->utils->debug_log('GOTOGAME PLAYER ID: ', $player_id, "PLAYER NAME: ", $player_name);

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('GOTO GAME MAINTENANCE');
            return;
        }

		# NOT LOGIN, GOTO DEMO GAME
		if (!$player_name && !$player_id) {
			$extra = [];
			$extra['game_code'] = $game_code;
			$extra['game_mode'] = $game_mode;
			$extra['is_mobile'] = $is_mobile;

			if ($game_mode == 'false' || $game_mode == 'demo' || !$game_mode) {
				$this->goto_demo_game($game_platform_id, $game_type, $language, $extra);
				return;
			} else {
				$loginUrl = $this->utils->getSystemUrl('player', '/iframe/auth/login', false);
				redirect($loginUrl);
			}
		}


		#login player, but game_mode is set to false, it will redirect to demo mode
		if ($game_mode == 'false' || $game_mode == 'demo' || !$game_mode) {
			$this->utils->debug_log('DEMO GAME EXTRA =----------------> ', $extra);
			$extra['game_code'] = $game_code;
			$extra['game_mode'] = $game_mode;
			$extra['player_name'] = $player_name;
			$extra['is_mobile'] = $is_mobile;
			$this->goto_demo_game($game_platform_id, $game_type, $language, $extra);
			return;
		}
		$this->load->model(array('external_system', 'game_provider_auth'));
		$success = $this->prepareGotoGame($player_name, $player_id, $game_platform_id);
		$this->utils->debug_log('GOTOGAME SUCCESS: ', $success);

		$loginInfo = $this->game_provider_auth->getLoginInfoByPlayerId($player_id, $game_platform_id);

		# load GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK EXIST ON API AND UPDATE REGISTER FLAG
		$player = $this->checkExistOnApiAndUpdateRegisterFlag($api,$player_id,$player_name);

		if ($success && $loginInfo) {

			// =============================================================================
            # CHECK PLAYER IF EXIST
            $player = $api->isPlayerExist($player_name);

            # IF NOT CREATE PLAYER
            if (isset($player['exists']) && !$player['exists']) {
                if(!is_null($player['exists'])){
                   $this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
                }
            }
			// =============================================================================

			$_transferResult = $this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$this->utils->debug_log('GOTOGAME INFO: gc: ', $game_code, " gm: ", $game_mode, " gt: ", $game_type, " sga: ", $side_game_api, " lang: ", $language, " extra: ", $extra);

			$this->_refreshBalanceFromApiAndUpdateSubWallet($game_platform_id, $api, $player_name, $player_id);

            if(is_null($language) || $language == 'null'){
                $language = $this->language_function->getCurrentLanguage();
            }

			$rlt = $api->queryForwardGame(
				$player_name,
				array(
					'game_code' => $game_code,
					'game_mode' => $game_mode,
					'game_type' => $game_type,
					'game_name' => isset($extra['game_name']) ? $extra['game_name'] : '',
					'password' => $loginInfo->password,
					'language' => $language,
					'side_game_api' => $side_game_api,
					'is_mobile' => $is_mobile,
					'extra' => $extra,
				)
			);
			$this->playerTrackingEventPlayNow($game_platform_id, $rlt, ["game_code" => $game_code, "game_type" => $game_type], $_transferResult);
			$this->utils->debug_log('GOTOGAME RLT >---------------------->  ', $rlt, $isRedirect);
			if (isset($rlt['success']) && $rlt['success']) {
				$platformName = $this->external_system->getNameById($game_platform_id);

				if ($side_game_api) {
					$platformName = $side_game_api;
				}
				$iframeName = isset($rlt['iframeName']) ? $rlt['iframeName'] : "";
                $getPlayerGameHistoryURL = $api->queryBetDetailLink($player_name);
                // $this->utils->debug_log('====================== game history url ========================', $getPlayerGameHistoryURL);
                if ($getPlayerGameHistoryURL['success']) {
                    $data = array(
                        'url' => $rlt['url'],
                        'platformName' => $platformName,
                        'iframeName' => $iframeName,
                        'getPlayerGameHistoryURL' => isset($getPlayerGameHistoryURL['url'])?$getPlayerGameHistoryURL['url']:null,
                        'platform_id' =>  $game_platform_id
                    );
                } else {
                    $data = array(
                        'url' => $rlt['url'],
                        'platformName' => $platformName,
                        'iframeName' => $iframeName,
                        'platform_id' =>  $game_platform_id
                    );
				}

				//OGP-19931 enalble scrolling
				if($game_platform_id == IMESB_API){
					$data['isScrolling'] = 'yes';
				}

                if($api->getSystemInfo('enabled_fr_feature')){
                	$api->getCampaigns($player_name);
                	$extra['game_code'] = $game_code;
                	$playerFreeRounds = $api->getPlayerFreeRounds($player_name,$extra);
                	unset($playerFreeRounds['success']);
                	unset($playerFreeRounds['response_result_id']);
                	$data['player_free_rounds'] = $playerFreeRounds;
                	// echo "<pre>";
                	// print_r($data);exit();
                	if(!empty($playerFreeRounds)){
						return $this->load->view('iframe/player/goto_isb_freespin',$data);
                	}
				}

				if (isset($rlt['is_redirect'])) {
					//overwrite
					$isRedirect = $rlt['is_redirect'];
				}

                if($is_mobile) {
                    $isRedirect = true;
                }

                $isRedirect = $api->getSystemInfo('is_redirect', $isRedirect);


				if (!$isRedirect) {
					$this->processAdditionalIframeAttributesPerGame($data,$game_platform_id,$game_code);
					$this->load->view($this->utils->getPlayerCenterTemplate() . '/game_iframe', $data);
				} else {
					if (!empty($rlt['url'])) {
						redirect($rlt['url']);
					}
				}

				return $data;
			} else {
				die(lang('goto_game.error'));
			}

		} else {
			$this->returnBadRequest();
		}

	}

	public function favorite() {

		if (isset($_SERVER['HTTP_ORIGIN'])) {
			$this->output->set_header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
			$this->output->set_header('Access-Control-Allow-Credentials: true');
		}

		$this->load->model('favorite_game_model');

		$success = false;
		$player_id = $this->authentication->getPlayerId();
		$name = $this->input->get_post('name');
		$image = $this->input->get_post('image');
		$url = $this->input->get_post('url');
		$game_platform_id = $this->input->get_post('game_platform_id');
		$game_type_id = $this->input->get_post('game_type_id');
		$game_description_id = $this->input->get_post('game_description_id');

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		if ($player_id) {

			if (!$this->favorite_game_model->added_to_favorites($player_id, $url)) {
				$success = $this->favorite_game_model->add_to_favorites(array(
					'player_id' => $player_id,
					'name' => $name,
					'image' => $image,
					'url' => $url,
					'game_platform_id' => $game_platform_id,
					'game_type_id' => $game_type_id,
					'game_description_id' => $game_description_id,
				));
				$message = 'added';
			} else {
				$success = $this->favorite_game_model->remove_from_favorites($player_id, $url);
				$message = 'removed';
			}

		} else {
			$message = 'player is not loggged in';
		}

		$response = array(
			'success' => $success,
			'message' => $message,
		);

		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode($response));

	}

	public function remove_from_favorites() {

		$this->load->model('favorite_game_model');

		$player_id = $this->authentication->getPlayerId();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }
		$url = $this->input->get_post('url');
		$success = $this->favorite_game_model->remove_from_favorites($player_id, $url);

		$response = array('success' => $success);

		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode($response));

	}

	public function is_favorite() {

		$this->output->set_header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
		$this->output->set_header('Access-Control-Allow-Credentials: true');

		$this->load->model('favorite_game_model');

		$player_id = $this->authentication->getPlayerId();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }
		$url = $this->input->get_post('url');
		$favorite = $this->favorite_game_model->added_to_favorites($player_id, $url);

		$response = array(
			'success' => TRUE,
			'favorite' => ($player_id && $favorite),
		);

		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode($response));

	}

	public function favorites() {

		$this->output->set_header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
		$this->output->set_header('Access-Control-Allow-Credentials: true');

		$this->load->model('favorite_game_model');

		$player_id = $this->authentication->getPlayerId();
		$favorites = $this->favorite_game_model->get_favorites($player_id);

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		$response = array(
			'success' => TRUE,
			'favorites' => array_column($favorites, 'url'),
		);

		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode($response));

	}

	public function favorite_details() {

		$this->output->set_header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
		$this->output->set_header('Access-Control-Allow-Credentials: true');

		$this->load->model('favorite_game_model');

		$player_id = $this->authentication->getPlayerId();
		$favorites = $this->favorite_game_model->get_favorites($player_id);

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		$response = array(
			'success' => TRUE,
			'favorites' => $favorites,
		);

		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode($response));

	}

	public function recently_played() {

		$this->output->set_header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
		$this->output->set_header('Access-Control-Allow-Credentials: true');

		$player_id = $this->authentication->getPlayerId();

		$recently_played = $this->utils->get_recently_played();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		$response = array(
			'success' => TRUE,
			'recently_played' => $recently_played,
		);

		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode($response));

	}

	public function goto_maintenance($gameAPIName) {
		$maintenanceConfig = $this->config->item('maintenance_url');

		if (is_array($maintenanceConfig) && array_key_exists($gameAPIName, $maintenanceConfig)) {
			$this->utils->debug_log($this->utils->getSystemUrl('www').$maintenanceConfig[$gameAPIName]);
			redirect($this->utils->getSystemUrl('www').$maintenanceConfig[$gameAPIName]);
		} elseif (is_array($maintenanceConfig) && array_key_exists('*', $maintenanceConfig)) {
			$this->utils->debug_log($this->utils->getSystemUrl('www').$maintenanceConfig['*']);

			redirect($this->utils->getSystemUrl('www').$maintenanceConfig['*']);
		} else {
			die(lang('goto_game.sysMaintenance'));
		}
	}

	/**
	 * overview : go to ipm v2
	 *
	 * @param none
	 */
	public function goto_ipm_v2_game($lang = null) {

        $is_mobile = $this->utils->is_mobile();

        # LOAD MODEL AND LIBRARIES
        $this->load->model(array('game_provider_auth', 'external_system'));

        # DECLARE WHICH GAME PLATFORM TO USE
        $game_platform_id = IPM_V2_SPORTS_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('ipm');
            return;
        }

        $api = $this->utils->loadExternalSystemLibObject($game_platform_id);
        $platformName = $this->external_system->getNameById($game_platform_id);

        //if not login
        if (!$this->authentication->isLoggedIn() && $gameMode == 'real') {
            $this->goPlayerLogin();
        }

        # GET LOGGED-IN PLAYER
        $player_id = $this->authentication->getPlayerId();
        $session_id = $this->session->userdata('session_id');
        $playerName = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }
        # LOAD GAME API

        # CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
        $this->checkBlockGamePlatformSetting($game_platform_id);

        # CHECK PLAYER IF EXIST
        $player = $api->isPlayerExist($playerName);

        # IF NOT CREATE PLAYER
        if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

        # CHECK IF LOGGED-IN PLAYER IS BLOCKED
        $blocked = $api->isBlocked($playerName);

        if (!$blocked) {
            $this->_transferAllWallet($player_id, $playerName, $game_platform_id);
            if(empty($lang)) {
            	$lang = $this->language_function->getCurrentLanguage();
            }

            $params = array(
                'language' => $lang,
                'is_mobile' => $is_mobile
            );

            $data = $api->queryForwardGame($playerName, $params);
            //var_dump($data);die();
            $this->utils->debug_log("queryForwardGame", var_export($data, true));
            if (isset($data['success']) && $data['success']) {
            	if ($is_mobile) {
            		redirect($data['url']);
            	}else{
	                $this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName));
            	}
                return;
            } else {
                die(lang('goto_game.error'));
            }
        } else {
            die(lang('goto_game.blocked'));
        }
	}

	public function goto_ld_casino_game($mode = "real") {
		$this->load->model(array('external_system', 'game_provider_auth'));
		$game_platform_id = LD_CASINO_API;


		$player_name = $this->authentication->getUsername();
		$player_id = $this->authentication->getPlayerId();
		$is_mobile = $this->utils->is_mobile();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		$extra = array();
		$extra['game_mode'] = $mode;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('LD Casino');
            return;
        }

		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$player = $api->isPlayerExist($player_name);

		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		$blocked = $api->isBlocked($player_name, $extra);

		$blocked = $api->isBlocked($player_name);
		if (!$blocked) {
			$extra = array();
			$extra['language'] = $this->language_function->getCurrentLanguage();
			$extra['is_mobile'] = $is_mobile;

			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$rlt = $api->queryForwardGame($player_name, $extra);
			$this->CI->utils->debug_log("goto ld casino", $rlt['url']);
			$platformName = $this->external_system->getNameById($game_platform_id);
			$data = array('url' => $rlt['url'], 'platformName' => $platformName,);

			if ($is_mobile){
				return redirect($data['url']);
			} else {
				$this->load->view('iframe/game_iframe', $data);
			}
		}  else {
			die(lang('goto_game.blocked'));
		}
	}

	/**
	 * overview : go to t1lottery game
	 * mode: real or fun
	 */
	public function goto_t1lottery($lottery_type = NULL, $lottery_game_id = NULL, $mode = 'real', $language = null, $is_mobile = null, $redirection='newtab') {

        switch($lottery_type){
            case 'lottery':
                $lottery_type = 'lottery';
                break;
            case 'mini':
                $lottery_type = 'mini';
                break;
            default:
                $lottery_type = 'lottery';
                break;
        }

        // $mode = ($mode == '_null') ? 'real' : $mode;
        //$language = ($language == '_null') ? 'zh-cn' : $language;

        if(is_null($language) || $language == '_null')
        {
            $language = $this->language_function->getCurrentLanguage();
        }

        $is_mobile = ($is_mobile == '_null') ? null : $is_mobile;
        // $mode = (empty($mode)) ? 'real' : (($mode == 'trial') ? 'trial' : 'real');
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		$this->load->model(array('game_provider_auth', 'external_system'));

		$game_platform_id = T1LOTTERY_API;

        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('T1Lottery');
            return;
        }

		$player_name = $this->authentication->getUsername();
		$player_id = $this->authentication->getPlayerId();

		$params=[
            'lottery_type' => $lottery_type,
		    'lottery_game_id' => $lottery_game_id,
		    'mode' => $mode,
		    'language' => $language,
            'is_mobile' => $is_mobile,
            'ip' => $this->input->ip_address()
        ];
       	//check logged player
		if(empty($player_id)){
			if($mode=='trial'){
				$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
				if ($api && $api->isActive()) {
					$rlt = $api->queryForwardGame($player_name, $params);
					if($rlt['success'] && !empty($rlt['url'])){
						if($is_mobile){
							return redirect($rlt['url']);
							// header('Location: '.$url['url']);
						}else{
							$this->utils->debug_log('goto T1 LOTTERY API game=>', $rlt['url']);
							if($redirection=='iframe'){
								redirect($rlt['url']);
							}else{
								$this->load->view($this->utils->getPlayerCenterTemplate() .  '/game_iframe', array('url' => $rlt['url'], 'platformName' => $platformName));
							}
							return;
						}
					}else{
						$this->goto_maintenance('T1Lottery');
						return;
					}
				}else{
		            $this->goto_maintenance('T1Lottery');
		            return;
				}

				return ;
			}else{
				return $this->goPlayerLogin();
			}
		}

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		$platformName = $this->external_system->getNameById($game_platform_id);

		switch ($language) {
			case 'zh-cn':
				$platformName = '致富彩票';
				break;
			// Template for default value.
			default:
				break;
		}

		$success = $this->prepareGotoGame($player_name, $player_id, $game_platform_id);
		$data = array('platformName' => $platformName);
		if ($success) {
			$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$url = $api->queryForwardGame($player_name, $params);
			if($url['success'] && !empty($url['url'])){
				if($is_mobile){
					return redirect($url['url']);
					// header('Location: '.$url['url']);
				}else{
					$this->utils->debug_log('goto T1 LOTTERY API game=>', $url['url']);
					if($redirection=='iframe'){
						redirect($url['url']);
					}else{
						$this->load->view($this->utils->getPlayerCenterTemplate() .  '/game_iframe', array('url' => $url['url'], 'platformName' => $platformName, 'isScrolling' => true, 'isOverflowAuto' => true));
					}
					return;
				}
			}else{
				$this->goto_maintenance($platformName);
				return;
			}
		}
	}

	public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
            case '1':
                $lang = 'en-us'; // english
                break;
            case '2':
                $lang = 'zh-cn'; // chinese
                break;
            case '3':
                $lang = 'id-id'; // indonesia
                break;
            case '4':
                $lang = 'vi-vn'; // vietnamese
                break;
            case '5':
                $lang = 'ko-kr'; // korean
                break;
            case '6':
                $lang = 'th-th'; // Thai
                break;
            default:
                $lang = 'en-us'; // default as english
                break;
        }
        return $lang;
    }

	/**
	 * overview : go to T1 games
	 * mode: real or fun
	 * MG Casino launcher - /iframe_module/goto_t1games/1008/_mglivecasino
	 * DG Casino launcher - /iframe_module/goto_t1games/1009/
	 * Pragmatic Play - /iframe_module/goto_t1games/1011/{gamecode}/real
	 * PNG - /iframe_module/goto_t1games/1010/{gamecode}/real
	 * TTG - /iframe_module/goto_t1games/1012/{gamecode}/real/0/{gameid}
	 * AGIN Casino - /iframe_module/goto_t1games/1013/null/real/0
	 * AGIN Fishing Game - /iframe_module/goto_t1games/1013/null/real/6
	 * JUMB slots /iframe_module/goto_t1games/1018/{game_code}/{real/fun}/slots
	 * JUMB fishing /iframe_module/goto_t1games/1018/{game_code}/{real/fun}/fishing
	 * ISB /iframe_module/goto_t1games/1020/{game_code}/{real/fun}/
	 * Flow Gaming /iframe_module/goto_t1games/1029/{game_code}/{real/fun}/{game_platform}
	 * KYCARD /iframe_module/goto_t1games/1032/{game_code}
	 */
	public function goto_t1games($game_platform_id, $gamecode="null",$mode='real', $game_type="null",$game_id="null",$language="null",$redirection = 'newtab') {
		if(strtolower($gamecode) == "null"){
			$gamecode = null;
		}

		if(strtolower($game_type) == "null"){
			$game_type = null;
		}

		if(strtolower($game_id) == "null"){
			$game_id = null;
		}

		if(strtolower($language) == "null" || strtolower($language) == "default" ){
			$language = null;
		}

        if(is_null($language)){
           $language = $this->language_function->getCurrentLanguage();
        }

		$is_mobile = $this->utils->is_mobile();
		# /iframe_module/goto_ttggame/526/FrogsNFlies/0/

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'operatorglobalsettings'));


		# DECLARE WHICH GAME PLATFORM TO USE
		if(!($game_platform_id )){
			die(lang('goto_game.error'));
		}

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('t1games');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# CHECK IF GAME STATUS IF ACTIVE **game_description ['status'] == 0
        
        $this->load->model(['game_description_model']);
		$game_status = $this->game_description_model->getActiveGameStatus($game_platform_id, $gamecode);

		if(isset($game_status) && $game_status == 0){
			return die(lang('goto_game.error'));
		}


		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);


		$favicon_brand = $api->getSystemInfo('favicon', false);

		if(empty($language)){
			$language = $this->getLauncherLanguage($this->language_function->getCurrentLanguage());
		}

        $isPlayerLogin = $this->authentication->isLoggedIn();

		//if not login
        if (!$isPlayerLogin) {
            $extra['game_id'] = $game_id;
            $extra['game_code'] = $gamecode;
            $extra['language'] = $language;
            $extra['game_mode'] = $extra['mode'] = $mode;
            $extra['game_type'] = $game_type;
            $data = $api->queryForwardGame(null, $extra);

            //game API that have demo game and not required authenticated player
            $t1_games_not_allowed_login_on_demo = [
                T1_PINNACLE_SEAMLESS_GAME_API,
                T1_BETGAMES_SEAMLESS_GAME_API,
                T1_TWAIN_SEAMLESS_GAME_API,
            ];

            if (in_array($game_platform_id, $t1_games_not_allowed_login_on_demo) && $mode != 'real') {
                if ($game_platform_id == T1_BETGAMES_SEAMLESS_GAME_API) {
                    return $this->load->view('iframe/player/goto_betgames_seamless_game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true, 'game_platform_name' => 'T1_BETGAMES_SEAMLESS_GAME_API'));
                }
    
                if ($game_platform_id == T1_TWAIN_SEAMLESS_GAME_API) {
                    return $this->load->view('iframe/player/goto_twain_seamless_game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true, 'game_platform_name' => 'T1_TWAIN_SEAMLESS_GAME_API', 'params' => $data['params']));
                }

                return redirect($data['url']);
            }

            $this->goPlayerLogin();
        }

		$this->load->model(array('external_system', 'game_provider_auth'));
		$success = $this->prepareGotoGame($player_name, $player_id, $game_platform_id);
		$this->utils->debug_log('GOTO_T1GAMES SUCCESS: ', $success);

		$loginInfo = $this->game_provider_auth->getLoginInfoByPlayerId($player_id, $game_platform_id);
		
		if ($success && $loginInfo) {
			// =============================================================================
            # CHECK PLAYER IF EXIST
			$extra['game_mode'] = $mode;
            $player = $api->isPlayerExist($player_name, $extra);

            # IF NOT CREATE PLAYER
            if (isset($player['exists']) && !$player['exists']) {
                if(!is_null($player['exists'])){
                   $this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api, $extra);
                }
            }
			// =============================================================================

		}	

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);
		$apiName = $this->external_system->getSystemName($game_platform_id);

		if ((isset($blocked['blocked']) && $blocked['blocked']) || $blocked) {
            $this->goBlock();
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$extra['game_id'] = $game_id;
			$extra['game_code'] = $gamecode;
			$extra['language'] = $language;
			$extra['mode'] = $mode;

			if($game_platform_id == T1MGPLUS_API){
				$extra['platform'] = $is_mobile?'mobile':'pc';
				$extra['game_type'] = isset($game_type) ? $game_type : 'Game';
			}else{
				$extra['platform'] = $is_mobile?'mobile':'pc';
				$extra['game_type'] = $game_type;
			}

            $is_mobile_redirect = $api->getSystemInfo('is_mobile_redirect', true);
            $extra_info_redirection = $api->getSystemInfo('redirection', $redirection);
            $redirection = $extra_info_redirection == $this->redirection_iframe ? $extra_info_redirection : $this->redirection_newtab;

			$extra['redirection'] = $redirection;
			$extra['cashierURL'] = base_url().'iframe_module/iframe_viewMiniCashier';

			$extra['extra'] = ['t1_lobby_url' => $this->get_lobby_url()];

			$data = $api->queryForwardGame($player_name, $extra);

			if($game_platform_id == T1MGPLUS_API){
				if ($is_mobile) {
					redirect($data['url']);
				} else {
					$this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $apiName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true));
					return;
				}
			}

			if (isset($data['success']) && $data['success']) {
                if ($game_platform_id == T1_PINNACLE_SEAMLESS_GAME_API) {
                    if ($is_mobile) {
                        if ($is_mobile_redirect) {
                            redirect($data['url']);
                        } else {
                            $this->load->view('iframe/player/goto_pinnacle_game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true, 'no_scrolling' => true, 'origin' => $data['origin']));
                            return;
                        }
                    } else {
                        if ($redirection == $this->redirection_iframe) {
                            $this->load->view('iframe/player/goto_pinnacle_game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true, 'no_scrolling' => true, 'origin' => $data['origin']));
                            return;
                        } else {
                            redirect($data['url']);
                        }
                    }
                }

                if ($game_platform_id == T1_PNG_SEAMLESS_API) {
                    return $this->load->view('iframe/player/goto_png_seamless_game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true, 'game_platform_name' => 'T1_PNG_SEAMLESS_API', 'api_domain' => $data['api_domain'], 'lobby_url' => $data['lobby_url']));
                }

                if ($game_platform_id == T1_BETGAMES_SEAMLESS_GAME_API) {
                    return $this->load->view('iframe/player/goto_betgames_seamless_game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true, 'game_platform_name' => 'T1_BETGAMES_SEAMLESS_GAME_API'));
                }

                if ($game_platform_id == T1_TWAIN_SEAMLESS_GAME_API) {
                    return $this->load->view('iframe/player/goto_twain_seamless_game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true, 'game_platform_name' => 'T1_TWAIN_SEAMLESS_GAME_API', 'params' => $data['params']));
                }

				if ($game_platform_id == T1_AVIATRIX_SEAMLESS_GAME_API) {
					return $this->load->view('iframe/player/goto_aviatrix_seamless_game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true, 'game_platform_name' => 'T1_AVIATRIX_SEAMLESS_GAME_API'));
                }

                $result = array(
                    'url' => $data['url'],
                    'platformName' => $platformName,
                    'allow_fullscreen' => true,
                );

				if($is_mobile){
                    if ($is_mobile_redirect) {
                        redirect($data['url']);
                    } else {
                        return $this->load->view('iframe/game_iframe', $result);
                    }
				}else{
					if ($redirection == $this->redirection_iframe) {
						return $this->load->view('iframe/game_iframe', $result);
					}
                    header('Location: '.$data['url']);
				}
			} else {
				die(lang('goto_game.error'));
			}
			return;
		}
	}

	// public function goto_ld_lottery_game($mode = "real") {
	public function goto_ct_lottery_game($mode = "real") {	// Change name to ct lottery as per client request
		$this->load->model(array('external_system', 'game_provider_auth'));

		$game_platform_id = LD_LOTTERY_API;

		$player_name = $this->authentication->getUsername();
		$player_id = $this->authentication->getPlayerId();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('ld lottery game');
            return;
        }

		$is_mobile = $this->utils->is_mobile();

		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$player = $api->isPlayerExist($player_name);

		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		$blocked = $api->isBlocked($player_name);
		if (!$blocked) {

			$extra = array();
			$extra['language'] = $this->language_function->getCurrentLanguage();
			$extra['is_mobile'] = $is_mobile;
			$extra['game_mode'] = $mode;

			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);


			$rlt = $api->queryForwardGame($player_name, $extra);

			$this->CI->utils->debug_log("goto evolution gaming", $rlt['url']);
			$platformName = $this->external_system->getNameById($game_platform_id);

			$data = array('url' => $rlt['url'], 'platformName' => $platformName,);
            $this->load->view('iframe/game_iframe', $data);

		}  else {
			die(lang('goto_game.blocked'));
		}

	}

	public function goto_kglottery_game($mode = "real"){
		$this->goto_fadald_lottery_game($mode);
	}

	// public function goto_ld_lottery_game($mode = "real") {
	public function goto_fadald_lottery_game($mode = "real") {
		$this->load->model(array('external_system', 'game_provider_auth'));

		$game_platform_id = FADA_LD_LOTTERY_API;

		$player_name = $this->authentication->getUsername();
		$player_id = $this->authentication->getPlayerId();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('FADA ld lottery game');
            return;
        }

		$is_mobile = $this->utils->is_mobile();

		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$player = $api->isPlayerExist($player_name);

		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		$blocked = $api->isBlocked($player_name);
		if (!$blocked) {

			$extra = array();
			$extra['language'] = $this->language_function->getCurrentLanguage();
			$extra['is_mobile'] = $is_mobile;
			$extra['game_mode'] = $mode;
			$extra['fixed_language'] = $api->getSystemInfo('fixed_language', false);

			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);


			$rlt = $api->queryForwardGame($player_name, $extra);

			$this->CI->utils->debug_log("goto FADA LOTTERY", $rlt['url']);
			$platformName = $this->external_system->getNameById($game_platform_id);

			$data = array('url' => $rlt['url'], 'platformName' => $platformName,);
            $this->load->view('iframe/game_iframe', $data);

		}  else {
			die(lang('goto_game.blocked'));
		}

	}

	/**
	 * overview : go to Yoplay game
	 * mode: real or fun
	 */
	public function goto_t1yoplaygame($gamecode = null) {
		$is_mobile = $this->utils->is_mobile();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'operatorglobalsettings'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = T1YOPLAY_API;

		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('t1yoplay');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

		# CHECK PLAYER IF EXIST

		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked['blocked']) {
			die(lang('goto_game.blocked'));
		} else {

			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$extra['game_code'] = $gamecode;
			$extra['language'] = $this->language_function->getCurrentLanguage();
			$extra['mode'] = 'real';
			$extra['platform'] = $is_mobile?'mobile':'pc';

			$data = $api->queryForwardGame($player_name, $extra);

			if (isset($data['success']) && $data['success']) {
				if($is_mobile){
					redirect($data['url']);
				}else{
					$this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName));
				}
			} else {
				die(lang('goto_game.error'));
			}
			return;
		}
	}

	public function goto_ebet_opus() {
        $this->load->model(array('external_system', 'game_provider_auth'));

        $game_platform_id = EBET_OPUS_API;
        $is_mobile = $this->utils->is_mobile();

		$player_id = $this->authentication->getPlayerId();
        $player_name = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('EBET_OPUS');
            return;
        }

        $api = $this->utils->loadExternalSystemLibObject($game_platform_id);
        $extra = array();

        $extra['language']  = $this->language_function->getCurrentLanguage();
        $extra['is_mobile']    = $is_mobile;

        $this->CI->utils->debug_log("Ebet Opus Extrainfo =================================================>", $extra);

        # CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

        $rlt = $api->queryForwardGame($player_name, $extra);
        if (!empty($rlt['success']) && !empty($rlt['url'])) {
            $result = $this->_transferAllWallet($player_id, $player_name, $game_platform_id);
            $this->CI->utils->debug_log("Ebet Opus _transferAllWallet =================================================>", $result);
        }

        $this->CI->utils->debug_log("goto Ebet Opus gaming", $rlt['url']);

        $platformName = $this->external_system->getNameById($game_platform_id);
        $data = array(
            'url'           => $rlt['url'],
            'platformName'  => $platformName,
        );

        if (!empty($rlt['success']) && !empty($rlt['url'])) {
			if($is_mobile){
				redirect($data['url']);
			}else{
				$this->load->view('iframe/game_iframe', $data);
			}
		} else {
			die(lang('goto_game.error'));
		}
    }

    public function goto_pinnaclegame($game_type = 'sports', $game_platform_id=null) {
		#check if moile
		$is_mobile = $this->utils->is_mobile();
		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = is_null($game_platform_id) ? PINNACLE_API : $game_platform_id;
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('pinnacle');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if($player['exists'] !== null){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);

		$platformName = $this->external_system->getNameById($game_platform_id);

		$currentLang = $this->language_function->getCurrentLanguage();

		switch ($currentLang) {
			case 1:
				$extra['language'] ='en';
				break;
			case 2:
				$extra['language'] ='zh-cn';
				break;
			case 3:
				$extra['language'] ='id';
				break;
			case 4:
				$extra['language'] ='vi';
				break;
			case 5:
				$extra['language'] ='ko';
				break;
			case 6:
				$extra['language'] = 'th';
				break;
			default:
				$extra['language'] ='en';
				break;
		}

		$extra['game_type'] = $game_type;

		if(!$this->authentication->isLoggedIn()){
			$extra['game_mode'] = 'trial';

			if(!$api->allow_launch_demo_without_authentication==true){
				$this->goPlayerLogin();
			}
		}

		if (!$blocked) {
			$extra['home_url'] = $this->utils->getSystemUrl('www');
			$rlt = $api->queryForwardGame($playerName, $extra);
			$this->utils->debug_log('goto_pinnaclegame rlt', $rlt, $playerName, $extra);
			if (isset($rlt['success']) && $rlt['success']) {

				if($this->authentication->isLoggedIn()){
					$this->game_provider_auth->setPlayerStatusOnline($game_platform_id, $player_id);
				}

				$this->_transferAllWallet($player_id, $playerName, $game_platform_id);


		        $is_redirect = $api->getSystemInfo('is_redirect', false);

		        if ($is_redirect && isset($rlt['success']) && $rlt['success']) {
		        	if (isset($rlt['url'])) {
						redirect($rlt['url']);
			        } else {
						die(lang('goto_game.error'));
					}
					return;
		        }

				if(!$is_mobile){
					$data = array(
						'url' => $rlt['url'],
						'platformName' => $platformName,
					);
					$this->load->view('iframe/game_iframe', $data);
					return;
				}else{
					redirect($rlt['url']);
				}
			} else {
				die(lang('goto_game.error'));
			}
		}
		else{
			die(lang('goto_game.blocked'));
		}
	}

	public function goto_ebet_dt_game($game_code, $game_mode = 'real', $language = null) {
		$this->load->model(array('external_system', 'game_provider_auth'));

		$game_platform_id = EBET_DT_API;
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('EBET_DT');
            return;
        }
		$success = $this->prepareGotoGame($player_name, $player_id, $game_platform_id);
		$loginInfo = $this->game_provider_auth->getLoginInfoByPlayerId($player_id, $game_platform_id);

		if ($success && $loginInfo) {
			$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			if(empty($language)){
				$language = $this->language_function->getCurrentLanguage();
			}

			$rlt = $api->queryForwardGame($player_name, array(
				'game_code' => $game_code,
				'game_mode' => $game_mode == 'demo' || $game_mode == 'trial' || $game_mode == 'fun',
				'language' => $language,
			));

			if (isset($rlt['success']) && $rlt['success']) {
				$platformName = $this->external_system->getNameById($game_platform_id);
				$iframeName = isset($rlt['iframeName']) ? $rlt['iframeName'] : "";

				$data = array(
					'url' => $rlt['url'],
					'platformName' => $platformName,
					'iframeName' => $iframeName,
				);

				$this->load->view($this->utils->getPlayerCenterTemplate() . '/game_iframe', $data);

				return $data;
			} else {
				die(lang('goto_game.error'));
			}

		} else {
			$this->returnBadRequest();
		}

	}

	public function goto_mg_dashur_game($game_type = null, $game_id=null, $game_mode='real') {

		$this->load->model(array('external_system', 'game_provider_auth'));

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

		$is_mobile = $this->utils->is_mobile();
		$game_platform_id = MG_DASHUR_API;

		$player_name = $this->authentication->getUsername();
		$player_id = $this->authentication->getPlayerId();

		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$extra = array();
		$extra['language'] = $this->language_function->getCurrentLanguage();
		if(!empty($game_type)) {
			$extra['game_type'] = $game_type;
		}
		if(!empty($game_id)) {
			$extra['item_id'] = $game_id;
		}
		$extra['game_mode'] = $game_mode;
		$extra['is_mobile'] = $is_mobile;
		# NOT LOGIN, GOTO DEMO GAME
		if (!$this->authentication->isLoggedIn()) {
			if (in_array($game_mode, ['trial','fun'])) {
				$rlt = $api->queryForwardGame(null, $extra);
				$platformName = $this->external_system->getNameById($game_platform_id);
				$data = array('url' => $rlt['url'], 'platformName' => $platformName,);
				if($is_mobile){
					redirect($rlt['url']);
				}
				$this->load->view('iframe/game_iframe', $data);
				return;
			}else{
				$this->goPlayerLogin();
			}
		}

		#BLOCK LOGIN GAME BY USER STATUS
		if($this->CI->utils->blockLoginGame($player_id)){
			$this->goBlock();
			return;
		}
		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
		if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
			$this->goto_maintenance('HG');
			return;
		}

		$player = $api->isPlayerExist($player_name);

		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		$blocked = $api->isBlocked($player_name);
		if (!$blocked) {

			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$rlt = $api->queryForwardGame($player_name, $extra);

			$this->CI->utils->debug_log("goto mg dashur gaming", $rlt['url']);
			$platformName = $this->external_system->getNameById($game_platform_id);

			$data = array('url' => $rlt['url'], 'platformName' => $platformName,);
			if($is_mobile){
				redirect($rlt['url']);
			}
			$this->load->view('iframe/game_iframe', $data);

		}  else {
			die(lang('goto_game.blocked'));
		}
	}

	public function goto_tcg_lottery($redirection='iframe') {

		$this->load->model(array('external_system', 'game_provider_auth'));

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

		$game_platform_id = TCG_API;

		$player_name = $this->authentication->getUsername();
		$player_id = $this->authentication->getPlayerId();


		#BLOCK LOGIN GAME BY USER STATUS
		if($this->CI->utils->blockLoginGame($player_id)){
			$this->goBlock();
			return;
		}
		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
		if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
			$this->goto_maintenance('TCG');
			return;
		}

		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		#$player = $api->isPlayerExist($player_name);

		//if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
		//	if(!is_null($player['exists'])){
		//		$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
		//	}
		//}

		# exclude auto create from getOrCreateLoginInfoByPlayerId (should base on api rules
		$gameUsername = $this->CI->game_provider_auth->getGameUsernameByPlayerId($player_id, $game_platform_id);
		if(!$gameUsername) {
			$password = $api->getPasswordFromPlayer($player_name);
			$api->createPlayer($player_name, $player_id, $password,null, array());
		}

		$is_mobile = $this->utils->is_mobile();

		$blocked = $api->isBlocked($player_name);

		if (!$blocked) {

			$extra = array();
			$extra['language'] = $this->language_function->getCurrentLanguage();

			$platform = $is_mobile ? 'mobile' : 'web';

			$extra['platform'] = $platform;

			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$rlt = $api->queryForwardGame($player_name, $extra);

			$this->CI->utils->debug_log("goto tcg lottery", $rlt['url']);
			$platformName = $this->external_system->getNameById($game_platform_id);

			$data = array('url' => $rlt['url'], 'platformName' => $platformName);

			if($is_mobile){
				redirect($data['url']);
			}else{
				if($redirection == 'iframe'){
					$this->load->view('iframe/game_iframe', $data);
				}else{
					redirect($data['url']);
				}
			}
		}  else {
			die(lang('goto_game.blocked'));
		}

	}

    public function goBlock(){
        $message['message']=lang('goto_game.blocked');
        if ($this->utils->getConfig('blocked_url')) {
            redirect($this->utils->getSystemUrl('www') . $this->utils->getConfig('blocked_url'));
        }else{
            $this->load->view('iframe/player/blocked',$message);
        }
    }

    public function launch_t1sagaming($game_code, $platformName, $redirection, $reponse_result = null) {
    	$this->CI->utils->debug_log("call launch_t1sagaming", $game_code, $reponse_result);
    	$is_redirect = strtolower($redirection) == 'newtab';

    	if (isset($reponse_result['success']) && $reponse_result['success']) {
    		# for slot game
			if ($game_code) {
				# check if mobile or redirection
				if (!$is_redirect && !$reponse_result['mobile']) {
					$this->load->view('iframe/game_iframe', array('url' => $reponse_result['url'], 'platformName' => $platformName));
					return;
				}

				redirect($reponse_result['url']);
				return;
			} else {
				# for live dealer
				$reponse_result['mobile'] = $this->utils->is_mobile()?"true":"false";
				$reponse_result['is_iframe'] = !$this->utils->is_mobile();;
				$this->load->view('iframe/player/goto_sagaminggame', $reponse_result);
				return;
			}
		} else {
			die(lang('goto_game.error'));
			return;
		}
    }

    public function goto_ls_casino_game($game_mode = 'real', $gameType = "AllGames", $target = "iframe") {
		$this->load->model(array('external_system', 'game_provider_auth'));

		$game_platform_id = LS_CASINO_GAME_API;
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('LS_CASINO');
            return;
        }

        //if not login OGP-9422
        if (!$this->authentication->isLoggedIn()) {
        	// get demo_url from game_api_ls_casino
        	$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
        	$params = array(
				'game_type' => $gameType,
				'game_mode' => $game_mode,
			);

			$rlt = $api->queryForwardGame($player_name, $params);

			$data = array(
					'url' => $rlt['url'],
				);
			if ($target == "iframe") {
				$this->load->view($this->utils->getPlayerCenterTemplate() . '/game_iframe', $data);
			} else {
				if (!empty($rlt['url'])) {
					redirect($rlt['url']);
				}
			}
		}

		$success = $this->prepareGotoGame($player_name, $player_id, $game_platform_id);
		$loginInfo = $this->game_provider_auth->getLoginInfoByPlayerId($player_id, $game_platform_id);

		if ($success && $loginInfo) {
			$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

			$params = array(
				'game_type' => $gameType,
				'game_mode' => $game_mode,
			);

			$rlt = $api->queryForwardGame($player_name, $params);
			if (isset($rlt['success']) && $rlt['success']) {

				$platformName = $this->external_system->getNameById($game_platform_id);
				$iframeName = isset($rlt['iframeName']) ? $rlt['iframeName'] : "";

				$data = array(
					'url' => $rlt['url'],
					'platformName' => $platformName,
					'iframeName' => $iframeName,
				);

				if ($target == "iframe") {
					$this->load->view($this->utils->getPlayerCenterTemplate() . '/game_iframe', $data);
				} else {
					if (!empty($rlt['url'])) {
						redirect($rlt['url']);
					}
				}

				return $data;
			} else {
				die(lang('goto_game.error'));
			}
		} else {
			$this->returnBadRequest();
		}

	}

	public function goto_betsoft_game($game_mode = 'real', $game_id='191',  $target = "iframe") {
		$this->load->model(array('external_system', 'game_provider_auth'));

		$game_platform_id = BETSOFT_API;
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

		#BLOCK LOGIN GAME BY USER STATUS
		if($this->CI->utils->blockLoginGame($player_id)){
			$this->goBlock();
			return;
		}

		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
		if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
			$this->goto_maintenance('BETSOFT GAME');
			return;
		}

		$success = $this->prepareGotoGame($player_name, $player_id, $game_platform_id);
		$loginInfo = $this->game_provider_auth->getLoginInfoByPlayerId($player_id, $game_platform_id);

		if ($success && $loginInfo) {
			$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$this->_refreshBalanceFromApiAndUpdateSubWallet($game_platform_id, $api, $player_name, $player_id);

			$params = array(
					'game_id' => $game_id,
					'mode' => $game_mode,
			);

			$rlt = $api->queryForwardGame($player_name, $params);
			if (isset($rlt['success']) && $rlt['success']) {
				$platformName = $this->external_system->getNameById($game_platform_id);

				$data = array(
						'url' => $rlt['url'],
						'platformName' => $platformName
				);

				if ($target == "iframe") {
					$this->load->view($this->utils->getPlayerCenterTemplate() . '/game_iframe', $data);
				} else {
					if (!empty($rlt['url'])) {
						redirect($rlt['url']);
					}
				}
			} else {
				die(lang('goto_game.error'));
			}
		} else {
			$this->returnBadRequest();
		}

	}


	public function goto_tianhao($game_code = null) {
		$is_mobile = $this->utils->is_mobile();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = TIANHAO_API;
  		//$this->checkGameIfLaunchable($game_platform_id,$gameid,$machid,null,true);

		$language = $this->language_function->getCurrentLanguage();
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('TIANHAO_API');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

		#BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists']) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);
		$extra['game_code'] = $game_code;

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$data = $api->queryForwardGame($player_name, $extra);

			if ($data['success']) {
				if($is_mobile){
					redirect($data['url']);
				}else{
					$this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName));
				}
			} else {
				die(lang('goto_game.error'));
			}
			return;
		}
	}

	public function goto_lotus() {
		$is_mobile = $this->utils->is_mobile();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = LOTUS_API;
  		//$this->checkGameIfLaunchable($game_platform_id,$gameid,$machid,null,true);

		$language = $this->language_function->getCurrentLanguage();
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('LOTUS_API');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

		#BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists']) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
    		$extra['language'] = $this->language_function->getCurrentLanguage();
			$data = $api->queryForwardGame($player_name,$extra);
			redirect($data['url']);
			if ($data['success']) {
				if($is_mobile){
					redirect($data['url']);
				}else{
					$this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName));
				}
			} else {
				die(lang('goto_game.error'));
			}
			return;
		}
	}

	public function goto_aviasport() {
		$is_mobile = $this->utils->is_mobile();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = AVIA_ESPORT_API;
  		//$this->checkGameIfLaunchable($game_platform_id,$gameid,$machid,null,true);

		$language = $this->language_function->getCurrentLanguage();
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('AVIA_ESPORT_API');
            return;
        }

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

		if(!$player_id){
			$required_login_on_launching_trial = $api->getSystemInfo('required_login_on_launching_trial', false);
			if($required_login_on_launching_trial && !$this->authentication->isLoggedIn()){
				$this->goPlayerLogin();
			}

			$extra['language'] = $this->language_function->getCurrentLanguage();
			$extra['game_mode'] = 'trial';
			$data = $api->queryForwardGame(null,$extra);
			if ($data['success']) {
				redirect($data['url']);
			} else {
				die(lang('goto_game.error'));
			}
			return;
		}

		#BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists']) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$extra =[];
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
    		$extra['language'] = $this->language_function->getCurrentLanguage();
    		$extra['game_mode'] = 'real';
			$data = $api->queryForwardGame($player_name,$extra);
			if ($data['success']) {
				redirect($data['url']);
			} else {
				die(lang('goto_game.error'));
			}
			return;
		}
	}

	public function goto_oggame_v2() {
		$is_mobile = $this->utils->is_mobile();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = OG_V2_API;

		$language = $this->language_function->getCurrentLanguage();
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('OG_V2_API');
            return;
        }

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

		#BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists']) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$extra =[];
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
    		$extra['language'] = $language;
    		$extra['is_mobile'] = $is_mobile;
			$data = $api->queryForwardGame($player_name,$extra);
			if ($data['success']) {
				if($is_mobile){
					redirect($data['url']);
				}else{
					$this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName));
				}
			} else {
				die(lang('goto_game.error'));
			}
			return;
		}
	}

    /**
     * [checkGameIfLaunchable description]
     * @param  [int] $game_platform_id          [defined API Id]
     * @param  [string] $game_code              [game launch code]
     * @param  [string] $game_id                [other attributes of the game]
     * @param  [string] $sub_game_provider      [sub game provider]
     * @param  [boolean] $check_attributes_only [check attributes only]
     * @return [boolean/array]                  [return error message or boolean]
     */
    private function checkGameIfLaunchable($game_platform_id, $game_code, $game_id = null, $sub_game_provider = null,$check_attributes_only = null){
        if ($this->utils->isEnabledFeature('dont_allow_disabled_game_to_be_launched')) {
            $this->load->model('game_description_model');

            if (empty($game_platform_id))
                return show_error(lang('Game platform ID is required'), 400);

            if (empty($game_code) && empty($game_id))
                return true;

            $launchable = $this->game_description_model->checkGameIfLaunchable($game_platform_id, $game_code, $game_id, $sub_game_provider,$check_attributes_only);

            if (is_array($launchable)) {
                return show_error(lang($launchable['error']), 400);
            }elseif(empty($launchable)){
                $this->goto_maintenance('MG');
            }
        }

        return true;
    }

    public function goto_glgame($game_mode = 'real') {
    	// Initial setup
    	$this->load->model([ 'game_provider_auth', 'external_system' ]);

    	$game_platform_id = GL_API;
    	$language = $this->language_function->getCurrentLanguage();
    	$is_mobile = $this->utils->is_mobile();

    	// Load API
    	$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

		// Check platform maintenance
		if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('GLOBAL LOTTERY');
            return;
        }

        // Check player login
        if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

    	// For demo mode
    	if ($game_mode == 'demo' || $game_mode == false) {
    		$fg_query = $api->queryForwardGame(null);
    		$this->utils->debug_log(__METHOD__, 'demo-mode', [ 'fg_query' => $fg_query ]);
    		if (isset($fg_query['url'])) {
	    		redirect($fg_query['url']);
	    	}

    		return;
    	}

		// Acquire player_id/name
        $player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

		// Check site-wide player blocked status
		if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

        // Check existence of game account and create in case not
		$player = $api->isPlayerExist($player_name);
		if (isset($player['exists']) && !$player['exists']) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		// Check if game platform setting is blocked
		$this->checkBlockGamePlatformSetting($game_platform_id);

		// Check game-only player blocked status
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		}

		$this->_transferAllWallet($player_id, $player_name, $game_platform_id);

		// invoke api::queryForwardGame()
		$fg_query = $api->queryForwardGame($player_name);

		$this->CI->utils->debug_log(__METHOD__, 'queryForwardGame return', [ 'fg_query' => $fg_query ]);

		if (!$fg_query['success']) {
			die(lang('goto_game.error'));
		}

		if($api->getSystemInfo('enable_speed_test') && !empty($api->getSystemInfo('host_data'))){
			$this->load->view('iframe/player/goto_glgame', array('url' => $fg_query['url'], 'platformName' => $platformName, 'host_data' => $fg_query['host_data']));
			return;
		}
		redirect($fg_query['url']);

		return;
    }

    public function goto_mtech_bbin($game_type = null, $game_mode = 'real', $target = "newtab") {
		$this->load->model(array('external_system', 'game_provider_auth'));

		$game_platform_id = MTECH_BBIN_API;
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();
		$is_mobile = $this->utils->is_mobile();

		#BLOCK LOGIN GAME BY USER STATUS
		if($this->CI->utils->blockLoginGame($player_id)){
			$this->goBlock();
			return;
		}

		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
		if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
			$this->goto_maintenance('BBIN MTECH GAME');
			return;
		}

		$success = $this->prepareGotoGame($player_name, $player_id, $game_platform_id);
		$loginInfo = $this->game_provider_auth->getLoginInfoByPlayerId($player_id, $game_platform_id);

		if ($success && $loginInfo) {
			$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$this->_refreshBalanceFromApiAndUpdateSubWallet($game_platform_id, $api, $player_name, $player_id);

			$params = array(
				"game_type" => $game_type,
	    		"is_fun_game" => strtolower($game_mode) != "real",
	    		"is_mobile" => $is_mobile,
	    		"language" => $this->language_function->getCurrentLanguage(),
			);

			$rlt = $api->queryForwardGame($player_name, $params);
			$this->CI->utils->debug_log("mtech bbin URL >----------------------------> ", $rlt);

			if (isset($rlt['success']) && $rlt['success']) {
				$platformName = $this->external_system->getNameById($game_platform_id);

				$data = array(
						'url' => $rlt['url'],
						'platformName' => $platformName
				);

				if ($target == "iframe" && !$is_mobile) {
					$this->load->view($this->utils->getPlayerCenterTemplate() . '/game_iframe', $data);
				} else {
					if (!empty($rlt['url'])) {
						redirect($rlt['url']);
					}
				}
			} else {
				die(lang('goto_game.error'));
			}
		} else {
			$this->returnBadRequest();
		}

	}


    /**
     * Launch specific mtech_bbin_api games without going through lobby
     * currently written specifically for live and xbb live games
     */
    public function goto_mtech_bbin_game($game_type  = null, $game_code =null, $game = null, $game_mode = 'real', $redirection = "newtab") {
		$this->load->model(array('external_system', 'game_provider_auth'));

		$game_platform_id = MTECH_BBIN_API;
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();
		$is_mobile = $this->utils->is_mobile();

		#BLOCK LOGIN GAME BY USER STATUS
		if($this->CI->utils->blockLoginGame($player_id)){
			$this->goBlock();
			return;
		}

		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
		if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
			$this->goto_maintenance('BBIN MTECH GAME');
			return;
		}

		$success = $this->prepareGotoGame($player_name, $player_id, $game_platform_id);
		$loginInfo = $this->game_provider_auth->getLoginInfoByPlayerId($player_id, $game_platform_id);

		if ($success && $loginInfo) {
			$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$this->_refreshBalanceFromApiAndUpdateSubWallet($game_platform_id, $api, $player_name, $player_id);

			$params = array(
				"game_type" => $game_type,
	    		"is_fun_game" => strtolower($game_mode) != "real",
	    		"is_mobile" => $is_mobile,
	    		"language" => $this->language_function->getCurrentLanguage(),
                "game_code" => $game_code,
                "game" => $game
			);

			$rlt = $api->queryForwardGame($player_name, $params);
			$this->CI->utils->debug_log("mtech bbin URL >----------------------------> ", $rlt);

			if (isset($rlt['success']) && $rlt['success']) {
				$platformName = $this->external_system->getNameById($game_platform_id);

				$data = array(
						'url' => $rlt['url'],
						'platformName' => $platformName
				);

				if ($redirection == "iframe" && !$is_mobile) {
					$this->load->view($this->utils->getPlayerCenterTemplate() . '/game_iframe', $data);
				} else {
					if (!empty($rlt['url'])) {
						redirect($rlt['url']);
					} else {
                        die(lang('goto_game.error'));
                    }
				}
			} else {
				die(lang('goto_game.error'));
			}
		} else {
			$this->returnBadRequest();
		}

	}

	/**
	 * overview : go to ebet
	 *
	 * @param $gameMode
	 * @param $gameCode
	 */
	public function goto_ebetgame_usd($gameMode = 'real', $is_mobile = null) {
		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'static_site'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = EBET_USD_API;

		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
		if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
			$this->goto_maintenance('EBET');
			return;
		}

		# LOAD THE CONFIG FOR THE GAME PLATFORM API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

		if ($gameMode == 'trial') {
			$rlt = $api->queryForwardGame();
		} else {

			$player_id = $this->authentication->getPlayerId();
			$player_name = $this->authentication->getUsername();

			#BLOCK LOGIN GAME BY USER STATUS
			if($this->CI->utils->blockLoginGame($player_id)){
				$this->goBlock();
				return;
			}

			if (empty($player_name)) {
				$this->goPlayerLogin();
				return;
			}

			$success = $this->prepareGotoGame($player_name, $player_id, $game_platform_id);

			if ($success) {
				$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
				$rlt = $api->queryForwardGame($player_name, ['is_mobile' => $is_mobile]);
			}

		}

		if (isset($rlt['success']) && $rlt['success']) {
			$this->utils->debug_log('goto ebet game=>', $rlt['url']);
			if($is_mobile){
				redirect($rlt['url']);
			}else{
				$this->load->view('iframe/game_iframe', array('url' => $rlt['url'], 'platformName' => $platformName));
			}
		} else {
			return show_error(lang('goto_game.error'), 400);
		}

	}

	public function goto_pgsoft_tournament($tournament_code, $allow_reRegister = "true") {
		$is_mobile = $this->utils->is_mobile();

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = PGSOFT_API;
		$language = $this->language_function->getCurrentLanguage();
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$platformName = $this->external_system->getNameById($game_platform_id);

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('PGSOFT');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();
		$blocked = false;

		#BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		//if not login
		if (!$this->authentication->isLoggedIn()) {
			$this->goPlayerLogin();
		}

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists']) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);


		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
        	$protocol = $this->utils->ishttps()?"https://":"http://";
			$extra['tournament_id'] = $tournament_code;
			$extra['allow_re-register'] = $allow_reRegister;
			$extra['language'] = $language;
			$extra['is_mobile'] = $is_mobile;
			$extra['game_mode'] = "tournament";
			$extra['game_code'] = "lobby";
			$extra['home_url'] = $protocol.$this->utils->getHttpHost();

			$data = $api->queryForwardGame($player_name, $extra);

			if ($data['success']) {
				if($is_mobile){
					redirect($data['url']);
				}else{
					$this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName));
				}
			} else {
				die(lang('goto_game.error'));
			}
			return;
		}
	}

	public function goto_mtech_og($game_mode = "real",$target = "iframe") {
		$this->load->model(array('external_system', 'game_provider_auth'));
		$game_platform_id = MTECH_OG_API;
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();
		$is_mobile = $this->utils->is_mobile();

		#BLOCK LOGIN GAME BY USER STATUS
		if($this->CI->utils->blockLoginGame($player_id)){
			$this->goBlock();
			return;
		}

		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
		if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
			$this->goto_maintenance('OG MTECH GAME');
			return;
		}

		$success = $this->prepareGotoGame($player_name, $player_id, $game_platform_id);
		$loginInfo = $this->game_provider_auth->getLoginInfoByPlayerId($player_id, $game_platform_id);

		if ($success && $loginInfo) {
			$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$this->_refreshBalanceFromApiAndUpdateSubWallet($game_platform_id, $api, $player_name, $player_id);

			$params = array(
	    		"is_fun_game" => strtolower($game_mode) != "real",
	    		"is_mobile" => $is_mobile,
	    		"language" => $this->language_function->getCurrentLanguage(),
			);

			$rlt = $api->queryForwardGame($player_name, $params);
			$this->CI->utils->debug_log("mtech OG URL >----------------------------> ", $rlt);

			if (isset($rlt['success']) && $rlt['success']) {
				$platformName = $this->external_system->getNameById($game_platform_id);

				$data = array(
						'url' => $rlt['url'],
						'platformName' => $platformName
				);

				if ($target == "iframe" && !$is_mobile) {
					$this->load->view($this->utils->getPlayerCenterTemplate() . '/game_iframe', $data);
				} else {
					if (!empty($rlt['url'])) {
						redirect($rlt['url']);
					}
				}
			} else {
				die(lang('goto_game.error'));
			}
		} else {
			$this->returnBadRequest();
		}
	}

	public function goto_mtech_hb($game_code, $game_mode = "real",$target = "iframe") {
		$this->load->model(array('external_system', 'game_provider_auth'));
		$game_platform_id = MTECH_HB_API;
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();
		$is_mobile = $this->utils->is_mobile();

		#BLOCK LOGIN GAME BY USER STATUS
		if($this->CI->utils->blockLoginGame($player_id)){
			$this->goBlock();
			return;
		}

		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
		if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
			$this->goto_maintenance('HB MTECH GAME');
			return;
		}

		$success = $this->prepareGotoGame($player_name, $player_id, $game_platform_id);
		$loginInfo = $this->game_provider_auth->getLoginInfoByPlayerId($player_id, $game_platform_id);

		if ($success && $loginInfo) {
			$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$this->_refreshBalanceFromApiAndUpdateSubWallet($game_platform_id, $api, $player_name, $player_id);

			$params = array(
	    		"game_code" => $game_code,
	    		"is_fun_game" => strtolower($game_mode) != "real",
	    		"is_mobile" => $is_mobile,
	    		"language" => $this->language_function->getCurrentLanguage(),
			);

			$rlt = $api->queryForwardGame($player_name, $params);
			$this->CI->utils->debug_log("MTECH HB URL >----------------------------> ", $rlt);

			if (isset($rlt['success']) && $rlt['success']) {
				$platformName = $this->external_system->getNameById($game_platform_id);

				$data = array(
						'url' => $rlt['url'],
						'platformName' => $platformName
				);

				if ($target == "iframe" && !$is_mobile) {
					$this->load->view($this->utils->getPlayerCenterTemplate() . '/game_iframe', $data);
				} else {
					if (!empty($rlt['url'])) {
						redirect($rlt['url']);
					}
				}
			} else {
				die(lang('goto_game.error'));
			}
		} else {
			$this->returnBadRequest();
		}
	}

	public function goto_hgseamless_game($game_platform_id, $game_type, $game_mode, $language = null)
	{
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		/* VALID GAME TYPES
			'roullete'
	        'blackjack'
	        'baccarat'
	        'sicbo'
	        'dragontiger'
		*/

		switch ($game_type) {
			case 'roullete':
				$game_type = $api::TABLEGAMES_GAMETYPE_IDS[$game_type];
				break;
			case 'blackjack':
				$game_type = $api::TABLEGAMES_GAMETYPE_IDS[$game_type];
				break;
			case 'baccarat':
				$game_type = $api::TABLEGAMES_GAMETYPE_IDS[$game_type];
				break;
			case 'sicbo':
				$game_type = $api::TABLEGAMES_GAMETYPE_IDS[$game_type];
				break;
			case 'dragontiger':
				$game_type = $api::TABLEGAMES_GAMETYPE_IDS[$game_type];
				break;
			default:
				$game_type = $api::TABLEGAMES_GAMETYPE_IDS['baccarat'];
				break;
		}

		$this->goto_common_game($game_platform_id, null, $game_mode, $game_type, $language);
    }

    public function auth_betby(){
    	$player_name = $this->authentication->getUsername();
    	$language = $this->language_function->getCurrentLanguage();
    	# LOAD GAME API
        $api = $this->utils->loadExternalSystemLibObject(BETBY_SEAMLESS_GAME_API);
        if(empty($api)){
        	return show_error('Invalid game api', 400);
        }

        // $isPlayerNotLogin = $this->authentication->isLoggedIn();
        // $player_name = null;
        if(empty($player_name)){
        	$extra['game_mode'] = "notlogin";
            $extra['language'] = $language;
            $data = $api->queryForwardGame(null,$extra);
            return $this->returnJsonpResult($data);
        }

        # GET LOGGED-IN PLAYER
        // $player_id = $this->authentication->getPlayerId();
        // $player_name = $this->authentication->getUsername();
        $extra['language'] = $language;
        $data = $api->queryForwardGame($player_name, $extra);
        return $this->returnJsonpResult($data);
    }

    public function launch_betby(){
    	$this->load->view('iframe/player/view_betby');
		return;
    }

    public function goto_common_game($game_platform_id, $game_code = null, $game_mode = 'real', $game_type = null, $language = null, $provider_id = null)
    {
    	if($game_platform_id == PT_V2_API){
    		return $this->goto_ptv2game('default', $game_code, $game_mode);
    	}

        $get_params = $this->input->get() ?: [];

		$this->goLaunchGameWithPlayerToken($get_params);

        # game API that have demo game and not required authenticated player
        /*$gameApiHavingDemoGame = [
          GOLDEN_RACE_GAMING_API,
		  AE_SLOTS_GAMING_API,
		  YGGDRASIL_API,
          PARIPLAY_SEAMLESS_API,
          DIGITAIN_SEAMLESS_API,
		  PINNACLE_SEAMLESS_GAME_API,
          T1_PINNACLE_SEAMLESS_GAME_API,
		  BTI_SEAMLESS_GAME_API,
		  BETBY_SEAMLESS_GAME_API
        ];*/

        $is_mobile = $this->utils->is_mobile();

		if($game_code=='_null'){
			$game_code = null;
		}

        # LOAD MODEL AND LIBRARIES
        $this->load->model(array('game_provider_auth', 'external_system', 'game_description_model'));

        if(is_null($language)){
           $language = $this->language_function->getCurrentLanguage();
        }

        # LOAD GAME API
        $api = $this->utils->loadExternalSystemLibObject($game_platform_id);
        if(empty($api)){
        	return show_error('Invalid game api', 400);
        }
        $platformName = $this->game_description_model->getGameNameByCurrentLang($game_code, $game_platform_id);
        $platformapi = $this->external_system->getSystemName($game_platform_id);
        $favicon_brand = $api->getSystemInfo('favicon', false);

		# game API that under maintenance to allow for game launch
		$allowed_maintenance_game_api_to_game_launch = $this->utils->getConfig('allowed_maintenance_game_api_to_game_launch');

		if(! is_array($allowed_maintenance_game_api_to_game_launch)){
			$allowed_maintenance_game_api_to_game_launch = [];
		}

		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE, if game API is in config allowed_maintenance_game_api_to_game_launch, allow it, even it's maintenance
		if(!in_array($game_platform_id,$allowed_maintenance_game_api_to_game_launch)){
			if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
				$this->goto_maintenance($game_platform_id);
				return;
			}
		}

		$this->checkGameFlag($api, $game_platform_id, $game_code);

		# CHECK IF GAME STATUS IF ACTIVE **game_description ['status'] == 0
		$game_status = $this->game_description_model->getActiveGameStatus($game_platform_id, $game_code);

			if(isset($game_status) && $game_status == 0){
				return die(lang('goto_game.error'));
		}

        # CHECK IF GAMEPLATFORM SETTING IS BLOCKED
        $this->checkBlockGamePlatformSetting($game_platform_id);
        $extra =[];
        if($game_platform_id == BBIN_API){
        	$extra['new_bbin_url'] = true;
        	$extra['game_url_no'] = $game_type;
        }

        $isPlayerLogin = $this->authentication->isLoggedIn();
		$blocked = false;
        if (!$isPlayerLogin) {
            $extra['game_code'] = $game_code;
            $extra['game_mode'] = $game_mode;
            $extra['game_type'] = $game_type;
            $extra['language'] = $language;
            $extra['is_mobile'] = $is_mobile;
			$extra['provider_id'] = $provider_id;

			# game API that have demo game and not required authenticated player
			if($api->allow_launch_demo_without_authentication==true){

				$data = $api->queryForwardGame(null,$extra);

				if($game_platform_id == DIGITAIN_SEAMLESS_API){
                    $data['title'] = $platformName;
                    $this->load->view('iframe/player/goto_digitain', $data);
                    return;
                }
    
                if($game_platform_id == BETBY_SEAMLESS_GAME_API){
                    $this->load->view('iframe/player/goto_betby', $data);
                    return;
                }

				if ($game_platform_id == IBC_ONEBOOK_SEAMLESS_API) {
					return redirect($data['url']);
				}

                if ($game_platform_id == BETGAMES_SEAMLESS_GAME_API) {
                    return $this->load->view('iframe/player/goto_betgames_seamless_game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true, 'game_platform_name' => 'BETGAMES_SEAMLESS_GAME_API', 'params' => $data['params']));
                }

                if ($game_platform_id == TWAIN_SEAMLESS_GAME_API) {
                    return $this->load->view('iframe/player/goto_twain_seamless_game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true, 'game_platform_name' => 'TWAIN_SEAMLESS_GAME_API', 'params' => $data['params']));
                }

                if ($game_platform_id == FBSPORTS_SEAMLESS_GAME_API) {
                	if ($is_mobile) {
                    	redirect($data['url']);
	                } else {
	                    return $this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true));
	                }
                }

				if(isset($data['url'])&&!empty($data['url'])){
					return redirect($data['url']);
				}
            }

			if(isset($extra['extra']['disable_home_link']) && $extra['extra']['disable_home_link']) {
				die(lang('goto_game.blocked'));
			}

			$this->goPlayerLogin();

        }else{
            # GET LOGGED-IN PLAYER
            $player_id = $this->authentication->getPlayerId();
            $player_name = $this->authentication->getUsername();

            $this->CI->load->model(array('player_model', 'operatorglobalsettings'));

            ##### START checking if account information requirements before launching game

            # OGP-27882 Allowed to launch complete account information
            $redirectGameLaunch = false;
            $popupMessage = '';
            $allowedToLaunchCompleteContact = $this->utils->getConfig('game_launch_allow_only_complete_contact');
            if($allowedToLaunchCompleteContact){
                $isAccountInfoComplete = $this->player_model->getPlayerAccountInfoStatus($player_id);
                if(isset($isAccountInfoComplete['missing_fields'])&&!empty($isAccountInfoComplete['missing_fields'])){                    
                    $popupMessage = lang('Please complete account information.');
                    $redirectGameLaunch = true;
                    $this->utils->error_log('game launch account information incomplete', 'isAccountInfoComplete', $isAccountInfoComplete);
                }
            }

            # OGP-27871 Allowed to launch verified contact number
            $allowedToLaunchVerfiedOnly = $this->utils->getConfig('game_launch_allow_only_verified_contact');
            if($allowedToLaunchVerfiedOnly){
                $isVerifiedPhone = $this->player_model->isVerifiedPhone($player_id);
                if(!$isVerifiedPhone){
                    $popupMessage = lang('Please provide and verify contact number.');
                    $redirectGameLaunch = true;                
                    $this->utils->error_log('game launch account information incomplete', 'isVerifiedPhone', $isVerifiedPhone);
                }
            }

            $allowedToLaunchExcepNoBalance = $this->utils->getConfig('game_launch_allow_only_complete_contact_except_no_balance');
            if($allowedToLaunchExcepNoBalance&&$redirectGameLaunch){
                //get main wallet balance
                $total = $this->wallet_model->getMainWalletBalance($player_id);
                $this->utils->debug_log('allow launch 0 balance', 'total', $total, 'allowedToLaunchExcepNoBalance', $allowedToLaunchExcepNoBalance,
                'redirectGameLaunch', $redirectGameLaunch);
                if($total<=0){
                    $redirectGameLaunch=false;
                }
            }

            if($redirectGameLaunch){
                $this->session->set_flashdata('message', $popupMessage);
                $data = [];
                $data['redirectNotVerifiedContactUrl'] = $this->utils->getPlayerProfileSetupUrlFromGameLaunch();
                if($is_mobile){
                    $data['redirectNotVerifiedContactUrl'] = $this->utils->getPlayerProfileUrl().'?is_game_launch=true';
                }
                $this->load->view('iframe/player/goto_iframe_redirect', $data);
                return;
            }
            ##### END checking if account information requirements before launching game 

            $allowed_games = $this->utils->getConfig('player_tag_allowed_games');
            if(!empty($allowed_games)) {
                $player_tags = array_column($this->CI->player_model->getPlayerTags($player_id), 'tagId');
                foreach($player_tags as $tag) {

                    if(
                        !empty($allowed_games[$tag]) &&
                        !(!empty($allowed_games[$tag][$game_platform_id]) && in_array($game_code, $allowed_games[$tag][$game_platform_id]))
                    ) {
                        $this->goBlock();
                        return;
                    }
                }
            }

			$player_tags = array_column($this->CI->player_model->getPlayerTags($player_id), 'tagId');
			$no_game_allowed_tag = json_decode($this->operatorglobalsettings->getSettingJson('no_game_allowed_tag'), true);
			if (!empty($player_tags) && !empty($no_game_allowed_tag)) {
				foreach ($player_tags as $tag) {
					if (in_array($tag, $no_game_allowed_tag)) {
						$this->goBlock();
                        return;
					}
				}
			}

            #BLOCK LOGIN GAME BY USER STATUS
            if($this->CI->utils->blockLoginGame($player_id)){
                $this->goBlock();
                return;
            }

            $isPlayerNotLogin = $this->authentication->isLoggedIn();

            //check if not login
            if (!$isPlayerNotLogin) {
               $this->goPlayerLogin();
            }

            # CHECK PLAYER IF EXIST
            $player = $api->isPlayerExist($player_name);

            # IF NOT CREATE PLAYER
            if (isset($player['exists']) && !$player['exists']) {
                if(!is_null($player['exists'])){
                   $this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
                }
            }

            # CHECK IF LOGGED-IN PLAYER IS BLOCKED
            $blocked = $api->isBlocked($player_name);
            //exist main account
            $sub_game_provider_to_main_game_provider=$this->utils->getConfig('sub_game_provider_to_main_game_provider');
            if(!empty($sub_game_provider_to_main_game_provider) &&
            	array_key_exists($game_platform_id, $sub_game_provider_to_main_game_provider)){
            	//create main game account
            	$mainApiId=$sub_game_provider_to_main_game_provider[$game_platform_id];
            	$mainApi=$this->utils->loadExternalSystemLibObject($mainApiId);
            	if(!empty($mainApi)){
	            	$mainExistResult=$this->checkExistOnApiAndUpdateRegisterFlag($mainApi, $player_id, $player_name);
            		$this->utils->debug_log('checkExistOnApiAndUpdateRegisterFlag for main api', $mainExistResult);
            	}else{
            		$this->utils->error_log('load main class failed', $mainApiId, $game_platform_id);
            	}
            }
        }

		if (!isset($player_id) || empty($player_id)) {
			die(lang('goto_game.blocked'));
		}

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
            // $extra =[];
            $show_low_balance_prompt = false;
            $low_balance_prompt = $this->utils->getConfig('show_low_balance_prompt_on_game_launch');
            if (!empty($low_balance_prompt) && array_key_exists($game_platform_id, $low_balance_prompt)) {
                $show_low_balance_prompt = false;
                if (($is_mobile == true && $this->utils->getConfig('show_low_balance_prompt_on_game_launch_on_mobile') == true) || ($is_mobile == false && $this->utils->getConfig('show_low_balance_prompt_on_game_launch_on_desktop') == true)) {
                    $balance = $api->queryPlayerBalance($player_name);
                    if($balance['success']) {
                        $balance = $balance['balance'];
                    }
                    else {
                        $balance = 0;
                    }
                    $minimum_balance_to_show_low_balance_prompt = $low_balance_prompt[$game_platform_id];
                    if($balance < $minimum_balance_to_show_low_balance_prompt) {
                        $show_low_balance_prompt = true;
                    }
                }
            }

			$_transferResult = $this->_transferAllWallet($player_id, $player_name, $game_platform_id);
    		$extra['game_code'] = $game_code;
    		$extra['game_mode'] = $game_mode;
    		$extra['game_type'] = $game_type;
    		$extra['language'] = $language;
    		$extra['is_mobile'] = $is_mobile;
            $extra = array_merge($extra, $get_params);
			$platformapi = $this->external_system->getSystemName($game_platform_id);

			if($platformapi == "VIVOGAMING_API"){
				$extra['game_type'] = 'lobby';
			}

			$data = $api->queryForwardGame($player_name,$extra);
			$this->playerTrackingEventPlayNow($game_platform_id, $data, $extra, $_transferResult);

			switch($platformapi) {
                case 'AFB88_API':
                    $platformName = 'AFB';
                    break;
                case 'DONGSEN_ESPORTS_API':
                case 'DONGSEN_LOTTERY_API':
                    $platformName = 'DongSen';
                    break;
                case 'TFGAMING_ESPORTS_API':
                    $platformName = 'TFgaming Esports';
                    break;
                case 'RG_API':
                    $platformName = 'Ray Gaming';
                    break;
                case 'JOKER_API':
                    $platformName = 'Joker Gaming';
                    break;
                default:
	                $platformName = isset($data['game_platform_name']) ? $data['game_platform_name'] : $this->external_system->getNameById($game_platform_id);
                	break;
            }

            if($game_platform_id == PGSOFT_API || $game_platform_id == PGSOFT_SEAMLESS_API || $game_platform_id == PGSOFT2_SEAMLESS_API || $game_platform_id == PGSOFT3_SEAMLESS_API || $game_platform_id == PGSOFT3_API || $game_platform_id == IDN_PGSOFT_SEAMLESS_API){
	            if($api->getSystemInfo('enabled_new_queryforward') && isset($data['is_html']) && $data['is_html'] == true){
					$this->load->view(
						'iframe/pgsoft_game_iframe', 
						array(
							'platformName' => $platformName,
							'html' => $data['html']
						)
					);
					return;
				}
			}

	        $is_redirect = $api->getSystemInfo('is_redirect', false);
            $is_mobile_redirect = $api->getSystemInfo('is_mobile_redirect', true);

	        if ($is_redirect && $data['success']) {
	        	if(isset($data['url'])){
					redirect($data['url']);
		        } else {
					die(lang('goto_game.error'));
				}
				return;
	        }

            if (array_key_exists('forward_url', $data)) {
                return redirect($data['forward_url']);
            }
            if($game_platform_id == BETBY_SEAMLESS_GAME_API){
				$this->load->view('iframe/player/goto_betby', $data);
				return;
			}

            if($game_platform_id == DIGITAIN_SEAMLESS_API){
            	$data['title'] = $platformName;
            	if ($is_mobile) {
            		$this->load->view('iframe/player/goto_digitain_mobile_view', $data);
					return;
            	}
            	if(isset($data['view']) && $data['view'] == "asian"){
            		$this->load->view('iframe/player/goto_digitain_asian_view', $data);
					return;
            	}

            	if(isset($game_code) && $game_code == "esports"){
            		$this->load->view('iframe/player/goto_digitain_esports_view', $data);
					return;
            	}

				$this->load->view('iframe/player/goto_digitain', $data);
				return;
			}

			if($platformapi == 'ION_GAMING_API' || $platformapi == 'ION_GAMING_IDR1_API' || $platformapi == 'IONGAMING_SEAMLESS_GAME_API' || $platformapi == 'IONGAMING_SEAMLESS_IDR1_GAME_API' || $platformapi == 'IONGAMING_SEAMLESS_IDR2_GAME_API'){
					$this->load->view('iframe/player/goto_iongaming', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'payload' => $data['payload']));
					return;
			}

			if($game_platform_id == S128_GAME_API){
				$this->CI->utils->debug_log('S128 (goto_common_game)', $data);
				$this->load->view('iframe/player/goto_s128_game', array('url' => $data['url'],
				'platformName' => $platformName,
				'lang' => @$data['data']['lang'],
				'session_id' => @$data['data']['session_id'],
				'login_id' => @$data['data']['login_id']));
				return;
			}

			# NETENT GAME start HERE
			$allNetentPlatformApi = [
				"NETENT_GAME_API",
				"NETENT_SEAMLESS_GAME_API",
				"NETENT_SEAMLESS_GAME_IDR1_API",
				"NETENT_SEAMLESS_GAME_CNY1_API",
				"NETENT_SEAMLESS_GAME_THB1_API",
				"NETENT_SEAMLESS_GAME_MYR1_API",
				"NETENT_SEAMLESS_GAME_VND1_API",
				"NETENT_SEAMLESS_GAME_USD1_API"
			];

			if(in_array($platformapi,$allNetentPlatformApi)){

				$netentViewData = [
					'platformName' => $platformName,
					'favicon_brand' => $favicon_brand,
					'gamePlatformMode' => isset($data['gamePlatformMode']) ? $data['gamePlatformMode'] : null,
					'gameMode' => isset($data['gameMode']) ? $data['gameMode'] : null,
					'allow_fullscreen' => false, // game provider advised, in order game will resize to the frame it has availabl
					'lobbyUrl' => isset($data['lobbyUrl']) ? $data['lobbyUrl'] : null,
					'casinoBrand' => isset($data['casinoBrand']) ? $data['casinoBrand'] : null,
					'sessionId' => isset($data['sessionId']) ? $data['sessionId'] : null,
					'lang' => isset($data['lang']) ? $data['lang'] : null,
					'gameId' => isset($data['gameId']) ? $data['gameId'] : null,
					'staticServerURL' => isset($data['staticServerURL']) ? $data['staticServerURL'] : null,
					'gameServerURL' => isset($data['gameServerURL']) ? $data['gameServerURL'] : null,
					'gameJsUrl' => isset($data['gameJsUrl']) ? $data['gameJsUrl'] : null

				];

				return $this->load->view('iframe/player/goto_netent_game',$netentViewData);
			}
			# NETENT GAME end HERE

			if($platformapi == 'MGPLUS_API' || $platformapi == 'T1MGPLUS_API' || $platformapi == 'MGPLUS2_API'){
				if ($is_mobile) {
					redirect($data['url']);
				} else {
					$this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true));
					return;
				}
			}

			if($platformapi == 'ASIASTAR_API'){
				$this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true));
				return;
            }

			if($platformapi == 'TPG_API'){
                if($game_mode == 'app') {
                    $this->load->view('iframe/player/goto_mobile_game', array('url' => $data['url'], 'logo_url' => $data['logo_url'], 'download_url' => $data['download_url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true));
                    return;
                }
			}

			if($platformapi == 'TP_API'){
                if($game_type === 'fishing' ||$game_type === 'slots') {
					redirect($data['url']);
                    return;
                }
			}

			if($platformapi == 'BG_SEAMLESS_GAME_THB1_API' || $platformapi == 'BG_SEAMLESS_GAME_API'){
				if(isset($data['url']) && !empty($data['url'])){
					$this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true));
					return;
				}else{
					die(lang('goto_game.error'));
				}
			}

			if ($platformapi === 'BOOMING_SEAMLESS_API') {
				$this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true, 'no_scrolling' => true));
			}

			if($platformapi == 'JOKER_API'){
				if($game_mode == 'app') {
					redirect($data['url']);
                    return;
				} else {
					$this->load->view('iframe/player/goto_joker_game', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true));
					return;
				}
            }

            if($platformapi == 'RGS_API'){
                $this->game_request_promo($api, $player_id);
				redirect($data['url']);
                return;
			}

			if($platformapi == 'TGP_AG_API'){
				redirect($data['url']);
                return;
			}

			if($platformapi == 'VIVOGAMING_API' || $platformapi == 'VIVOGAMING_IDR_B1_API' || $platformapi ==  'VIVOGAMING_IDR_B1_ALADIN_API'){
				$this->load->model('game_description_model');

				$unique_id = "game_code";

				# Roulette
				if($game_type == "roulette"){
					#american
					if($game_code == 182){
						$unique_id = "external_game_id";
						$game_type = 5;
					}else{
						#European
						$unique_id = "external_game_id";
						$game_type = 1;
					}
				}

				$platformName = $this->game_description_model->getGameNameByCurrentLang($game_type, $game_platform_id,$unique_id);

				$data['isAllowFullScreen'] = "allowfullscreen=\"true\"";
				$data['platformName'] = $platformName;

				$this->load->view($this->utils->getPlayerCenterTemplate() . '/game_iframe', $data);
				return;
			}
            if($platformapi == 'CHAMPION_SPORTS_GAME_API') {
				if($is_mobile || (isset($data['redirect']) && $data['redirect'])){
					redirect($data['url']);
				}else{
                    $this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true, 'no_scrolling' => true));
                    return;
				}
            }

            if($platformapi == 'LIONKING_GAME_API'){
                if($game_mode == 'app') {
                    $this->load->view('iframe/player/goto_mobile_game', array('url' => $data['url'], 'logo_url' => $data['logo_url'], 'download_url' => $data['download_url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true));
                    return;
                }
			}

            if ($game_platform_id == WAZDAN_SEAMLESS_GAME_API) {
                $this->load->view('iframe/player/goto_wazdan_game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true, 'no_scrolling' => true));
                return;
            }

            if ($game_platform_id == PNG_SEAMLESS_GAME_API) {
                $this->load->view('iframe/player/goto_png_seamless_game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true, 'show_low_balance_prompt' => $show_low_balance_prompt, 'game_platform_name' => 'PNG_SEAMLESS_GAME_API', 'params' => $data['params'], 'api_domain' => $data['api_domain'], 'lobby_url' => $data['lobby_url']));
                return;
            }

            if ($game_platform_id == PINNACLE_SEAMLESS_GAME_API) {
                $this->load->view('iframe/player/goto_pinnacle_game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true, 'no_scrolling' => true, 'origin' => $data['origin']));
                return;
            }

            if ($game_platform_id == BETGAMES_SEAMLESS_GAME_API) {
                return $this->load->view('iframe/player/goto_betgames_seamless_game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true, 'game_platform_name' => 'BETGAMES_SEAMLESS_GAME_API', 'params' => $data['params']));
            }

            if ($game_platform_id == TWAIN_SEAMLESS_GAME_API) {
                return $this->load->view('iframe/player/goto_twain_seamless_game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true, 'game_platform_name' => 'TWAIN_SEAMLESS_GAME_API', 'params' => $data['params']));
            }

			if ($game_platform_id == AVIATRIX_SEAMLESS_GAME_API) {
				return $this->load->view('iframe/player/goto_aviatrix_seamless_game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true, 'game_platform_name' => 'AVIATRIX_SEAMLESS_GAME_API', 'params' => $data['params']));
			}
			if ($game_platform_id == PLAYSTAR_SEAMLESS_GAME_API) {
				return $this->load->view('iframe/player/goto_playstar_seamless_game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true, 'game_platform_name' => 'PLAYSTAR_SEAMLESS_GAME_API', 'params' => $data['params']));
			}
			if ($game_platform_id == IDN_SPADEGAMING_SEAMLESS_GAME_API) {
				return $this->load->view('iframe/player/goto_idn_spade_gaming_seamless_game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true, 'game_platform_name' => 'IDN_SPADEGAMING_SEAMLESS_GAME_API', 'params' => $data['params']));
			}

			if ($game_platform_id == ULTRAPLAY_SEAMLESS_GAME_API) {
				$this->utils->debug_log('ULTRAPLAY_SEAMLESS_DEBUG =================================================> ', $data['url']);
				$is_redirect = $api->getSystemInfo('is_redirect', false);
				if ($is_redirect){
					$this->utils->debug_log('ULTRAPLAY_SEAMLESS_DEBUG =================================================>redirect');
					redirect($data['url']);
					return;
				}else{
					$this->utils->debug_log('ULTRAPLAY_SEAMLESS_DEBUG =================================================>iframe');
					$this->load->view('iframe/player/goto_ultraplay_game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'origin' => 'https://stage-t1.ultraplay.net'));
					return;
				}
			}

			if ($data['success']) {
				if($is_mobile || (isset($data['redirect']) && $data['redirect'])){
                    if($show_low_balance_prompt) {
                        return $this->load->view('iframe/low_balance_prompt', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'show_low_balance_prompt' => $show_low_balance_prompt));
                    }

                    if ($is_mobile_redirect) {
                        redirect($data['url']);
                    } else {
                        $this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true, 'show_low_balance_prompt' => $show_low_balance_prompt));
                    }
				}else{
					$this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true, 'show_low_balance_prompt' => $show_low_balance_prompt));
				}
			} else {
				die(lang('goto_game.error'));
			}
			return;
		}
	}

	/**
	 * overview : go to booming game
	 *
	 * @param string	$game_code
	 * @param string 	$game_mode
	 */
	public function goto_booming($game_platform_id, $game_code = null, $game_mode = "real") {
		$is_mobile = $this->utils->is_mobile();
		$extra =[];

		if ($game_mode == "demo") {
			$extra['demo'] = true;
		}

		$extra['game_code'] = $game_code;
		$extra['is_mobile'] = $is_mobile;

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system'));

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('BOOMING');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$playerName = $this->authentication->getUsername();
        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED for player
		$this->checkBlockGamePlatformSetting($game_platform_id);

        $this->checkGameIfLaunchable($game_platform_id,$game_code);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($playerName);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($playerName);

		$platformName = $this->external_system->getNameById($game_platform_id);
		if (!$blocked) {
			$this->_transferAllWallet($player_id, $playerName, $game_platform_id);
			$rlt = $api->queryForwardGame($playerName, $extra);
			if (isset($rlt['success']) && $rlt['success'] && $is_mobile) {
				redirect($rlt['url']);
				return;
			}
			if (isset($rlt['success']) && $rlt['success'] && $rlt['isRedirect']) {
				redirect($rlt['url']);
				return;
			}
			if (isset($rlt['success']) && $rlt['success']) {
				$data = array(
					'url' => $rlt['url'],
					'platformName' => $platformName,
					'allow_fullscreen' => true,
					'no_scrolling' => true,
				);
				$this->utils->debug_log(' BOOMING goto url data - =================================================> ' . json_encode($data,true));
				$this->load->view('iframe/game_iframe', $data);
				return;
			} else {
				// echo 'goto error';
				die(lang('goto_game.error'));
			}
		}
		else{
			die(lang('goto_game.blocked'));
		}
	}

	public function get_lobby_url($lobby_extension = NULL, $override = false) {

		if ($override) {
			return $lobby_extension;
		}

		$this->load->library('user_agent');

		$lobbyurl = $this->agent->referrer();

		if ($lobbyurl) {

			if ($lobby_extension) {
				$lobbyurl = parse_url($lobbyurl, PHP_URL_SCHEME) . '://' . parse_url($lobbyurl, PHP_URL_HOST);
			}

		} else {

			$lobbyurl  = $this->utils->is_mobile() ? $this->utils->getSystemUrl('m') : $this->utils->getSystemUrl('www');

		}

		if ($lobby_extension) {
			$lobbyurl .= '/' . ltrim($lobby_extension, '/');
		}

		// var_dump($lobbyurl);

		return $lobbyurl;

	}

	/**
	 * overview : go to yggdrasil game
	 *
	 * @param string	$game_code
	 * @param boolean	$is_mobile
	 * @param boolean	$is_trial
	 */
	public function goto_yggdrasil($game_code, $is_mobile = false, $game_mode = 'real') {
		$this->CI->utils->debug_log("goto_yggdrasil, game_code = $game_code, is_mobile = $is_mobile, game_mode = $game_mode");

		if (empty($is_mobile)) {
			$is_mobile = $this->utils->is_mobile();
		} else {
			$is_mobile = $is_mobile == 'true';
		}

		# LOAD MODEL AND LIBRARIES
		$this->load->model(array('game_provider_auth', 'external_system', 'operatorglobalsettings'));

		# DECLARE WHICH GAME PLATFORM TO USE
		$game_platform_id = YGGDRASIL_API;

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance('AGHG');
            return;
        }

		# GET LOGGED-IN PLAYER
		$player_id = $this->authentication->getPlayerId();
		$player_name = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($game_platform_id);

		# CHECK PLAYER IF EXIST
		$player = $api->isPlayerExist($player_name);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
			}
		}

		# CHECK IF GAMEPLATFORM SETTING IS BLOCKED
		$this->checkBlockGamePlatformSetting($game_platform_id);

		# CHECK IF LOGGED-IN PLAYER IS BLOCKED
		$blocked = $api->isBlocked($player_name);

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
			$currency = $this->config->item('default_currency');
			$result = $api->queryForwardGame($player_name, array(
				'game_code' => $game_code,
				'game_mode' => $game_mode,
				'channel' => ($is_mobile?'mobile':'pc')
			));
			$this->CI->utils->debug_log("goto_yggdrasil URL >-------------------------------------> ", $result);


			$platformName = $this->external_system->getNameById($game_platform_id);

			$data = array(
					'url' => @$result['url'],
					'platformName' => $platformName
			);

			if (!empty($data['url'])) {
				$this->load->view('iframe/game_iframe', $data);
			}else{
				die(lang('goto_game.error'));
			}
			return;
		}
    }

    public function goto_rg_game() {
        $is_mobile = $this->utils->is_mobile();
        $game_platform_id = RG_API;
        # LOAD MODEL AND LIBRARIES
        $this->load->model(array('game_provider_auth', 'external_system', 'game_description_model'));

        $language = $this->language_function->getCurrentLanguage();

        # LOAD GAME API
        $api = $this->utils->loadExternalSystemLibObject($game_platform_id);
        $platformName = 'Ray Gaming';
        $favicon_brand = $api->getSystemInfo('favicon', false);

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance($game_platform_id);
            return;
        }

        # CHECK IF GAMEPLATFORM SETTING IS BLOCKED
        $this->checkBlockGamePlatformSetting($game_platform_id);

        $blocked = false;
        $player_name = null;

        $required_login_on_launching_trial = $api->getSystemInfo('required_login_on_launching_trial', true);
        if ($this->authentication->isLoggedIn()) {

            # GET LOGGED-IN PLAYER
            $player_id = $this->authentication->getPlayerId();
            $player_name = $this->authentication->getUsername();

            #BLOCK LOGIN GAME BY USER STATUS
            if($this->CI->utils->blockLoginGame($player_id)){
                $this->goBlock();
                return;
            }

            # CHECK PLAYER IF EXIST
            $player = $api->isPlayerExist($player_name);

            # IF NOT CREATE PLAYER
            if (isset($player['exists']) && !$player['exists']) {
                if(!is_null($player['exists'])){
                    $this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
                }
            }

            # CHECK IF LOGGED-IN PLAYER IS BLOCKED
            $blocked = $api->isBlocked($player_name);
        }
        else if($required_login_on_launching_trial) {
            $this->goPlayerLogin();
        }

        if ($blocked) {
            die(lang('goto_game.blocked'));
        } else {
            $extra =[];

            if ($this->authentication->isLoggedIn()) {
                $this->_transferAllWallet($player_id, $player_name, $game_platform_id);
            }

            $extra['language'] = $language;
            $extra['is_mobile'] = $is_mobile;
            $platformapi = $this->external_system->getSystemName($game_platform_id);
            $data = $api->queryForwardGame($player_name,$extra);

            if ($data['success']) {
                if($is_mobile || (isset($data['redirect']) && $data['redirect'])){
                    redirect($data['url']);
                }else{
                    $this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true));
                }
            } else {
                die(lang('goto_game.error'));
            }
            return;
        }
    }

	public function goto_hub88($external_game_id, $game_mode = "real") {
        $language = $this->language_function->getCurrentLanguage();
		$game_platform_id = HUB88_API;

		$this->launch_game($game_platform_id, $external_game_id, $language, $game_mode);
	}

	public function goto_sagaming_logout() {
		$platformName = 'SAGaming';
		$this->load->view('iframe/player/goto_sagaming_logout', array('platformName' => $platformName));
		return;
	}

	public function goto_betgames($token=false) {

       $is_mobile = $this->utils->is_mobile();
        $game_platform_id = BETGAMES_SEAMLESS_THB1_GAME_API;
        # LOAD MODEL AND LIBRARIES
        $this->load->model(array('game_provider_auth', 'external_system', 'game_description_model'));

        $language = $this->language_function->getCurrentLanguage();

        # LOAD GAME API
        $api = $this->utils->loadExternalSystemLibObject($game_platform_id);
        $platformName = $this->external_system->getNameById($game_platform_id);
        $favicon_brand = $api->getSystemInfo('favicon', false);

        # CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
        if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
            $this->goto_maintenance($game_platform_id);
            return;
        }

        # CHECK IF GAMEPLATFORM SETTING IS BLOCKED
        $this->checkBlockGamePlatformSetting($game_platform_id);

        $blocked = false;
        $player_name = null;

        if ($this->authentication->isLoggedIn()) {

            # GET LOGGED-IN PLAYER
            $player_id = $this->authentication->getPlayerId();
            $player_name = $this->authentication->getUsername();

            #BLOCK LOGIN GAME BY USER STATUS
            if($this->CI->utils->blockLoginGame($player_id)){
                $this->goBlock();
                return;
            }

            # CHECK PLAYER IF EXIST
            $player = $api->isPlayerExist($player_name);

            # IF NOT CREATE PLAYER
            if (isset($player['exists']) && !$player['exists']) {
                if(!is_null($player['exists'])){
                    $this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
                }
            }

            # CHECK IF LOGGED-IN PLAYER IS BLOCKED
            $blocked = $api->isBlocked($player_name);
        }

        if ($blocked) {
            die(lang('goto_game.blocked'));
        } else {
        	if($token==true) {
        		$extra['language'] = $language;
        		$extra['token'] = $token;
        		$data = $api->queryForwardGame($player_name,$extra);
        		$this->load->view('iframe/player/goto_betgamestoken', array('token' => $data['token'], 'platformName' => $platformName, 'player_name' => $player_name));
        		return;
        	}
            $extra =[];

            if ($this->authentication->isLoggedIn()) {
                $this->_transferAllWallet($player_id, $player_name, $game_platform_id);
            }

            $extra['token'] = false;
            $extra['language'] = $language;
            $extra['is_mobile'] = $is_mobile;
            $platformapi = $this->external_system->getSystemName($game_platform_id);
            $data = $api->queryForwardGame($player_name,$extra);
            // print_r($data);exit;

            if ($data['success']) {
            	$this->load->view('iframe/player/goto_betgames', array('url' => $data['params'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true, 'player_name' => $player_name));
            } else {
                die(lang('goto_game.error'));
            }
            return;
        }
	}

	private function getMaintenanceUrlByMerchantCode($merchant_code){
		$maintenanceUrl=null;
    	//use merchant_code maintenance page
    	$maintenance_url_for_agent=$this->utils->getConfig('maintenance_url_for_agent');
    	if(!empty($maintenance_url_for_agent)){
    		$this->utils->debug_log('search maintenance url for agent', $maintenance_url_for_agent, $merchant_code);
    		if(array_key_exists($merchant_code, $maintenance_url_for_agent)){
    			$maintenanceUrl=$maintenance_url_for_agent[$merchant_code];
            	if(!empty($maintenanceUrl)){
            		if(substr($maintenanceUrl, 0, 7)!='http://' &&
            			substr($maintenanceUrl, 0, 8)!='https://'){
	            		if($this->utils->is_mobile()){
	            			$maintenanceUrl=$this->utils->getSystemUrl('m', $maintenanceUrl);
	            		}else{
	            			$maintenanceUrl=$this->utils->getSystemUrl('www', $maintenanceUrl);
	            		}
            		}
            		$this->utils->error_log('cannot find token, go to maintenance', $maintenanceUrl);
            	}
    		}
    	}
        return $maintenanceUrl;
	}

	/**
     * Launch Player Game Lobby
    */
	public function player_game_lobby(){

		$this->load->library(['game_list_lib', 'player_library']);
        $this->load->model([ 'game_type_model' , 'wallet_model', 'common_token','game_description_model']);

		/* PARAMS */
		$token = $this->input->get('token');
		$game_platform_id = $this->input->get('game_platform_id');
		$merchant_code = $this->input->get('merchant_code');
		$home_link = $this->input->get('home_link');
		$cashier_link = $this->input->get('cashier_link');
		$logo_link = $this->input->get('logo_link');
		$language = $this->input->get('language');
		$append_target_db=$this->input->get('append_target_db');
		$on_error_redirect = $this->input->get('on_error_redirect');
		//OGP-32458 send post message 
		$post_message_on_error = $this->input->get('post_message_on_error');
		//convert to bool
		$append_target_db=$append_target_db=='true' || $append_target_db=='1';
		$lang_key = $this->get_lang_code($language);
		$game_type_code = $this->input->get('game_type');

		/* PLAYER INFO */
        $playerInfo = $this->common_token->getPlayerInfoByToken($token);
        if(empty($playerInfo)){
        	if($post_message_on_error){
        		$post_message['error_message'] = lang("Invalid player token.");
        		return $this->load->view('iframe/player/view_post_message_closed', $post_message);
        	}

            $this->utils->flash_message(FLASH_MESSAGE_TYPE_DANGER, 'invalid token');
            if(!empty($home_link)){
            	return redirect($home_link);
            }
            if(!empty($on_error_redirect)){
            	return redirect($on_error_redirect);
            }
            if(!empty($merchant_code)){
            	$maintenanceUrl=$this->getMaintenanceUrlByMerchantCode($merchant_code);
            	if(!empty($maintenanceUrl)){
		            return redirect($maintenanceUrl);
            	}
            }
            return redirect($this->goPlayerHome());
        }

		$allow_clear_session = $this->config->item('allow_clear_session_when_launch_game');
		$this->utils->debug_log(__METHOD__, 'allow_clear_session_when_launch_game', $allow_clear_session);

        /* LOCK LOGIN */
        $username=$playerInfo['username'];
        $login_result=['success'=>false];
        $result=$this->localLockOnlyWithResult(Utils::LOCAL_LOCK_ACTION_PLAYER_LOGIN,
        	$username, function(&$error) use(&$login_result, $playerInfo){
        	$success=true;
	        $login_result = $this->player_library->login_by_player_info($playerInfo);
        	return $success;
        });

        /* CHECK LOGIN RESULT*/
        if(!$login_result['success']){
        	$err=lang('Login error');
        	if(!empty($login_result['errors'])){
        		if(is_array($login_result['errors'])){
        			$err=end($login_result['errors']);
        		}else{
        			$err=$login_result['errors'];
        		}
        	}
        	if($post_message_on_error){
        		$post_message['error_message'] = $err;
        		return $this->load->view('iframe/player/view_post_message_closed', $post_message);
        	}
        	if(!empty($home_link)){
            	return redirect($home_link);
            }
            if(!empty($on_error_redirect)){
            	return redirect($on_error_redirect);
            }
            if(!empty($merchant_code)){
            	$maintenanceUrl=$this->getMaintenanceUrlByMerchantCode($merchant_code);
            	if(!empty($maintenanceUrl)){
		            return redirect($maintenanceUrl);
            	}
            }
            $this->utils->flash_message(FLASH_MESSAGE_TYPE_DANGER, $err);
            return redirect($this->goPlayerHome());
        }
        $sqlInfo=null;
        /* GAMES DATA*/
        $game_type_list =  $this->CI->game_type_model->queryByGamePlatformId($game_platform_id, null, $sqlInfo, true);#get gametype only show insite
        $extra = array(
        	"home_link" => $home_link,
        );

        $default_active = !empty($game_type_code) ? $game_type_code : "slots";

        if(!empty($game_type_list)){
        	foreach ($game_type_list as $index => $game_type) {
    			if($game_type['game_type_unique_code'] == "unknown" || empty($game_type['game_type_unique_code'])){ #unset empty uniquecode
    				unset($game_type_list[$index]);
    				continue;
    			}
        		$game_type_name = isset($game_type['game_type_name_detail'][$lang_key]) ? $game_type['game_type_name_detail'][$lang_key] : $game_type['game_type_name_detail']['en'];
        		$data_game_type['game_type_name'] = $game_type_name;
        		$data_game_type['game_type_unique_code'] = $game_type['game_type_unique_code'];
        		$data_game_type['game_type_icon'] = $this->CI->game_list_lib->processGameTypeIcon($game_type['game_type_unique_code']);
        		$data_game_type['is_active'] = ($game_type['game_type_unique_code'] == $default_active )?: false;
        		$game_type_list[$index] = $data_game_type;
				unset($data_game_type);
        	}
        }
        $data['int_lang'] = $this->get_lang_code($language, true);
        $data['language'] = $language;
        $data['types'] = array_values($game_type_list);
        $data['logo_link'] = $logo_link;
        $data['title'] = $this->external_system->getNameById($game_platform_id);
        $data['game_platform_id'] = $game_platform_id;
        $data['home_link'] = $home_link;
		$data['cashier_link'] = $cashier_link;
        $data['append_target_db']=$append_target_db;
        $data['disable_newtab'] = $this->config->item('disable_newtab_player_lobby');

        $data['static_details'] = array(
        	"no_result" => lang('lang.norec', $data['int_lang']),
        	"real" => lang('Real', $data['int_lang']),
        	"demo" => lang('Demo', $data['int_lang']),
        );

        if(in_array($game_platform_id, GAME_DESCRIPTION_MODEL::GAME_API_WITH_LOBBYS)) {
			$is_mobile = $this->utils->is_mobile();
			$api = $this->utils->loadExternalSystemLibObject($game_platform_id);
			$extra['game_mode'] = 'real';
			// $extra['game_type'] = null;
			$extra['game_type'] = !empty($game_type_code) ? $game_type_code : null;
			$extra['language'] = $language;
			$extra['is_mobile'] = $is_mobile;
			$extra['is_lobby'] = true;
			$rlt = $api->queryForwardGame($username,$extra);
		}

		if(isset($rlt['url']) && $rlt['success']) {
			redirect($rlt['url']);
		} else {
        	$this->load->view('game_lobby/player_game_lobby', $data);
		}

	}

	public function demo_game_lobby(){

		$this->load->library(['game_list_lib', 'player_library']);
        $this->load->model([ 'game_type_model' , 'wallet_model', 'common_token','game_description_model', 'external_system']);

		/* PARAMS */
		$token = $this->input->get('token');
		$game_platform_id = $this->input->get('game_platform_id');
		$merchant_code = $this->input->get('merchant_code');
		$home_link = $this->input->get('home_link');
		$logo_link = $this->input->get('logo_link');
		$language = $this->input->get('language');
		$append_target_db=$this->input->get('append_target_db');
		$on_error_redirect = $this->input->get('on_error_redirect');
		//OGP-32458 send post message 
		$post_message_on_error = $this->input->get('post_message_on_error');
		//convert to bool
		$append_target_db=$append_target_db=='true' || $append_target_db=='1';
		$lang_key = $this->get_lang_code($language);

        $sqlInfo=null;
        /* GAMES DATA*/
        $game_type_list =  $this->game_type_model->queryByGamePlatformId($game_platform_id, null, $sqlInfo, true);#get gametype only show insite
        $extra = array(
        	"home_link" => $home_link,
        );
        $default_active = "slots";

        if(!empty($game_type_list)){
        	foreach ($game_type_list as $index => $game_type) {
    			if($game_type['game_type_unique_code'] == "unknown" || empty($game_type['game_type_unique_code'])){ #unset empty uniquecode
    				unset($game_type_list[$index]);
    				continue;
    			}
        		$game_type_name = isset($game_type['game_type_name_detail'][$lang_key]) ? $game_type['game_type_name_detail'][$lang_key] : $game_type['game_type_name_detail']['en'];
        		$data_game_type['game_type_name'] = $game_type_name;
        		$data_game_type['game_type_unique_code'] = $game_type['game_type_unique_code'];
        		$data_game_type['game_type_icon'] = $this->game_list_lib->processGameTypeIcon($game_type['game_type_unique_code']);
        		$data_game_type['is_active'] = ($game_type['game_type_unique_code'] == $default_active )?: false;
        		$game_type_list[$index] = $data_game_type;
				unset($data_game_type);
        	}
        }
        $data['int_lang'] = $this->get_lang_code($language, true);
        $data['language'] = $language;
        $data['types'] = array_values($game_type_list);
        $data['logo_link'] = $logo_link;
        $data['title'] = $this->external_system->getNameById($game_platform_id);
        $data['game_platform_id'] = $game_platform_id;
        $data['home_link'] = $home_link;
        $data['append_target_db']=$append_target_db;

        $data['static_details'] = array(
        	"no_result" => lang('lang.norec', $data['int_lang']),
        	"real" => lang('Real', $data['int_lang']),
        	"demo" => lang('Demo', $data['int_lang']),
        );

        $this->load->view('game_lobby/demo_game_lobby', $data);
	}

	protected function get_lang_code($lang = "en-us",$useInt = false){
		$this->CI->load->library(['language_function']);
		switch (strtolower($lang)) {
			case 'zh-cn':
				return ($useInt) ? language_function::INT_LANG_CHINESE : language_function::ISO2_LANG[language_function::INT_LANG_CHINESE];
				break;
			case 'id-id':
				return ($useInt) ? language_function::INT_LANG_INDONESIAN : language_function::ISO2_LANG[language_function::INT_LANG_INDONESIAN];
				break;
			case 'vi-vn':
				return ($useInt) ? language_function::INT_LANG_VIETNAMESE : language_function::ISO2_LANG[language_function::INT_LANG_VIETNAMESE];
				break;
			case 'ko-kr':
				return ($useInt) ? language_function::INT_LANG_KOREAN : language_function::ISO2_LANG[language_function::INT_LANG_KOREAN];
				break;
			case 'th-th':
				return ($useInt) ? language_function::INT_LANG_THAI : language_function::ISO2_LANG[language_function::INT_LANG_THAI];
				break;
			default:
				return ($useInt) ? language_function::INT_LANG_ENGLISH : language_function::ISO2_LANG[language_function::INT_LANG_ENGLISH];
				break;
		}
	}

	public function launch_game_by_lobby($game_platform_id, $game_code = '_null', $language = 'zh-cn',
		$game_mode = 'real', $platform = '_null', $game_type = '_null', $merchant_code = '_null',$redirection = "_null",$t1_extra = "_null") {
		$this->launch_game($game_platform_id, $game_code, $language,
			$game_mode, $platform, $game_type, $merchant_code,$redirection,$t1_extra);
	}

    public function game_request_promo($api, $player_id){
        $promoCmsSettingId = $api->getSystemInfo('promo_cms_id', null); // #1

        if(empty($promoCmsSettingId)){
            $this->utils->debug_log('empty promoCmsSettingId', $promoCmsSettingId);
            return;
        }

        $action = 0; // #2
        $preapplication = null; // #3
        $is_api_call = false;  // #4
        $ret_to_api = false;  // #5
        $extra_info = null; //#6.1
        $allowGoPlayerPromotions = false; // #7
        $allowAlertMessage = false; // #8
        $lastAlertMessagesCollection = []; // #9

        $this->request_promo($promoCmsSettingId // #1
                            , $action // #2
                            , $preapplication // #3
                            , $is_api_call  // #4
                            , $ret_to_api  // #5
                            , $player_id // #6
                            , $extra_info // #6.1
                            , $allowGoPlayerPromotions // #7
                            , $allowAlertMessage // #8
                            , $lastAlertMessagesCollection // #9
                        );
	}

	public function goto_phoenix_chess_card_poker($game_code, $game_mode = "real") {
		$game_platform_id = PHOENIX_CHESS_CARD_POKER_API;
		$this->goto_common_game($game_platform_id, $game_code, $game_mode);
	}

	public function goto_12live_game($game_platform_id, $game_code = null, $game_type = null, $provider_id = null, $game_mode = 'real', $language = null)
    {

        $is_mobile = $this->utils->is_mobile();

        # LOAD MODEL AND LIBRARIES
        $this->load->model(array('game_provider_auth', 'external_system', 'game_description_model'));

        if(is_null($language)){
           $language = $this->language_function->getCurrentLanguage();
        }

        # LOAD GAME API
        $api = $this->utils->loadExternalSystemLibObject($game_platform_id);
        if(empty($api)){
        	return show_error('Invalid game api', 400);
        }
        $platformName = $this->game_description_model->getGameNameByCurrentLang($game_code, $game_platform_id);
        $platformapi = $this->external_system->getSystemName($game_platform_id);
        $favicon_brand = $api->getSystemInfo('favicon', false);

		# game API that under maintenance to allow for game launch
		$allowed_maintenance_game_api_to_game_launch = $this->utils->getConfig('allowed_maintenance_game_api_to_game_launch');

		if(! is_array($allowed_maintenance_game_api_to_game_launch)){
			$allowed_maintenance_game_api_to_game_launch = [];
		}

		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE, if game API is in config allowed_maintenance_game_api_to_game_launch, allow it, even it's maintenance
		if(!in_array($game_platform_id,$allowed_maintenance_game_api_to_game_launch)){
			if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
				$this->goto_maintenance($game_platform_id);
				return;
			}
		}

        # CHECK IF GAMEPLATFORM SETTING IS BLOCKED
        $this->checkBlockGamePlatformSetting($game_platform_id);

        # GET LOGGED-IN PLAYER
        $player_id = $this->authentication->getPlayerId();
        $player_name = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

        $isPlayerNotLogin = $this->authentication->isLoggedIn();

        //check if not login
        if (!$isPlayerNotLogin) {
           $this->goPlayerLogin();
        }

        # CHECK PLAYER IF EXIST
        $player = $api->isPlayerExist($player_name);

        # IF NOT CREATE PLAYER
        if (isset($player['exists']) && !$player['exists']) {
            if(!is_null($player['exists'])){
               $this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
            }
        }

        # CHECK IF LOGGED-IN PLAYER IS BLOCKED
        $blocked = $api->isBlocked($player_name);

        //exist main account
	    $sub_game_provider_to_main_game_provider=$this->utils->getConfig('sub_game_provider_to_main_game_provider');
	    if(!empty($sub_game_provider_to_main_game_provider) &&
	    	array_key_exists($game_platform_id, $sub_game_provider_to_main_game_provider)){
	    	//create main game account
	    	$mainApiId=$sub_game_provider_to_main_game_provider[$game_platform_id];
	    	$mainApi=$this->utils->loadExternalSystemLibObject($mainApiId);
	    	if(!empty($mainApi)){
	        	$mainExistResult=$this->checkExistOnApiAndUpdateRegisterFlag($mainApi, $player_id, $player_name);
	    		$this->utils->debug_log('checkExistOnApiAndUpdateRegisterFlag for main api', $mainExistResult);
	    	}else{
	    		$this->utils->error_log('load main class failed', $mainApiId, $game_platform_id);
	    	}
	    }

		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$extra =[];
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
    		$extra['game_code'] = $game_code;
    		$extra['game_mode'] = $game_mode;
    		$extra['game_type'] = $game_type;
    		$extra['language'] = $language;
    		$extra['provider_id'] = $provider_id;
    		$extra['is_mobile'] = $is_mobile;
			$platformapi = $this->external_system->getSystemName($game_platform_id);

			$data = $api->queryForwardGame($player_name,$extra);

	        $is_redirect = $api->getSystemInfo('is_redirect', false);

	        if ($is_redirect && $data['success']) {
	        	if(isset($data['url'])){
					redirect($data['url']);
		        } else {
					die(lang('goto_game.error'));
				}
				return;
	        }

			if ($data['success']) {
				if($is_mobile || (isset($data['redirect']) && $data['redirect'])){
					redirect($data['url']);
				}else{
					$this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true));
				}
			} else {
				die(lang('goto_game.error'));
			}
			return;
		}
	}

	/**
	 * Launch bbin lobby by active site:
	 * @param active_site
	 * @param game_mode
	 * @param language
	 * active_site
	    NBB Sports：nbbsport,
	    Lottery: lottery,
	    Live: live,
	    Casino(slots): casino,
	    card：card,
	    XBB Casino：xbbcasino,
	    Fishing Game：fisharea
	 *
	 * <domain>/player_center/goto_bbin_lobby/<active site>
	 */

	public function goto_bbin_lobby($active_site = 'live',  $game_mode = 'real', $language = 'null'){
		return $this->goto_bbin_game(0, 'null', 'null', $game_mode, $language, $active_site);
	}

	/**
	 * Launch bbin game by game url API :
	 * @param game_type
	 * @param game_code
	 * @param game_mode
	 * @param language
	 * @param game_url_no
	    * 3 = GameUrlBy3 (The link of live game)
		* 5 = GameUrlBy5 (The link of casino game)
		* 12 = GameUrlBy12 (The link of lottery game)
		* 30 = GameUrlBy30 (The link of BB Fishing Connoisseur game)
		* 31 = GameUrlBy31 (The link of New BB Sport)
		* 38 = GameUrlBy38 (The link of BB Fishing Master game)
		* 66 = GameUrlBy66 (The link of BB Battle)
		* 73 = GameUrlBy73 (The link of XBB Lottery game)
		* 75 = GameUrlBy75 (The link of XBB live game)
		* 76 = GameUrlBy76 (The link of XBB casino game)
		* 93 = GameUrlBy93 (The link of NBB Blockchain game)
		* 107 = GameUrlBy107 (The link of BBP casino game)
		* 109 = GameUrlBy109 (The link of BB Sports)
		* 0 = LobbyUrl (The link of Game Lobby)
	 *
	 */

	public function goto_bbin_game($game_url_no, $game_type = 'null', $game_code = 'null', $game_mode = 'real', $language = 'null', $active_site = 'null')
    {
        $is_mobile = $this->utils->is_mobile();

        # LOAD MODEL AND LIBRARIES
        $this->load->model(array('external_system', 'game_description_model'));

        if(is_null($game_type)  || $game_type == 'null'){
           $game_type = null;
        }

        if(is_null($game_code)  || $game_code == 'null'){
           $game_code = null;
        }

        if(is_null($language)  || $language == 'null'){
           $language = $this->language_function->getCurrentLanguage();
        }

        if(is_null($active_site)  || $active_site == 'null'){
           $active_site = null;
        }

		# OGP-26282
		$get_params = $this->input->get() ?: [];
		$this->goLaunchGameWithPlayerToken($get_params);

        # LOAD GAME API
        $game_platform_id = BBIN_API;
        $api = $this->utils->loadExternalSystemLibObject($game_platform_id);
        if(empty($api)){
        	return show_error('Invalid game api', 400);
        }
        $enabled_new_queryforward = $api->getSystemInfo('enabled_new_queryforward', true); // must be enabled to all client
        if(!$enabled_new_queryforward){
        	return show_error('Disabled new queryforward', 400);
        }


        $platformName = $this->game_description_model->getGameNameByCurrentLang($game_code, $game_platform_id);
        $platformapi = $this->external_system->getSystemName($game_platform_id);
        $favicon_brand = $api->getSystemInfo('favicon', false);

		# game API that under maintenance to allow for game launch
		$allowed_maintenance_game_api_to_game_launch = $this->utils->getConfig('allowed_maintenance_game_api_to_game_launch');

		if(! is_array($allowed_maintenance_game_api_to_game_launch)){
			$allowed_maintenance_game_api_to_game_launch = [];
		}

		# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE, if game API is in config allowed_maintenance_game_api_to_game_launch, allow it, even it's maintenance
		if(!in_array($game_platform_id,$allowed_maintenance_game_api_to_game_launch)){
			if ($this->utils->setNotActiveOrMaintenance($game_platform_id)) {
				$this->goto_maintenance($game_platform_id);
				return;
			}
		}

        # CHECK IF GAMEPLATFORM SETTING IS BLOCKED
        $this->checkBlockGamePlatformSetting($game_platform_id);


        # GET LOGGED-IN PLAYER
        $player_id = $this->authentication->getPlayerId();
        $player_name = $this->authentication->getUsername();

        #BLOCK LOGIN GAME BY USER STATUS
        if($this->CI->utils->blockLoginGame($player_id)){
            $this->goBlock();
            return;
        }

        $isPlayerNotLogin = $this->authentication->isLoggedIn();

        //check if not login
        if (!$isPlayerNotLogin) {
           $this->goPlayerLogin();
        }

        # CHECK PLAYER IF EXIST
        $player = $api->isPlayerExist($player_name);

        # IF NOT CREATE PLAYER
        if (isset($player['exists']) && !$player['exists']) {
            if(!is_null($player['exists'])){
               $this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api);
            }
        }

        # CHECK IF LOGGED-IN PLAYER IS BLOCKED
        $blocked = $api->isBlocked($player_name);
        //exist main account



		if ($blocked) {
			die(lang('goto_game.blocked'));
		} else {
			$extra =[];
			$this->_transferAllWallet($player_id, $player_name, $game_platform_id);
    		$extra['game_code'] = $game_code;
    		$extra['game_mode'] = $game_mode;
    		$extra['game_type'] = $game_type;
    		$extra['language'] = $language;
    		$extra['is_mobile'] = $is_mobile;
    		$extra['active_site'] = $active_site;
    		$extra['game_url_no'] = $game_url_no;
			$extra['new_bbin_url'] = true; // it will use for new query forward game
			$platformapi = $this->external_system->getSystemName($game_platform_id);

			if($platformapi == "VIVOGAMING_API"){
				$extra['game_type'] = 'lobby';
			}

			$data = $api->queryForwardGame($player_name,$extra);
	        $is_redirect = $api->getSystemInfo('is_redirect', false);

	        if ($is_redirect && $data['success']) {
	        	if(isset($data['url'])){
					redirect($data['url']);
		        } else {
					die(lang('goto_game.error'));
				}
				return;
	        }


			if ($data['success']) {
				if($is_mobile || (isset($data['redirect']) && $data['redirect'])){
					redirect($data['url']);
				}else{
					$this->load->view('iframe/game_iframe', array('url' => $data['url'], 'platformName' => $platformName, 'favicon_brand' => $favicon_brand, 'allow_fullscreen' => true));
				}
			} else {
				die(lang('goto_game.error'));
			}
			return;
		}
	}

	public function checkGameFlag($api, $game_platform_id, $game_code = null){
		$this->load->model(array('game_description_model'));
		$check_game_code_flag = $api->getSystemInfo('check_game_code_flag', false);
		if($check_game_code_flag && isset($game_code)){
			$flag = $this->game_description_model->check_gamecode_flag($game_platform_id, $game_code);
			if(!$flag){
				return show_error('Invalid game code access', 400);
			}
		}
	}

	public function goto_home_url(){
		$home_url = isset($_GET['home_url']) ? $_GET['home_url'] : null;
		if(empty($home_url)){
			$home_url = $this->utils->getSystemUrl('player');
		} else {
			if ($this->utils->getConfig('always_https') || $this->utils->isHttps()) {
				$home_url = 'https://' . $home_url;
			} else {
				$home_url = 'http://' . $home_url;
			}
		}
		$data['home_url'] = $home_url;
		return $this->load->view('iframe/player/goto_home_url', $data);
	}

	public function post_message_home_page(){
		$data['redirect_url'] = $this->utils->getSystemUrl('player', '/player_center/post_message_origin');
		return $this->load->view('iframe/player/view_post_message_home_page', $data);
	}

	public function post_message_origin(){
		return $this->load->view('iframe/player/view_post_message_origin');
	}

	public function view_testing_page_iframe(){
		$testing_url = $this->utils->getConfig('view_testing_page_iframe_url');
		$data['iframe_url'] = $this->utils->getSystemUrl('player', '/player_center/post_message_home_page');
		if(!empty($testing_url)){
			$data['iframe_url'] = $testing_url;
		}
		return $this->load->view('iframe/player/view_testing_page_iframe', $data);
	}

	protected function goLaunchGameWithPlayerToken($params) {
		$login_with_token = isset($params['login_with_token'])?$params['login_with_token']:null;
		$return_home_domain = isset($params['fail'])?$params['fail']:'';
		$fail_url = $this->input->get('fail') ?: $this->utils->getConfig('login_with_token_fail_url');
		if(!empty($login_with_token)){
			$cookie_lifetime_rhd_default = 60 * 10;
			$this->load->library(['player_library']);
			$result = $this->player_library->login_by_token($login_with_token);
			if($result['success']){
				$this->load->helper('cookie');
				$cookie_lifetime_rhd = $this->utils->getConfig('cookie_lifetime_return_home_domain') ?: $cookie_lifetime_rhd_default;
				set_cookie('return_home_domain', $return_home_domain, $cookie_lifetime_rhd);
			}else{
				if(!empty($fail_url)){
					redirect($fail_url);
				}
				redirect($this->goPlayerHome());
			}
		}
	}

	private function playerTrackingEventPlayNow($gamePlatformId, $apiResponse = [], $gameParams = [], $transferResponse = []){
        $this->load->model(['game_description_model']);

		$playerId = $this->authentication->getPlayerId();
		$playerName = $this->authentication->getUsername();
		$gameCode = isset($gameParams['game_code']) && $gameParams['game_code'] !== '_null' ?  $gameParams['game_code'] : null;
		$gameType = isset($gameParams['game_type']) ?  $gameParams['game_type'] : null;

        if (!empty($gameCode)) {
            // remove default game type to use game code for getting the correct game type.
            $remove_game_type_apis = [
                EVOLUTION_GAMING_API,
            ];
    
            if (in_array($gamePlatformId, $remove_game_type_apis)) {
                $gameType = null;
            }
        } else {
            // slots
            $update_game_types_to_slots = [
                'yoplay',
            ];

            if (in_array($gameType, $update_game_types_to_slots)) {
                $gameType = 'slots';
            }

            // live_dealer
            $update_game_types_to_live_dealer = [
                'html_live_games',
                'casino',
            ];

            if (in_array($gameType, $update_game_types_to_live_dealer)) {
                $gameType = 'live_dealer';
            }


            // sports
            $update_game_types_to_sports = [
                'sportsbook',
            ];

            if (in_array($gameType, $update_game_types_to_sports)) {
                $gameType = 'sports';
            }
        }

		if(isset($apiResponse['success']) && $apiResponse['success']){
			if($this->CI->config->item('enable_player_action_trackingevent_system')){
				$trackingType = $this->utils->getConfig('player_tracking_type');
				$this->utils->debug_log('=====trackingType', $trackingType);
				switch($trackingType){
					case 'posthog':
						$this->load->library('user_agent');
						$depositAmount = isset($transferResponse['summary']['amount_transferred'])? $transferResponse['summary']['amount_transferred']: 0;
                        // lobby type
						if(empty($gameCode) && empty($gameType)){
                            $api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
                            $defaultProviderLobbyGameType = $api->default_provider_lobby_game_type;

							$lobby_category_config = $this->utils->getConfig('lobby_launch_provider_category');

                            if (!empty($defaultProviderLobbyGameType)) {
                                $gameType = $defaultProviderLobbyGameType;
                            } else {
                                if(isset($lobby_category_config[$gamePlatformId])){
                                    $gameType = $lobby_category_config[$gamePlatformId];
                                }
                            }
						}
                        // game code type
						if(!empty($gameCode) && empty($gameType)){
							$gameType = $this->game_description_model->getGameTypeCodeByGameCode($gamePlatformId, $gameCode);
						}
						
						$track_post_data = array(
							'provider' => $this->external_system->getSystemName($gamePlatformId),
							'deposit_amount' => $depositAmount,
							'game_type' => $gameType,
							'username' => $playerName,
							'launcher' => current_url()
						);
						break;
					default:
						$track_post_data = array(
							"launcher" => __function__
						);
						break;
				}

                $this->utils->debug_log('playerTrackingEventPlayNow_check_game_type', 'game_platform_id', $gamePlatformId, 'game_type', $gameType);

				$this->utils->playerTrackingEvent($playerId, 'TRACKINGEVENT_SOURCE_TYPE_PLAY', $track_post_data);
			}
		}
	}
}
////END OF FILE/////////