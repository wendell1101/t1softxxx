<?php
/**
 * @property \Utils $utils
 * @property \Player_model $player_model
 */
trait roulette_command_module {
	public function addRouletteToPlayer($r_name, $quantity, $player_id, $generate_by, $exp_at=null){
		$res = false;

		$this->load->library(['player_manager']);
		$this->load->model(['player_model']);
		$this->utils->debug_log("# addRouletteToPlayer: start: ", $r_name, $quantity, $player_id, $generate_by, $exp_at);

		try {
			$verify = $this->verifyRouletteName($r_name);
			$this->utils->debug_log("# addRouletteToPlayer: verify: ", $verify);

			if (!$verify['success']) {
				$this->utils->error_log('verify r_name failed', $verify['error_message']);
				return false;
			}

			if ($quantity <= 0) {
				$this->utils->error_log('illegal quantity', $quantity);
				return false;
			}

			$username = $this->player_model->getUsernameById($player_id);
			$check_username_exist = $this->player_model->checkUsernameExist($username);

			$this->utils->debug_log('check username exist', $check_username_exist);
			if (!$check_username_exist) {
				$this->utils->error_log('username not exist', $check_username_exist);
				return false;
			}

			$roulette_api = $this->getRouletteApi($r_name);

			$res = $roulette_api->generateAdditionalSpin($quantity, $player_id, null, null, $generate_by, $exp_at);

			$this->utils->debug_log("# addRouletteToPlayer: end: ", $res);
			
		} catch (Exception $e) {
			$message = $e->getMessage();
			$this->utils->error_log('addRouletteToPlayer failed', $message);
			return false;
		}
	}

	public function verifyRouletteName($roulette_name)
	{
        $verify_result = ['success' => true, 'error_message' => ''];

        try {
            $api_name = 'roulette_api_' . $roulette_name;
            $classExists = file_exists(strtolower(APPPATH.'libraries/roulette/'.$api_name.".php"));

            if (!$classExists) {
                $this->utils->debug_log(__METHOD__, lang('Cannot find class ' . $classExists));
                $verify_result['error_message'] = lang('Cannot find roulette.');
                $verify_result['success'] = false;
                return $verify_result;
            }

            $this->load->library('roulette/'.$api_name);
            $roulette_api = $this->$api_name;

            if (!$roulette_api) {
                $this->utils->debug_log(__METHOD__, lang('Cannot find ' . $api_name . ' api'));
                $verify_result['error_message'] = lang('Cannot find roulette.');
                $verify_result['success'] = false;
                return $verify_result;
            }
            return $verify_result;
        } catch (Exception $ex) {
            $this->utils->debug_log('============'. __METHOD__ .' APIException', $ex->getMessage());

            $verify_result['error_message'] = 'Unknown Error during '.__METHOD__;
            $verify_result['success'] = false;
            return $verify_result;
        }
	}

	public function getRouletteApi($roulette_name){
        $api_name = 'roulette_api_' . $roulette_name;
        $this->load->library('roulette/'.$api_name);
        $roulette_api = $this->$api_name;
        return $roulette_api;
    }
}