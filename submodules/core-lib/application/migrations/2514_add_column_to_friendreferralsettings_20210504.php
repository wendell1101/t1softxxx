<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_friendreferralsettings_20210504 extends CI_Migration {

    private $tableName = 'friendreferralsettings';

    public function up() {
        $fields = array(
            'referrerDepositCount' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => 0
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('referrerDepositCount', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('referrerDepositCount', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'referrerDepositCount');
            }
        }
    }
}