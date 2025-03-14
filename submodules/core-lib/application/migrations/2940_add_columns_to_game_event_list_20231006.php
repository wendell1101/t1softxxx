<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_columns_to_game_event_list_20231006 extends CI_Migration {

    private $tableName = 'game_event_list';

    public function up() {
        $column = array(
            'event_name' => array(
				'type' => 'VARCHAR',
				'constraint' => '200',
				'null' => false,
            ),
            'event_banner_url' => array(
				'type' => 'VARCHAR',
				'constraint' => '200',
				'null' => false,
            ),
			'pc_enable'=>array(
				'type' => 'boolean',
				'null' => false,
				'default' => true,
            ),
			'mobile_enable'=>array(
				'type' => 'boolean',
				'null' => false,
				'default' => true,
            ),
			'is_maintenance'=>array(
				'type' => 'boolean',
				'null' => false,
				'default' => false,
            ),
			'screen_mode'=>array(
				'type' => 'tinyint',
				'null' => false,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('event_name', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $column);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('event_name', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'event_name');
            }
            if($this->db->field_exists('event_banner_url', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'event_banner');
            }
            if($this->db->field_exists('pc_enable', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'event_banner');
            }
            if($this->db->field_exists('mobile_enable', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'event_banner');
            }
            if($this->db->field_exists('is_maintenance', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'event_banner');
            }
            if($this->db->field_exists('screen_mode', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'event_banner');
            }
        }
    }
}