<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_columns_to_playerpromo_20231222 extends CI_Migration {

    private $tableName = 'playerpromo';

    public function up() {
        $fields = array(
            'group_name_on_created' => array(
                'type' => 'TEXT',
                'null' => true,
            ),
            'vip_level_on_created' => array( // cloned from vipsettingcashbackrule.vipLevel
                'type' => 'INT',
				'constraint' => '10',
				'null' => false,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('group_name_on_created', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('group_name_on_created', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'group_name_on_created');
                $this->dbforge->drop_column($this->tableName, 'vip_level_on_created'); // cloned from vipsettingcashbackrule.vipLevel
            }
        }
    }
}
