<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_to_sagaming_game_logs_20241106 extends CI_Migration {
    private $tableName = 'sagaming_game_logs';

    public function up()
    {
        $this->load->model('player_model');

        // Check if table exists and fields are present before adding indexes
        if ($this->utils->table_really_exists($this->tableName)) {
            $fields = ['created_at', 'updated_at'];

            foreach ($fields as $field) {
                if ($this->db->field_exists($field, $this->tableName)) {
                    $this->player_model->addIndex($this->tableName, 'idx_' . $field, $field);
                }
            }
        }
    }

    public function down()
    {
        
    }
}