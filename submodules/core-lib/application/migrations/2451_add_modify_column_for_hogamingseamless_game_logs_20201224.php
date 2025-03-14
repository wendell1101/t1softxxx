<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_modify_column_for_hogamingseamless_game_logs_20201224 extends CI_Migration {

    private $tableName = 'hogamingseamless_game_logs';

    public function up()
    {
        if ($this->utils->table_really_exists($this->tableName)) {
            $new_column = array(
                'winning_amount' => array(
                    'type' => 'double',
                    'null' => true,
                )
            );

            $modified_column = array(
                'bet_id' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '100',
                    'null' => true
                )
            );

            if (!$this->db->field_exists('winning_amount', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $new_column);
            }

            if ($this->db->field_exists('bet_id', $this->tableName)) {
                $this->dbforge->modify_column($this->tableName, $modified_column);
            }
        }
    }

    public function down()
    {
        $this->dbforge->drop_column($this->tableName, 'winning_amount');
    }
}

