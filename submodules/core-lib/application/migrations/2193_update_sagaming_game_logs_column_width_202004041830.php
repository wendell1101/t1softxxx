<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_update_sagaming_game_logs_column_width_202004041830 extends CI_Migration {
    public function up() {
        //modify column size
        $fields = array(
            'GameID' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true,
            ),
        );
        $this->dbforge->modify_column('sagaming_game_logs', $fields);
    }

    public function down() {
        // not able to rollback due to data truncation
    }
}
