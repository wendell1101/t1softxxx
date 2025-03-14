<?php
require_once dirname(__FILE__) . '/base_model.php';

/**
 */
class Common_cashback_multiple_range_templates_model extends BaseModel {
    private $tableName = 'common_cashback_multiple_range_templates';

    public function __construct() {
        parent::__construct();
    }

    public function hasTemplate($tpl_id){
        return $this->getTemplate($tpl_id);
    }

    public function getTemplate($tpl_id){
        $query = $this->db->get_where($this->tableName, [
            'cb_mr_tpl_id' => $tpl_id
        ]);

        $data = $this->getOneRowArray($query);

        if(empty($data)){
            return FALSE;
        }

        return $data;
    }

    /**
     * Get the game tag in template
     *
     * @return void
     */
    public function getGameTagActiveTemplate($template_name = 'game_tag'){

        $query = $this->db->get_where($this->tableName, [
            'active' => 1
            , 'template_name' => $template_name // Common_Cashback_multiple_rules::COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_TAG
        ]);
        $this->db->limit(1);
        $data = $this->getOneRowArray($query);
        if(empty($data)){
            if(!$this->createTemplate($template_name, 'multiple range by game tag')){
                return FALSE;
            }else{
                $insert_tpl_id = $this->db->insert_id();
                $this->setActiveTemplate($insert_tpl_id, $template_name);
            }

            $active_template = $this->getLastUpdateTemplate($template_name);
            if(empty($active_template)){
                return FALSE;
            }
            $this->setActiveTemplate($active_template['cb_mr_tpl_id'], $template_name);

            return $active_template;
        }
        return $data;
    }// EOF getGameTagActiveTemplate

    /**
     * Get a Activated Template By Name
     *
     * @param string $template_name
     * @return void
     */
    public function getActiveTemplate($template_name = 'Default'){
        $this->db->limit(1);
        $query = $this->db->get_where($this->tableName, [
            'active' => 1
            , 'template_name' => $template_name
        ]);

        $data = $this->getOneRowArray($query);
        if(empty($data)){
            if(!$this->createTemplate($template_name)){
                return FALSE;
            }else{
                $insert_tpl_id = $this->db->insert_id();
                $this->setActiveTemplate($insert_tpl_id, $template_name);
            }

            $active_template = $this->getLastUpdateTemplate( $template_name );
            if(empty($active_template)){
                return FALSE;
            }
            $data = $active_template;
        }

        return $data;
    } // EOF getActiveTemplate

    /**
     * Get the Latest Updated Template
     *
     * @param string $template_name The template name.
     * @return bool|array The field-value array otherwise false on failed or empty.
     */
    public function getLastUpdateTemplate($template_name = 'Default'){
        $this->db->order_by('updated_at', 'desc');
        // $query = $this->db->get($this->tableName)
        $query = $this->db->get_where($this->tableName, [
            'active' => 1
            , 'template_name' => $template_name
        ]);

        $data = $this->getOneRowArray($query);

        if(empty($data)){
            return FALSE;
        }

        return $data;
    } // EOF getLastUpdateTemplate

    public function createTemplate($template_name = '', $note = ''){
        $data = [
            'template_name' => $template_name,
            'note' => $note,
            'active' => 0, // default always inactive
            'created_at' => $this->utils->getNowForMysql(),
            'updated_at' => $this->utils->getNowForMysql(),
        ];

        return $this->db->insert($this->tableName, $data);
    }

    public function updateTemplate($tpl_id, $template_name, $note){
        $data = [
            'template_name' => $template_name,
            'note' => $note,
            'updated_at' => $this->utils->getNowForMysql()
        ];

        $result = $this->db->update($this->tableName, $data, ['cb_mr_tpl_id' => $tpl_id]);

        return ($result) ? TRUE : FALSE;
    }

    /**
     * Set Active in Template by the template name
     * Only one record should be activated.
     *
     * @param integer $tpl_id
     * @param string $template_name The template name
     * @return void
     */
    public function setActiveTemplate($tpl_id, $template_name = 'Default'){

        $result = $this->db->update($this->tableName, ['active' => 0], [
            'template_name' => $template_name
        ]);

        if(!$result){
            return FALSE;
        }

        $data = [
            'active' => 1,
            'updated_at' => $this->utils->getNowForMysql()
        ];

        $result = $this->db->update($this->tableName, $data, ['cb_mr_tpl_id' => $tpl_id]);

        return ($result) ? TRUE : FALSE;
    } // EOF setActiveTemplate
}
