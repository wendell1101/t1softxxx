<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_tables_hedging_total_for_ag_20201222 extends CI_Migration {

	private $tableName4detail_info = 'hedging_total_detail_info';
	private $tableName4player = 'hedging_total_detail_player';

	public function up() {

		$fields4detail_info = array(
			'id'			=> [ 'type' => 'BIGINT', 'null' => false, 'auto_increment' => TRUE, ],

			'table_id'		=> [ 'type' => 'VARCHAR', 'constraint' => 32, 'null' => true ] ,
			'contnet_id'	=> [ 'type' => 'VARCHAR', 'constraint' => 64, 'null' => true ] ,
			'members'	=> [ 'type' => 'VARCHAR', 'constraint' => 255, 'null' => true ] ,

			'banker'		=> [ 'type' => 'VARCHAR', 'constraint' => 255, 'null' => true ] ,
			'player'	=> [ 'type' => 'VARCHAR', 'constraint' => 255, 'null' => true ] ,

			'dragon'	=> [ 'type' => 'VARCHAR', 'constraint' => 255, 'null' => true ] ,
			'tiger'		=> [ 'type' => 'VARCHAR', 'constraint' => 255, 'null' => true ] ,

			'big'		=> [ 'type' => 'VARCHAR', 'constraint' => 255, 'null' => true ] ,
			'small'		=> [ 'type' => 'VARCHAR', 'constraint' => 255, 'null' => true ] ,

			'sic_bo_odd'	=> [ 'type' => 'VARCHAR', 'constraint' => 255, 'null' => true ] ,
			'sic_bo_even'	=> [ 'type' => 'VARCHAR', 'constraint' => 255, 'null' => true ] ,

			'red' 		=> [ 'type' => 'VARCHAR', 'constraint' => 255, 'null' => true ] ,
			'black'		=> [ 'type' => 'VARCHAR', 'constraint' => 255, 'null' => true ] ,

			'roulette_odd' 	=> [ 'type' => 'VARCHAR', 'constraint' => 255, 'null' => true ] ,
			'roulette_even'		=> [ 'type' => 'VARCHAR', 'constraint' => 255, 'null' => true ] ,

			'hedge_difference' 	=> [ 'type' => 'DOUBLE', 'null' => false ] ,
			'hedge_index'		=> [ 'type' => 'DOUBLE', 'null' => false ] ,
			'hedge_spicious'	=> [ 'type' => 'INT', 'null' => false ] ,

			'md5sum' => [ 'type' => 'VARCHAR', 'constraint' => 32, 'null' => true ] ,

			'created_at'	=> [ 'type' => 'DATETIME', 'null' => false ] ,
			'updated_at'	=> [ 'type' => 'DATETIME', 'null' => true ] ,
		);

		if ( ! $this->db->table_exists($this->tableName4detail_info) ) {
			$this->dbforge->add_field($fields4detail_info);
			$this->dbforge->add_key('id', TRUE);

			$this->dbforge->create_table($this->tableName4detail_info);
			// Add Index
	        $this->load->model('player_model');
			$this->player_model->addIndex($this->tableName4detail_info, 'hedging_total_detail_info_table_id', 'table_id');
			$this->player_model->addIndex($this->tableName4detail_info, 'hedging_total_detail_info_md5sum', 'md5sum'); // by the row

			$this->player_model->addIndex($this->tableName4detail_info, 'hedging_total_detail_info_created_at', 'created_at');
			$this->player_model->addIndex($this->tableName4detail_info, 'hedging_total_detail_info_updated_at', 'updated_at');
		}


		$fields4player = array(
			'id'			=> [ 'type' => 'BIGINT', 'null' => false, 'auto_increment' => TRUE, ],

			'table_id'		=> [ 'type' => 'VARCHAR', 'constraint' => 32, 'null' => true ] ,

			/**
			 *
			 * player_username mapping to player.playerId
			 * The example: CI2x89kp1388
			 *
			 * CI2 > 總線名稱
			 * x89 > 客戶前墜
			 * kp1388 > 玩家帳號
			 *
			 */
			'player_id'	=> [ 'type' => 'BIGINT', 'null' => true ] ,

			'created_at'	=> [ 'type' => 'DATETIME', 'null' => false ] ,
			'updated_at'	=> [ 'type' => 'DATETIME', 'null' => true ] ,
		);

		if ( ! $this->db->table_exists($this->tableName4player) ) {
			$this->dbforge->add_field($fields4player);
			$this->dbforge->add_key('id', TRUE);

			$this->dbforge->create_table($this->tableName4player);
			// Add Index
	        $this->load->model('player_model');
			$this->player_model->addIndex($this->tableName4player, 'hedging_total_player_table_id', 'table_id');
			$this->player_model->addIndex($this->tableName4player, 'hedging_total_player_player_id', 'player_id');

			$this->player_model->addIndex($this->tableName4player, 'hedging_total_player_created_at', 'created_at');
			$this->player_model->addIndex($this->tableName4player, 'hedging_total_player_updated_at', 'updated_at');
		}
	}

	public function down() {
		if($this->db->table_exists($this->tableName4detail_info)){
			$this->dbforge->drop_table($this->tableName4detail_info);
		}
		if($this->db->table_exists($this->tableName4player)){
			$this->dbforge->drop_table($this->tableName4player);
		}
	}
}
