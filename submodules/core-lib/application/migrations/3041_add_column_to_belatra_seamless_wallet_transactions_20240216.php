<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_belatra_seamless_wallet_transactions_20240216 extends CI_Migration {

    private $tableName = 'belatra_seamless_wallet_transactions';

    public function up() {
        $field = array(
            'request_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true
            ),
            'player_id' => array(
                'type' => 'INT',
                // 'constraint' => '32',
                'null' => true
            ),
            'original_action_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('request_id', $this->tableName) && !$this->db->field_exists('player_id', $this->tableName) && !$this->db->field_exists('original_action_id', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_original_action_id', 'original_action_id');
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('request_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'request_id');
            }
            if($this->db->field_exists('player_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'player_id');
            }
            if($this->db->field_exists('original_action_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'original_action_id');
            }
        }
    }
}