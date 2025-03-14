<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_created_at_and_updated_at_to_all_game_logs_20160211 extends CI_Migration {

	// private $game_logs = array('ab_game_logs','ag_game_logs','aghg_game_logs','agin_game_logs','agpt_game_logs','agshaba_game_logs','beteast_game_logs','bs_game_logs','crown_game_logs','ctxm_game_logs', 'ebet_game_logs','ebet2_game_logs','entwine_game_logs','fg_game_logs','fishinggame_game_logs','gameplay_game_logs','gamesos_game_logs','gd_game_logs','gsag_game_logs','gsmg_game_logs','gspt_game_logs','haba88_game_logs','hrcc_game_logs','ibc_game_logs','impt_game_logs','imslots_game_logs','inteplay_game_logs','isb_game_logs','kenogame_game_logs','lb_game_logs','nt_game_logs','one88_game_logs','onesgame_game_logs','oneworks_game_logs','opus_game_logs','pt_game_logs','qt_game_logs','seven77_game_logs','ttg_game_logs','uc_game_logs','wft_game_logs','xhtdlottery_game_logs');


	// private $created_at_only_game_logs = array('mg_game_logs');

	// private $updated_at_only_to_game_logs = array('bbin_game_logs',);
	public function up() {
		// foreach ($this->game_logs as $tableName) {
		// 	$this->dbforge->add_column($tableName, array(
		// 		'created_at' => array(
		// 			'type' => 'DATETIME',
		// 			'null' => true,
		// 		),
		// 		'updated_at' => array(
		// 			'type' => 'DATETIME',
		// 			'null' => true,
		// 		),
		// 	));
		// }
		// foreach ($this->updated_at_only_to_game_logs as $updated_at) {
		// 	$this->dbforge->add_column($updated_at, array(
		// 		'created_at' => array(
		// 			'type' => 'DATETIME',
		// 			'null' => true,
		// 		),
		// 	));
		// 	
		// 	foreach ($this->created_at_only_to_game_logs as $created_at) {
		// 	$this->dbforge->add_column($created_at, array(
		// 		'updated_at' => array(
		// 			'type' => 'DATETIME',
		// 			'null' => true,
		// 		),
		// 	));
		// }

	}

	public function down() {
		// foreach ($this->game_logs as $tableName) {
		// 	$this->dbforge->drop_column($tableName, 'created_at');
		// }
		// foreach ($this->game_logs as $tableName) {
		// 	$this->dbforge->drop_column($tableName, 'updated_at');
		// }

		// foreach ($this->updated_at_only_to_game_logs as $updated_at) {
		// 	$this->dbforge->drop_column($updated_at, 'created_at');
		// }
		// 
		// foreach ($this->created_at_only_to_game_logs as $created_at) {
		// 	$this->dbforge->drop_column($created_at, 'updated_at');
		// }
	}
}
