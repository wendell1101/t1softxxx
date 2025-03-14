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
            <li class="login-btn-wrapper">
                <form class="navbar-form pull-left">
                    <button type="button" class="btn btn-default btn-login" data-href="<?=site_url('affiliate');?>">
                        <img src="/resources/images/icon-login.svg"></img>
                        <?=lang('Login')?>
                    </button>
                </form>
	        </li>
            <li class="reg-btn-wrapper">
                <form class="navbar-form pull-left" >
                    <button type="button" class="btn btn-info btn-register" data-href="<?=site_url('affiliate/register');?>">
                        <img src="/resources/images/icon-register.svg"></img>
                        <?=lang('Register')?>
                    </button>
                </form>
	        </li>
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

		</ul>
    </div>
</nav>
<style type="text/css">
.login .input-group .form-control {
    z-index: 1;
}
.reg-btn-wrapper .btn-register {
	text-transform: capitalize;
}
</style>

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

    $(document).on("ready",function(){
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

        $('body').on('click', '.btn-register', function(e){
            clicked_btn_register(e);
        });
        $('body').on('click', '.btn-login', function(e){
            clicked_btn_login(e);
        });
        function clicked_btn_register(e){
            var target$El = $(e.target);
            var _href = target$El.data('href');
            if(!$.isEmptyObject(_href)){
                window.location=_href;
            }
        }
        function clicked_btn_login(e){
            var target$El = $(e.target);
            var _href = target$El.data('href');
            if(!$.isEmptyObject(_href)){
                window.location=_href;
        	}
		}
    });

</script>