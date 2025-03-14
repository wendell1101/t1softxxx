<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_update_entwine_game_description_20161103 extends CI_Migration {
	
	const FLAG_TRUE = 1;
	const FLAG_FALSE = 0;

	public function up() {

		$this->db->start_trans();

		$game_descriptions = array(
			array( 
				'game_platform_id' => 49, 
				'game_code' => '10001', 
				'game_name' => '_json:{"1":"Baccarat Traditional","2":"Baccarat Traditional"}', 
				'english_name' => "Baccarat Traditional", 
			), 
			array( 
				'game_platform_id' => 49, 
				'game_code' => '10002', 
				'game_name' => '_json:{"1":"Baccarat Pair","2":"Baccarat Pair"}', 
				'english_name' => "Baccarat Pair", 
			), 
			array( 
				'game_platform_id' => 49, 
				'game_code' => '20001', 
				'game_name' => '_json:{"1":"Dragon/Tiger","2":"Dragon/Tiger"}', 
				'english_name' => "Dragon/Tiger", 
			), 
			array( 
				'game_platform_id' => 49, 
				'game_code' => '30001', 
				'game_name' => '_json:{"1":"VIP Baccarat","2":"VIP Baccarat"}', 
				'english_name' => "VIP Baccarat", 
			), 
			array( 
				'game_platform_id' => 49, 
				'game_code' => '50001', 
				'game_name' => '_json:{"1":"Asian Roulette","2":"Asian Roulette"}', 
				'english_name' => "Asian Roulette", 
			), 
			array( 
				'game_platform_id' => 49, 
				'game_code' => '50002', 
				'game_name' => '_json:{"1":"International Roulette","2":"International Roulette"}', 
				'english_name' => "International Roulette", 
			), 
			array( 
				'game_platform_id' => 49, 
				'game_code' => '50003', 
				'game_name' => '_json:{"1":"Roulette","2":"Roulette"}', 
				'english_name' => "Roulette", 
			), 
			array( 
				'game_platform_id' => 49, 
				'game_code' => '60001', 
				'game_name' => '_json:{"1":"Sic Bo","2":"Sic Bo"}', 
				'english_name' => "Sic Bo", 
			), 
			array( 
				'game_platform_id' => 49, 
				'game_code' => '90001', 
				'game_name' => '_json:{"1":"Super Baccarat","2":"Super Baccarat"}', 
				'english_name' => "Super Baccarat", 
			), 
			array( 
				'game_platform_id' => 49, 
				'game_code' => '90002', 
				'game_name' => '_json:{"1":"Super 6 Baccarat","2":"Super 6 Baccarat"}', 
				'english_name' => "Super 6 Baccarat", 
			), 
			array( 
				'game_platform_id' => 49, 
				'game_code' => '90003', 
				'game_name' => '_json:{"1":"Dragon Bonus Baccarat","2":"Dragon Bonus Baccarat"}', 
				'english_name' => "Dragon Bonus Baccarat", 
			), 
			array( 
				'game_platform_id' => 49, 
				'game_code' => '90004', 
				'game_name' => '_json:{"1":"Points Baccarat","2":"Points Baccarat"}', 
				'english_name' => "Points Baccarat", 
			), 
			array( 
				'game_platform_id' => 49, 
				'game_code' => '100001', 
				'game_name' => '_json:{"1":"Blackjack","2":"Blackjack"}', 
				'english_name' => "Blackjack", 
			), 
			array( 
				'game_platform_id' => 49, 
				'game_code' => 'unknown', 
				'game_name' => '_json:{"1":"entwine.unknown","2":"entwine.unknown"}', 
				'english_name' => "entwine.unknown", 
			), 
		);

		$this->db->where('game_platform_id', ENTWINE_API);
		$this->db->update_batch('game_description', $game_descriptions, 'game_code');

		$this->db->trans_complete();
	}

	public function down() {
	}
}
