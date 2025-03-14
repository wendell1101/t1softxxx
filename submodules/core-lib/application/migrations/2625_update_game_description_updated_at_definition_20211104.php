<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_update_game_description_updated_at_definition_20211104 extends CI_Migration
{

    private $tableName = 'game_description';

    public function up()
    {
        if($this->utils->table_really_exists($this->tableName))
        {
            if($this->db->field_exists('updated_at', $this->tableName)){
                $this->db->query('ALTER TABLE game_description MODIFY COLUMN updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP');
            }
        }
    }

    public function down()
    {

    }
}