<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_crypto_currency_setting_20210728 extends CI_Migration
{

    private $tableName = "crypto_currency_setting";

    public function up()
    {
        $fields = array(
            "id" => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ),
            "crypto_currency" => array(
                'type' => 'VARCHAR',
                'constraint' => '10',
                "null" => true
            ),
            "transaction" => array(
                'type' => 'VARCHAR',
                'constraint' => '10',
                "null" => true
            ),
            'exchange_rate_multiplier' => array(
                "type" => "DOUBLE",
                'default' => 1
            ),
            "created_at" => array(
                'type' => 'DATETIME',
                'null' => true
            ),
            "update_at" => array(
                'type' => 'DATETIME',
                'null' => true
            ),
            'update_by' => array(
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => true
            ),
        );

        if(! $this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName, 'idx_crypto_currency', 'crypto_currency');
        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}