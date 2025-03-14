<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_columns_to_ruby_play_transactions_20200615 extends CI_Migration {

    private $tableName = 'ruby_play_transactions';

    public function up() {

        $fields = array(
            'action' => array(
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            ),
            'before_balance' => array(
                'type' => 'double',
                'null' => true,
            ),
            'after_balance' => array(
                'type' => 'double',
                'null' => true,
            ),
            'start_at' => array(
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            ),
            'end_at' => array(
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            )
        );

        if(!$this->db->field_exists($fields, $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
        $this->player_model->dropIndex('ruby_play_transactions', 'idx_transactionId');
        $this->player_model->dropIndex('ruby_play_transactions', 'idx_referenceTransactionId');
    }

    public function down() {
        if($this->db->field_exists('before_balance', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'before_balance');
        }
        if($this->db->field_exists('after_balance', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'after_balance');
        }
        if($this->db->field_exists('action', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'action');
        }
        if($this->db->field_exists('start_at', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'start_at');
        }
        if($this->db->field_exists('end_at', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'end_at');
        }
    }
}