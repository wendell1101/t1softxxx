<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_update_column_to_game_description_20240120 extends CI_Migration {

    private $tableName = 'game_description';


    public function up() {
		$this->db->query("ALTER TABLE game_description MODIFY updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
	}

    

    public function down() {
        
    }
}