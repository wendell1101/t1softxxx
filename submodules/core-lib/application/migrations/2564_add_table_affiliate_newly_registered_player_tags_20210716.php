<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_affiliate_newly_registered_player_tags_20210716 extends CI_Migration
{

    private $tableName = "affiliate_newly_registered_player_tags";

    public function up()
    {
        $fields = array(
            "id" => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ),
            "affiliate_id" => array(
                "type" => "INT",
                "null" => true
            ),
            "tag_id" => array(
                "type" => "INT",
                "null" => true
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false,
            )
        );

        if(! $this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName, 'idx_affiliate_id', 'affiliate_id');

            $this->player_model->addIndex($this->tableName, 'idx_tag_id', 'tag_id');
        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}