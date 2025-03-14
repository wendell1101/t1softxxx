<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_kyc_status_20220920 extends CI_Migration
{
	private $tableName = 'kyc_status';


    public function up() {
        $this->load->model('player_model');

        /// kyc_status
        $fields = array(
            'order_id' => array(
                'type' => 'INT',
                'null' => true,
                'default' => 0
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){

            if(!$this->db->field_exists('order_id', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }

            $this->db->trans_start();

            $data = array(
                array(
                    'rate_code' => 'LOW',
                    'order_id' => '1',
                ),
                array(
                    'rate_code' => 'MED',
                    'order_id' => '2',
                ),
                array(
                    'rate_code' => 'HIG',
                    'order_id' => '3',
                ),
                array(
                    'rate_code' => 'VER',
                    'order_id' => '4',
                ),
                array(
                    'rate_code' => 'A',
                    'order_id' => '1',
                ),
                array(
                    'rate_code' => 'B',
                    'order_id' => '2',
                ),
                array(
                    'rate_code' => 'C',
                    'order_id' => '3',
                ),
                array(
                    'rate_code' => 'D',
                    'order_id' => '4',
                ),
            );

            $this->db->update_batch($this->tableName, $data, 'rate_code');

            $this->db->trans_complete();

        }

    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('order_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'order_id');
            }
        }



    }
}