<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_model.php';

class Game_tags extends BaseModel {

    protected $tableName = "game_tags";

    function __construct() {
        parent::__construct();
    }

    /**
     * Sync Tag name By Tag Code
     *
     * @param mixed $tagCode
     * @param mixed $newTagName
     * @param mixed $newTagCode
     * @param string $key
     *
     * @return int
    */
    public function syncTagNameByTagCode($tagCode,$newTagName,$newTagCode,$key='tag_code')
    {
        if( !empty($tagCode) && !empty($newTagName) && !empty($newTagCode)){

            $data['updated_at'] = $this->utils->getNowForMysql();
            $data['tag_name'] = $newTagName;
            $data['tag_code'] = $newTagCode;

            $this->updateGameTags($key,$tagCode,$data);

            return $this->db->affected_rows();
        }
        return 0;
    }

    /**
     * Update The Data base in table field name
     *
     * @param string $key
     * @param mixed $value
     * @param array $data
     *
     * @return int
    */
    public function updateGameTags($key=null,$value=null,$data=null)
    {
        if(! is_null($key) && !is_null($value) && !is_null($data)){

            if($this->isFieldExist($key,$value)){
                $this->db->where($key,$value)
                    ->update($this->tableName,$data);

                    return $this->db->affected_rows();
            }
        }
        return 0;
    }

    /**
     * Insert or Update Game Tag
     *
     * @param array $key
     *
     * @return boolean
    */
    public function insertUpdateGameTag($data)
    {
        if(is_array($data) && count($data) > 0){

            foreach($data as $d){

                if(isset($d['tag_code'])){
                    $isExist = $this->isFieldExist('tag_code',$d['tag_code']);
                    $now = $this->utils->getNowForMysql();
                    $tagName = isset($d['tag_name']) ? $d['tag_name'] : null;

                    if($isExist){
                        $this->db->where('tag_code', $d['tag_code'])
                            ->set([
                                'updated_at' => $now,
                                'tag_name' => $tagName,
                                'tag_code' => $d['tag_code']
                            ]);

                        $u = $this->runAnyUpdate($this->tableName);
                        $this->utils->info_log(__METHOD__.' update',$u,'tag_code',$d['tag_code']);
                    }else{
                        $d['created_at'] = $this->utils->getNowForMysql();
                        $lid = $this->insertData($this->tableName,$d);
                        $this->utils->info_log(__METHOD__.' last insert id',$lid);
                    }
                }else{
                    $this->utils->info_log(__METHOD__.' no tag_code, skipped',$d);
                    continue;
                }
            }
        }

        return false;
    }

    /**
     * Check if field exist
     * @param string $key
     * @param mixed $value
     *
     * @return boolean
     */
    public function isFieldExist($key=null,$value=null)
    {
        if(! is_null($key) && !is_null($value)){

            $this->db->from($this->tableName)
                ->where($key,$value);

            return $this->runExistsResult();
        }
        return false;
    }

    public function getAllGameTags($db = null, $is_custom = false, $custom_array_where = []) {
        if(empty($db)){
            $db = $this->db;
        }

        $db->from($this->tableName);

        if ($is_custom) {
            $db->where('is_custom', $is_custom);
        }

        if (!empty($custom_array_where) && is_array($custom_array_where)) {
            $db->where($custom_array_where);
        }

        return $this->runMultipleRowArray($db);
    }

    public function getGameTagWithId($tag_id = '') {
        $this->db->from($this->tableName);
        $this->db->where('id', $tag_id);
        return $this->runOneRowArray();
    } // EOF getGameTagWithId

    // public function getGameTagListWithCode($tag_code = '') {
    //     $this->db->from($this->tableName);
    //     $this->db->where('tag_code', $tag_code);
    //     return $this->runMultipleRowArray();
    // } // EOF getGameTagListWithCode
    //
    // /**
    //  * Get The game_description.game_type_id list limit by game_platform_id list
    //  *
    //  * @param array $game_platform_id_list The  external_system(_list).id list.
    //  * @return array $game_type_id_list The game_description.game_type_id list.
    //  */
    // public function getGameTagIdByPlatformIdList($game_platform_id_list = []){
    //     $this->load->library(['og_utility']);
    //     $rows = $this->getGameDescriptionListByGamePlatformIdList($game_platform_id_list);
    //     $game_type_id_list = $this->og_utility->array_pluck($rows, 'game_type_id');
    //     return $game_type_id_list;
    // }// EOF getGameTypeIdByPlatformIdList

    public function getAllGameTagsWithPagination($offset = null, $amountPerPage = null, &$total_rows = null) {

        $this->db->from($this->tableName);

        $reset = false; // for keep $this->db->where()...
        $total_rows = $this->db->count_all_results('', $reset);

        $this->db->count_all_results('', true); // for clear limit

        $this->db->from($this->tableName);
        // about pagination
        if( ! is_null($amountPerPage) && ! is_null($offset)){
            $this->db->limit($amountPerPage, $offset);
        }else if( ! is_null($amountPerPage) ){
            $this->db->limit($amountPerPage);
        }

        return $this->runMultipleRowArray();
    } // EOF getAllGameTags

    public function getGameTagByTagCode($code) {
		$qry = $this->db->get_where($this->tableName, array('tag_code' => $code));
		return $this->getOneRowArray($qry);
	}

    public function queryAllGameTags($request, $is_export = false){
        // print_r($request);exit();
        $isNavigation = isset($request['isNavigation']) ? $request['isNavigation'] : false;

        $i = 0;

        $columns = array();

        $columns[] = array(
            'alias' => 'is_custom',
            'select' => 'game_tags.is_custom',
            'name' => lang("Is Custom"),
            'formatter' => 'languageFormatter',
        );
        $columns[] = array(
            'alias' => 'flag_show_in_site',
            'select' => 'game_tags.flag_show_in_site',
            'name' => lang("flag_show_in_site"),
        );

        $columns[] = array(
            'dt' => !$isNavigation ? $i++ : "",
            'alias' => 'tag_name',
            'name' => lang("Tag Name"),
            'select' => 'game_tags.tag_name',
            'formatter' => function ($d,$row) use ($is_export) {
                $translation_array = $this->utils->text_from_json($d);
                $languages = array_values($this->CI->language_function->getAllSystemLanguages());
                $translation = "";
                if(!empty($translation_array)){
                    $array_lang = [];
                    foreach ($translation_array as $key => $value) {
                        $lang_key = array_search($key, array_column($languages, 'key'));

                        if(!$is_export){
                            $translation .= "<br> {$languages[$lang_key]['word']} : {$value} <br>";
                        }
                        else {
                            // $translation .= "{$languages[$lang_key]['word']} : {$value} |";
                            $array_lang[$languages[$lang_key]['word']] = $value;
                        }
                    }
                    if($is_export){
                        return $array_lang;
                    }
                }
                return $translation;
            }
        );
        $columns[] = array(
            'dt' => $i++,
            'alias' => 'tag_code',
            'select' => 'game_tags.tag_code',
            'name' => lang("Tag Code"),
            'formatter' => 'languageFormatter',
        );
        $columns[] = array(
            'dt' => !$isNavigation ? $i++ : "",
            'alias' => 'created_at',
            'select' => 'game_tags.created_at',
            'name' => lang("Created at"),
            'formatter' => 'languageFormatter',
        );
        $columns[] = array(
            'dt' => !$isNavigation ? $i++ : "",
            'alias' => 'updated_at',
            'name' => lang("Updated at"),
            'select' => 'game_tags.updated_at',
        );

        if( ! $is_export ){

            $columns[] = array(
                'dt' => !$isNavigation ? $i++ : "",
                'alias' => 'id',
                'select' => 'game_tags.id',
                'formatter' => function ($d,$row) use ($is_export) {
                    $output = '';

                    if ($row['is_custom'] && !in_array($row['tag_code'], $this->utils->getConfig('protected_system_game_tags'))) {
                        $output .= '<a href="javascript:void(0)" onclick="edit_game_tag('.$d.')" title="' . lang("sys.gt23") . '" class="edit-gt" id="edit_gt-' . $d . '" data-row-id="' . $d . '" ><span class="glyphicon glyphicon-edit"></span></a>';
                        $output .= '<a href="javascript:void(0)" onclick="delete_game_tag('.$d.')" title="' . lang('Delete this game tag') . '" class="delete-gt right" id="delete_gt-' . $d . '" data-row-id="'.$d.'"><span style="color:#ff3333" class="glyphicon glyphicon-trash"></span></a>';
                    }

                    return $output;

                }
            );

            if($isNavigation){
                $columns[] = array(
                    'dt' => $i++ ,
                    'alias' => 'id',
                    'select' => 'game_tags.id',
                    'formatter' => function ($d,$row) use ($is_export) {
                        $output = '';

                        if ($row['is_custom'] && !in_array($row['tag_code'], $this->utils->getConfig('protected_system_game_tags'))) {
                            $output .= '<a href="javascript:void(0)" onclick="edit_game_tag('.$d.')" title="' . lang("sys.gt23") . '" class="edit-gt" id="edit_gt-' . $d . '" data-row-id="' . $d . '" ><span class="glyphicon glyphicon-edit"></span></a>';
                            $output .= '<a href="javascript:void(0)" onclick="delete_game_tag('.$d.')" title="' . lang('Delete this game tag') . '" class="delete-gt right" id="delete_gt-' . $d . '" data-row-id="'.$d.'"><span style="color:#ff3333" class="glyphicon glyphicon-trash"></span></a>';
                        }
                        $isChecked = $row['flag_show_in_site'] == 1 ? "checked" : ""; 
                        $translation_array = $this->utils->text_from_json($row['tag_name']);
                        return '<div class="" style="">
                            <div class="" title="">
                                <div class="onoffswitch">
                                    <input type="checkbox" name="nav_gt-' . $d . '" class="onoffswitch-checkbox" id="nav_gt-' . $d . '" value="false" '.$isChecked.' data-row-id="'.$d.'" data-row-tag="'.$row['tag_code'].'" data-row-name="'.$translation_array[1].'">
                                    <label class="onoffswitch-label" for="nav_gt-' . $d . '">
                                        <span class="onoffswitch-inner"></span>
                                        <span class="onoffswitch-switch"></span>
                                    </label>
                                </div>
                            </div>
                        </div>';

                    }
                );
            }

        }


        $table = 'game_tags';
        // $joins = array(
        //     'external_system' => 'external_system.id = game_type.game_platform_id',
        // );

        # START PROCESS SEARCH FORM #################################################################################################################################################
        $where = array();
        $where[] = "game_tags.deleted_at IS NULL";
        if($isNavigation){
            $filters = isset($request['filters']) ? $request['filters'] : [];
            if(!empty($filters)){
                foreach ($filters as $key => $filter) {
                    // print_r($filter);
                    if($filter['name'] == "flag_show_in_site" && $filter['value'] === "1"){
                        $where[] = "game_tags.flag_show_in_site = 1";
                    }
                    if($filter['name'] == "flag_show_in_site" && $filter['value'] === "0" ){
                        $where[] = "game_tags.flag_show_in_site = 0";
                    }

                    if($filter['name'] == "tag_code" && !empty($filter['value'])){
                        $where[] = "game_tags.tag_code like '%{$filter['value']}%'";
                    }
                }
            }
            
        }
        // print_r($where);

        $values = array();

        $this->load->library('data_tables');
        // $input = $this->data_tables->extra_search($request);
        // $where[] = "external_system.system_name <> '' ";
        // if (isset($input['game_platform_id'])) {
        //     $where[] = "game_type.game_platform_id = ?";
        //     $values[] = $input['game_platform_id'];
        // }else{
        //     //only for active api
        //     $apiArr=$this->utils->getAllCurrentGameSystemList();
        //     $where[] = "game_type.game_platform_id in ( ".implode(',', $apiArr)." )";
        // }
        // if (isset($input['game_type'])) {
        //     $where[] = "game_type.game_type LIKE '%".$input['game_type']."%' ";
        //     $values[] = $input['game_type'];
        // }
        $result = $this->data_tables->get_data($request, $columns, $table, $where, $values);
        return $result;
    }

    /**
     * Check if tag exist
     * @param string $tagCode
     *
     * @return boolean
     */
    public function isTagExist($tagCode = null)
    {
        if(!is_null($tagCode)){

            $this->db->from($this->tableName)
                ->where('tag_code',$tagCode);
            return $this->runExistsResult();
        }
        return false;
    }

    /**
     * Get tagged games
     *
     * @return array
     */
    public function getTagGames($tagCode = null, $db=null)
    {
        if(empty($db)){
            $db = $this->db;
        }
        $where  = '';
        if(!empty($tagCode)){
            if(is_array($tagCode)){
                $where  = ' AND gt.tag_code IN ("'.implode('","', $tagCode).'")';
            }else{
                $where  = ' AND gt.tag_code="'.$tagCode.'"';
            }
        }
        $sql="select gt.id as tag_id, gt.tag_code, group_concat(gtl.game_description_id) as game_description_ids
        from game_tags gt
        join game_tag_list as gtl on gtl.tag_id=gt.id
        where gt.deleted_at is NULL
        $where
        group by gt.id
        order by gt.id asc;";
        $query = $db->query($sql);
        return $query->result_array();
    }
    
    public function getTagGamesList($tagCode = null, $db=null, $gameDescriptionId = null)
    {
        if(empty($db)){
            $db = $this->db;
        }
        $where  = '';
        if(!empty($tagCode)){
            if(is_array($tagCode)){
                $where  = ' AND gt.tag_code IN ("'.implode('","', $tagCode).'")';
            }else{
                $where  = ' AND gt.tag_code="'.$tagCode.'"';
            }
        }
        if(!empty($gameDescriptionId)){
            if(is_array($gameDescriptionId)){
                $where  = ' AND gtl.game_description_id IN ('.implode(',', $gameDescriptionId).')';
            }else{
                $where  = ' AND gtl.game_description_id='.$gameDescriptionId;
            }
        }
        $sql="select gt.id as tag_id, gt.tag_code, gtl.game_description_id
        from game_tags gt
        join game_tag_list as gtl on gtl.tag_id=gt.id
        where gt.deleted_at is NULL
        $where
        order by gt.id asc;";
        $query = $db->query($sql);
        return $query->result_array();
    }

    /**
     * Get games with tags
     *
     * @return array
     */
    public function getGamesWithTag($tagCode = null, $db=null)
    {
        if(empty($db)){
            $db = $this->db;
        }
        $where = '';
        if(!empty($tagCode)){
            if(is_array($tagCode)){
                $where  = ' AND game_tags.tag_code IN ("'.implode('","', $tagCode).'")';
            }else{
                $where  = ' AND game_tags.tag_code="'.$tagCode.'"';
            }
        }

        $sql="select game_description.*, game_description.id as game_description_id, external_system.system_code game_api_system_code, 
        external_system.status game_api_status, external_system.maintenance_mode as under_maintenance, 
        game_type.game_type game_type_name, group_concat(game_tags.tag_code) as tags
        from game_description
        join external_system on external_system.id=game_description.game_platform_id
        join game_type on game_type.id=game_description.game_type_id
        join game_tag_list on game_tag_list.game_description_id=game_description.id
        join game_tags on game_tags.id=game_tag_list.tag_id
        where game_description.`status` = 1 AND game_description.`flag_show_in_site` = 1 AND game_type.`game_type` not like '%unknown%' and game_tags.deleted_at is NULL
        $where
        group by game_description.id;";

        $query = $db->query($sql);
        return $query->result_array();
    }

    public function getGamePlatformTags($db=null){
        if(empty($db)){
            $db = $this->db;
        }
        $db->select('game_platform_tag_list.game_platform_id, game_tags.tag_code')->from('game_platform_tag_list')
            ->join('game_tags', 'game_tags.id=game_platform_tag_list.tag_id')
            ->join('external_system', 'external_system.id=game_platform_tag_list.game_platform_id')
            ->where('game_tags.tag_code !=', 'unknown')
            ->where('external_system.status', External_system::STATUS_NORMAL);


        return $this->runMultipleRowArray($db);
    }

    public function deleteTagListByGamePlatform($gamePlatformId, $db=null){
        if(empty($db)){
            $db = $this->db;
        }
        $db->where('game_platform_id', $gamePlatformId);
        return $this->runRealDelete('game_platform_tag_list', $db);
    }

    public function syncTagCode($tagCodeList, $db=null){
        if(empty($db)){
            $db = $this->db;
        }
        //loop each game tag, insert to super db if not exist
        foreach($tagCodeList as $tagCode){
            // query game tag from super db
            $db->from('game_tags')
                ->where('tag_code', $tagCode);
            $row=$this->runOneRowArray($db);
            // if game tag not exist in super db
            if(empty($row)){
                // insert game tag to super db
                $this->utils->debug_log('insert game tag to super db', $tagCode);
                $data=['tag_code'=>$tagCode, 'tag_name'=>$tagCode,
                    'updated_at'=>$this->utils->getNowForMysql(),
                    'created_at'=>$this->utils->getNowForMysql()];
                $success=$this->runInsertData('game_tags', $data, $db);
                // check success
                if(!$success){
                    $this->utils->error_log('insert game tag to super db failed', $tagCode);
                    return false;
                }
            }
        }

        return true;
    }

    public function insertTagListByGamePlatform($gamePlatformId, $tagCodeList, $db=null, &$count=0){
        if(empty($db)){
            $db = $this->db;
        }
        // query id by tag code
        $tagMap=[];
        // query id by tag code list
        $db->select('id, tag_code')->from('game_tags')->where_in('tag_code', $tagCodeList);
        $rows=$this->runMultipleRowArray($db);
        foreach ($rows as $row) {
            $tagMap[$row['tag_code']]=$row['id'];
        }
        $insertData = array();
        foreach ($tagCodeList as $tagCode) {
            if(isset($tagMap[$tagCode])){
                $insertData[] = array(
                    'game_platform_id' => $gamePlatformId,
                    'tag_id' => $tagMap[$tagCode],
                );
            }else{
                // print error
                $this->utils->error_log('tag code not exist in super db', $tagCode);
                return false;
            }
        }
        return $this->runBatchInsertWithLimit($db, 'game_platform_tag_list', $insertData, 100, $count);
    }

    public function addTagToGameDescription($tagId, $gameDescriptionId, $extra = [], $db=null){
        if(empty($db)){
            $db = $this->db;
        }
        // check and insert
        $db->select('id')->from('game_tag_list')
            ->where('tag_id', $tagId)->where('game_description_id', $gameDescriptionId);
        $row=$this->runOneRowArray($db);
        if(empty($row)){
            // add game tag to game description
            $data=[
                'tag_id'=>$tagId,
                'game_description_id'=>$gameDescriptionId,
                'status'=>self::DB_TRUE,
                'game_order'=>0,
                'updated_at'=>$this->utils->getNowForMysql(),
                'created_at'=>$this->utils->getNowForMysql()
            ];

            if(isset($extra['game_order'])){
                $data['game_order'] = (int)$extra['game_order'];
            }

            return $this->runInsertData('game_tag_list', $data, $db);
        }

        return false;
    }

    public function isTagActive($tag_id) {
        $this->db->from($this->tableName)->where(['id' => $tag_id, 'deleted_at' => null]);

        return $this->runExistsResult();
    }

    public function createGameTag($tag_code) {
        $this->db->from('game_tags')->where(['tag_code' => $tag_code]);
        $tag_id = $this->runOneRowOneField('id');
        $now = $this->utils->getNowForMysql();

        if (!$tag_id) {
            $tag_name_arr = [
                '1' => $tag_code,
                '2' => $tag_code,
                '3' => $tag_code,
                '4' => $tag_code,
                '5' => $tag_code
            ];
    
            $game_tag_new_data = [
                'tag_code' => $tag_code,
                'tag_name' => '_json:'.json_encode($tag_name_arr),
                'created_at' => $now,
                'updated_at' => $now,
                'is_custom' => true
            ];

            $tag_id = $this->runInsertData('game_tags', $game_tag_new_data);
        }

        return $tag_id ?: false;
    }

    public function queryGameTagsForNavigation($default, $orderType){
        $show_in_site = self::DB_TRUE;
        $sql = "select gt.tag_name as title, gt.tag_code as tag, gt.game_tag_order as gt_order
        from game_tags gt
        where gt.flag_show_in_site = {$show_in_site}
        ORDER BY gt.tag_code != '{$default}', gt.game_tag_order = 0, gt.game_tag_order {$orderType}";
        $query = $this->db->query($sql);
        $results = $query->result_array();
        return $results;
        // print_r($results);exit();
    }

    public function resetGameTagOrder(){
        $this->db->where('game_tag_order != 0')
        ->update($this->tableName,['game_tag_order'=> 0]);
        return $this->db->affected_rows();
    }
}
