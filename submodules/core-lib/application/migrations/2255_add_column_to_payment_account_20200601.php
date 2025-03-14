<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_payment_account_20200601 extends CI_Migration {

    private $tableName = 'payment_account';

    public function up() {
        $fields = array(
            'player_deposit_fee_percentage' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'min_player_deposit_fee' => array(
                'type' => 'DOUBLE',
                'default' => 0,
                'null' => false,
            ),
            'max_player_deposit_fee' => array(
                'type' => 'DOUBLE',
                'default' => 0,
                'null' => false,
            ),
        );

        if(!$this->db->field_exists('player_deposit_fee_percentage', $this->tableName)){
            $this->load->model('player_model');
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('player_deposit_fee_percentage', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'player_deposit_fee_percentage');
        }
        if($this->db->field_exists('max_player_deposit_fee', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'max_player_deposit_fee');
        }
        if($this->db->field_exists('min_player_deposit_fee', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'min_player_deposit_fee');
        }
    }
}