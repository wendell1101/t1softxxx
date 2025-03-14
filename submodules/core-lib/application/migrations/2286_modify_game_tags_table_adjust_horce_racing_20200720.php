<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_game_tags_table_adjust_horce_racing_20200720 extends CI_Migration {

    private $tableName='game_tags';
    private $tagCode = 'horce_racing';

    public function up() {
        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('game_tags');
            $newTagName = '_json:{"1":"Horse Racing","2":"赛马","3":"Horse Racing","4":"Horse Racing","5":"경마"}';
            $newTagCode = 'horse_racing';
            $this->game_tags->syncTagNameByTagCode($this->tagCode,$newTagName,$newTagCode);
        }
    }

    public function down() {
    }
}