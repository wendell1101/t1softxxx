<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_update_hrcc_game_description_20161102 extends CI_Migration {
	
	const FLAG_TRUE = 1;
	const FLAG_FALSE = 0;

	public function up() {

		$this->db->start_trans();

		$game_descriptions = array(
			array( 
				'game_platform_id' => 73, 
				'game_code' => 'unknown', 
				'game_name' => '_json:{"1":"Unknown HRCC Game","2":"Unknown HRCC Game"}', 
				'english_name' => 'Unknown HRCC Game',
			),
		);

		$this->db->where('game_platform_id', HRCC_API);
		$this->db->update_batch('game_description', $game_descriptions, 'game_code');

		$this->db->trans_complete();
	}

	public function down() {
	}
}
