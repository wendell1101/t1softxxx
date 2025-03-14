<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_ip_address_to_ab_game_logs_20210624 extends CI_Migration {

	private $tableName = 'ab_game_logs';

    public function up() {
        $fields = array(
            'ip' => array(
                'type' => 'varchar',
                'constraint' => 100,
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('ip', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('ip', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'ip');
            }
        }
        
    }
}

////END OF FILE////