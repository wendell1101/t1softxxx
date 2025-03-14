<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_cock_fight_in_game_tags_table_20200922 extends CI_Migration {

    private $tableName='game_tags';

    public function up() {
        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('game_tags');
            $gameTag = [
                [
                    "tag_name" => '_json:{"1":"Cock Fight","2":"Cock Fight","3":"Cock Fight","4":"Cock Fight","5":"斗鸡"}',
                    "tag_code" => "cock_fight",
                ]
            ];
            $this->game_tags->insertUpdateGameTag($gameTag);
        }
    }

    public function down() {
    }
}