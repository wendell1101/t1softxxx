<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_betgames_game_logs_20200804 extends CI_Migration
{

    private $tableName = "betgames_game_logs";

    public function up()
    {
        $fields = array(
            "id" => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ),
            "player_name" => array(
                "type" => "INT",
                "null" => false
            ),
            "method" => array(
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => false
            ),
            "amount" => array(
                "type" => "DOUBLE",
                "null" => true
            ),
            'result_amount' => array(
                "type" => "DOUBLE",
                "null" => true,
            ),
            "currency" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true
            ),
            "bet_id" => array(
                "type" => "BIGINT",
                "null" => true
            ),
            "transaction_id" => array(
                "type" => "BIGINT",
                "null" => true
            ),
            "promo_transaction_id" => array(
                "type" => "BIGINT",
                "null" => true
            ),
            "retrying" => array(
                "type" => "TINYINT",
                "constraint" => "11",
                "null" => true,
            ),
            "bet" => array(
                "type" => "VARCHAR",
                "constraint" => "360",
                "null" => true,
            ),
            "bet_type" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true,
            ),
            "type" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true,
            ),
            'odd' => array(
                "type" => "DOUBLE",
                "null" => true,
            ),
            'bet_time' => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true,
            ),
            "game" => array(
                "type" => "VARCHAR",
                "constraint" => "300",
                "null" => true,
            ),
            "draw_code" => array(
                "type" => "DOUBLE",
                "null" => true,
            ),
            "draw_time" => array(
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true,
            ),
            "subscription_id" => array(
                "type" => "DOUBLE",
                "null" => true,
            ),
            "subscription_time" => array(
                "type" => "VARCHAR",
                "constraint" => "30",
                "null" => true,
            ),
            "combination_id" => array(
                "type" => "DOUBLE",
                "null" => true,
            ),
            "combination_time" => array(
                "type" => "DOUBLE",
                "null" => true,
            ),
            "is_mobile" => array(
                "type" => "DOUBLE",
                "null" => true,
            ),
            "action" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true,
            ),
            "before_balance" => array(
                "type" => "DOUBLE",
                "null" => true,
            ),
            "after_balance" => array(
                "type" => "DOUBLE",
                "null" => true,
            ),
            "start_at" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true,
            ),
            "end_at" => array(
                "type" => "VARCHAR",
                "constraint" => "60",
                "null" => true,
            ),


            # SBE additional info
            "response_result_id" => array(
                "type" => "INT",
                "null" => true
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
            ),
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            )
        );

        if(! $this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName,"idx_player_name","player_name");
            $this->player_model->addIndex($this->tableName,"idx_bet_time","bet_time");
            $this->player_model->addIndex($this->tableName,"idx_draw_time","draw_time");
            $this->player_model->addIndex($this->tableName,"idx_subscription_time","subscription_time");
            $this->player_model->addIndex($this->tableName,"idx_combination_time","combination_time");
            $this->player_model->addIndex($this->tableName,"idx_transaction_id","transaction_id");
            $this->player_model->addUniqueIndex($this->tableName,"idx_external_uniqueid","external_uniqueid");
        }
    }

    public function down()
    {
        if($this->db->table_exist($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}