<?php

defined('BASEPATH') OR exit('No direct script access allowed');
class Migration_add_column_to_playstar_seamless_wallet_transactions_20250227 extends CI_Migration {

    private $tableName = 'idn_playstar_seamless_wallet_transactions';
	private $originalTable = 'playstar_seamless_wallet_transactions';
    
    public function up() {
        $fields = [
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ),
        ];

        if($this->utils->table_really_exists($this->originalTable)){
            if(!$this->db->field_exists('md5_sum', $this->originalTable)){
                $this->dbforge->add_column($this->originalTable, $fields);
            }
        }
        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('md5_sum', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->originalTable)){
            if($this->db->field_exists('md5_sum', $this->originalTable)){
                $this->dbforge->drop_column($this->originalTable, 'md5_sum');
            }
        }
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('md5_sum', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'md5_sum');
            }
        }
    }

}
