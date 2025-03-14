<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class Migration_add_column_to_hydako_game_logs_20200909 extends CI_Migration
{
    private $tableName = 'hydako_thb1_game_logs';

    public function up()
    {
        $fields = array(
            'orig_regDate' => array(
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('orig_regDate', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down()
    {
        $this->dbforge->drop_column($this->tableName, 'orig_regDate');
    }
}
