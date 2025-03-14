<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_for_friendreferralsettings_20210308 extends CI_Migration {

    private $tableName='friendreferralsettings';

    public function up() {
        $column = array(
            'registered_from' => array(
                'type' => 'date',
                'null' => true,
            )
        );

        $column2 = array(
            'registered_to' => array(
                'type' => 'date',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('registered_from', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $column);
            }
            if(!$this->db->field_exists('registered_to', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $column2);
            }
        }
    }

    public function down() {
        if($this->db->field_exists('registered_from', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'registered_from');
        }
        if($this->db->field_exists('registered_to', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'registered_to');
        }
    }
}