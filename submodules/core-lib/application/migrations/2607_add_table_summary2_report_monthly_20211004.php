<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_summary2_report_monthly_20211004 extends CI_Migration {

    private $tableName = 'summary2_report_monthly';

    public function up() {
        $fields = array(
            'id' => array(
                'type' => 'BIGINT',
                'auto_increment' => TRUE,
                'unsigned' => TRUE,
            ),
            'summary_trans_year_month' => array(
                'type' => 'VARCHAR',
                'constraint' => '6',
                'null' => true,
            ),
            'count_new_player' => array(
                'type' => 'INT',
                'null' => true,
                'default' => 0,
            ),
            'count_all_players' => array(
                'type' => 'INT',
                'null' => true,
                'default' => 0,
            ),
            'count_first_deposit' => array(
                'type' => 'INT',
                'null' => true,
                'default' => 0,
            ),
            'count_second_deposit' => array(
                'type' => 'INT',
                'null' => true,
                'default' => 0,
            ),
            'total_deposit' => array(
                'type' => 'DOUBLE',
                'null' => true,
                'default' => 0.00,
            ),
            'total_withdrawal' => array(
                'type' => 'DOUBLE',
                'null' => true,
                'default' => 0.00,
            ),
            'total_bonus' => array(
                'type' => 'DOUBLE',
                'null' => true,
                'default' => 0.00,
            ),
            'total_cashback' => array(
                'type' => 'DOUBLE',
                'null' => true,
                'default' => 0.00,
            ),
            'total_fee' => array(
                'type' => 'DOUBLE',
                'null' => true,
                'default' => 0.00,
            ),
            'total_player_fee' => array(
                'type' => 'DOUBLE',
                'null' => true,
                'default' => 0.00,
            ),
            'total_bank_cash_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
                'default' => 0.00,
            ),
            'total_bet' => array(
                'type' => 'DOUBLE',
                'null' => true,
                'default' => 0.00,
            ),
            'total_win' => array(
                'type' => 'DOUBLE',
                'null' => true,
                'default' => 0.00,
            ),
            'total_loss' => array(
                'type' => 'DOUBLE',
                'null' => true,
                'default' => 0.00,
            ),
            'total_payout' => array(
                'type' => 'DOUBLE',
                'null' => true,
                'default' => 0.00,
            ),
            'last_update_time' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'currency_key' => array(
                'type' => 'VARCHAR',
                'constraint' => '5',
                'null' => true,
            ),
            'unique_key' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
            'count_deposit_member' => array(
                'type' => 'INT',
                'null' => true,
                'default' => 0,
            ),
            'count_active_member' => array(
                'type' => 'INT',
                'null' => true,
                'default' => 0,
            ),
        );

        if(!$this->utils->table_really_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_summary_trans_year_month', 'summary_trans_year_month');
            $this->player_model->addIndex($this->tableName, 'idx_unique_key', 'unique_key', true);
        }

    }

    public function down() {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
