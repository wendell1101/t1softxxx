<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_hogaming_seamless_idr1to7_transaction_logs_20200922 extends CI_Migration {

    private $table_idr = ['hogaming_seamless_idr1_transaction_logs','hogaming_seamless_idr2_transaction_logs','hogaming_seamless_idr3_transaction_logs','hogaming_seamless_idr4_transaction_logs','hogaming_seamless_idr5_transaction_logs','hogaming_seamless_idr6_transaction_logs','hogaming_seamless_idr7_transaction_logs'];

    public function up()
    {
        if(!empty($this->table_idr)){
            foreach ($this->table_idr as $table) {
                $field = array(
                    'related_data' => array(
                        'type' => 'json',
                        'null' => true,
                    ),
                );
                if(!$this->db->field_exists('related_data', $table)){
                    $this->dbforge->add_column($table, $field);
                }
            }
        }
    }

    public function down()
    {
        if(!empty($this->table_idr)){
            foreach ($this->table_idr as $table) {
                if($this->db->field_exists('related_data', $table)){
                    $this->dbforge->drop_column($table, 'related_data');
                }
            }
        }
    }
}