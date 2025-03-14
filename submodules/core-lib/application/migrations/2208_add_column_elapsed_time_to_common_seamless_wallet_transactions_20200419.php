<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_elapsed_time_to_common_seamless_wallet_transactions_20200419 extends CI_Migration
{
	private $tableName = 'common_seamless_wallet_transactions';

    public function up() {

        $fields = array(
            'elapsed_time' => array(
                'type' => 'INT',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('elapsed_time', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if( $this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('elapsed_time', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'elapsed_time');
            }
        }
    }
}