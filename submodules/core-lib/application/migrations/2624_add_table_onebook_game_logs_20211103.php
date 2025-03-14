<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_onebook_game_logs_20211103 extends CI_Migration 
{

	private $tableName = 'onebook_game_logs';

	public function up() 
	{
        if(!$this->utils->table_really_exists($this->tableName))
		{
			$this->db->query('CREATE TABLE `onebook_game_logs` LIKE `onebook_thb1_game_logs`');
	    }
	}

	public function down() 
	{
		
	}
}