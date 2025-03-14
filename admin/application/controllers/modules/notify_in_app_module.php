<?php

trait notify_in_app_module {

    /**
     * do_notify_send_with_mapping_cmd function
     *
     * @param string $player_mapping_id {player_id_0}-{msg_id_0}_{player_id_1}-{msg_id_1}_{player_id_2}-{msg_id_2}_...
     * @param [type] $source_method
     * @return void
     */
    public function do_notify_send_with_mapping_cmd($player_mapping_id, $source_method){
        $this->load->library(['notify_in_app_library']);
        $player_id_list = [];
        $player_mapping_list =[];
        if( strpos($player_mapping_id, '_') !== false|| true){
            // ref. to https://regex101.com/r/4nOibf/1
            $regex = '/(?<player_id>\d+)\-(?<mapping_id>\d+)_?/';
            preg_match_all($regex, $player_mapping_id, $matches, PREG_SET_ORDER, 0);
            if( ! empty($matches) ){
                foreach($matches as $indexNumber => $currMatche){
                    $player_id_list[] = $currMatche['player_id'];
                    $player_mapping_list[$currMatche['player_id']]= $currMatche['mapping_id'];
                }
            }
            $player_id_list = array_filter($player_id_list);
        }

        if( ! empty( $player_id_list ) ){
            $totals = count( $player_id_list);

            foreach($player_id_list as $index_number => $player_id){
                $mapping_id = null;
                if( !empty($player_mapping_list[$player_id])){
                    $mapping_id = $player_mapping_list[$player_id];
                }
                $this->notify_in_app_library->do_notify_send($player_id, $source_method, $mapping_id);
                $this->utils->debug_log('notify_in_app_module.do_notify_send_with_mapping_cmd.index:', $index_number
                                        , 'totals:', $totals
                                        , 'params:', $player_id, $source_method, $mapping_id );
            }
        }
    }


    public function do_notify_send_cmd($player_id, $source_method){
        $this->load->library(['notify_in_app_library']);
        $player_id_list = [];
        if( strpos($player_id, '_') !== false){
            $player_id_list = array_filter(explode('_', $player_id));
        }else{
            // only one
            $player_id_list[0] = $player_id;
        }

        if( ! empty( $player_id_list ) ){
            $totals = count( $player_id_list);
            foreach($player_id_list as $index_number => $player_id){
                $mapping_id = null;
                $this->notify_in_app_library->do_notify_send($player_id, $source_method, $mapping_id);
                $this->utils->debug_log('notify_in_app_module.do_notify_send_cmd.index:', $index_number
                                        , 'totals:', $totals
                                        , 'params:', $player_id, $source_method, $mapping_id );
            }
        }
    } // EOF do_notify_send_cmd

}// end trait
