<?php

defined("BASEPATH") OR exit("No direct script access allowed");


class Migration_add_table_sa_gaming_seamless_thb1_game_logs_20200423 extends CI_Migration
{
    private $tableName = "sa_gaming_seamless_thb1_game_logs";

    public function up() {
		$fields = array(
			'id' => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
			),
			'PlayerId' => array(
				'type' => 'INT',               
                'null' => true,                
			),
			'BetTime' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'PayoutTime' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'Username' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'HostID' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'GameID' => array(
				'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true,
			),
			'Round' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'Set' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'BetID' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'BetAmount' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'ResultAmount' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'Balance' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'GameType' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'BetType' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'BetSource' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'State' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
			),
			'Detail' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
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
            'extGameCode' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
            ),
            'extra' => array(
                'type' => 'text',
                'null' => true,
            ),
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ),
		);

        if(! $this->utils->table_really_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # add index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName,"idx_extGameCode","extGameCode");
            $this->player_model->addUniqueIndex($this->tableName,"idx_BetID","BetID");
            $this->player_model->addUniqueIndex($this->tableName,"idx_external_uniqueid","external_uniqueid");
            
        }
	}

	public function down() {
		if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
	}
}