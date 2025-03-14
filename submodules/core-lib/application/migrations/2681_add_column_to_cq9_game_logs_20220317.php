<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_cq9_game_logs_20220317 extends CI_Migration {
    private $tableName = 'cq9_game_logs';

    public function up() {
        $field = array(
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            )
        );

        if($this->utils->table_really_exists($this->tableName)) {
            $this->load->model('player_model');
            if(!$this->db->field_exists('md5_sum', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $field);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)) {
            if($this->db->field_exists('md5_sum', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'md5_sum');
            }
        }
    }
}
