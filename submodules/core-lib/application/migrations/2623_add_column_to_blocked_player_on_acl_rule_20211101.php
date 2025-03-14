<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_blocked_player_on_acl_rule_20211101 extends CI_Migration
{
    private $tableName = 'blocked_player_on_acl_rule';

    public function up()
    {
        $fields = array(
            'username' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName))
        {
            if(!$this->db->field_exists('username', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down()
    {
    }
}
