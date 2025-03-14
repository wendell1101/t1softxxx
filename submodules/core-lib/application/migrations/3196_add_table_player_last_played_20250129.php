<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_player_last_played_20250129 extends CI_Migration {

    private $tableName = 'player_last_played';

    public function up()
    {
        $fields = array(
            'player_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'betting_time' => array(
                'type' => 'DATETIME',
                'null' => true
            )  
        );

        if(!$this->utils->table_really_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->create_table($this->tableName);

            # Add Index
            $this->load->model('player_model');
            # add index unique
            $this->player_model->addUniqueIndex($this->tableName, 'idx_player_id', 'player_id');
        }
    }

    public function down() {
        $this->dbforge->drop_table($this->tableName);
    }
}