<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_yggdrasil_game_logs_20220224 extends CI_Migration {
    private $tableName = 'yggdrasil_game_logs';

    public function up() {
        $field = array(
            'subreference' => array(
                'type' => 'VARCHAR',
				'constraint' => '100',
				'null' => true,
            )
        );

        $field1 = array(
            'DCGameID' => array(
                'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
            )
        );

        if($this->utils->table_really_exists($this->tableName)) {
            $this->load->model('player_model');

            if(!$this->db->field_exists('subreference', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $field);
                $this->player_model->addIndex($this->tableName, 'idx_subreference', 'subreference');
            }

            if(!$this->db->field_exists('DCGameID', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $field1);
                $this->player_model->addIndex($this->tableName, 'idx_DCGameID', 'DCGameID');
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)) {
            if($this->db->field_exists('subreference', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'subreference');
            }

            if($this->db->field_exists('DCGameID', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'DCGameID');
            }
        }
    }
}
///END OF FILE/////