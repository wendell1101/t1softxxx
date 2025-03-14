<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class Migration_add_column_to_game_description_20200828 extends CI_Migration
{
    private $tableName = 'game_description';

    public function up()
    {
        $fields = array(
            'flag_hot_game' => array(
                'type' => 'TINYINT(1)',
                'null' => true,
            ),
        );
        if(!$this->db->field_exists('flag_hot_game', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down()
    {
        $this->dbforge->drop_column($this->tableName, 'flag_hot_game');
    }
}
