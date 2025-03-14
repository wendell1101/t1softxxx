<ul class="sidebar-nav" id="sidebar">
    <?php
        $permissions = $this->permissions->getPermissions();
        //print_r($permissions);
        $active= isset($active) ? $active : '';
        // $enabledReport = ['player_additional_roulette_report'];
        $enabledReport = $this->utils->getConfig('custom_report_tab_sidebar_item');
		if (!empty($enabledReport)) {
			foreach ($enabledReport as $value) {
        		switch ($value) {
                    case 'player_additional_roulette_report': ?>
                        <li>
                            <a class="list-group-item" id="player_additional_roulette_report" style="border: 0px;margin-bottom:0.1px;" href="<?php echo site_url('report_custom_additional_management/viewPlayerAdditionalRouletteReport'); ?>" data-toggle="tooltip" data-placement="right" title="<?=lang('player_additional_roulette_report');?>">
                                <i class="fa fa-list-alt <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
                                <span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:inline-block;"' : 'style="display:none;"';?> ><?=lang('player_additional_roulette_report');?></span>
                            </a>
                        </li>
                        <?php break;
                    case 'player_additional_report': ?>
                        <li>
                            <a class="list-group-item" id="player_additional_report" style="border: 0px;margin-bottom:0.1px" href="<?=site_url('report_custom_additional_management/viewPlayerAdditionalReport')?>" data-toggle="tooltip" data-placement="right" title="<?=lang('player_additional_report');?>">
                                <i class="icon-users <?=($this->session->userdata('sidebar_status') == 'active') ? '' : 'pull-right';?>" id="icon"></i>
                                <span id="hide_text" <?=($this->session->userdata('sidebar_status') == 'active') ? 'style="display:inline-block;"' : 'style="display:none;"';?> >
                                       <?=lang('player_additional_report');?>
                                    </span>
                            </a>
                        </li>
                        <?php break;
                    default:
            			break;
            	}
            } // EOF foreach ($permissions as $value) {...
        } // EOF  if ($permissions != null) {...
    ?>
</ul>

<ul id="sidebar_menu" class="sidebar-nav">
    <li class="sidebar-brand">
        <a id="menu-toggle" href="#" onclick="Template.changeSidebarStatus();">
            <span id="main_icon" class="icon-arrow-<?=($this->session->userdata('sidebar_status') == 'active') ? 'left' : 'right';?> pull-right"></span></span>
        </a>
    </li>
</ul>

<script type="text/javascript">
    $( document ).ready(function() {
        $('#main_icon').on('click',function(){
            if($("#wrapper").hasClass("active")){
                $.each($('.sidebar-nav li a i'),function( index, value ){
                    $(value).addClass('pull-right');
                });
            }else{
                $.each($('.sidebar-nav li a i'),function( index, value ){
                    $(value).removeClass('pull-right');
                });
            }
        });
    });
</script>
