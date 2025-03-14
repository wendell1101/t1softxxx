<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_playercryptobankdetails_20220617 extends CI_Migration
{

    private $tableName = "playercryptobankdetails";

    public function up()
    {
        $fields = array(
            "id" => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ),
            'player_id' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'player_bank_detailsid' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'crypto_username' => array(
                'type' => 'varchar',
                'constraint' => '120',
            ),
            'crypto_email' => array(
                'type' => 'varchar',
                'constraint' => '120',
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'created_by' => array(
                'type' => 'INT',
                'null' => false,
                'default' => 0
            ),
            'updated_by' => array(
                'type' => 'TEXT',
                'null' => TRUE,
            ),
            'notes' => array(
                'type' => 'varchar',
                'constraint' => '2000',
            ),
        );

        if(! $this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName);

            // # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_player_bank_detailsid', 'player_bank_detailsid');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}