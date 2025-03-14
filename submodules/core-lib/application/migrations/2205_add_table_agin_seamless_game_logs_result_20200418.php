<?php

defined("BASEPATH") OR exit("No direct script access allowed");


class Migration_add_table_agin_seamless_game_logs_result_20200418 extends CI_Migration
{
    private $tableName = "agin_seamless_game_logs_result";

    public function up()
    {
        $fields = [
            "id" => [
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ],
            'data_type' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => false,
			),
			'game_code' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => false,
			),
			'table_code' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => false,
			),
			'begin_time' => array(
				'type' => 'DATETIME',
				'null' => false,
			),
			'close_time' => array(
				'type' => 'DATETIME',
				'null' => false,
			),
			'dealer' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => false,
			),
			'shoe_code' => array(
				'type' => 'INT',
				'null' => true,
			),
			'flag' => array(
				'type' => 'INT',
				'null' => true,
			),
			'banker_point' => array(
				'type' => 'INT',
				'null' => true,
			),
			'player_point' => array(
				'type' => 'INT',
				'null' => true,
			),
			'card_num' => array(
				'type' => 'INT',
				'null' => true,
			),
			'pair' => array(
				'type' => 'INT',
				'null' => true,
			),
			'game_type' => array(
				'type' => 'VARCHAR',
				'constraint' => '10',
				'null' => false,
			),
			'dragon_point' => array(
				'type' => 'INT',
				'null' => true,
			),
			'tiger_point' => array(
				'type' => 'INT',
				'null' => true,
			),
			'card_list' => array(
				'type' => 'TEXT',
				'null' => true,
			),
			'vid' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => false,
			),
			'platform_type' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => false,
			),
            "created_at DATETIME DEFAULT CURRENT_TIMESTAMP" => [
                "null" => false
            ],
            "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" => [
                "null" => false
            ],
        ];

        if(! $this->utils->table_really_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName);

            # add index
            $this->load->model("player_model");
			$this->player_model->addIndex($this->tableName,"idx_game_code","game_code");

        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}