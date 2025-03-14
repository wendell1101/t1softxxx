<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_ebet_seamless_wallet_transactions_20220914 extends CI_Migration {

    private $tableName = 'ebet_seamless_wallet_transactions';

    public function up() {
        $fields = [
            'response_status' => array(
                'type' => 'INT',
                'null' => true
            ),
            'refund_money' => array(
                'type' => 'DOUBLE',
                'null' => true
            ),

        ];

        if(!$this->db->field_exists('response_status', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('response_status', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'seqNo');
        }
    }
}