<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_sale_orders_20200601 extends CI_Migration {

    private $tableName = 'sale_orders';

    public function up() {
        $fields = array(
            'player_fee' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'transaction_fee' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('player_fee', $this->tableName)){
            $this->load->model('player_model');
            $this->dbforge->add_column($this->tableName, $fields, 'amount');
        }
    }

    public function down() {
        if($this->db->field_exists('player_fee', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'player_fee');
        }
    }
}