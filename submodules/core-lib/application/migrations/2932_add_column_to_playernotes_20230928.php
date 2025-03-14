<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_playernotes_20230928 extends CI_Migration {

	private $tableName = 'playernotes';

	public function up() {
		$fields = array(
			'tag_remark_id' => array(
                'type' => 'INT',
				'null' => true,
			)
		);

		if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('tag_remark_id', $this->tableName)){
				$this->dbforge->add_column($this->tableName, $fields);
				$this->load->model('player_model');
                $this->player_model->addIndex($this->tableName, 'idx_tag_remark_id','tag_remark_id');
            }
        }
	}

	public function down() {
		if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('tag_remark_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'tag_remark_id');
            }
        }
	}
}