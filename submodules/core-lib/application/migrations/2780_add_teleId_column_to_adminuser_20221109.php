<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_teleId_column_to_adminuser_20221109 extends CI_Migration {

    private $tableName = 'adminusers';

    public function up() {
        $field1 = array(
            'tele_id_2' => array(
                'type' => 'varchar(50)',
                'null' => true,
            ),
        );
        $field2 = array(
            'tele_id_3' => array(
                'type' => 'varchar(50)',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('tele_id_2', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field1);
            }
            if(!$this->db->field_exists('tele_id_3', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field2);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('tele_id_2', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'tele_id_2');
            }
            if($this->db->field_exists('tele_id_3', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'tele_id_3');
            }
        }
    }
}