<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_new_game_tags_20220419 extends CI_Migration {

    private $tableName = 'game_tags';

    public function up() {

        $game_tags = array(
            "graph" => array(
                "tag_name" => '_json:{"1":"Graph","2":"Graph","3":"Graph","4":"Graph","5":"Graph"}',
                "tag_code" => "graph",
            ),
            "chess" => array(
                "tag_name" => '_json:{"1":"Chess","2":"Chess","3":"Chess","4":"Chess","5":"Chess"}',
                "tag_code" => "chess",
            ),
        );

        foreach ($game_tags as $key => $value) {
            $this->db->select('id')->where('tag_code',$key);
            $result = $this->db->get($this->tableName);
            if(isset($result->row()->id)){
                $value['updated_at'] = $this->utils->getNowForMysql();
                $this->db->where('tag_code', $key);
                $this->db->update($this->tableName, $value);
            }else{
                $value['created_at'] = $this->utils->getNowForMysql();
                $this->db->insert($this->tableName,$value);
            }
        }
    }

    public function down() {
        $game_tags = array("graph");
        foreach ($game_tags as $key) {
            $this->db->where('tag_code', $key);
            $this->db->delete($this->tableName);
        }
    }
}