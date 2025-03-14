<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_gfg_seamless_transactions_20230406 extends CI_Migration {

    private $tableName = 'gfg_seamless_transactions';

    public function up() {
        $fields = array(
            'before_lock_balance' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'after_lock_balance' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
        );
        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('before_lock_balance', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('before_lock_balance', $this->tableName)){
                $this->dbforge->drop_column('gfg_seamless_transactions', 'before_lock_balance');
            }
        }
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('after_lock_balance', $this->tableName)){
                $this->dbforge->drop_column('gfg_seamless_transactions', 'after_lock_balance');
            }
        }
    }
}
