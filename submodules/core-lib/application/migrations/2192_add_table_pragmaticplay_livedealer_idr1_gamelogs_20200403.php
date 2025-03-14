<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_pragmaticplay_livedealer_idr1_gamelogs_20200403 extends CI_Migration {
	private $tableIDR = 'pragmaticplay_livedealer_idr1_gamelogs';
	private $tableCNY = 'pragmaticplay_livedealer_cny1_gamelogs';
	private $tableTHB = 'pragmaticplay_livedealer_thb1_gamelogs';
	private $tableMYR = 'pragmaticplay_livedealer_myr1_gamelogs';
	private $tableVND = 'pragmaticplay_livedealer_vnd1_gamelogs';
	private $tableUSD = 'pragmaticplay_livedealer_usd1_gamelogs';

	public function up() {
		$fields = array(
			'id' => array(
				'type' => 'BIGINT',
				'null' => false,
				'auto_increment' => TRUE,
			),
			'sbeplayerid' => array(
				'type' => 'INT',
				'constraint' => '11',
				'null' => true,
			),
			'username' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'playerid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'extplayerid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'gameid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'playsessionid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'timestamp' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'referenceid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'type' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'amount' => array(
                'type' => 'DOUBLE',
                'null' => true
			),
			'currency' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
            'related_uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'last_sync_time' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
	       'parent_session_id' => array(		
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ),
           'start_date' => array(
                'type' => 'DATETIME',
                'null' => true
            ),
            'end_date' => array(
                'type' => 'DATETIME',
                'null' => true
            ),
            'status' => array(	
                'type' => 'VARCHAR',
                'constraint' => '5',
                'null' => true
            ),
           'type_game_round' => array(	
                'type' => 'VARCHAR',
                'constraint' => '5',
                'null' => true
            ),
           'bet' => array(           
                'type' => 'DOUBLE',
                'null' => true
            ),
            'win' => array(         
                'type' => 'DOUBLE',
                'null' => true
            ),
           'jackpot' => array(        
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ),
			'external_uniqueid' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'response_result_id' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
			),
			'result_time' => array(
				'type' => 'TIMESTAMP',
				'null' => true,
			),
		);

		if(!$this->db->table_exists($this->tableIDR)){

			$this->dbforge->add_field($fields);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->create_table($this->tableIDR);

	        $this->load->model('player_model');
	        $this->player_model->addUniqueIndex($this->tableIDR, 'idx_pp_livedealer_idr1_game_logs_external_uniqueid', 'external_uniqueid');
	        $this->player_model->addIndex($this->tableIDR, 'idx_pp_livedealer_idr1_md5_sum', 'md5_sum');
	        $this->player_model->addIndex($this->tableIDR, 'idx_pp_livedealer_idr1_result_time', 'result_time');
	        $this->player_model->addIndex($this->tableIDR, 'idx_pp_livedealer_idr1_playerid', 'playerid');
		}

		if(!$this->db->table_exists($this->tableCNY)){

			$this->dbforge->add_field($fields);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->create_table($this->tableCNY);

	        $this->load->model('player_model');
	        $this->player_model->addUniqueIndex($this->tableCNY, 'idx_pp_livedealer_cny1_game_logs_external_uniqueid', 'external_uniqueid');
	        $this->player_model->addIndex($this->tableCNY, 'idx_pp_livedealer_cny1_md5_sum', 'md5_sum');
	        $this->player_model->addIndex($this->tableCNY, 'idx_pp_livedealer_cny1_result_time', 'result_time');
	        $this->player_model->addIndex($this->tableCNY, 'idx_pp_livedealer_cny1_playerid', 'playerid');
		}

		if(!$this->db->table_exists($this->tableTHB)){

			$this->dbforge->add_field($fields);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->create_table($this->tableTHB);

	        $this->load->model('player_model');
	        $this->player_model->addUniqueIndex($this->tableTHB, 'idx_pp_livedealer_thb1_game_logs_external_uniqueid', 'external_uniqueid');
	        $this->player_model->addIndex($this->tableTHB, 'idx_pp_livedealer_thb1_md5_sum', 'md5_sum');
	        $this->player_model->addIndex($this->tableTHB, 'idx_pp_livedealer_thb1_result_time', 'result_time');
	        $this->player_model->addIndex($this->tableTHB, 'idx_pp_livedealer_thb1_playerid', 'playerid');
		}

		if(!$this->db->table_exists($this->tableMYR)){

			$this->dbforge->add_field($fields);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->create_table($this->tableMYR);

	        $this->load->model('player_model');
	        $this->player_model->addUniqueIndex($this->tableMYR, 'idx_pp_livedealer_myr1_game_logs_external_uniqueid', 'external_uniqueid');
	        $this->player_model->addIndex($this->tableMYR, 'idx_pp_livedealer_myr1_md5_sum', 'md5_sum');
	        $this->player_model->addIndex($this->tableMYR, 'idx_pp_livedealer_myr1_result_time', 'result_time');
	        $this->player_model->addIndex($this->tableMYR, 'idx_pp_livedealer_myr1_playerid', 'playerid');
		}

		if(!$this->db->table_exists($this->tableVND)){

			$this->dbforge->add_field($fields);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->create_table($this->tableVND);

	        $this->load->model('player_model');
	        $this->player_model->addUniqueIndex($this->tableVND, 'idx_pp_livedealer_vnd1_game_logs_external_uniqueid', 'external_uniqueid');
	        $this->player_model->addIndex($this->tableVND, 'idx_pp_livedealer_vnd1_md5_sum', 'md5_sum');
	        $this->player_model->addIndex($this->tableVND, 'idx_pp_livedealer_vnd1_result_time', 'result_time');
	        $this->player_model->addIndex($this->tableVND, 'idx_pp_livedealer_vnd1_playerid', 'playerid');
		}

		if(!$this->db->table_exists($this->tableUSD)){

			$this->dbforge->add_field($fields);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->create_table($this->tableUSD);

	        $this->load->model('player_model');
	        $this->player_model->addUniqueIndex($this->tableUSD, 'idx_pp_livedealer_usd1_game_logs_external_uniqueid', 'external_uniqueid');
	        $this->player_model->addIndex($this->tableUSD, 'idx_pp_livedealer_usd1_md5_sum', 'md5_sum');
	        $this->player_model->addIndex($this->tableUSD, 'idx_pp_livedealer_usd1_result_time', 'result_time');
	        $this->player_model->addIndex($this->tableUSD, 'idx_pp_livedealer_usd1_playerid', 'playerid');
		}
	}

	public function down() {
		if($this->db->table_exists($this->tableIDR)){
			$this->dbforge->drop_table($this->tableIDR);
		}
		if($this->db->table_exists($this->tableCNY)){
			$this->dbforge->drop_table($this->tableCNY);
		}
		if($this->db->table_exists($this->tableTHB)){
			$this->dbforge->drop_table($this->tableTHB);
		}
		if($this->db->table_exists($this->tableMYR)){
			$this->dbforge->drop_table($this->tableMYR);
		}
		if($this->db->table_exists($this->tableVND)){
			$this->dbforge->drop_table($this->tableVND);
		}
		if($this->db->table_exists($this->tableUSD)){
			$this->dbforge->drop_table($this->tableUSD);
		}
	}
}
