<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_sale_orders_20220107 extends CI_Migration {

    private $tableName ='sale_orders';

    public function up() {
        $fields = array(
            'player_payment_type_name' => array(
                'type' => 'VARCHAR',
                'constraint' => '1000',
                'null' => true,
            ),
        );
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('player_payment_type_name', $this->tableName)){
                $this->dbforge->modify_column($this->tableName, $fields);
            }
        }
    }
    public function down() {
    }
}

