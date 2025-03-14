<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_walletaccount_additional_20210805 extends CI_Migration
{

    private $tableName = "walletaccount_additional";

    public function up()
    {
        $fields = array(
            "id" => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ),

            "walletAccountId" => array(
                "type" => "INT",
                "null" => false
            ),

            "vip_level_info" => array(
                "type" => "JSON",
                "null" => true
            ),
            "transactionCode" => array(
                "type" => "VARCHAR",
                "constraint" => "64",
                "null" => true
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

            // # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName, 'idx_walletAccountId', 'walletAccountId');
            $this->player_model->addIndex($this->tableName, 'idx_transactionCode', 'transactionCode');
        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}