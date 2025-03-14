<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_game_tags_20240720 extends CI_Migration {
	private $tableName = 'game_tags';

	public function up() {
		$fields = array(
            'flag_show_in_site' => array(
                'type' => 'boolean',
                'default' => 0,
				'null' => true,
            ),
            'game_tag_order' => array(
                'type' => 'INT',
                'default' => 0,
                'null' => true,
            ),
        );

		$this->load->model('player_model');
		if($this->utils->table_really_exists($this->tableName)){
			if(!$this->db->field_exists('flag_show_in_site', $this->tableName) && !$this->db->field_exists('game_tag_order', $this->tableName)){
				$this->dbforge->add_column($this->tableName, $fields);
			}
		}
	}

	public function down() {
		if($this->utils->table_really_exists($this->tableName)){
			if($this->db->field_exists('flag_show_in_site', $this->tableName)){
				$this->dbforge->drop_column($this->tableName, 'flag_show_in_site');
			}

			if($this->db->field_exists('game_tag_order', $this->tableName)){
				$this->dbforge->drop_column($this->tableName, 'game_tag_order');
			}
		}
	}
}
