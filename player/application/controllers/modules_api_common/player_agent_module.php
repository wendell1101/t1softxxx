<?php

/**
 * agent
 *
 * uri: /agent
 */
trait player_agent_module{

	public function agent($action=null, $additional=null, $append=null){
		if(!$this->initApi()){
			return;
		}

		if(is_numeric($action)){
			// /agents/{agentId}/players
		}

		switch ($action) {
			case 'create-player':
				break;
			case 'create-sub-agent':
				break;
			case 'tracking-codes':
				break;
			case 'tracking-domains':
				break;
			case 'settlement-report':
				break;
		}

		$this->returnErrorWithCode(self::CODE_GENERAL_CLIENT_ERROR);
	}

}
