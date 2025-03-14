<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_pretty_gaming_tables_20200730 extends CI_Migration
{
    private $transactionTableName = "pretty_gaming_seamless_api_transaction";
    private $transactionTableNameTHB1 = "pretty_gaming_seamless_api_transaction_thb1";
    public function up() {

        $fields1 = array(
            'before_balance' => array(
                'type' => 'DOUBLE',
                'null' => true
            ),
            'after_balance' => array(
                'type' => 'DOUBLE',
                'null' => true
            ),
        );

        if ($this->utils->table_really_exists($this->transactionTableName)) {
            if (!$this->db->field_exists('before_balance', $this->transactionTableName) && !$this->db->field_exists('after_balance', $this->transactionTableName)) {
                $this->dbforge->add_column($this->transactionTableName, $fields1);
            }
        }
        if ($this->utils->table_really_exists($this->transactionTableNameTHB1)) {
            if (!$this->db->field_exists('before_balance', $this->transactionTableNameTHB1) && !$this->db->field_exists('after_balance', $this->transactionTableNameTHB1)) {
                $this->dbforge->add_column($this->transactionTableNameTHB1, $fields1);
            }
        }

    }

    public function down() {
        if ($this->utils->table_really_exists($this->transactionTableName)) {
            if ($this->db->field_exists('before_balance', $this->transactionTableName) && $this->db->field_exists('after_balance', $this->transactionTableName)) {
                $this->dbforge->drop_column($this->transactionTableName, 'before_balance');
                $this->dbforge->drop_column($this->transactionTableName, 'after_balance');
            }
        }

        if ($this->utils->table_really_exists($this->transactionTableNameTHB1)) {
            if ($this->db->field_exists('before_balance', $this->transactionTableNameTHB1) && $this->db->field_exists('after_balance', $this->transactionTableNameTHB1)) {
                $this->dbforge->drop_column($this->transactionTableNameTHB1, 'before_balance');
                $this->dbforge->drop_column($this->transactionTableNameTHB1, 'after_balance');
            }
        }
    }
}