<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_score_rank_20220414 extends CI_Migration {

    private $tableName = 'score_rank';

    public function up() {
        $fields = array(
            'rank_key' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('rank_key', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
            if($this->db->field_exists('rank_key', $this->tableName)){                                
                $this->player_model->addIndex($this->tableName,'idx_rank_key','rank_key');
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('rank_key', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'rank_key');
            }
        }
    }
}