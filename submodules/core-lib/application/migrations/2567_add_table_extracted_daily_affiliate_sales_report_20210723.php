<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_extracted_daily_affiliate_sales_report_20210723 extends CI_Migration
{

    private $tableName = "extracted_daily_affiliate_sales_report";

    public function up()
    {
        $fields = array(
            "id" => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ),

            // Date fields
            "settled_date" => array(
                "type" => "DATE",
                "null" => true
            ),

            // Turnover fields
            "nancy_turnover" => array( // #1
                "type" => "DOUBLE",
                "null" => true
            ),
            "susan_turnover" => array( // #2
                "type" => "DOUBLE",
                "null" => true
            ),
            "jc_turnover" => array( // #3
                "type" => "DOUBLE",
                "null" => true
            ),
            "kong_turnover" => array( // #4
                "type" => "DOUBLE",
                "null" => true
            ),
            "henry_turnover" => array( // #5
                "type" => "DOUBLE",
                "null" => true
            ),
            "kamil_turnover" => array( // #6
                "type" => "DOUBLE",
                "null" => true
            ),
            "others_turnover" => array( // #7
                "type" => "DOUBLE",
                "null" => true
            ),

            // Profit fields
            "nancy_profit" => array( // #1
                "type" => "DOUBLE",
                "null" => true
            ),
            "susan_profit" => array( // #2
                "type" => "DOUBLE",
                "null" => true
            ),
            "jc_profit" => array( // #3
                "type" => "DOUBLE",
                "null" => true
            ),
            "kong_profit" => array( // #4
                "type" => "DOUBLE",
                "null" => true
            ),
            "henry_profit" => array( // #5
                "type" => "DOUBLE",
                "null" => true
            ),
            "kamil_profit" => array( // #6
                "type" => "DOUBLE",
                "null" => true
            ),
            "others_profit" => array( // #7
                "type" => "DOUBLE",
                "null" => true
            ),

            // Number of First Deposit fields
            "nancy_number_of_first_deposit" => array( // #1
                "type" => "DOUBLE",
                "null" => true
            ),
            "susan_number_of_first_deposit" => array( // #2
                "type" => "DOUBLE",
                "null" => true
            ),
            "jc_number_of_first_deposit" => array( // #3
                "type" => "DOUBLE",
                "null" => true
            ),
            "kong_number_of_first_deposit" => array( // #4
                "type" => "DOUBLE",
                "null" => true
            ),
            "henry_number_of_first_deposit" => array( // #5
                "type" => "DOUBLE",
                "null" => true
            ),
            "kamil_number_of_first_deposit" => array( // #6
                "type" => "DOUBLE",
                "null" => true
            ),
            "others_number_of_first_deposit" => array( // #7
                "type" => "DOUBLE",
                "null" => true
            ),

            // Number of Active Players fields
            "nancy_number_of_active_players" => array( // #1
                "type" => "DOUBLE",
                "null" => true
            ),
            "susan_number_of_active_players" => array( // #2
                "type" => "DOUBLE",
                "null" => true
            ),
            "jc_number_of_active_players" => array( // #3
                "type" => "DOUBLE",
                "null" => true
            ),
            "kong_number_of_active_players" => array( // #4
                "type" => "DOUBLE",
                "null" => true
            ),
            "henry_number_of_active_players" => array( // #5
                "type" => "DOUBLE",
                "null" => true
            ),
            "kamil_number_of_active_players" => array( // #6
                "type" => "DOUBLE",
                "null" => true
            ),
            "others_number_of_active_players" => array( // #7
                "type" => "DOUBLE",
                "null" => true
            ),

            // Summary fields
            "summary_total_turnover" => array( // #1
                "type" => "DOUBLE",
                "null" => true
            ),
            "summary_total_profit" => array( // #2
                "type" => "DOUBLE",
                "null" => true
            ),
            "summary_total_number_of_first_deposits" => array( // #3
                "type" => "DOUBLE",
                "null" => true
            ),
            "summary_active_total" => array( // #4
                "type" => "DOUBLE",
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
            $this->player_model->addIndex($this->tableName, 'idx_settled_date', 'settled_date');

            // $this->player_model->addIndex($this->tableName, 'idx_tag_id', 'tag_id');
        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}