<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_transfer_conditions_20200518 extends CI_Migration
{
    private $tableName = 'transfer_conditions';

    public function up() {

        $fields1 = array(
            'wallet_type' => array(
                'type' => 'INT',
                'null' => true,
                'default' => 0,
            ),
        );

        $fields2 = array(
            'detail_status' => array(
                'type' => 'INT',
                'default' => 1, #default to active
            ),
        );

        $fields3 = array(
            'note' => array(
                'type' => 'TEXT',
                'null' => true,
            ),
        );


        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('wallet_type', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields1);
            }
            if(!$this->db->field_exists('detail_status', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields2);
            }
            if(!$this->db->field_exists('note', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields3);
            }
        }

    }

    public function down() {
        if( $this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('wallet_type', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'wallet_type');
            }
            if($this->db->field_exists('detail_status', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'detail_status');
            }
            if($this->db->field_exists('note', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'note');
            }
        }
    }
}