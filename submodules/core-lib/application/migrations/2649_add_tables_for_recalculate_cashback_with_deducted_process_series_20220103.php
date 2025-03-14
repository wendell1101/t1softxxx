<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_tables_for_recalculate_cashback_with_deducted_process_series_20220103 extends CI_Migration
{
    private $table_for_wc_deducted_process = "withdraw_condition_deducted_process";
    private $table_for_recalculate_cashback = "recalculate_cashback";

    public function up()
    {
        # wc_deducted_process
        $fields_for_wc_deducted_process = array(
            "id"                    => ["type" => "BIGINT", "null" => false, "auto_increment" => true],
            "player_id"             => ['type' => 'INT', 'null' => false],
            "withdraw_condition_id" => ["type" => "INT", "null" => true],
            "before_amount"         => ["type" => "DOUBLE", "null" => false],
            "after_amount"          => ["type" => "DOUBLE", "null" => false],
            "cashback_total_date"   => ['type' => 'DATE', 'null' => false],
            "created_at DATETIME DEFAULT CURRENT_TIMESTAMP" => ['null' => false],
            "game_platform_id"      => ['type' => 'INT', 'null' => false],
            "game_type_id"          => ['type' => 'INT', 'null' => false],
            "game_description_id"   => ['type' => 'INT', 'null' => false]
        );

        if (!$this->utils->table_really_exists($this->table_for_wc_deducted_process)) {
            $this->dbforge->add_field($fields_for_wc_deducted_process);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->table_for_wc_deducted_process);

            $this->load->model('player_model');
            $this->player_model->addIndex($this->table_for_wc_deducted_process, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->table_for_wc_deducted_process, 'idx_withdraw_condition_id', 'withdraw_condition_id');
            $this->player_model->addIndex($this->table_for_wc_deducted_process, 'idx_cashback_total_date', 'cashback_total_date');
            $this->player_model->addIndex($this->table_for_wc_deducted_process, 'idx_game_platform_id', 'game_platform_id');
            $this->player_model->addIndex($this->table_for_wc_deducted_process, 'idx_game_type_id', 'game_type_id');
            $this->player_model->addIndex($this->table_for_wc_deducted_process, 'idx_game_description_id', 'game_description_id');
        }

        #recalculate cashback
        $fields_for_recalculate_cashback = [
            "id" => ["type" => "INT", "null" => false, "auto_increment" => true],
            "total_date" => ['type' => 'DATE', 'null' => false],
            "recalculate_times" => ['type' => 'INT', 'default' => 0],
            "last_recalculate_date_on" => ['type' => 'DATETIME', 'null' => true],
            "last_recalculate_by" => ['type' => 'INT', 'null' => true],
            "uniqueid" => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true]
        ];

        if(! $this->db->table_exists($this->table_for_recalculate_cashback)){
            $this->dbforge->add_field($fields_for_recalculate_cashback);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->table_for_recalculate_cashback);

            // # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->table_for_recalculate_cashback, 'idx_total_date', 'total_date');
            $this->player_model->addIndex($this->table_for_recalculate_cashback, 'idx_last_recalculate_date_on', 'last_recalculate_date_on');
            $this->player_model->addIndex($this->table_for_recalculate_cashback, 'idx_last_recalculate_by', 'last_recalculate_by');
            $this->player_model->addIndex($this->table_for_recalculate_cashback, 'idx_uniqueid', 'uniqueid');
        }

    }

    public function down(){
        if($this->db->table_exists($this->table_for_wc_deducted_process)){
            $this->dbforge->drop_table($this->table_for_wc_deducted_process);
        }
        if($this->db->table_exists($this->table_for_recalculate_cashback)){
            $this->dbforge->drop_table($this->table_for_recalculate_cashback);
        }
    }
}