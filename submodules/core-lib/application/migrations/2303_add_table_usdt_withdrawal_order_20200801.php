<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_usdt_withdrawal_order_20200801 extends CI_Migration
{

    private $tableName = "usdt_withdrawal_order";

    public function up()
    {
        $fields = array(
            "id" => array(
                "type" => "INT",
                "null" => false,
                "auto_increment" => true
            ),
            "wallet_account_id" => array(
                "type" => "INT",
                "null" => false
            ),
            "transfered_usdt" => array(
                "type" => "DOUBLE",
                "null" => true
            ),
            "rate" => array(
                "type" => "DOUBLE",
                "null" => true
            ),
            "created_at" => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            "updated_at" => array(
                'type' => 'DATETIME',
                'null' => true,
            )
        );

        if(! $this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName, 'idx_usdt_withdrawal_order_wallet_account_id', 'wallet_account_id', true);
        }
    }

    public function down()
    {
        if($this->db->table_exist($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}