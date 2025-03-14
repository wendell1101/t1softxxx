<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_player_login_via_same_ip_logs_20211101 extends CI_Migration
{

    private $tableName = "player_login_via_same_ip_logs";

    public function up()
    { //  id, ip, logged_in_at, username, login_result, player_id, tag_id,  tagged_name, create_at, updated_at
        $fields = array(
            "id" => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ),
            'ip' => array(
                'type' => 'VARCHAR',
                'null' => true,
                'constraint' => 50,
            ),
            "logged_in_at" => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'username' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => false,
			),
            'login_result' => array(
                'type' => 'INT', // 1: success; 0: failed
                'null' => true,
            ),
            'player_id' => array(
				'type' => 'INT',
				'null' => false,
            ),
            'tag_id' => array(
				'type' => 'INT',
				'null' => false,
            ),
            'tagged_name' => array(
                'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            ),
            'create_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
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

            // # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_create_at', 'create_at');
            $this->player_model->addIndex($this->tableName, 'idx_logged_in_at', 'logged_in_at');
            $this->player_model->addIndex($this->tableName, 'idx_username', 'username');
            $this->player_model->addIndex($this->tableName, 'idx_tag_id', 'tag_id');
            $this->player_model->addIndex($this->tableName, 'idx_ip', 'ip');
        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}