<?php $this->session->set_userdata('current_url', current_url()); ?>
<!-- <?php $user_theme //= !empty($this->session->userdata('affiliate_theme')) ? $this->session->userdata('affiliate_theme') : 'flatly'; ?> -->
<?php $user_theme = 'flatly'; ?>
<?php $currentLang = isset($_GET['lang']) ? $_GET['lang'] : $this->session->userdata('login_lan');
if(empty($currentLang)){
	$this->load->library('language_function');
	$currentLang=$this->language_function->getCurrentLanguage();
}
$availableCurrencyList=$this->utils->getAvailableCurrencyList();
$activeCurrencyKeyOnMDB=$this->utils->getActiveCurrencyKeyOnMDB();

$affId=$this->session->userdata('affiliateId');
$availSubAff=false;
if($affId){
	$this->load->model(['affiliatemodel']);
	$availSubAff=$this->affiliatemodel->isAvailableSubAffiliate($affId);
}
$company_title = $this->config->item('aff_page_title');
$page_title = (isset($company_title) ? $company_title.' ' : ''). lang('reg.affilate');
?>
<style>
@media (max-width: 768px) {
    .navbar-toggle {
        width: 90%;
        float: none;
        margin-left: 5%;
    }
    .user-option {
        text-align: center;
    }
}
.disable-link{
	pointer-events: none !important;
	cursor: default;
}

</style>
<nav class="navbar navbar-default" style="margin-bottom: 0px; border-radius: 0px;">
    <div class="container">
        <div class="navbar-header">
            <!-- <a class="navbar-brand brand-logo" style='margin-top:-10px;' href="<?=site_url('affiliate')?>"> -->
            <?php
            	$aff_link       = $this->utils->isEnabledFeature('aff_disable_logo_link') ? 'javascript:void(0)' : $this->utils->getSystemUrl('www');
            	$aff_link_class = $this->utils->isEnabledFeature('aff_disable_logo_link') ? 'disable-link' : '';

				$force_aff_domain = $this->utils->getConfig('enable_aff_logo_link_force_redirecting_domain');
				if (!empty($force_aff_domain) && !$this->utils->isEnabledFeature('aff_disable_logo_link')) {
					$aff_link = $force_aff_domain;
				}
            ?>
            <a class="navbar-brand brand-logo <?=$aff_link_class?>" style='margin-top:-10px;' href="<?=$aff_link?>">
                <?php $logo = @get_site_navbar_logo();
                $site_logo=get_site_login_logo();
                ?>
                <?php if(isset($logo) && $logo) { ?>
                    <img class="brand-img" style="display:inline;" src="<?=$logo;?>" height="35"> <?=$page_title; ?>
                <?php } else if(!empty($site_logo)) { ?>
                     <img class="brand-img" style="display:inline;" src="<?=$site_logo?>" height="35"> <?=$page_title; ?>
                <?php } ?>
            </a>
        </div>
		<ul class="nav navbar-right navbar-nav">
			<?php if (empty($this->utils->getConfig('hide_select_language')) && !empty($this->utils->getConfig('visible_options_under_language_selection')) && !$this->utils->isEnabledFeature('hide_affiliate_language_dropdown')): ?>
				<?php if(!($this->utils->getConfig('aff_language_flag_mod'))):?>
				<li style="margin-right: 10px;">
					<div class="custom-dropdown">
					<select class="form-control input-sm user-option" name="language" id="language" onchange="changeLanguage();" style="margin-top: 12px;">
						<?php foreach ($this->utils->getConfig('visible_options_under_language_selection') as $lang_option_key => $lang_option): ?>

			        		<option value="<?=$lang_option?>" <?php echo ($this->session->userdata('afflang') == $lang_option || $currentLang == $lang_option) ? ' selected="selected"' : '';?>>
				        		<?php 
								switch ($lang_option) {
				        			case '1':
				        				echo "English";
				        				break;
				        			case '2':
				        				echo "中文";
				        				break;
				        			case '3':
				        				echo "Indonesian";
				        				break;
				        			case '4':
				        				echo "Vietnamese";
				        				break;
				        			case '5':
				        				echo "Korean";
				        				break;
				        			case '6':
				        				echo "Thai";
				        				break;
									case '7':
				        				echo "India";
				        				break;
									case '8':
				        				echo "Portuguese";
				        				break;
				        			default:
				        				echo "English";
				        				break;
				        		} ?>
				        	</option>
						<?php endforeach ?>
			        </select>
			    	</div>
		        </li>
				<?php else:?>
					<li>
						<a class="dropdown-toggle user-option" data-toggle="dropdown" id="new_language_area">
							<?php
							$afflang = $this->session->userdata('afflang');
							$flagIcon = "/resources/images/flag_icon/";
							$languageOptions = [
								'1' => ['EN', 'English'],
								'2' => ['CN', 'China'],
								'3' => ['ID', 'Indo'],
								'4' => ['VN', 'Viet'],
								'5' => ['KR', 'Korea'],
								'6' => ['TH', 'Thai'],
								'7' => ['IN', 'India'],
								'8' => ['PT', 'Portuguese'],
							];
							$selectedLanguage = isset($languageOptions[$afflang]) ? $languageOptions[$afflang] : $languageOptions['1'];
							echo '<img src="' . $flagIcon . $selectedLanguage[1] . '.png" height="20" style="margin-right: 10px;"></img>';
							echo '<span>' . $selectedLanguage[0] . '</span> <span class="caret"></span>';
							?>
						</a>
						<ul class="dropdown-menu user-option" style="min-width:0;border-bottom: none;" role="menu">
							<?php foreach ($this->utils->getConfig('visible_options_under_language_selection') as $lang_option): ?>
								<?php
								echo '<li onclick="changeLanguageNew(\'' . $lang_option. '\')"  style="padding:0;width: 92px;"><a>';
								echo '<img src="' . $flagIcon . $languageOptions[$lang_option][1] . '.png" height="20" style="margin-right: 10px;padding-left: 10px;"></img>';
								echo $languageOptions[$lang_option][0]  . '</a></li>';
								?>
							<?php endforeach ?>
						</ul>
					</li>
				<?php endif;?>
			<?php endif ?>
            <?php
            if(!empty($availableCurrencyList)){
            ?>
				<li style="margin-right: 10px;">
                    <div class="custom-dropdown">
                        <select class="form-control input-sm user-option" id="currency_list" onchange="changeCurrency(this);" style="margin-top: 12px;">
                            <option value="super" ><?=lang('All')?></option>
                        <?php
                        foreach ($availableCurrencyList as $key => $value) {
                        ?>
                            <option value="<?=$key?>" <?php echo ($activeCurrencyKeyOnMDB == $key) ? 'selected' : '' ?> ><?=lang($value['name'])?></option>
                        <?php
                        }
                        ?>
                        </select>
                    </div>
		        </li>
            <?php
            }
            ?>

	        <li class="language-selection-box" style="display: none">
	        	<a href="#" class="dropdown-toggle user-option" data-toggle="dropdown" href="#"><i class="glyphicon glyphicon-adjust"></i> <span class="caret"></span></a>

		        <ul class="dropdown-menu user-option" role="menu">
		            <li <?php if($user_theme == 'flatly') echo 'class="active"'; ?>><a href="<?=BASEURL . 'affiliate/switchTheme/flatly'?>">Flatly</a></li>
		            <!-- OGP-15587 Remove the theme function and don't allow client to change the theme color  -->
		            <!-- <li <?php if($user_theme == 'paper') echo 'class="active"'; ?>><a href="<?=BASEURL . 'affiliate/switchTheme/paper'?>">Paper</a></li>
		            <li <?php if($user_theme == 'readable') echo 'class="active"'; ?>><a href="<?=BASEURL . 'affiliate/switchTheme/readable'?>">Readable</a></li>
		            <li <?php if($user_theme == 'journal') echo 'class="active"'; ?>><a href="<?=BASEURL . 'affiliate/switchTheme/journal'?>">Journal</a></li>
		            <li <?php if($user_theme == 'spacelab') echo 'class="active"'; ?>><a href="<?=BASEURL . 'affiliate/switchTheme/spacelab'?>">SpaceLab</a></li>
		            <li <?php if($user_theme == 'slate') echo 'class="active"'; ?>><a href="<?=BASEURL . 'affiliate/switchTheme/slate'?>">Slate</a></li>
		            <li <?php if($user_theme == 'cerulean') echo 'class="active"'; ?>><a href="<?=BASEURL . 'affiliate/switchTheme/cerulean'?>">Cerulean</a></li>
		            <li <?php if($user_theme == 'lumen') echo 'class="active"'; ?>><a href="<?=BASEURL . 'affiliate/switchTheme/lumen'?>">Lumen</a></li>
		            <li <?php if($user_theme == 'yeti') echo 'class="active"'; ?>><a href="<?=BASEURL . 'affiliate/switchTheme/yeti'?>">Yeti</a></li>
		            <li <?php if($user_theme == 'simplex') echo 'class="active"'; ?>><a href="<?=BASEURL . 'affiliate/switchTheme/simplex'?>">Simplex</a></li> -->
		        </ul>
	        </li>
	        <?php if(!empty($this->session->userdata('affiliateUsername'))) { ?>
			<li>
				<a href="#" class="dropdown-toggle user-option" data-toggle="dropdown" href="#"><?=lang('nav.welcome')?> , <span class="aff_name_color"><?=$this->session->userdata('affiliateUsername') . '! '?></span><span class="caret"></span></a>

		        <ul class="dropdown-menu user-option" role="menu">
		            <!-- <li><a href="<?=BASEURL . 'affiliate/modifyAccount'?>"><i class="glyphicon glyphicon-cog"></i> Settings</a></li> -->
		            <!-- <li><a href="<?=BASEURL . 'affiliate/modifyAccount'?>"><i class="glyphicon glyphicon-user"></i> <?=lang('nav.modifyAccount');?></a></li> -->
		            <li><a href="<?=BASEURL . 'affiliate/logout'?>"><i class="glyphicon glyphicon-off"></i> <?=lang('nav.logOut');?></a></li>
		        </ul>
			</li>
			<?php } ?>
		</ul>
    </div>
</nav>

<?php if(!empty($this->session->userdata('affiliateUsername'))) { ?>
	<div id="navbar" class="navbar navbar-inverse" style="border-radius: 0px;">
		<div class="container">
	        <div class="navbar-header">
	          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
	            <span class="sr-only">Toggle navigation</span>
	            <span class="glyphicon glyphicon-align-justify glyphicon-chevron-down" style="color:white;"></span>
	          </button>
	        </div>
			<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
			    <ul class="nav navbar-nav navbar-left user-option">
					<?php if(!$this->utils->is_readonly()){?>
						<li id="home"><a href="<?=site_url('affiliate')?>"><?=lang('Home')?></a></li>
					<?php }?>
					<?php if( ! $this->utils->isEnabledFeature('aff_hide_traffic_stats')){?>
						<li id="trafficStats"><a href="<?=site_url('affiliate/traffic_stats?enable_date=true')?>"><?=lang('nav.traffic');?></a></li>
					<?php }?>
					<?php if($this->utils->isEnabledFeature('player_stats_on_affiliate')){?>
					<li id="playerStats"><a href="<?=site_url('affiliate/player_stats?enable_date=true')?>"><?=lang('Player Statistics');?></a></li>
					<?php }?>
					<li id="bannerLists"><a href="<?=BASEURL . 'affiliate/bannerLists'?>"><?=lang('nav.banner');?></a></li>
                    <?php if(!$this->utils->isEnabledFeature('hide_aff_cashier_navbar')):?>
                        <?php if(!$this->utils->is_readonly()){?>
                            <li id="cashier"><a href="<?=BASEURL . 'affiliate/cashier'?>"><?=lang('nav.cashier');?></a></li>
                            <?php if($this->utils->isEnabledFeature('show_transactions_history_on_affiliate')){ ?>
                                <li id="paymentHistory"><a href="<?=BASEURL . 'affiliate/paymentHistory'?>"><?=lang('nav.transaction');?></a></li>
                            <?php }?>
                            <?php if( ! $this->utils->isEnabledFeature('hide_sub_affiliates_on_affiliate') && $availSubAff){ ?>
                                <li id="subaffiliates"><a href="<?=BASEURL . 'affiliate/subaffiliates'?>"><?=lang('nav.sub-affiliates');?></a></li>
                            <?php }?>
                        <?php }?>
                    <?php endif;?>
					<?php if($this->utils->isEnabledFeature('player_list_on_affiliate')){?>
					<li id="playersList"><a href="<?=BASEURL . 'affiliate/playersList'?>"><?=lang('traffic.playerlist');?></a></li>
					<?php }?>

					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?=lang('aff_header.report');?> <span class="caret"></span></a>
						<ul class="dropdown-menu">
							<?php if($this->utils->isEnabledFeature('affiliate_monthly_earnings') && !$this->utils->is_readonly()){?>
							<li id="monthlyEarnings"><a href="<?=BASEURL . 'affiliate/affiliateEarnings'?>"><?=lang('nav.earnings');?></a></li>
							<?php }?>
							<?php if($this->utils->isEnabledFeature('affiliate_player_report') && !$this->utils->is_readonly()){?>
							<li id="affiliate_player_report"><a href="<?=BASEURL . 'affiliate/affiliate_player_report'?>"><?=lang('report.s09');?></a></li>
							<?php }?>
							<?php if($this->utils->isEnabledFeature('affiliate_game_history')){?>
							<li id="affiliate_game_history"><a href="<?=BASEURL . 'affiliate/affiliate_game_history'?>"><?=lang('Game History');?></a></li>
							<?php }?>
							<?php if($this->utils->isEnabledFeature('affiliate_credit_transactions')){?>
							<li id="affiliate_credit_transactions"><a href="<?=BASEURL . 'affiliate/affiliate_credit_transactions'?>"><?=lang('Transactions');?></a></li>
							<?php }?>
							<?php if(!$this->utils->isEnabledFeature('disabled_game_logs_in_aff') && !$this->utils->is_readonly()){?>
							<li id="affiliate_games_report"><a href="<?=BASEURL . 'affiliate/affiliate_games_report'?>"><?=lang('report.g01');?></a></li>
							<?php }?>
						</ul>
					</li>
					<li id="modifyAccount"><a href="<?=BASEURL . 'affiliate/modifyAccount'?>"><?=lang('nav.modifyAccount');?></a></li>
					<li id="affSourceCode"><a href="<?=BASEURL . 'affiliate/affSourceCode'?>"><?=lang('Affiliate Source Code');?></a></li>
			    </ul>
			</div>
		</div>
	</div><!--/.navbar-collapse -->
<?php } ?>

<style type="text/css">
.overlay_screen {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999999;
    background-color: #000;

    font-size: 24px;
    font-family: sans-serif;
    color: white;
    text-align: center;
    flex-direction: column;
    justify-content: center;
}
</style>
<div style="display: none" id="_lock_screen"></div>


<script type="text/javascript">
	function changeLanguage(newhref=null) {
		var lang = $('#language').val();
	    $.get('/affiliate/changeLanguage/' + lang, function() {
	    	if(newhref!=null){
            	window.location.href = newhref;
	    	}else{
            	location.reload();
	    	}
	    })
	}

	function changeLanguageNew(lang,newhref=null) {
	    $.get('/affiliate/changeLanguage/' + lang, function() {
	    	if(newhref!=null){
            	window.location.href = newhref;
	    	}else{
            	location.reload();
	    	}
	    })
	}

    function _lock_page(msg){
        $('#_lock_screen').addClass('overlay_screen').html(msg).fadeTo(0, 0.4).css('display', 'flex');
    }

    function _unlock_page(){
        $('#_lock_screen').removeClass('overlay_screen').html('').css('display', 'none');
    }

    function changeCurrency(ele){
        //call change active db
        var key=$(ele).val();
        //lock page
        _lock_page("<?=lang('Changing Currency')?>");
        $.ajax(
            '/affiliate/change_active_currency_for_logged/'+key,
            {
                dataType: 'json',
                cache: false,
                success: function(data){
                    if(data && data['success']){
                        window.location.reload();
                    }else{
                        alert("<?=lang('Change Currency Failed')?>");
			            _unlock_page();
                    }
                },
                error: function(){
                    alert("<?=lang('Change Currency Failed')?>");
		            _unlock_page();
                }
            }
        ).always(function(){
            // _unlock_page();
        });
    }

    $(document).one("ready",function(){
        //alert(<?php echo isset($_GET['lang'])?$_GET['lang']:''."!=".$currentLang;?>);
        if(<?php echo isset($_GET['lang']) ? "true" : "false";?>){
           var clang = <?php echo $currentLang ?>;
			$('#language').val(clang);
            var newhref = removeParam(window.location.href);
            // if(window.location.href){
			changeLanguage(newhref);
            // }
        }
        function removeParam(uri) {

           return uri.substring(0, uri.indexOf('?'));
        }
    });

</script>