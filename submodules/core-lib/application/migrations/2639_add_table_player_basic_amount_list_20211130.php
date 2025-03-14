<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_player_basic_amount_list_20211130 extends CI_Migration
{

    private $tableName = "player_basic_amount_list";

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            "player_username" => array(
                "type" => "VARCHAR",
                'constraint' => '50',
                'null' => true,
            ),
            'total_deposit_amount' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
            'total_bet_amount' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false,
            )
        );

        if(! $this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName);

            // add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName, 'idx_player_username', 'player_username');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');

        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}