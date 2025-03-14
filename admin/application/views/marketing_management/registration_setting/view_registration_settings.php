<style type="text/css">
	.style-in-iframe {
		position: absolute;
	    top: -56px;
	    left: -23px;
	    width: 100%;
        overflow-y: scroll;
        height: 1610px;
	}
    .style-in-iframe>.panel-primary{
        border: none;
    }
    .style-in-iframe>.panel-primary .panel-footer{
        display: none;
    }
</style>

<div id="registration_main_content">
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h1 class="panel-title"><i class="icon-cog"></i> <?=lang('mark.regsetting');?></h1>
		</div>
		<div class="panel-body" id="player_panel_body">
			<div class="col-md-12">
				<ul class="nav nav-tabs d-block">
					<li class="active" id="player"><a href="#" onclick="changeRegistrationSettings('1');" data-toggle="tab"><?=lang('a_header.player');?></a></li>
					<?php if(!$this->utils->isEnabledFeature('close_aff_and_agent')):?>
					<li class="" id="affiliate"><a href="#" onclick="changeRegistrationSettings('2');" data-toggle="tab"><?=lang('a_header.affiliate');?></a></li>
					<?php endif ?>
				</ul>

				<div id="nav_content" style="width: 100%; height: auto; float: left; border: 1px solid lightgray; border-top: none; padding: 0;">

				</div>
			</div>
		</div>
		<div class="panel-footer"></div>
	</div>
</div>

<script type="text/javascript">
	if ( self !== top ) {
		$('nav').remove();
		$('#sidebar-wrapper').remove();
		$('#registration_main_content').addClass('style-in-iframe');
	}

	var _viewRegistrationSettings = {};
    _viewRegistrationSettings._enable_restrict_username_more_options = <?= empty($this->utils->getConfig('enable_restrict_username_more_options'))? 0: 1 ?>;
	_viewRegistrationSettings._username_requirement_mode_number_only = <?=Operatorglobalsettings::USERNAME_REQUIREMENT_MODE_NUMBER_ONLY ?>;
	_viewRegistrationSettings._username_requirement_mode_letters_only = <?=Operatorglobalsettings::USERNAME_REQUIREMENT_MODE_LETTERS_ONLY ?>;
	_viewRegistrationSettings._username_requirement_mode_numbers_and_letters_only = <?=Operatorglobalsettings::USERNAME_REQUIREMENT_MODE_NUMBERS_AND_LETTERS_ONLY ?>;
	/// _MTMG = _marketing_management
	var _MTMG = marketing_management.initialize({
		'viewRegistrationSettings': _viewRegistrationSettings
	});

	$(document).ready(function() {

		_MTMG.viewRegistrationSettings.initEvents();

		changeRegistrationSettings('<?=$type;?>');
		if ( self !== top ) {
			$('footer').hide();
			$('#lhc_status_container').hide();
		}

		var selectorList = [];
		selectorList.push('#updateRegistrationSettingsForm');
		$('body').on('reset', selectorList.join(','), function(e){
			var theTarget$El = $(e.target);
			setTimeout(function(){ // delay
				theTarget$El.find('input:checkbox.onoffswitch-checkbox[onchange*="toggleFieldVisibility"]').each(function(){
					toggleFieldVisibility( $(this).data('registration-field-id') );
				});
			}, 300);
		});

		var selectorList = [];
		selectorList.push('#updateAccountInformationForm');
		$('body').on('reset', selectorList.join(','), function(e){
			var theTarget$El = $(e.target);
			setTimeout(function(){ // delay
				theTarget$El.find('input:checkbox.onoffswitch-checkbox[onchange*="toggleFieldVisibility"]').each(function(){
					toggleFieldVisibility( $(this).data('registration-field-id')+ '_account');
				});
			}, 300);
		});

		var selectorList = [];
		selectorList.push('#updateLoginSettingsForm');
		$('body').on('reset', selectorList.join(','), function(e){
			setTimeout(function(){ // delay
				initPlayerLoginFailedInputGroup();
			}, 300);
		});
	});

	function setMinMaxPassword($min = 6,$max = 20){
		$('input[name=set_min_max_password]').each(function() {
			$(".set_min_max_password").empty();
			if (this.checked) {
				$(".set_min_max_password").append('<div class="pull-left" style="margin-right: 20px;">\
			    	<?=lang('Minimum Password')?>\
			    </div>\
			    <div class="pull-left">\
			    	<input type="number" class="" id="min_password" name="min_password" style="width: 80px;" min="6" max="20" value="'+$min+'" onkeydown="return false">\
			    </div>\
			    <div class="clearfix"></div>\
			    <br>\
			    <div class="pull-left" style="margin-right: 20px;">\
			    	<?=lang('Maximum Password')?>\
			    </div>\
			    <div class="pull-left">\
			    	<input type="number" class="" id="max_password" name="max_password" style="width: 80px;" min="6" max="20" value="'+$max+'" onkeydown="return false">\
			    </div>')
			}
			else{
				$(".set_min_max_password").empty();
			}
		});
	}

	function changeRegistrationSettings(type, callbackOnReadyStateChange) {
	    var xmlhttp = GetXmlHttpObject();

	    if (xmlhttp == null) {
	        alert("Browser does not support HTTP Request");
	        return;
	    }

	    url = base_url + "marketing_management/changeRegistrationSettings/" + type;

		var div = document.getElementById("nav_content");

	    xmlhttp.onreadystatechange = function() {
			if (xmlhttp.readyState == 4) {
				div.innerHTML = xmlhttp.responseText;
	            $('.tab').removeClass('active');

	            if(type == 1) {
					initializedCheckAll('visible');
					initializedCheckAll('required');
	                $('#player').addClass('active');
                    initPlayerLoginFailedInputGroup();
                    $('input[name="player_login_failed_attempt_blocked"]').on('click' ,function(){
                        initPlayerLoginFailedInputGroup();
                    });
	            }else if (type == 2) {
					initializedCheckAll('visible');
					initializedCheckAll('required');
	                $('#affiliate').addClass('active');
				}
				$('#Manual_unlock').on('click' ,function(){
					$('.player_login_failed_attempt_set_locktime>div>.warning_message').addClass('hide_warning_message');
					$('#player_login_failed_attempt_reset_timeout').attr({readonly:'readonly',disabled:'disabled'});
				});
				$('#Auto_unlock').on('click' ,function(){
					$('#player_login_failed_attempt_reset_timeout').removeAttr('readonly').removeAttr('disabled');
				});
				$("#player_login_failed_attempt_times").on('focus',function() {
					$(this).next('.warning_message').removeClass('hide_warning_message');
				}).on('blur',function() {
					$(this).next('.warning_message').addClass('hide_warning_message');
				});

				$("#player_login_failed_attempt_reset_timeout").on('focus',function() {
					$('.player_login_failed_attempt_set_locktime>div>.warning_message').removeClass('hide_warning_message');
				}).on('blur',function() {
					$('.player_login_failed_attempt_set_locktime>div>.warning_message').addClass('hide_warning_message');
				});
	        }

	        if (xmlhttp.readyState != 4) {
	            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/><?=lang('text.loading')?></td></tr></table>';
	        }

			/// aka. Call callbackOnReadyStateChange()/_changeRegistrationSettingsCallback() from xmlhttp with params, cloned_arguments.
			if( typeof(callbackOnReadyStateChange) !== 'undefined'
				&& typeof(callbackOnReadyStateChange) === 'function'
			){
				var cloned_arguments = Array.prototype.slice.call(arguments);
				callbackOnReadyStateChange.apply(xmlhttp, cloned_arguments);
			}else{
				_changeRegistrationSettingsCallback.apply(xmlhttp, cloned_arguments);
			}

	    }

	    xmlhttp.open("GET", url, true);
	    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	    xmlhttp.send();
	} // EOF changeRegistrationSettings()
	//
	function _changeRegistrationSettingsCallback(){

		var cloned_arguments = Array.prototype.slice.call(arguments);
		if( typeof($('#orignal_data').html()) !== 'undefined' ){
			_MTMG.viewRegistrationSettings.orignal_data = JSON.parse($('#orignal_data').html());
		}
		// _MTMG.viewRegistrationSettings Need assign to 1st param
		_MTMG.viewRegistrationSettings.onreadystatechange.apply(_MTMG.viewRegistrationSettings, cloned_arguments);
	}// EOF _changeRegistrationSettingsCallback()

	function initPlayerLoginFailedInputGroup(){
		var player_login_failed_input_group = [
			'player_login_failed_attempt_reset_timeout',
			'player_login_failed_attempt_admin_unlock',
			'player_login_failed_attempt_times'
		];
		if(!$('input[name="player_login_failed_attempt_blocked"]')[0].checked){
			$.each(player_login_failed_input_group, function(index, inputName){
				$('input[name="'+inputName+'"]').attr({readonly:'readonly',disabled:'disabled'});
			});
		} else {
			$.each(player_login_failed_input_group, function(index, inputName){
				$('input[name="'+inputName+'"]').removeAttr('readonly').removeAttr('disabled');
			});
		}

		if($('#Manual_unlock')[0].checked) {
			$('.player_login_failed_attempt_set_locktime>div>.warning_message').addClass('hide_warning_message');
			$('#player_login_failed_attempt_reset_timeout').attr({readonly:'readonly',disabled:'disabled'});
		}
		return false;
	}

	function fullAddressToggle(target) {
		$('.'+ target +'_address_toggle').toggle();
	}

    function hasDuplicates(array){
		return (new Set(array)).size !== array.length;
    }

    function validatePlayerRegForm(){
        //don't allow agency and aff
        // var field_agency=$('#updateRegistrationSettingsForm input[name=46_visible]').is(":checked");
        // var field_aff=$('#updateRegistrationSettingsForm input[name=13_visible]').is(":checked");
        // if(field_aff && field_agency){
        // 	alert("<?=lang('Cannot show agency and affiliate both')?>");
        // 	return false;
        // }

		if( $('input[name="min_password"]:visible').length > 0 ){ // if enable "Set Min/Max Password".
			var min = $('input[name="min_password"]:visible').val();
			var max = $('input[name="max_password"]:visible').val();
			if( parseInt(max,10) < parseInt(min,10) ){ // adjust for max value SMALL than min value.
				setMinMaxPassword(max, min);
			}
		}

        return true;
    }

</script>
