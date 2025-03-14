<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_deletedOn_and_isDeleted_on_playertag_20200826 extends CI_Migration {

    private $tableName = 'playertag';

    public function up() {

        $fields = array(
            'deletedAt' => array(
                "type" => "DATETIME",
                "null" => true
            ),
            'isDeleted' => array(
                'type' => 'BOOLEAN',
                'null' => false,
                'default' => 0,
            ),
        );

        if(!$this->db->field_exists('deletedAt', $this->tableName) && !$this->db->field_exists('isDeleted', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('deletedAt', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'deletedAt');
        }
        if($this->db->field_exists('isDeleted', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'isDeleted');
        }
    }
}