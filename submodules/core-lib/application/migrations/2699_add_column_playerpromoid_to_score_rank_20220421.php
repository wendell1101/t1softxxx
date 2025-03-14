<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_playerpromoid_to_score_rank_20220421 extends CI_Migration {

    private $tableName = 'score_rank';

    public function up() {
        $fields = array(
            'playerpromoId' => array(
                'type' => 'INT',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('playerpromoId', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
            if($this->db->field_exists('playerpromoId', $this->tableName)){                                
                $this->player_model->addIndex($this->tableName,'idx_playerpromoId','playerpromoId');
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('playerpromoId', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'playerpromoId');
            }
        }
    }
}