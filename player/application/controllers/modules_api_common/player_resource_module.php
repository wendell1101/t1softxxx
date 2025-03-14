<?php

/**
 * uri: /player-resource
 */
trait player_resource_module{

	public function player_resource($action, $additional=null){
		if(!$this->initApi()){
			return;
		}

		if(is_numeric($action)){

		}

		switch ($action) {
			case 'create':
				break;
		}

		$this->returnErrorWithCode(self::CODE_GENERAL_CLIENT_ERROR);
	}

}
