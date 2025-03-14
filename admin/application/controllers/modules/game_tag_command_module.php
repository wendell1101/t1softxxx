<?php

trait Game_tag_command_module {

    public function remove_game_tag_new_release($start_remove = true) {
        $this->load->model(['game_tags', 'game_tag_list', 'game_description_model']);
        $now = $this->utils->getNowForMysql();
        $game_tag_code = $this->utils->getConfig('game_tag_code_for_new_release');
        $interval = $this->utils->getConfig('game_tag_new_release_interval');
        $interval_expr = isset($interval['expr']) ? $interval['expr'] : 1;
        $interval_unit = isset($interval['unit']) ? $interval['unit'] : 'MONTH';
        $game_tag_details = $this->game_tags->getGameTagByTagCode($game_tag_code);
        $game_tag_id = isset($game_tag_details['id']) ? $game_tag_details['id'] : null;
        $game_tag_list = $this->game_tag_list->getGameTagListByTagId($game_tag_id);
        $list_count = count($game_tag_list);
        $removed_count = 0;

        if (!empty($game_tag_list) && is_array($game_tag_list)) {
            foreach ($game_tag_list as $list) {
                $id = $list['id'];
                $game_description_id = $list['game_description_id'];
                $created_at = $list['created_at'];

                // set expiration date
                $date = date_create($created_at);
                date_add($date, date_interval_create_from_date_string($interval_expr . $interval_unit));
                $expiration_date = date_format($date, 'Y-m-d H:i:s');
    
                // $diff = date_diff(date_create($created_at), date_create($expiration_date));
    
                $this->utils->debug_log(__CLASS__, __METHOD__, 'created_at', $created_at, 'expiration_date', $expiration_date, 'now', $now, 'list', $list);
    
                if ($start_remove) {
                    if ($now >= $expiration_date) {
                        $removed_count++;
                        // delete row in game_tag_list table
                        $this->game_tag_list->deleteTag($id);
                        // update flag_new_game column in game_description table
                        $this->game_description_model->updateGameDescription(['flag_new_game' => Game_description_model::FLAG_UNTAGGED_NEW_GAME_UNTAG], $game_description_id);
                    }
                }
            }
        }

        $this->utils->info_log(__CLASS__, __METHOD__, 'list_count', $list_count, 'removed_count', $removed_count);
    }
}