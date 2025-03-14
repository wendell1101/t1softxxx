<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_im_seamless_wallet_transactions_20231108 extends CI_Migration {

    private $tableName = 'im_seamless_wallet_transactions';

    public function up() {
        $field = array(
            'converted_amount' => array(
                'type' => 'double',
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('converted_amount', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('converted_amount', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'converted_amount');
            }
        }
    }
}