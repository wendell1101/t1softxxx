<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_affiliatepayment_20220510 extends CI_Migration {

    private $tableName = 'affiliatepayment';

    public function up() {

        $field = array(
            "banktype_id" => [
                "type" => "INT",
                "null" => true
            ],
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('banktype_id', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);

                # add Index
                $this->load->model('player_model');
                $this->player_model->addIndex($this->tableName,'idx_banktype_id','banktype_id');

                // migration_version=2695, score_rank.rank_key
                $this->player_model->addIndex('score_rank','idx_rank_key','rank_key');
                // migration_version=2699, score_rank.playerpromoId
                $this->player_model->addIndex('score_rank','idx_playerpromoId','playerpromoId');
                // migration_version=2696, total_score.rank_name
                $this->player_model->addIndex('total_score','idx_rank_name','rank_name');

            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('banktype_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'banktype_id');
            }
        }
    }
}