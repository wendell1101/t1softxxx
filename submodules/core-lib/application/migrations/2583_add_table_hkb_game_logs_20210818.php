<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_hkb_game_logs_20210818 extends CI_Migration
{

    private $tableName = "hkb_game_logs";

    public function up()
    {
        $fields = array(
            "id" => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ),
            'version_key' => array(
                'type' => 'BIGINT',
                'constraint' => '13',
                'null' => true,
            ),
            'row_id' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => true,
            ),
            'prefix' => array(
                'type' => 'VARCHAR',
                'constraint' => '3',
                'null' => true,
            ),
            'user_id' => array(
                'type' => 'INT',
                'constraint' => '10',
                'null' => true,
            ),
            'username' => array(
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true,
            ),
            'nickname' => array(
				'type' => 'VARCHAR',
				'constraint' => '6',
				'null' => true,
			),
            'status' => array(
				'type' => 'TINYINT',
				'constraint' => '4',
				'null' => true,
			),
            'trans_id' => array(
                'type' => 'INT',
                'constraint' => '10',
                'null' => true,
            ),
            'trans_time' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'winloss_time' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'period' => array(
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true,
            ),
            'game_id' => array(
                'type' => 'SMALLINT',
                'constraint' => '5',
                'null' => true,
            ),
            'winloss_amount' => array(
                'type' => 'DECIMAL',
                'constraint' => '16,2',
                'null' => true,
            ),
            'main_balance' => array(
                'type' => 'DECIMAL',
                'constraint' => '16,2',
                'null' => true,
            ),
            'game_balance' => array(
                'type' => 'DECIMAL',
                'constraint' => '16,2',
                'null' => true,
            ),
            'turn_over' => array(
                'type' => 'DECIMAL',
                'constraint' => '16,2',
                'null' => true,
            ),
            'net_turn_over' => array(
                'type' => 'DECIMAL',
                'constraint' => '16,2',
                'null' => true,
            ),
            'bet_type_id' => array(
                'type' => 'INT',
                'constraint' => '10',
                'null' => true,
            ),
            'user_ip' => array(
                'type' => 'VARCHAR',
                'constraint' => '40',
                'null' => true,
            ),
            'table_id' => array(
                'type' => 'SMALLINT',
                'constraint' => '5',
                'null' => true,
            ),
            'reward_balance' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'channel' => array(
                'type' => 'TINYINT',
                'constraint' => '3',
                'null' => true,
            ),
            'detail' => array(
                'type' => 'TEXT',
                'null' => true,
            ),
            # SBE additional info
            "response_result_id" => array(
                "type" => "INT",
                "null" => true
            ),
            "external_uniqueid" => array(
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ),
            "created_at DATETIME DEFAULT CURRENT_TIMESTAMP" => array(
                "null" => false
            ),
            "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" => array(
                "null" => false
            ),
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            )
        );

        if(!$this->utils->table_really_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model("player_model");
            $this->player_model->addUniqueIndex($this->tableName,"idx_version_key","version_key");
            $this->player_model->addUniqueIndex($this->tableName,"idx_external_uniqueid","external_uniqueid");
        }
    }

    public function down()
    {
        if($this->db->table_exist($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}