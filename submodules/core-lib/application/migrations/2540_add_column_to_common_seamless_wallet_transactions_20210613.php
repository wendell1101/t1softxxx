<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_common_seamless_wallet_transactions_20210613 extends CI_Migration {

    private $tableName = 'common_seamless_wallet_transactions';

    public function up() {
        $field1 = array(
            'bet_amount' => array(
                'type' => 'DOUBLE',
                'null' => true
            )
        );

        $field2 = array(
            'result_amount' => array(
                'type' => 'DOUBLE',
                'null' => true
            )
        );

        $field3 = array(
            'flag_of_updated_result' => array(
                'type' => 'BOOLEAN',
                'null' => true,
                'default' => 0,
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('bet_amount', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field1);
            }
            if(!$this->db->field_exists('result_amount', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field2);
            }
            if(!$this->db->field_exists('flag_of_updated_result', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field3);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('bet_amount', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'bet_amount');
            }
            if($this->db->field_exists('result_amount', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'result_amount');
            }
            if($this->db->field_exists('flag_of_updated_result', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'flag_of_updated_result');
            }
        }
    }
}
