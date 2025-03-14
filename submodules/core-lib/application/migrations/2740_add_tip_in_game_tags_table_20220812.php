<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_tip_in_game_tags_table_20220812 extends CI_Migration {

    private $tableName='game_tags';

    public function up() {
        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('game_tags');
            $gameTag = [
                [
                    "tag_name" => '_json:{"1":"Tip","2":"小费","3":"Tip","4":"tiền boa","5":"팁","6":"เคล็ดลับ","7":"बख्शीश","8":"gorjeta"}',
                    "tag_code" => "tip",
                ]
            ];
            $this->game_tags->insertUpdateGameTag($gameTag);
        }
    }

    public function down() {
        $gameTag = array("tip");
        
        foreach ($gameTag as $key) {
            $this->db->where('tag_code', $key);
            $this->db->delete($this->tableName);
        }
    }
}