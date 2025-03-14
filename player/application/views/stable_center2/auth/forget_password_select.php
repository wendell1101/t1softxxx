<div class="row " style="padding-top:10%; padding-bottom:10%;">
	<div class="col-md-4 col-md-offset-4 fp-wrapper">
		<div class="panel panel-default"  style="padding-bottom: 20px">
			<div class="panel-heading" style="margin-bottom: 20px">
				<?=lang('lang.forgotpasswd')?>
				<a href="/iframe/auth/login" class="close" aria-hidden="true">×</a>
			</div>
			<div style="padding:0 10px">
			<?php if ($password_recovery_option_1 && !$this->utils->getConfig('hide_default_forgot_password_settings')) { ?>
			<input type="button" class="btn btn-primary" value="<?=lang('Find password by security question')?>"
				onclick="location.href='<?=site_url('iframe_module/forgot_password')?>'" />
			<?php } ?>
			<?php if ($password_recovery_option_2 && !$this->utils->getConfig('hide_default_forgot_password_settings')) { ?>
			<input type="button" class="btn btn-primary" value="<?=lang('Find password by SMS')?>"
				onclick="location.href='<?=site_url('iframe_module/password_recovery_sms')?>'" />
				<?php if(!$this->CI->config->item('disabled_voice')) { ?>
					<input type="button" class="btn btn-primary" value="<?=lang('Find password by voice service')?>"
						onclick="location.href='<?=site_url('iframe_module/password_recovery_sms/voice')?>'" />
				<?php } ?>
			<?php } ?>
			<?php if ($password_recovery_option_3 && !$this->utils->getConfig('hide_default_forgot_password_settings')) { ?>
			<input type="button" class="btn btn-primary" value="<?=lang('Find password by email')?>"
				onclick="location.href='<?=site_url('iframe_module/password_recovery_email')?>'" />
			<?php } ?>
			<?php if ($this->CI->config->item('enable_forget_password_custom_block')){
                echo $this->CI->config->item('forget_password_custom_block_content');
            }?>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    <?php if(!empty($this->utils->getTrackingScriptWithDoamin("player", "title", "header"))){?>
        $("title").text('<?=$this->utils->getTrackingScriptWithDoamin("player", "title", "header");?>');
    <?php }?>

    <?php if(!empty($this->utils->getTrackingScriptWithDoamin("player", "meta_description", "header"))){?>
        $("head").append(<?=json_encode($this->utils->getTrackingScriptWithDoamin("player", "meta_description", "header"))?>);
    <?php }?>

    <?php if(!empty($this->utils->getTrackingScriptWithDoamin("player", "meta_keywords", "header"))){?>
        $("head").append(<?=json_encode($this->utils->getTrackingScriptWithDoamin("player", "meta_keywords", "header"))?>);
    <?php }?>

    $("body").addClass("pwd_recovery");
});
</script>