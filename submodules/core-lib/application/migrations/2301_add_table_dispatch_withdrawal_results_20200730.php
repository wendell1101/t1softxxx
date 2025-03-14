<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_dispatch_withdrawal_results_20200730 extends CI_Migration
{

    private $tableName4results = "dispatch_withdrawal_results";

    public function up()
    {

        // for dispatch_withdrawal_conditions_included_game_description
        $fields4results = array(
            'id' => array(
				'type' => 'INT',
				'unsigned' => TRUE,
				'auto_increment' => TRUE,
			),
            "wallet_account_id" => array(  // F.K. walletaccount.walletAccountId
                "type" => "BIGINT",
                "null" => false
            ),
            "definition_id" => array(  // F.K. dispatch_withdrawal_definition.id
                "type" => "BIGINT",
                "null" => false
            ),
            "definition_results" => array(
                "type" => "JSON",
                "null" => true
            ),
            "result_dw_status" => array(
                "type" => "varchar",
                "constraint" => "255",
                "null" => true
            ),
            "dispatch_order" => array(
                'type' => 'INT',
                "null" => true
            ),

            "definition2dw_status" => array(
                "type" => "varchar",
                "constraint" => "255",
                "null" => true
            ),
            "after_status" => array(
                "type" => "varchar",
                "constraint" => "255",
                "null" => true
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
        );

        if( ! $this->db->table_exists($this->tableName4results) ){
            $this->dbforge->add_field($fields4results);
            $this->dbforge->add_key("id",true); // for P.K.
            $this->dbforge->create_table($this->tableName4results);

            # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName4results,"idx_dispatch_withdrawal_results_definition_id","definition_id");
            $this->player_model->addIndex($this->tableName4results,"idx_dispatch_withdrawal_results_wallet_account_id","wallet_account_id");
        }

    }

    public function down()
    {
        if($this->db->table_exist($this->tableName4results)){
            $this->dbforge->drop_table($this->tableName4results);
        }
    }
}