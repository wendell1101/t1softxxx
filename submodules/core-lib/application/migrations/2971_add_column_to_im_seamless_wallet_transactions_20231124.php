<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_im_seamless_wallet_transactions_20231124 extends CI_Migration {

    private $tableName = 'im_seamless_wallet_transactions';

    public function up() {
        $field = array(
            'settled_at' => array(
                'type' => 'DATETIME',
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('settled_at', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('settled_at', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'settled_at');
            }
        }
    }
}