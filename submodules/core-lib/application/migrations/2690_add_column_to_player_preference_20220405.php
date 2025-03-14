<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_player_preference_20220405 extends CI_Migration {

    private $tableName = 'player_preference';

    public function up() {
        $field = array(
            'disabled_withdrawal_until' => array(
                'type' => 'datetime',
                'null' => true
            )
        );

        if($this->utils->table_really_exists($this->tableName)) {
            $this->load->model('player_model');

            if(!$this->db->field_exists('disabled_withdrawal_until', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $field);
                $this->player_model->addIndex($this->tableName, 'idx_disabled_withdrawal_until', 'disabled_withdrawal_until');
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)) {
            if($this->db->field_exists('disabled_withdrawal_until', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'disabled_withdrawal_until');
            }
        }
    }
}
