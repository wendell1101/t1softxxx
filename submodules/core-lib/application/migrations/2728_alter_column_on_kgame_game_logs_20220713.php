<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_alter_column_on_kgame_game_logs_20220713 extends CI_Migration {

	private $tableName = 'kgame_game_logs';

	public function up() {

        if(!$this->db->field_exists('created_at', $this->tableName)){
            $this->dbforge->add_column($this->tableName, [
                'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                       'null' => false,
                )
            ]);
        }

        if(!$this->db->field_exists('updated_at', $this->tableName)){
            $this->dbforge->add_column($this->tableName, [
                'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                    'null' => false,
                )
            ]);
        }else{
            $this->db->query('ALTER TABLE '.$this->tableName.' MODIFY COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        }

	}

	public function down() {}
}