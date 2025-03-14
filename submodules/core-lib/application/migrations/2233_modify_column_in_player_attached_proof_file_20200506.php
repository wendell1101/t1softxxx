<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_in_player_attached_proof_file_20200506 extends CI_Migration {

    private $tableName = 'player_attached_proof_file';

    public function up() {
        $fields = array(
            'file_name' => array(
                'type' => 'varchar',
                'constraint'=> '100',
                'null' => true,
            ),
        );
        $this->dbforge->modify_column($this->tableName, $fields);

    }

    public function down() {
        $fields = array(
            'file_name' => array(
                'type' => 'varchar',
                'constraint'=> '25',
                'null' => true,
            ),
        );
        $this->dbforge->modify_column($this->tableName, $fields);
    }
}