<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_seamless_missing_payout_20220218 extends CI_Migration
{

    private $tableName = "seamless_missing_payout_report";

    public function up()
    {
        $fields = array(
            "id" => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ),
            'transaction_date' => array(
                'type' => 'DATETIME',
                'null' => true
            ),
            "transaction_type" => array(
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ),            
            "transaction_status" => array(
                "type" => "INT",
                "null" => true
            ),
            'status' => array(
                "type" => "INT",
                "null" => true
            ),


            "game_platform_id" => array(
                "type" => "INT",
                "null" => true
            ),
            "player_id" => array(
                "type" => "BIGINT",
                "null" => true
            ),
            "round_id" => array(
                "type" => "VARCHAR",
                "constraint" => "150",
                "null" => true
            ),
            "transaction_id" => array(
                "type" => "BIGINT",
                "null" => true
            ),
            "amount" => array(
                "type" => "DOUBLE",
                "null" => true
            ),
            "added_amount" => array(
                "type" => "DOUBLE",
                "null" => true
            ),     
            "deducted_amount" => array(
                "type" => "DOUBLE",
                "null" => true
            ),     

            "fixed_by" => array(
                "type" => "INT",
                "null" => true
            ),
            "game_description_id" => array(
                "type" => "INT",
                "null" => true
            ),
            "game_type_id" => array(
                "type" => "INT",
                "null" => true
            ),
            'note' => array(
                'type' => 'TEXT',
                'null' => true,
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
            )
        );

        if(! $this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName,"idx_transaction_date","transaction_date");
            $this->player_model->addIndex($this->tableName,"idx_game_platform_id","game_platform_id");
            $this->player_model->addIndex($this->tableName,"idx_player_id","player_id");
            $this->player_model->addIndex($this->tableName,"idx_round_no","round_id");
            $this->player_model->addIndex($this->tableName,"idx_transaction_id","transaction_id");
            $this->player_model->addIndex($this->tableName,"idx_transaction_type","transaction_type");
            $this->player_model->addIndex($this->tableName,"idx_updated_at","updated_at");
            $this->player_model->addIndex($this->tableName,"idx_status","status");
            $this->player_model->addIndex($this->tableName,"idx_transaction_status","transaction_status");
            $this->player_model->addUniqueIndex($this->tableName, 'idx_uniqueid', 'external_uniqueid,player_id,game_platform_id');
        }
    }

    public function down()
    {
        if($this->db->table_exist($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}