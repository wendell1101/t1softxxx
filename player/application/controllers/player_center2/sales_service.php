<?php
require_once 'PlayerCenterBaseController.php';

/**
 * Provides referral function
 *
 */
class Sales_service extends PlayerCenterBaseController{
    public function __construct(){
        parent::__construct();

        $this->load->vars('content_template', 'default_with_menu.php');
        $this->load->vars('activeNav', 'sales_service');
    }

    public function index(){
        $player_id = $this->load->get_var('playerId');
        $this->load->model(array('sales_agent', 'player_model'));

        /** @var \Sales_agent $sales_agent */
		$sales_agent = $this->{"sales_agent"};
        $player_sales_agent = $sales_agent->getPlayerSalesAgentDetailById($player_id);
        $data['enabled_sales_agent'] = $this->utils->getConfig('enabled_sales_agent');
        $data['sales_agent_name'] = !empty($player_sales_agent['realname']) ? $player_sales_agent['realname'] : lang('lang.norecyet');
        $data['chat_platform1'] = isset($player_sales_agent['chat_platform1']) ? $player_sales_agent['chat_platform1'] : lang('lang.norecyet');
        $data['chat_platform2'] = isset($player_sales_agent['chat_platform2']) ? $player_sales_agent['chat_platform2'] : lang('lang.norecyet');

        $this->loadTemplate();
        $this->template->append_function_title(lang('sales_agent'));
        $this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate() . '/player/sales_service', $data);
        $this->template->render();
    }
}