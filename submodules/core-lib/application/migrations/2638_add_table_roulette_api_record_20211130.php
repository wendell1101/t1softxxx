<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_roulette_api_record_20211130 extends CI_Migration
{

    private $tableName = "roulette_api_record";

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
            'promo_cms_id' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'bonus_amount' => array(
                'type' => 'DOUBLE',
                'null' => false,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'type' => array(
                'type' => 'TINYINT',
                'null' => false,
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
            $this->player_model->addIndex($this->tableName, 'idx_promo_cms_id', 'promo_cms_id');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_type', 'type');
        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}