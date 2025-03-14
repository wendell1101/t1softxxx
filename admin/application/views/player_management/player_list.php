<?php include VIEWPATH . '/includes/messages.php'; ?>

<?php
if (isset($_GET['master_affiliate'])) {
    $master_affiliate = $_GET['master_affiliate'];
} else {
    $master_affiliate = null;
}
if (isset($_GET['master_agent'])) {
    $master_agent = $_GET['master_agent'];
} else {
    $master_agent = null;
}

$player_list_custom_order = isset($this->utils->getConfig('player_list_column_order')['custom_order']) ? $this->utils->getConfig('player_list_column_order')['custom_order']:$this->utils->getConfig('player_list_column_order')['default_order'];
?>
<style>
    .timezone-text-info {
        color: inherit;
    }

    .color_ticket_wrapper {
        padding: 2px 4px 2px 4px;
        margin: 2px;
        white-space: nowrap;
    }
</style>
<h4 class="m-t-0"><?=lang('Player List')?></h4>
<form id="search-form_new">
<div class="panel panel-primary">
  <div class="panel-heading">
    <h4 class="page-header m-t-10 general-conditions-panel"><?=lang('General Conditions')?></h4>
    <div class="row">
      <div class="col-md-12 p-0">
        <div class="col-md-3">
          <div class="form-group">
            <label class="control-label"><?=lang('player.38')?>:</label>
            <div class="input-group">
                <input id="search_registration_date" class="form-control input-sm dateInput" data-start="#registration_date_from" data-end="#registration_date_to" data-time="true"/>
                <span class="input-group-addon input-sm">
                    <input type="checkbox" name="search_reg_date" id="search_reg_date" <?php echo $conditions['search_reg_date']  == 'on' ? 'checked="checked"' : '' ?> />
                </span>
                <input type="hidden" name="registration_date_from" id="registration_date_from" value="<?=$conditions['registration_date_from'];?>" />
                <input type="hidden" name="registration_date_to" id="registration_date_to" value="<?=$conditions['registration_date_to'];?>" />
            </div>
          </div>
        </div> <!-- EOF .col-md-3 -->
        <div class="col-md-3">
          <div class="form-group">
            <label class="control-label"><?=lang('Last Login Date')?>:</label>
            <div class="input-group">
                <input id="search_last_login_date" class="form-control input-sm dateInput" data-start="#last_login_date_from" data-end="#last_login_date_to" data-time="true"/>
                <span class="input-group-addon input-sm">
                    <input type="checkbox" name="search_last_log_date" id="search_last_log_date" <?php echo $conditions['search_last_log_date']  == 'on' ? 'checked="checked"' : '' ?> />
                </span>
                <input type="hidden" name="last_login_date_from" id="last_login_date_from" value="<?=$conditions['last_login_date_from'];?>" />
                <input type="hidden" name="last_login_date_to" id="last_login_date_to" value="<?=$conditions['last_login_date_to'];?>" />
            </div>
          </div>
        </div> <!-- EOF .col-md-3 -->

        <?php if($enable_timezone_query): ?>
            <!-- Timezone( + - ) hr -->
            <div class="col-md-2 col-lg-2">
                <label class="control-label" for="group_by"><?=lang('Timezone')?></label>
                <!-- <input type="number" id="timezone" name="timezone" class="form-control input-sm " value="<?=$conditions['timezone'];?>" min="-12" max="12"/> -->
                <?php
                $default_timezone = $this->utils->getTimezoneOffset(new DateTime());
                $timezone_offsets = $this->utils->getConfig('timezone_offsets');
                $timezone_location = $this->utils->getConfig('current_php_timezone');
                ?>
                <select id="timezone" name="timezone"  class="form-control input-sm">
                    <?php for($i = 12;  $i >= -12; $i--): ?>
                        <?php if($conditions['timezone'] || $conditions['timezone'] == '0' ): ?>
                            <option value="<?php echo $i > 0 ? "+{$i}" : $i ;?>" <?php echo ($i == $conditions['timezone']) ? 'selected' : ''?>> <?php echo $i > 0 ? "+{$i}" : $i ;?>:00</option>
                        <?php else: ?>
                        <option value="<?php echo $i > 0 ? "+{$i}" : $i ;?>" <?php echo ($i==$default_timezone) ? 'selected' : ''?>> <?php echo $i >= 0 ? "+{$i}" : $i ;?></option>
                    <?php endif;?>
                <?php endfor;?>
                </select>
                <div class="" style="">
                    <i class="text-info timezone-text-info" style="font-size:10px;"><?php echo lang('System Timezone') ?>: (GMT <?php echo ( $default_timezone >= 0) ? '+'. $default_timezone  : $default_timezone; ?>) <?php echo $timezone_location ;?></i>
                </div>
            </div>
        </div><!-- EOF col-md-12 p-0 -->
        <div class="col-md-12 p-0">
        <?php else: ?>
            <input type="hidden" id="timezone" name="timezone" class="form-control input-sm " value="0"/>
        <?php endif; // EOF if($enable_timezone_query): ?>

        <!-- prevent_player_list_preload -->
        <?php if($this->utils->getConfig('prevent_player_list_preload')): ?>
        <input type="hidden" id="prevent_player_list_preload" name="prevent_player_list_preload" class="form-control input-sm" value="<?=$conditions['prevent_player_list_preload'];?>" />
        <?php endif; // EOF if($prevent_player_list_preload): ?>

        <div class="col-md-2" style="margin-top:-5px;">
            <div class="form-group">
                    <label  for="username" class="control-label">
                        <?=lang('Username')?>:
                    </label>
                    <input type="radio" id="search_by_exact" name="search_by" value="2" <?php echo $conditions['search_by']  == '2' ? 'checked="checked"' : '' ?>/>
                    <label  for="search_by_exact" class="control-label">
                        <?=lang('Exact.abridged')?>
                    </label>
                    <input type="radio" id="search_by_similar" name="search_by" value="1" <?php echo $conditions['search_by']  == '1' ? 'checked="checked"' : '' ?> />
                    <label  for="search_by_similar" class="control-label">
                        <?=lang('Similar.abridged')?>
                    </label>
                <input type="text" name="username" id="username" value="<?php echo $conditions['username']; ?>" class="form-control input-sm">
            </div>
        </div> <!-- EOF .col-md-2 -->
        <div class="col-md-2">
            <div class="form-group">
                <label for="game_username" class="control-label"><?=lang('Game Account Username')?>:</label>
                <input type="text" name="game_username" id="game_username" class="form-control input-sm"  value="<?php echo $conditions['game_username']; ?>" >
            </div>
        </div> <!-- EOF .col-md-2 -->
        <div class="col-md-2">
            <div class="form-group">
                <label class="control-label"><?=lang('player_list.fields.vip_level')?>:</label>
                <select name="player_level[]" id="player_level" multiple="multiple" class="form-control input-sm">
                    <?php if (!empty($levels)): ?>
                        <?php foreach ($levels as $levelId => $levelName): ?>
                            <option value="<?=$levelId?>" <?=is_array($conditions['player_level']) && in_array($levelId, $conditions['player_level']) ? "selected" : "" ?> ><?=$levelName?></option>
                        <?php endforeach ?>
                    <?php endif ?>
                </select>
            </div>
        </div> <!-- EOF .col-md-2 -->

        <?php if ( empty($this->utils->getConfig('hide_iptaglist') ) ): ?>
        <div class="col-md-2">
        <div class="form-group">
            <label class="control-label"><?=lang('exclude_ip_tag_list')?>:</label>
            <select name="ip_tag_list[]" id="ip_tag_list" multiple="multiple" class="form-control input-md">
                <?php if (!empty($ip_tags)): ?>
                    <option value="notag" id="notag" <?=is_array($selected_ip_tags) && in_array('notag', $selected_ip_tags) ? "selected" : "" ?>><?=lang('player.tp12')?></option>
                    <?php foreach ($ip_tags as $tag): ?>
                        <option value="<?=$tag['id']?>" <?=is_array($selected_ip_tags) && in_array($tag['id'], $selected_ip_tags) ? "selected" : "" ?> ><?=$tag['name']?></option>
                    <?php endforeach ?>
                <?php endif ?>
            </select>
          </div>
        </div> <!-- EOF .col-md-2 -->
    <?php endif ?>
      </div><!-- EOF col-md-12 p-0 -->

      <div class="col-md-12 p-0">
       <div class="col-md-3">
          <div class="form-group">
            <label class="control-label"><?=lang('exclude_player')?>:</label>
            <select name="tag_list[]" id="tag_list" multiple="multiple" class="form-control input-md">
                <?php if (!empty($tags)): ?>
                    <option value="notag" id="notag" <?=is_array($selected_tags) && in_array('notag', $selected_tags) ? "selected" : "" ?>><?=lang('player.tp12')?></option>
                    <?php foreach ($tags as $tag): ?>
                        <option value="<?=$tag['tagId']?>" <?=is_array($selected_tags) && in_array($tag['tagId'], $selected_tags) ? "selected" : "" ?> ><?=$tag['tagName']?></option>
                    <?php endforeach ?>
                <?php endif ?>
            </select>
          </div>
        </div>
       <?php if (!$this->utils->isEnabledFeature('close_aff_and_agent')): ?>
        <div class="col-md-3">
          <div class="form-group">
            <label for="affiliate" class="control-label"><?=lang('player.24')?>:</label>
            <div class="input-group">
                <input type="text" name="affiliate" id="affiliate" class="form-control input-sm" value="<?php echo $conditions['affiliate']; ?>"/>
              <span class="input-group-addon input-sm">
                <input type="checkbox" name="aff_include_all_downlines"  <?php echo $conditions['aff_include_all_downlines']  == 'on' ? 'checked="checked"' : '' ?>/>
                <?=lang('Include Direct Downline')?> <!-- Include All Downlines Affiliate -->
              </span>
            </div>
          </div>
        </div>
        <?php endif; // EOF if (!$this->utils->isEnabledFeature('close_aff_and_agent')) ?>
        <div class="col-md-4" style="margin-top:-5px;">
          <div class="form-group">

            <label class="control-label" for="agent_name">
              <?=lang('Under Agency')?>: <!-- own_downline_or_agency_line trans to own_downline and agency_line -->
            </label>
                <input type="radio" id="own_downline" name="own_downline_or_agency_line" value="own_downline" <?php echo $conditions['own_downline_or_agency_line']  == 'own_downline' ? 'checked="checked"' : '' ?>/>
                <label class="control-label" for="own_downline">
                    <?=lang('Own Downline')?> <!-- Created on Agency -->
                </label>
                <input type="radio" id="agency_line" name="own_downline_or_agency_line" value="agency_line" <?php echo $conditions['own_downline_or_agency_line']  == 'agency_line' ? 'checked="checked"' : '' ?>/>
                <label class="control-label" for="agency_line">
                    <?=lang('Agency Line')?> <!-- Include All Downlines -->
                </label>
            </label>
            <input type="text" name="agent_name" id="agent_name" class="form-control input-sm" value="<?php echo $conditions['agent_name']; ?>">
          </div>
        </div>
        <div class="col-md-2">
          <div class="form-group">
            <label class="control-label"><?=lang('Referred By')?>:</label>
            <input type="text" name="referred_by" class="form-control input-sm" value="<?php echo $conditions['referred_by']; ?>">
          </div>
        </div> <!-- EOF col-md-2 -->

      </div> <!-- EOF col-md-12 p-0 -->

      <div class="col-md-12 p-0">
       <div class="col-md-3">
          <div class="form-group">
            <label class="control-label"><?=lang('include_player')?>:</label>
            <select name="include_tag_list[]" id="include_tag_list" multiple="multiple" class="form-control input-md">
                <?php if (!empty($tags)): ?>
                    <option value="notag" id="notag" <?=is_array($include_selected_tags) && in_array('notag', $include_selected_tags) ? "selected" : "" ?>><?=lang('player.tp12')?></option>
                    <?php foreach ($tags as $tag): ?>
                        <option value="<?=$tag['tagId']?>" <?=is_array($include_selected_tags) && in_array($tag['tagId'], $include_selected_tags) ? "selected" : "" ?> ><?=$tag['tagName']?></option>
                    <?php endforeach ?>
                <?php endif ?>
            </select>
          </div>
        </div><!-- EOF .col-md-3 -->
      </div> <!-- EOF col-md-12 p-0 -->
    </div> <!-- EOF .row -->
  </div><!-- EOF panel-heading -->
  <div class="panel-body advanced-conditions-panel">
    <h4><?=lang('Advanced Conditions')?></h4>
    <button class="btn btn-block collapsed collapse-btn" type="button" data-toggle="collapse" data-target="#advancedConditions" aria-expanded="false" aria-controls="collapseExample">
      <hr>
    </button>
    <div class="collapse" id="advancedConditions">
      <div class="row">
        <div class="clearfix col-md-12 p-0 m-b-5">
          <div class="col-md-2">
            <h5 class="text-nowrap">【<?=lang('lang.signupinfo')?>】</h5>
          </div>
          <div class="col-md-10">
            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
              <button data-for="registered_by" type="button" class="btn btn-block btn-conditions"><?=lang('Registered By')?></button>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
              <button data-for="registration_website" type="button" class="btn btn-block btn-conditions"><?=lang('Registered Website')?></button>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
              <button data-for="ip_address" type="button" class="btn btn-block btn-conditions"><?=lang('Signup IP')?></button>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
              <button data-for="lastLoginIp" type="button" class="btn btn-block btn-conditions"><?=lang('player_list.fields.last_login_ip')?></button>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
              <button data-for="blocked" type="button" class="btn btn-block btn-conditions"><?=lang('lang.accountstatus')?></button>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
              <button data-for="friend_referral_code" type="button" class="btn btn-block btn-conditions"><?=lang('player_list.fields.referral_code')?></button>
            </div>
          </div>
        </div>
        <div class="clearfix col-md-12 p-0 m-b-5">
          <div class="col-md-2">
            <h5 class="text-nowrap">【<?=lang('Personal Information')?>】</h5>
          </div>
          <div class="col-md-10">
            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
              <button data-for="first_name" type="button" class="btn btn-block btn-conditions"><?=lang('First Name')?></button>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
              <button data-for="last_name" type="button" class="btn btn-block btn-conditions"><?=lang('player.05')?></button>
            </div>
            <?php
            /// Patch for OGP-14711 : Modify new layout of the "Player List": SBE_Player > All Player
            // Remove this permission and combine with "View Player List Contact Information (Contact #)" permission.
            if (    $this->permissions->checkPermissions('view_player_detail_contactinfo_cn')
                || true // Patch for OGP-15540
            ): ?>
            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
              <button data-for="contactNumber" type="button" class="btn btn-block btn-conditions"><?=lang('player.63')?></button>
            </div>

            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
              <button data-for="phone_status" type="button" class="btn btn-block btn-conditions"><?=lang('Contact No. Verify Status')?></button> <!-- Phone Status -->
            </div>
            <?php
                // OGP-15078 "Contact No. Verify Status" should same as "Contact Number" by view_player_detail_contactinfo_cn.
                // Permission off, switch contact verify status in advanced condition still exists
                endif; //  if ($this->permissions->checkPermissions('view_player_detail_contactinfo_cn'))?>
            <?php if (  $this->permissions->checkPermissions('view_player_detail_contactinfo_em')
                    || true // Patch for OGP-15540
            ): // Patch for OGP-15077 ?>
            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
              <button data-for="email" type="button" class="btn btn-block btn-conditions"><?=lang('player_list.fields.email_address')?></button>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
              <button data-for="email_status" type="button" class="btn btn-block btn-conditions"><?=lang('Email Verify Status')?></button>
            </div>
            <?php endif; //  if ($this->permissions->checkPermissions('view_player_detail_contactinfo_em'))?>
            <?php
            // OGP-15079 Modify permission of "Search Player IM" and " View Player List Contact Information (IM)": SBE_System >View Roles
            if (    $this->permissions->checkPermissions('view_player_detail_contactinfo_im')
                || true // Patch for OGP-15540
            ): ?>
            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
              <button data-for="im_account" type="button" class="btn btn-block btn-conditions"><?=lang('IM Account')?></button>
            </div>
            <?php endif; //  if ($this->permissions->checkPermissions('view_player_detail_contactinfo_im'))?>
            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
              <button data-for="residentCountry" type="button" class="btn btn-block btn-conditions"><?=lang('player_list.fields.country')?></button>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
              <button data-for="city" type="button" class="btn btn-block btn-conditions"><?=lang('player_list.fields.city')?></button>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
              <button data-for="id_card_number" type="button" class="btn btn-block btn-conditions"><?=lang('player_list.fields.id_card_number')?></button>
            </div>
            <?php if(in_array('cpf_number', $player_list_custom_order)) :?>
            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
              <button data-for="cpf_number" type="button" class="btn btn-block btn-conditions"><?=lang('player_list.fields.cpf_number')?></button>
            </div>
            <?php endif; //  EOF if(in_array('cpf_number', $player_list_custom_order)) ?>
            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
              <button data-for="fields_search_dob" type="button" class="btn btn-block btn-conditions"><?=lang('player_list.fields.DOB')?></button>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
              <button data-for="affiliate_network_source" type="button" class="btn btn-block btn-conditions"><?=lang('Affiliate Network Source')?></button>
            </div>
          </div>
        </div>
        <div class="clearfix col-md-12 p-0 m-b-5">
          <div class="col-md-2">
            <h5 class="text-nowrap">【<?=lang('Others')?>】</h5>
          </div>
          <div class="col-md-10">
            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
              <button data-for="player_bank_account_number" type="button" class="btn btn-block btn-conditions"><?=lang('player_list.fields.bank_account')?></button>
            </div>

            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
              <!-- <button data-for="deposit_count" type="button" class="btn btn-block btn-conditions"><?=lang('Deposit Count')?></button> --> <!-- hook Deposit input -->
              <button data-for="deposit" type="button" class="btn btn-block btn-conditions"><?=lang('Has Deposit')?></button> <!-- hook Deposit select -->
            </div>

            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
              <button data-for="inputs-total_balance" data-col="col-total_balance" type="button" class="btn btn-block btn-conditions"><?=lang('Total Balance')?></button>
            </div>

            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
              <button data-for="search_deposit_approve" type="button" class="btn btn-block btn-conditions"><?=lang('player_list.search_deposit_approve')?></button>
            </div>

            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
              <button data-for="search_latest_deposit_date" type="button" class="btn btn-block btn-conditions"><?=lang('player_list.search_latest_deposit_date')?></button>
            </div>

            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
              <button data-for="inputs-total_deposit_count" data-col="col-total_deposit_count" type="button" class="btn btn-block btn-conditions"><?=lang('Total Deposit Count')?></button>
            </div>

            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
              <button data-for="inputs-total_deposit" data-col="col-total_deposit" type="button" class="btn btn-block btn-conditions"><?=lang('Total Deposit Amt')?></button>
            </div>

            <?php
            if(in_array('affiliate_source_code', $player_list_custom_order)) :?>
            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
                <button data-for="affiliate_source_code" type="button" class="btn btn-block btn-conditions"><?=lang('player_list.affiliate_source_code')?></button>
            </div>
            <?php endif; ?>

            <?php if(in_array('player_sales_agent', $player_list_custom_order)) :?>
                <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
                    <button data-for="player_sales_agent" type="button" class="btn btn-block btn-conditions"><?=lang('Has Sales Agent')?></button>
                </div>
            <?php endif; ?>

            <?php if(in_array('daysSinceLastDeposit', $player_list_custom_order)) :?>
                <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
                    <button data-for="daysSinceLastDeposit" type="button" class="btn btn-block btn-conditions"><?=lang('player.DaysSinceLastDeposit')?></button>
                </div>
            <?php endif; ?>

            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
                    <button data-for="cashback" data-col="col-cashback" type="button" class="btn btn-block btn-conditions"><?=lang('Cashback')?></button>
            </div>

            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
                    <button data-for="promotion"  data-col="col-promotion" type="button" class="btn btn-block btn-conditions"><?=lang('Promotion')?></button>
            </div>

            <?php if ($this->utils->getConfig('enabled_priority_player_features')) :?>
                <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
                    <button data-for="priority" data-col="col-priority" type="button" class="btn btn-block btn-conditions"><?=lang('player_list.fields.priority')?></button>
                </div>
            <?php endif; //  EOF if (!$this->utils->getConfig('enabled_priority_player_features')) ?>

            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
                    <button data-for="withdrawal_status"  data-col="col-withdrawal_status" type="button" class="btn btn-block btn-conditions"><?=lang('Withdrawal Status')?></button>
            </div>

          </div>
        </div>
      </div>
      <hr>
      <div class="row">
        <div class="clearfix col-md-12 p-0">
          <div class="col-md-2">
            <div class="form-group">
              <label for="registered_by" class="control-label"><?=lang('Registered By')?>:</label>
                <select name="registered_by" id="registered_by" class="form-control input-sm">
                    <option value="" <?php echo $conditions['registered_by']  == null ? 'selected' : '' ?> > <?=lang('All')?></option>
                    <option value="<?=Player_model::REGISTERED_BY_MASS_ACCOUNT?>" ><?=lang('Batch Create')?></option>
                    <option value="<?=Player_model::REGISTERED_BY_WEBSITE?>" ><?=lang('player.68')?></option>
                    <option value="<?=Player_model::REGISTERED_BY_MOBILE?>" ><?=lang('player_list.fields.Mobile')?></option>
                    <option value="<?=Player_model::REGISTERED_BY_IMPORTER?>" ><?=lang('Imported Account')?></option>
                    <option value="<?=Player_model::REGISTERED_BY_AGENCY_CREATED?>" ><?=lang('Created by agency')?></option>
                    <option value="<?=Player_model::REGISTERED_BY_PLAYER_CENTER_API?>" ><?=lang('Player Center API')?></option>
                </select>
            </div>
          </div>
          <div class="col-md-3 col-rwsb">
            <div class="form-group">
                <label for="registration_website"  class="control-label"><?=lang('Registered Website')?>:</label> <!-- orig: Registration Website -->
                <input type="radio" id="reg_web_search_by_exact" name="reg_web_search_by" value="2" <?php echo $conditions['reg_web_search_by']  == '2' ? 'checked="checked"' : '' ?>"/>
                <label  for="reg_web_search_by_exact" class="control-label">
                    <?=lang('Exact.abridged')?>
                </label>
                <input type="radio" id="reg_web_search_by_similar" name="reg_web_search_by" value="1" <?php echo $conditions['reg_web_search_by']  == '1' ? 'checked="checked"' : '' ?>" />
                <label  for="reg_web_search_by_similar" class="control-label">
                    <?=lang('Similar.abridged')?>
                </label>
                <input type="text" name="registration_website" id="registration_website" value="<?php echo $conditions['registration_website']; ?>" class="form-control input-sm">

            </div>
          </div>
          <div class="col-md-2">
            <div class="form-group">
              <label for="ip_address" class="control-label"><?=lang('Signup IP')?>:</label>
              <input type="text" name="ip_address" id="ip_address" class="form-control input-sm" value="<?php echo $conditions['ip_address']; ?>">
            </div>
          </div>
          <div class="col-md-2">
            <div class="form-group">
              <label for="lastLoginIp" class="control-label"><?=lang('player_list.fields.last_login_ip')?>:</label>
              <input type="text" name="lastLoginIp" id="lastLoginIp" class="form-control input-sm" value="<?php echo $conditions['lastLoginIp']; ?>">
            </div>
          </div>
          <div class="col-md-2">
            <div class="form-group">
              <label for="blocked" class="control-label"><?=lang('lang.accountstatus')?>:</label>
              <select name="blocked" id="blocked" class="form-control input-sm">
                <option value=""  <?php echo $conditions['blocked']  == null ? 'selected' : '' ?> ><?=lang('All')?></option>
                <option value="0" <?php echo $conditions['blocked']  == '0' ? 'selected' : '' ?> ><?=lang('status.normal')?></option>
                <option value="1" <?php echo $conditions['blocked']  == '1'? 'selected' : '' ?> ><?=lang('player_list.options.blocked')?></option>
                <?php if ($this->utils->isEnabledFeature('add_suspended_status')):?>
                    <option value="5" <?php echo $conditions['blocked']  == '5' ? 'selected' : '' ?> ><?=lang('player_list.options.suspended')?></option>
                <?php endif ?>
                <?php if ($this->utils->isEnabledFeature('responsible_gaming')):?>
                    <option value="7" <?php echo $conditions['blocked']  == '7' ? 'selected' : '' ?> ><?=lang('player_list.options.self_exclusion')?></option>
                <?php endif ?>
                <?php if ($this->operatorglobalsettings->getSettingBooleanValue('player_login_failed_attempt_blocked')):?>
                    <option value="<?=Player_model::STATUS_BLOCKED_FAILED_LOGIN_ATTEMPT?>" <?php echo $conditions['blocked']  == Player_model::STATUS_BLOCKED_FAILED_LOGIN_ATTEMPT ? 'selected' : '' ?> ><?=lang('player_list.options.failed_login_attempt')?></option>
                <?php endif ?>
              </select>
            </div>
          </div>
          <div class="col-md-2">
            <div class="form-group">
              <label data-for="friend_referral_code" class="control-label"><?=lang('player_list.fields.referral_code')?>:</label>
              <input type="text" name="friend_referral_code" id="friend_referral_code" class="form-control input-sm" value="<?php echo $conditions['friend_referral_code']; ?>" />
            </div>
          </div>
        <!-- </div> --> <!-- EOF clearfix col-md-12 p-0 -->
        <!-- <div class="clearfix col-md-12 p-0"> -->
          <div class="col-md-2">
            <div class="form-group">
              <label data-for="first_name" class="control-label"><?=lang('First Name')?>:</label>
              <input type="text" name="first_name" id="first_name" class="form-control input-sm" value="<?php echo $conditions['first_name']; ?>">
            </div>
          </div>
          <div class="col-md-2">
            <div class="form-group">
              <label data-for="last_name" class="control-label"><?=lang('player.05')?>:</label>
              <input type="text" name="last_name" id="last_name" class="form-control input-sm" value="<?php echo $conditions['last_name']; ?>">
            </div>
          </div>
          <?php
          /// Patch for OGP-14711 : Modify new layout of the "Player List": SBE_Player > All Player
    // Remove this permission and combine with "View Player List Contact Information (Contact #)" permission.
          if (  $this->permissions->checkPermissions('view_player_detail_contactinfo_cn')
              || true // Patch for OGP-15540
          ):?>
          <div class="col-md-2">
            <div class="form-group">
              <label class="control-label"><?=lang('player.63')?>:</label>
              <input type="text" name="contactNumber" id="contactNumber" class="form-control input-sm" value="<?php echo $conditions['contactNumber']; ?>" >
            </div>
          </div>

          <div class="col-md-2">
            <div class="form-group">
              <label class="control-label"><?=lang('Contact No. Verify Status')?>:</label>
              <select name="phone_status" id="phone_status" class="form-control input-sm">
                <option value=""  <?php echo $conditions['phone_status']  == null ? 'selected' : '' ?> ><?=lang('All')?></option>
                <option value="1" <?php echo $conditions['phone_status']  == '1' ? 'selected' : '' ?> ><?=lang('player_list.options.verified');?></option>
                <option value="0" <?php echo $conditions['phone_status']  == '0' ? 'selected' : '' ?> ><?=lang('player_list.options.not_verifiedked');?></option>
              </select>
            </div>
          </div>
          <?php
          // OGP-15078 "Contact No. Verify Status" should same as "Contact Number" by view_player_detail_contactinfo_cn.
          // Permission off, switch contact verify status in advanced condition still exists
        endif; // if ($this->permissions->checkPermissions('view_player_detail_contactinfo_cn')) ?>

          <?php if ($this->permissions->checkPermissions('view_player_detail_contactinfo_em')
                    || true // Patch for OGP-15540
          ): // Patch for OGP-15077 ?>
          <div class="col-md-2">
            <div class="form-group">
              <label data-for="email" class="control-label"><?=lang('player_list.fields.email_address')?>:</label>
              <input type="email" class="form-control input-sm" name="email" id="email" value="<?php echo $conditions['email']; ?>">
            </div>
          </div>
          <div class="col-md-2">
            <div class="form-group">
              <label data-for="email_status" class="control-label"><?=lang('Email Verify Status')?>:</label>
                <select name="email_status" id="email_status" class="form-control input-sm">
                    <option value="" <?php echo $conditions['email_status']  == null ? 'selected' : '' ?> ><?=lang('All')?></option>
                    <option value="1" <?php echo $conditions['email_status']  == '1'? 'selected' : '' ?>  ><?php echo lang('player_list.options.verified'); ?></option>
                    <option value="0" <?php echo $conditions['email_status']  == '0' ? 'selected' : '' ?> ><?php echo lang('player_list.options.not_verifiedked'); ?></option>
                </select>
            </div>
          </div>
          <?php endif; // if ($this->permissions->checkPermissions('view_player_detail_contactinfo_em')) ?>
        <!-- </div> --> <!-- EOF clearfix col-md-12 p-0 -->
        <!-- <div class="clearfix col-md-12 p-0"> -->
          <?php if (    $this->permissions->checkPermissions('view_player_detail_contactinfo_im')
                    || true // Patch for OGP-15540
          ): ?>
          <div class="col-md-2">
            <div class="form-group">
              <label for="im_account" class="control-label"><?=lang('IM Account')?>:</label>
              <input type="text" name="im_account" id="im_account" class="form-control input-sm" value="<?php echo $conditions['im_account']; ?>" />
            </div>
          </div>
          <?php endif; // EOF if ($this->permissions->checkPermissions('view_player_detail_contactinfo_im'))?>
          <div class="col-md-2">
            <div class="form-group">
              <label for="residentCountry" class="control-label"><?=lang('player_list.fields.country')?>:</label>
              <select name="residentCountry" id="residentCountry" class="form-control input-sm">
                <option value="" <?php echo $conditions['residentCountry']  == null ? 'selected' : '' ?> > <?=lang('All')?></option>
                <?php foreach (unserialize(COUNTRY_LIST) as $key): ?>
                <option value="<?=$key?>"  <?php echo $conditions['residentCountry']  == $key ? 'selected' : '' ?> >
                    <?=lang('country.' . $key)?>
                </option>
                <?php endforeach;?>
              </select>
            </div>
          </div>
          <div class="col-md-2">
            <div class="form-group">
              <label for="city" class="control-label"><?=lang('player.19')?>:</label>
              <input type="text" name="city" id="city" class="form-control input-sm"  value="<?php echo $conditions['city']; ?>"  >
            </div>
          </div>
          <div class="col-md-2">
            <div class="form-group">
              <label for="id_card_number" class="control-label"><?=lang('player_list.fields.id_card_number')?>:</label>
              <input type="text" name="id_card_number" id="id_card_number" class="form-control input-sm" value="<?php echo $conditions['id_card_number']; ?>">
            </div>
          </div>

          <?php if(in_array('cpf_number', $player_list_custom_order)) :?>
          <div class="col-md-2">
            <div class="form-group">
              <label for="cpf_number" class="control-label"><?=lang('player_list.fields.cpf_number')?>:</label>
              <input type="text" name="cpf_number" id="cpf_number" class="form-control input-sm" value="<?php echo $conditions['cpf_number']; ?>">
            </div>
          </div>
          <?php endif; //  EOF if(in_array('cpf_number', $player_list_custom_order)) ?>
          <div class="col-md-4 col-dob">
            <div class="form-group">
              <label for="field_search_dob" class="control-label">
                <?=lang('Date of Birth')?>:
                <span class="input-group-affix">
                    <input type="radio" id="without_year" name="with_year" value="0" <?php if (! $setWithYear4Default): ?> checked="checked" <?php endif; ?> />
                    <label for="without_year" class="control-label"><?=lang('dt.withoutYear')?></label>
                </span>
                <span class="input-group-affix">
                    <input type="radio" id="with_year" name="with_year" value="1" <?php if ($setWithYear4Default): ?> checked="checked" <?php endif; ?> />
                    <label for="with_year" class="control-label"><?=lang('dt.withYear')?></label>
                </span>
              </label>
              <div class="input-group-del input-group-body-del ">
                <span class="fields_search_dob hide">
                    <input id="field_search_dob" name="fields_search_dob" class="form-control input-sm dateInput" data-start=".dob_from.with_year" data-end=".dob_to.with_year" data-time="false" data-with-year="1" data-extra-attr="getExtraAttr4field_search_dob_with_year()"/>
                    <input type="hidden" name="dob_from" class="dob_from with_year" data-rename="dob_from" data-def="<?=$conditions['dob_from'];?>" value="<?php if (! $isValidDate4WithYear): echo date('Y'). '-'; endif;?><?=$conditions['dob_from'];?>" />
                    <input type="hidden" name="dob_to" class="dob_to with_year" data-rename="dob_to" data-def="<?=$conditions['dob_to'];?>" value="<?php if (! $isValidDate4WithYear): echo date('Y'). '-'; endif;?><?=$conditions['dob_to'];?>" />
                </span>
                <span class="fields_search_dob">
                    <input id="field_search_dob_without_year" name="fields_search_dob_without_year" class="form-control input-sm dateInput" data-start=".dob_from.without_year" data-end=".dob_to.without_year" data-time="false" data-with-year="0" data-future="TRUE" data-extra-attr="getExtraAttr4field_search_dob_without_year()"/>
                    <input type="hidden" name="dob_from" class="dob_from without_year" data-rename="dob_from" data-def="<?=$conditions['dob_from'];?>" value="<?=$conditions['dob_from'];?>" />
                    <input type="hidden" name="dob_to" class="dob_to without_year" data-rename="dob_to" data-def="<?=$conditions['dob_to'];?>" value="<?=$conditions['dob_to'];?>" />
                </span>
              </div>
            </div>
          </div>
          <div class="col-md-2">
            <div class="form-group">
              <label for="affiliate_network_source" class="control-label"><?=lang('Affiliate Network Source')?>:</label>
                <select name="affiliate_network_source" id="affiliate_network_source" class="form-control input-sm">
                    <option value=""><?= lang('N/A') ?></option>
                    <?php foreach($affliate_network_source_list as $key => $value):?>
                    <option value="<?=(is_integer($key)? strtolower($value): $value) ?>" <?php echo (strtolower($value) == strtolower($conditions['affiliate_network_source'])) ? 'selected': ''?>><?= lang($value); ?></option>
                    <?php endforeach;?>
                </select>
            </div>
          </div>
          <div class="col-md-2">
            <div class="form-group">
              <label for="player_bank_account_number" class="control-label"><?=lang('player_list.fields.bank_account')?>:</label>
              <input type="text" name="player_bank_account_number" id="player_bank_account_number" class="form-control input-sm" value="<?php echo $conditions['player_bank_account_number']; ?>">
            </div>
          </div>
        <!-- </div> --> <!-- EOF clearfix col-md-12 p-0 -->
        <!-- <div class="clearfix col-md-12 p-0"> -->
          <div class="col-md-2">
            <div class="form-group">
              <!-- deposit input -->
              <!-- <label for="deposit_count" class="control-label"><?=lang('Has Deposit')?>:</label> -->
              <!-- <input name="deposit_count" id="deposit_count" type="text" class="form-control input-sm" value="<?php echo $conditions['deposit_count']; ?>"> -->

                <!-- deposit select -->
                <label for="deposit" class="control-label"><?=lang('Has Deposit')?>:</label>
                <select name="deposit" id="deposit" class="form-control input-sm">
                    <option value="" <?php echo $conditions['deposit']  == null ? 'selected' : '' ?> > <?=lang('All')?></option>
                    <option value="1" <?php echo $conditions['deposit']  == '1'? 'selected' : '' ?> > <?=lang('Yes')?></option>
                    <option value="0" <?php echo $conditions['deposit']  == '0'? 'selected' : '' ?> > <?=lang('No')?></option>
                </select>
            </div>
          </div>

            <div class="col-md-4 col-total_balance">
                <div class="row">
                    <div class="clearfix col-md-12 p-0">
                        <div class="col-md-6">
                            <div class="form-group inputs-total_balance">
                                <label for="total_balance_more_than" class="control-label"><?=lang('Total Balance')?> &gt;= </label>
                                <input type="text" name="total_balance_more_than" id="total_balance_more_than" class="form-control input-sm " value="<?php echo $conditions['total_balance_more_than']; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group inputs-total_balance">
                                <label for="total_balance_less_than" class="control-label"><?=lang('Total Balance')?> &lt;= </label>
                                <input type="text" name="total_balance_less_than" id="total_balance_less_than" class="form-control input-sm" value="<?php echo $conditions['total_balance_less_than']; ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
              <div class="form-group">
                <label for="search_deposit_approve" class="control-label"><?=lang('player_list.search_deposit_approve')?></label>
                <input id="search_deposit_approve" name="search_deposit_approve" class="form-control input-sm dateInput" data-start="#deposit_approve_date_from" data-end="#deposit_approve_date_to" data-time="true"/>
                <input type="hidden" name="deposit_approve_date_from" id="deposit_approve_date_from" value="<?=$conditions['deposit_approve_date_from'];?>" />
                <input type="hidden" name="deposit_approve_date_to" id="deposit_approve_date_to" value="<?=$conditions['deposit_approve_date_to'];?>" />
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label for="search_latest_deposit_date" class="control-label"><?=lang('player_list.search_latest_deposit_date')?></label>
                <input id="search_latest_deposit_date" name="search_latest_deposit_date" class="form-control input-sm dateInput" data-start="#latest_deposit_date_from" data-end="#latest_deposit_date_to" data-time="true"/>
                <input type="hidden" name="latest_deposit_date_from" id="latest_deposit_date_from" value="<?=$conditions['latest_deposit_date_from'];?>" />
                <input type="hidden" name="latest_deposit_date_to" id="latest_deposit_date_to" value="<?=$conditions['latest_deposit_date_to'];?>" />
              </div>
            </div>

            <div class="col-md-4 col-total_deposit_count">
                <div class="row">
                    <div class="clearfix col-md-12 p-0">
                        <div class="col-md-6">
                            <div class="form-group inputs-total_deposit_count">
                                <label for="total_deposit_count_more_than" class="control-label"><?=lang('Total Deposit Count')?> &gt;= </label>
                                <input type="text" name="total_deposit_count_more_than" id="total_deposit_count_more_than" class="form-control input-sm " value="<?php echo $conditions['total_deposit_count_more_than']; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group inputs-total_deposit_count">
                                <label for="total_deposit_count_less_than" class="control-label"><?=lang('Total Deposit Count')?> &lt;= </label>
                                <input type="text" name="total_deposit_count_less_than" id="total_deposit_count_less_than" class="form-control input-sm" value="<?php echo $conditions['total_deposit_count_less_than']; ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-total_deposit">
                <div class="row">
                    <div class="clearfix col-md-12 p-0">
                        <div class="col-md-6">
                            <div class="form-group inputs-total_deposit">
                                <label for="total_deposit_more_than" class="control-label"><?=lang('Total Deposit Amt')?> &gt;= </label>
                                <input type="text" name="total_deposit_more_than" id="total_deposit_more_than" class="form-control input-sm " value="<?php echo $conditions['total_deposit_more_than']; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group inputs-total_deposit">
                                <label for="total_deposit_less_than" class="control-label"><?=lang('Total Deposit Amt')?> &lt;= </label>
                                <input type="text" name="total_deposit_less_than" id="total_deposit_less_than" class="form-control input-sm" value="<?php echo $conditions['total_deposit_less_than']; ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if(in_array('affiliate_source_code', $player_list_custom_order)) :?>
            <div class="col-md-4">
              <div class="form-group">
                <label for="affiliate_source_code" class="control-label"><?=lang('player_list.affiliate_source_code')?></label>
                <input type="text" name="affiliate_source_code" id="affiliate_source_code" class="form-control input-sm" value="<?php echo $conditions['affiliate_source_code']; ?>">
              </div>
            </div>
            <?php endif; ?>

            <?php if(in_array('player_sales_agent', $player_list_custom_order)) :?>
                <div class="col-md-2">
                    <div class="form-group">
                        <!-- player_sales_agent -->
                        <label for="player_sales_agent" class="control-label"><?=lang('Has Sales Agent')?>:</label>
                        <select name="player_sales_agent" id="player_sales_agent" class="form-control input-sm">
                            <option value="" <?php echo $conditions['player_sales_agent']  == null ? 'selected' : '' ?> > <?=lang('All')?></option>
                            <option value="1" <?php echo $conditions['player_sales_agent']  == '1'? 'selected' : '' ?> > <?=lang('Yes')?></option>
                            <option value="0" <?php echo $conditions['player_sales_agent']  == '0'? 'selected' : '' ?> > <?=lang('No')?></option>
                        </select>
                    </div>
                </div>
            <?php endif; ?>

            <?php if(in_array('daysSinceLastDeposit', $player_list_custom_order)) :?>
                <div class="col-md-3">
                    <div style = "display: flex;align-items: end;">
                        <div class="form-group" style = "margin-right: 5%;">
                            <select name="daysSinceLastDepositRange" id="daysSinceLastDepositRange"  class="form-control input-sm">
                                <option value="0" <?php echo $conditions['daysSinceLastDepositRange']  == "0" ? 'selected' : '' ?> > > </option>
                                <option value="1" <?php echo $conditions['daysSinceLastDepositRange']  == "1" ? 'selected' : '' ?> > < </option>
                                <option value="2" <?php echo $conditions['daysSinceLastDepositRange']  == "2" ? 'selected' : '' ?> > = </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="daysSinceLastDeposit" class="control-label"><?=lang('player.DaysSinceLastDeposit')?>:</label>
                            <input type="text" name="daysSinceLastDeposit" id="daysSinceLastDeposit" class="form-control input-sm" value="<?php echo $conditions['daysSinceLastDeposit']; ?>">
                        </div>
                    </div>
                </div>
            <?php endif; //  EOF if(in_array('cpf_number', $player_list_custom_order)) ?>

            <div class="col-md-2 col-cashback">
                <div class="form-group ">
                    <label for="cashback" class="control-label"><?=lang('cashback')?></label>
                    <select name="cashback" id="cashback" class="form-control input-sm ">
                        <option value="" <?php echo $conditions['cashback']  == null ? 'selected' : '' ?> > <?=lang('All')?></option>
                        <option value="0" <?php echo $conditions['cashback']  == "0" ? 'selected' : '' ?>><?=lang('enabled')?></option>
                        <option value="1" <?php echo $conditions['cashback']  == "1" ? 'selected' : '' ?>><?=lang('disabled')?></option>
                    </select>
                </div>
            </div>
            <div class="col-md-2 col-promotion">
                <div class="form-group ">
                    <label for="promotion" class="control-label"><?=lang('promotion')?></label>
                        <select name="promotion" id="promotion" class="form-control input-sm ">
                            <option value="" <?php echo $conditions['cashback']  == null ? 'selected' : '' ?> > <?=lang('All')?></option>
                            <option value="0" <?php echo $conditions['promotion']  == "0" ? 'selected' : '' ?>><?=lang('enabled')?></option>
                            <option value="1" <?php echo $conditions['promotion']  == "1" ? 'selected' : '' ?>><?=lang('disabled')?></option>
                        </select>
                </div>
            </div>

            <div class="col-md-2 col-withdrawal_status">
                <div class="form-group ">
                    <label for="withdrawal_status" class="control-label"><?=lang('Withdrawal Status')?></label>
                        <select name="withdrawal_status" id="withdrawal_status" class="form-control input-sm ">
                            <option value="" <?php echo $conditions['withdrawal_status']  == null ? 'selected' : '' ?> > <?=lang('All')?></option>
                            <option value="1" <?php echo $conditions['withdrawal_status']  == "1" ? 'selected' : '' ?>><?=lang('enabled')?></option>
                            <option value="0" <?php echo $conditions['withdrawal_status']  == "0" ? 'selected' : '' ?>><?=lang('disabled')?></option>
                        </select>
                </div>
            </div>

            <?php if ($this->utils->getConfig('enabled_priority_player_features')) :?>
            <div class="col-md-2 col-priority">
                <div class="form-group ">
                    <label for="priority" class="control-label"><?=lang('priority')?></label>
                        <select name="priority" id="priority" class="form-control input-sm ">
                            <option value="" <?php echo $conditions['priority']  == '' ? 'selected' : '' ?> > <?=lang('All')?></option>
                            <option value="0" <?php echo $conditions['priority']  == "0" ? 'selected' : '' ?>><?=lang('lang.no')?></option>
                            <option value="1" <?php echo $conditions['priority']  == "1" ? 'selected' : '' ?>><?=lang('lang.yes')?></option>
                        </select>
                </div>
            </div>
            <?php endif; //  EOF if ($this->utils->getConfig('enabled_priority_player_features')) ?>

        </div><!-- EOF clearfix col-md-12 p-0 -->
      </div>
    </div>
    <div class="text-center">
        <input type="button" value="<?=lang('lang.clear')?>" class="btn btn-default btn-sm btn-clear">
        <input type="submit" id="submit-search-btn" value="<?=lang('lang.search')?>" class="btn btn-primary btn-sm" disabled>
      </div>
  </div><!-- EOF panel-body -->
</div> <!-- EOF panel-primary -->
</form> <!-- EOF #search-form_new -->

<!--BATCH SMS MESSAGE BOX START-->
<div id="batch-sms-message-box" class="modal fade bs-example-modal-md" data-backdrop="static" data-keyboard="false" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header panel-heading">
                <h3 id="myModalLabel"><?=lang('lang.send.batch.sms-message')?></h3>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="help-block" id="conf-msg-ask"></div>
                        <div class="form-group" >
                            <label class="control-label"><?=lang('lang.select.players')?></label>
                            <div style=" max-height:100px;overflow-y:auto;">
                                <style>
                                    .select2-selection__rendered{color:#008CBA;}
                                </style>
                                <select class="from-username form-control" id="player_username" multiple="true" style="width:100%;"></select>
                                <button style="position:relative;bottom:30px;right:2px;" type="button" id="clear-member-selection" class="btn btn-default btn-xs pull-right" >
                                    <fa class="glyphicon glyphicon-remove"></fa><?=lang('lang.clear.selections')?>
                                </button>
                                <span class="help-block player-username-help-block" style="color:#F04124"></span>
                            </div>
                        </div>
                        <div class="form-group">
                           <label class="control-label" ><?=lang("cu.7")?></label>
                           <textarea class="form-control" id="message-body-sms" rows="3" maxlength="300"></textarea>
                           <span class="help-block" style="color:#F04124"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" >
                <div style="height:70px;position:relative;">
                    <button type="button" class="btn btn-default"  id="cancel-send" data-dismiss="modal"><?=lang('lang.close');?></button>
                    <?php if (!$this->utils->getConfig('disabled_sms')) {?>
                        <button type="button" id="send-sms-message" class="btn btn-success">
                            <i class="fa fa-phone"></i> <?=lang('Batch Send SMS')?>
                        </button>
                    <?php }?>
                </div>
            </div>
        </div>
    </div>
</div>
<!--BATCH MESSAGE BOX END-->

<!--BROADCAST MESSAGE BOX START-->
<div id="broadcast-message-box" class="modal fade bs-example-modal-md" data-backdrop="static" data-keyboard="false" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header panel-heading">
                <h3 id="myModalLabel"><?=lang('Broadcast Message')?></h3>
            </div>
            <div class="modal-body">
                <form id="broadcast-message-box-form">
                    <div class="row">
                        <div class="form-group">
                            <label for="subject"><?=lang("cs.subject")?> </label>
                            <input type="text" class="form-control" id="broadcast_message_subject" name="broadcast_message_subject" />
                            <span class="help-block" style="color:#F04124"></span>
                        </div>
                        <div class="form-group">
                           <label class="control-label" ><?=lang("cu.7")?></label>
                           <textarea class="form-control" id="broadcast_message_body" name="broadcast_message_body" rows="3" ></textarea>
                           <span class="help-block" style="color:#F04124"></span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" >
                <div style="height:70px;position:relative;">
                    <button type="button" class="btn btn-default" id="cancel_broadcast" data-dismiss="modal"><?=lang('lang.close');?></button>
                    <button type="button" id="send_broadcast_message" class="btn btn-primary"><i class="fa fa-comment"></i> <?=lang('Broadcast Message')?></button>
                </div>
            </div>
        </div>
    </div>
</div>
<!--BROADCAST MESSAGE BOX END-->

<!--BATCH UPDATE SALES AGENT BOX START-->
<div id="batch-update-sales-agent-box" class="modal fade bs-example-modal-md" data-backdrop="static" data-keyboard="false" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header panel-heading">
                <h3 id="myModalLabel"><?=lang('sales_agent.update.sales_information')?></h3>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="help-block" id="conf-msg-ask"></div>
                        <div class="form-group" >
                            <label class="control-label"><?=lang('lang.select.players')?></label>
                            <div style=" max-height:100px;overflow-y:auto;">
                                <style>
                                    .select2-selection__rendered{color:#008CBA;}
                                </style>
                                <select class="from-username form-control" id="player_username_sales_agent" multiple="true" style="width:100%;"></select>
                                <button style="position:relative;bottom:30px;right:2px;" type="button" id="clear-member-selection-sales-agent" class="btn btn-default btn-xs pull-right" >
                                    <fa class="glyphicon glyphicon-remove"></fa><?=lang('lang.clear.selections')?>
                                </button>
                                <span class="help-block player-username-help-block" style="color:#F04124"></span>
                            </div>
                        </div>
                        <div class="form-group">
                           <label class="control-label" ><?=lang("sales_agent")?></label>
                           <select name="sales_agent_id" class="form-control" id="sales-agent-id">
                                <option value=""><?= lang('sales_agent.remove.player.sales.agent')?></option>
                                <?php if (!empty($sales_agent)) : ?>
                                    <?php foreach ($sales_agent as $sales) : ?>
                                        <option value="<?=$sales['id']?>"><?=$sales['username']?></option>
                                    <?php endforeach ; ?>
                                    <?php endif ; ?>
                            </select>
                           <span class="help-block" style="color:#F04124"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" >
                <div style="height:70px;position:relative;">
                    <button type="button" class="btn btn-default"  id="cancel-update-sales-agent" data-dismiss="modal"><?=lang('lang.close');?></button>
                    <button type="button" id="update-sales-agent" class="btn btn-success">
                        <i class="fa fa-users"></i> <?=lang('sales_agent.update.sales_information')?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<!--BATCH MESSAGE BOX END-->

<?php if($this->utils->getConfig('enable_player_list_batch_send_smtp') && $this->permissions->checkPermissions('send_message_to_all')):?>
<?php include VIEWPATH . '/includes/send_batch_mail.php';?>
<?php endif;?>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-users"></i> <?=lang('Player List')?>
        </h4>
    </div>
    <div class="panel-body">
        <table class="table table-bordered table-hover" id="player-table">
            <thead>
                <tr>
                    <?php
                        $orderKey = isset($this->utils->getConfig('player_list_column_order')['custom_order']) ? $this->utils->getConfig('player_list_column_order')['custom_order']:$this->utils->getConfig('player_list_column_order')['default_order'];
                        foreach ($orderKey as $title) {
                            switch($title) {
                                case 'batch_message_action':
                                    echo '<th>
                                            <div class="dropdown select-users">
                                                <input type="checkbox" id="select_all_users_of_this_page">
                                                <a data-toggle="dropdown" href="javascript:void(0)" aria-expanded="true">
                                                    <i class="fa fa-caret-down"></i>
                                                </a>
                                                <ul class="dropdown-menu" aria-labelledby="select_all_users_of_this_page">
                                                    <li>
                                                        <a class="select_all_search_result">'.lang('Select All in Searching Result').'</a>
                                                    </li>
                                                    <li>
                                                        <a class="unselect_all_search_result">'.lang('Cancel All Selected').'</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </th>';
                                break;
                                case 'username':
                                    echo '<th>'.lang('player.01').'</th>';
                                break;
                                case 'first_name':
                                    echo '<th>'.lang("First Name").'</th>';
                                break;
                                case 'last_name':
                                    echo '<th>'. lang('Last Name').'</th>';
                                break;
                                case 'email':
                                    echo '<th>'. lang('player_list.fields.email_address').'</th>';
                                break;
                                case 'dialingCode':
                                    echo '<th>'. lang('Dialing Code').'</th>';
                                break;
                                case 'contactNumber':
                                    echo '<th>'. lang('player.63').'</th>';
                                break;
                                case 'online':
                                    echo '<th>'. lang('viewuser.03').'</th>';
                                break;
                                case 'lastLoginTime':
                                    echo '<th id="lastLoginTime">'. lang('Last Login Date').'</th>';
                                break;

                                case 'lastLoginIp':
                                    echo '<th>'. lang('player_list.fields.last_login_ip').'</th>';
                                break;
                                case 'blocked':
                                    echo '<th>'. lang('lang.accountstatus').'</th>';
                                break;
                                case 'createdOn':
                                    echo '<th id="colRegOn">'. lang('player.38').'</th>';
                                break;
                                case 'registered_by':
                                    echo '<th>'. lang('Registered By').'</th>';
                                break;
                                case 'registrationWebsite':
                                    echo '<th>'. lang('Registered Website').'</th>';
                                break;
                                case 'ip_address':
                                    echo '<th>'. lang('Signup IP').'</th>';
                                break;
                                case 'registration_ip_tags':
                                    if( empty( $this->utils->getConfig('hide_iptaglist') ) ){
                                        echo '<th>'. lang('IP Tags').'</th>';
                                    }
                                break;
                                case 'referral_code':
                                    echo '<th>'. lang('player_list.fields.referral_code').'</th>';
                                break;
                                case 'refereePlayerId':
                                    echo '<th>'. lang('Referred By').'</th>';
                                break;
                                case 'affiliate':
                                    if (!$this->utils->isEnabledFeature('close_aff_and_agent')){
                                        echo '<th>'. lang('player.24').'</th>';
                                    }
                                break;
                                case 'agent':
                                    if (!$this->utils->isEnabledFeature('close_aff_and_agent')){
                                        echo '<th>'. lang('Under Agency').'</th>';
                                    }
                                break;
                                case 'tagName':
                                    echo '<th>'. lang('Player Tag').'</th>';
                                break;
                                case 'group_level':
                                    echo '<th>'. lang('player_list.fields.vip_level').'</th>';
                                break;
                                case 'imAccount1':
                                    echo '<th>'. lang('player_list.fields.imaccount1').'</th>';
                                break;
                                case 'imAccount2':
                                    echo '<th>'. lang('player_list.fields.imaccount2').'</th>';
                                break;
                                case 'imAccount3':
                                    echo '<th>'. lang('player_list.fields.imaccount3').'</th>';
                                break;
                                case 'imAccount4':
                                    echo '<th>'. lang('player_list.fields.imaccount4').'</th>';
                                break;
                                case 'imAccount5':
                                    echo '<th>'. lang('player_list.fields.imaccount5').'</th>';
                                break;
                                case 'city':
                                    echo '<th>'. lang('player_list.fields.city').'</th>';
                                break;
                                case 'country':
                                    echo '<th>'. lang('player_list.fields.country').'</th>';
                                break;
                                case 'birthdate':
                                    echo '<th>'. lang('Date of Birth').'</th>';
                                break;
                                case 'lastDepositDateTime':
                                    if($this->utils->getConfig('display_last_deposit_col') == true){
                                        echo '<th>'. lang('player.lastDepositDateTime').'</th>';
                                    }
                                break;
                                case 'daysSinceLastDeposit':
                                    if($this->utils->getConfig('display_last_deposit_col') == true){
                                        echo '<th>'. lang('player.DaysSinceLastDeposit').'</th>';
                                    }
                                break;
                                case 'id_card_number':
                                    echo '<th>'. lang('player_list.fields.id_card_number').'</th>';
                                break;
                                case 'cpf_number':
                                    echo '<th>'. lang('player_list.fields.cpf_number').'</th>';
                                break;
                                case 'total_deposit_times':
                                    echo '<th>'. lang('Has Deposit').'</th>';
                                break;
                                case 'total_total_nofrozen':
                                    echo '<th>'. lang('Total Balance').'</th>';
                                break;
                                case 'total_deposit_amount':
                                    echo '<th>'. lang('Total Deposit Amt').'</th>';
                                break;
                                case 'total_withdrawal_amount':
                                    echo '<th>'. lang('Total Withdrawal Amt').'</th>';
                                break;
                                case 'net_cash':
                                    echo '<th>'. lang('Net Cash In/Out').'</th>';
                                break;
                                case 'total_betting_amount':
                                    echo '<th>'. lang('Total Bet Amt').'</th>';
                                break;
                                case 'approved_deposit_count':
                                    echo '<th>'. lang('Total Deposit Count').'</th>';
                                break;
                                case 'approved_withdraw_count':
                                    echo '<th>'. lang('Total Withdrawal Count').'</th>';
                                break;
                                case 'affiliate_source_code':
                                    echo '<th>'. lang('Affiliate Source Code').'</th>';
                                break;
                                case 'gender':
                                    echo '<th>'. lang('Gender').'</th>';
                                break;
                                case 'player_sales_agent':
                                    if($this->utils->getConfig('enabled_sales_agent')){
                                        echo '<th>'. lang('Has Sales Agent').'</th>';
                                    }
                                break;
                                case 'priority':
                                    if($this->utils->getConfig('enabled_priority_player_features')){
                                        echo '<th>'. lang('player_list.fields.priority').'</th>';
                                    }
                                break;
                            }
                        }
                    ?>

                    <?php if ($this->utils->isEnabledFeature('show_kyc_status')): ?>
                        <th><?=lang("KYC Level/Rate Code")?></th><!-- #33 KYC Level/Rate Code -->
                    <?php endif?>
                    <?php if ($this->utils->isEnabledFeature('show_risk_score')): ?>
                        <th><?=lang("Risk Level/Score")?></th> <!-- #34 Risk Level/Score -->
                    <?php endif?>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th id="ft-col" style="text-align:left;">
                        <table class="ft-table">
                            <tr class="ftmpl" style="display: none;">
                                <th class="fitem"></th>
                                <th class="fsep"></th>
                                <th class="fval" style="text-align: right;"></th>
                            </tr>
                        </table>
                    </th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="modal fade in" id="mainModal" tabindex="-1" role="dialog" aria-labelledby="mainModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="mainModalLabel"></h4>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer"></div>
        </div>
    </div>
</div>

<?php if ($this->utils->isEnabledFeature('export_excel_on_queue')) {?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
<?php }?>

<script type="text/javascript" src="<?=site_url().'resources/datatables/buttons.html5.min.js'?>"></script>

<?php if($this->utils->getConfig('enable_goto_page_pagination_in_player_list') == true){  ?>
<script type="text/javascript">
    (function ($) {
        function calcDisableClasses(oSettings) {
            var start = oSettings._iDisplayStart;
            var length = oSettings._iDisplayLength;
            var visibleRecords = oSettings.fnRecordsDisplay();
            var all = length === -1;

            // Gordey Doronin: Re-used this code from main jQuery.dataTables source code. To be consistent.
            var page = all ? 0 : Math.ceil(start / length);
            var pages = all ? 1 : Math.ceil(visibleRecords / length);

            var disableFirstPrevClass = (page > 0 ? '' : oSettings.oClasses.sPageButtonDisabled);
            var disableNextLastClass = (page < pages - 1 ? '' : oSettings.oClasses.sPageButtonDisabled);

            return {
                'first': disableFirstPrevClass,
                'previous': disableFirstPrevClass,
                'next': disableNextLastClass,
                'last': disableNextLastClass
            };
        }

        function calcCurrentPage(oSettings) {
            return Math.ceil(oSettings._iDisplayStart / oSettings._iDisplayLength) + 1;
        }

        function calcPages(oSettings) {
            return Math.ceil(oSettings.fnRecordsDisplay() / oSettings._iDisplayLength);
        }

        var firstClassName = 'first';
        var previousClassName = 'previous';
        var nextClassName = 'next';
        var lastClassName = 'last';

        var paginateClassName = 'paginate';
        var paginatePageClassName = 'paginate_page';
        var paginateInputClassName = 'paginate_input';
        var paginateTotalClassName = 'paginate_total';

        $.fn.dataTableExt.oPagination.input = {
            'fnInit': function (oSettings, nPaging, fnCallbackDraw) {
                var nFirst = document.createElement('span');
                var nPrevious = document.createElement('span');
                var nNext = document.createElement('span');
                var nLast = document.createElement('span');
                var nInput = document.createElement('input');
                var nTotal = document.createElement('span');
                var nInfo = document.createElement('span');

                var language = oSettings.oLanguage.oPaginate;
                var classes = oSettings.oClasses;
                var info = '<?=lang('Page');?>'+' _INPUT_ '+'<?=lang('Page Of');?>'+' _TOTAL_';

                nFirst.innerHTML = language.sFirst+"&nbsp;";
                nPrevious.innerHTML = language.sPrevious+"&nbsp;";
                nNext.innerHTML = language.sNext+"&nbsp;";
                nLast.innerHTML = language.sLast+"&nbsp;";

                nFirst.className = firstClassName + ' ' + classes.sPageButton;
                nPrevious.className = previousClassName + ' ' + classes.sPageButton;
                nNext.className = nextClassName + ' ' + classes.sPageButton;
                nLast.className = lastClassName + ' ' + classes.sPageButton;

                nInput.className = paginateInputClassName;
                nTotal.className = paginateTotalClassName;

                if (oSettings.sTableId !== '') {
                    nPaging.setAttribute('id', oSettings.sTableId + '_' + paginateClassName);
                    nFirst.setAttribute('id', oSettings.sTableId + '_' + firstClassName);
                    nPrevious.setAttribute('id', oSettings.sTableId + '_' + previousClassName);
                    nNext.setAttribute('id', oSettings.sTableId + '_' + nextClassName);
                    nLast.setAttribute('id', oSettings.sTableId + '_' + lastClassName);
                }

                nInput.type = 'text';
                nInput.style.cssText = 'width:50px;';

                nFirst.style.cssText = 'cursor: pointer; padding: 2px 4px;';
                nPrevious.style.cssText = 'cursor: pointer; padding: 2px 4px;';
                nNext.style.cssText = 'cursor: pointer; padding: 2px 4px;';
                nLast.style.cssText = 'cursor: pointer; padding: 2px 4px;';

                info = info.replace(/_INPUT_/g, '</span>' + nInput.outerHTML + '<span>');
                info = info.replace(/_TOTAL_/g, '</span>' + nTotal.outerHTML + '<span>');
                nInfo.innerHTML = '<span>' + info + ' </span>';

                nPaging.appendChild(nFirst);
                nPaging.appendChild(nPrevious);
                $(nInfo).children().each(function (i, n) {
                    nPaging.appendChild(n);
                });
                nPaging.appendChild(nNext);
                nPaging.appendChild(nLast);

                $(nFirst).click(function() {
                    var iCurrentPage = calcCurrentPage(oSettings);
                    if (iCurrentPage !== 1) {
                        oSettings.oApi._fnPageChange(oSettings, 'first');
                        fnCallbackDraw(oSettings);
                    }
                });

                $(nPrevious).click(function() {
                    var iCurrentPage = calcCurrentPage(oSettings);
                    if (iCurrentPage !== 1) {
                        oSettings.oApi._fnPageChange(oSettings, 'previous');
                        fnCallbackDraw(oSettings);
                    }
                });

                $(nNext).click(function() {
                    var iCurrentPage = calcCurrentPage(oSettings);
                    if (iCurrentPage !== calcPages(oSettings)) {
                        oSettings.oApi._fnPageChange(oSettings, 'next');
                        fnCallbackDraw(oSettings);
                    }
                });

                $(nLast).click(function() {
                    var iCurrentPage = calcCurrentPage(oSettings);
                    if (iCurrentPage !== calcPages(oSettings)) {
                        oSettings.oApi._fnPageChange(oSettings, 'last');
                        fnCallbackDraw(oSettings);
                    }
                });

                $(nPaging).find('.' + paginateInputClassName).keyup(function (e) {
                    // 38 = up arrow, 39 = right arrow
                    // if (e.which === 38 || e.which === 39) {
                    //     this.value++;
                    // }
                    // // 37 = left arrow, 40 = down arrow
                    // else if ((e.which === 37 || e.which === 40) && this.value > 1) {
                    //     this.value--;
                    // }

                    if (e.which === 13) {
                        if (this.value === '' || this.value.match(/[^0-9]/)) {
                            /* Nothing entered or non-numeric character */
                            this.value = this.value.replace(/[^\d]/g, ''); // don't even allow anything but digits
                            return;
                        }

                        var iNewStart = oSettings._iDisplayLength * (this.value - 1);
                        if (iNewStart < 0) {
                            iNewStart = 0;
                        }
                        if (iNewStart >= oSettings.fnRecordsDisplay()) {
                            iNewStart = (Math.ceil((oSettings.fnRecordsDisplay()) / oSettings._iDisplayLength) - 1) * oSettings._iDisplayLength;
                        }

                        oSettings._iDisplayStart = iNewStart;
                        oSettings.oInstance.trigger("page.dt", oSettings);
                        fnCallbackDraw(oSettings);
                    }
                });

                // Take the brutal approach to cancelling text selection.
                // $('span', nPaging).bind('mousedown', function () { return false; });
                // $('span', nPaging).bind('selectstart', function() { return false; });

                // If we can't page anyway, might as well not show it.
                var iPages = calcPages(oSettings);
                if (iPages <= 1) {
                    $(nPaging).hide();
                }
            },

            'fnUpdate': function (oSettings) {
                if (!oSettings.aanFeatures.p) {
                    return;
                }

                var iPages = calcPages(oSettings);
                var iCurrentPage = calcCurrentPage(oSettings);

                var an = oSettings.aanFeatures.p;
                if (iPages <= 1) // hide paging when we can't page
                {
                    $(an).hide();
                    return;
                }

                var disableClasses = calcDisableClasses(oSettings);

                $(an).show();

                // Enable/Disable `first` button.
                $(an).children('.' + firstClassName)
                    .removeClass(oSettings.oClasses.sPageButtonDisabled)
                    .addClass(disableClasses[firstClassName]);

                // Enable/Disable `prev` button.
                $(an).children('.' + previousClassName)
                    .removeClass(oSettings.oClasses.sPageButtonDisabled)
                    .addClass(disableClasses[previousClassName]);

                // Enable/Disable `next` button.
                $(an).children('.' + nextClassName)
                    .removeClass(oSettings.oClasses.sPageButtonDisabled)
                    .addClass(disableClasses[nextClassName]);

                // Enable/Disable `last` button.
                $(an).children('.' + lastClassName)
                    .removeClass(oSettings.oClasses.sPageButtonDisabled)
                    .addClass(disableClasses[lastClassName]);

                // Paginate of N pages text
                $(an).find('.' + paginateTotalClassName).html(iPages);

                // Current page number input value
                $(an).find('.' + paginateInputClassName).val(iCurrentPage);
            }
        };

    })(jQuery);
</script>
<?php } ?>

<script type="text/javascript">
    var dt_page = 0;
    var dt_start = 0;
    var dt_length = 0;
    var conditions = <?=json_encode($conditions)?>;
    var conditionsDefault = <?=json_encode($conditionsDefault)?>;

    $(document).ready(function(){
        $('#include_tag_list,#tag_list,#ip_tag_list').multiselect({
            enableFiltering: true,
            enableCaseInsensitiveFiltering: true,
            includeSelectAllOption: true,
            selectAllJustVisible: false,
            buttonWidth: '100%',
            buttonClass: 'btn btn-sm btn-default',
            buttonText: function(options, select) {
                if (options.length === 0) {
                    return '<?=lang('Select Tags');?>';
                } else {
                    var labels = [];
                    options.each(function() {
                        if ($(this).attr('label') !== undefined) {
                            labels.push($(this).attr('label'));
                        }
                        else {
                            labels.push($(this).html());
                        }
                    });
                    return labels.join(', ') + '';
                }
            }
        });

        $('#player_level').multiselect({
            enableFiltering: true,
            enableCaseInsensitiveFiltering: true,
            includeSelectAllOption: true,
            selectAllJustVisible: false,
            buttonWidth: '100%',
            buttonClass: 'btn btn-sm btn-default',
            buttonText: function(options, select) {
                if (options.length === 0) {
                    return '<?=lang('Select Player Level');?>';
                } else {
                    var labels = [];
                    options.each(function() {
                        if ($(this).attr('label') !== undefined) {
                            labels.push($(this).attr('label'));
                        }
                        else {
                            labels.push($(this).html());
                        }
                    });
                    return labels.join(', ') + '';
                }
            }
        });

        /*
         This  prevents loading of SBE login during ajax | The solution is to refresh the page  when unauthorized  or not logged in
         Note:This will parse all ajax request error and will activate when user not login during ajax
         Check also global ajaxerror at admin->views->template->template.php
         I added  to this page because it doesnt work when only in template.php, specially for jquery load function in this page
         work around OGP-1442
         */
        $(document).ajaxError(function(event,xhr,options,thrownError){
            if(thrownError = "Unauthorized"){
                console.log(thrownError);
                console.log(options);
                console.log(xhr);
            }
        });

        $(document).on( 'preInit.dt', function (e, settings) {
            var api = new $.fn.dataTable.Api( settings );

            var segIp = ipSeg();
            if (segIp) {
                $('#ip_address').val(segIp[0]).trigger('change');
            }
        } );

        var total_registered_players_today = 0;
        var total_registered_players = 0;
        var total_balance = 0;
        var total_deposit_count = 0;
        var total_bets_amount = 0;
        var total_deposit = 0;
        var total_withdraw = 0;
        var tableFilteredColumn = 17;
        var footer_val = {};
        var hiddenColumns = [];
        var sorting = JSON.parse('<?=json_encode($sorting);?>')
        var elem = $('#player-table thead tr th');
        var colOrderArray = JSON.parse('<?=json_encode($this->utils->getConfig('player_list_column_order'));?>');
        var playeListColumnDefs = JSON.parse('<?=json_encode($this->utils->getConfig('player_list_columnDefs'));?>');

        if(playeListColumnDefs['not_visible_columns']){
            hiddenColumns = [];
            var colOrderDef = colOrderArray['custom_order']? colOrderArray['custom_order'] : colOrderArray['default_order'];
            var hiddenCols = playeListColumnDefs['custom_not_visible_columns']? playeListColumnDefs['custom_not_visible_columns'] : playeListColumnDefs['not_visible_columns'];
            $.each(hiddenCols, function(index, col){
                colIndex = colOrderDef.indexOf(col);
                hiddenColumns.push(colIndex);
            });
        }

        // Get registered on column index
        var regOnIndex = elem.filter(function(index){
            if ($(this).attr("id") == "colRegOn") {
                return index;
            }
        }).index();


        var sortingIndex = elem.filter(function(index){
            if(sorting['sort_by'] && $(this).attr("id") == sorting['sort_by']){
                return index;
            }else{
                if($(this).attr("id") == "colRegOn"){
                    //default
                    return index;
                }
            }
        }).index();

        if (sortingIndex) {
            tableFilteredColumn = sortingIndex;
        }

        var dataTable = $('#player-table').DataTable({
            lengthMenu: JSON.parse('<?=json_encode($this->utils->getConfig('default_datatable_lengthMenu'))?>'),
            pageLength: <?=$this->utils->getDefaultItemsPerPage()?>,
            autoWidth: false,
            stateSave: <?php if ($this->utils->isEnabledFeature('column_visibility_report')) { ?> true <?php } else { ?> false <?php } ?>,
            dom: "<'row'<'col-md-12 m-b-30'<'#message-btn-sets.pull-left'><'pull-right'B><'pull-right'l><'pull-right progress-container'>>><'dt-information-summary1 text-info pull-left' i><'#select-all-tip.pull-left'><'dataTable-instance't><'row'<'col-md-12'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>>",
            columnDefs: [
                {
                    "targets" : hiddenColumns,
                    "visible" : false
                },
                {
                    "targets": 0,
                    "orderable": false
                }
            ],
            buttons: [
                {
                    extend: 'colvis',
                    columns: ':not(:nth-child(1))',
                    className: 'btn-linkwater',
                    postfixButtons: [
                        { extend: 'colvisRestore', text: '<?=lang('showDef')?>' },
                        { extend: 'colvisGroup', text: '<?=lang('showAll')?>', show: ':hidden'}
                    ]
                }
                <?php if ($this->permissions->checkPermissions('export_player_lists')) {?>
                    ,{
                        text: "<?php echo lang('CSV Export'); ?>",
                        className:'btn btn-sm btn-portage',
                        action: function ( e, dt, node, config ) {

                            // filted not used condition for remove name attr. while on submit.
                            var willRemoveNameAttrEls = conditionsInput.getFieldsByActivedBtnConditions( $('#advancedConditions').find('.btn-conditions:not(.active)') );
                            conditionsInput.scriptRemoveNameAttrByEls( willRemoveNameAttrEls, conditionsInput.removedNameAttr);
                            var form_params=$('#search-form_new').serializeArray();

                            conditionsInput.scriptRevertNameAttrByEls( willRemoveNameAttrEls, conditionsInput.removedNameAttr);

                            var d = {'extra_search': form_params, 'export_format': 'csv', 'export_type': export_type,
                                'draw':1, 'length':-1, 'start':0};

                            $("#_export_excel_queue_form").attr('action', site_url('/export_data/player_list_reports'));
                            $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                            $("#_export_excel_queue_form").submit();
                        }
                    }
                <?php } ?>
            ],

            order: [[tableFilteredColumn, sorting['sort_method']]],         // column sorting
            <?php if($this->utils->getConfig('enable_goto_page_pagination_in_player_list') == true){  ?>
            paginationType: 'input',
            <?php } ?>

            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            <?php if($this->utils->getConfig('prevent_player_list_preload') && empty($conditions['prevent_player_list_preload'])): ?>
                deferLoading: 0,
            <?php endif;?>
            ajax: function (data, callback, settings) {
                if (dt_start != 0) {
                    data['start'] = dt_start;
                }
                if (dt_length != 0) {
                    data['length'] = dt_length;
                }

                dataTable.page(dt_page);

                // filted not used condition for remove name attr. while on submit.
                var willRemoveNameAttrEls = conditionsInput.getFieldsByActivedBtnConditions( $('#advancedConditions').find('.btn-conditions:not(.active)') );

                // ignore hidden inputs
                conditionsInput.scriptRemoveNameAttrByEls( willRemoveNameAttrEls, conditionsInput.removedNameAttr);

                data.extra_search = $('#search-form_new').serializeArray();
                var formData = $('#search-form_new').serializeArray();

                conditionsInput.scriptRevertNameAttrByEls( willRemoveNameAttrEls, conditionsInput.removedNameAttr);

                var _api = this.api();
                var _container$El = $(_api.table().container());
                <?php if( ! empty($enable_go_1st_page_another_search_in_list) ): ?>
                    var _md5 = _pubutils.NON_ENG_MD5(JSON.stringify(formData));
                    _container$El.data('md5_formdata_ajax', _md5); // assign
                    /// for player list only
                    if( _container$El.data('md5_formdata_draw') != _container$El.data('md5_formdata_ajax') ){
                        // console.log('goto 1st page with data.start=0');
                        data['start'] = 0; // for sql
                    }else{
                        // console.log('idle with data.start=0');
                    }
                <?php endif;// EOF if( ! empty($enable_go_1st_page_another_search_in_list) ):... ?>

                var _ajax = $.post(base_url + "api/playerList", data, function(data) {
                    total_registered_players = data.total_registered_players;
                    total_registered_players_today = data.total_registered_players_today;

                    footer_val = [
                       { title: '<?= lang("report.sum20") ?> ', val: numeral(total_registered_players).format('0,000') } ,
                       { title: '<?= lang("report.sum19") ?> ', val: numeral(total_registered_players_today).format('0,000') } ,
                    ];

                    callback(data);
                    attachCheckboxEventListener();
                    attachSelectAllSearchResultEventListener(data.recordsTotal, data.all_usernames, data.select_all_result_size_limit);
                    initSelectAllofThisPage();
                    show_selected_tip();

                    if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
                        dataTable.buttons().disable();
                    }
                    else {
                        dataTable.buttons().enable();
                    }
                    $('#submit-search-btn').attr('disabled',false);
                    var countdownn = "<?= $this->utils->getConfig('enabled_playerlist_search_cooldown_time');?>";
                    if (countdownn > 0) {
                        var lastSearchTime = "<?=$this->session->userdata('last_search_playerlist_time');?>";
                        var currentTime = "<?=time();?>";
                        if (currentTime - lastSearchTime <= countdownn) {
                            countdownn =currentTime - lastSearchTime;
                        }
                        var btnmoema=$("#submit-search-btn");
                        btnmoema.addClass("disabled").prop('disabled', true);
                        btnmoema.val("<?=lang('lang.search')?>（"+countdownn+"s）");
                        var mysint=setInterval(function(){
                            countdownn--;
                            btnmoema.val("<?=lang('lang.search')?>（"+countdownn+"s）");
                            if(countdownn<0){
                                clearInterval(mysint);
                                btnmoema.val("<?=lang('lang.search')?>");
                                btnmoema.removeClass("disabled").prop('disabled', false);
                            }
                        },1000);
                    }
                },'json');

                _ajax.always(function(jqXHR, textStatus){
                    <?php if( ! empty($enable_go_1st_page_another_search_in_list) ): ?>
                        if( _container$El.data('md5_formdata_draw') != _container$El.data('md5_formdata_ajax') ){
                            // goto 1st page
                            // console.log('goto 1st page');
                            /// here is not work
                        }else{
                            // idle
                            // console.log('idle');
                        }
                    <?php endif;// EOF if( ! empty($enable_go_1st_page_another_search_in_list) ):... ?>
                    _container$El.data('md5_formdata_draw', _container$El.data('md5_formdata_ajax') ); // assign
                });
            },
            initComplete: function(row, data) {
                <?php if($this->utils->getConfig('enable_goto_page_pagination_in_player_list') == true){  ?>
                $('#player-table_paginate').addClass('pagination');
                <?php } ?>

                var colspanNum = $('#player-table thead tr th').length;
                columnVisibilityChange(colspanNum);

                <?php if ($this->permissions->checkPermissions('send_message_sms')) {?>
                    $("#message-btn-sets").append('<button type="button" id="batch-message-btn" class="btn btn-portage btn-sm"><i class="icon-bubble2"></i><?=lang('lang.send.batch.message')?></button>');
                <?php } ?>
                <?php if ($this->permissions->checkPermissions('send_message_to_all')) {?>
                    $("#message-btn-sets").append('<button type="button" id="broadcast-message-btn" class="btn btn-portage btn-sm"><i class="fa fa-comments"></i><?=lang('Broadcast Message')?></button>');
                <?php } ?>
                <?php if (!$this->utils->getConfig('disabled_sms')) {?>
                    $("#message-btn-sets").append('<button type="button" id="batch-sms-message-btn" class="btn btn-portage btn-sm"><i class="icon-bubble2"></i><?=lang('lang.send.batch.sms-message')?></button>');
                <?php } ?>
                <?php //sendgrid send batch mail template
                if($this->utils->getConfig('enable_player_list_batch_send_smtp') && $this->permissions->checkPermissions('send_message_to_all') && !empty($this->utils->getConfig('send_batch_email_type'))):?>
                    $("#message-btn-sets").append('<button type="button" id="batch-send-mail-btn" class="btn btn-portage btn-sm"><i class="icon-mail2"></i><?=lang('lang.send.batch.sendgrid-mail')?></button>');
                <?php endif;?>

                <?php if ($this->permissions->checkPermissions('edit_player_sales_agent') && $this->utils->getConfig('enabled_sales_agent')) {?>
                    $("#message-btn-sets").append('<button type="button" id="batch-update-sales-agent-btn" class="btn btn-portage btn-sm"><i class="icon-user2"></i><?=lang('sales_agent.update.sales_information')?></button>');
                <?php } ?>

                attachMessageBtnEventListener();
                <?php if(empty($conditions['prevent_player_list_preload'])): ?>
                    $('#submit-search-btn').attr('disabled',false);
                <?php endif;?>

                <?php if($this->config->item('enabled_playerlist_search_cooldown_time')): ?>
                    if (typeof data != 'undefined') {
                        if (typeof data.message != 'undefined') {
                            alert(data.message);
                        }
                    }
                <?php endif;?>
            },
            footerCallback: function ( row, data, start, end, display ) {
                var api = this.api();
                // Update footer
                /// Patch for OGP-14711 : Modify new layout of the "Player List": SBE_Player > All Player
                // Remove this permission and combine with "All Players" permission.
                <?php if ($this->permissions->checkPermissions('player_list')) { ?>
                    var tfooter = api.column().footer();
                    // Remove existing footer rows
                    $(tfooter).find('.ft-table tr.frow').remove();
                    // Render footer rows
                    for (var i in footer_val) {
                        var row = footer_val[i];
                        var tmpl = $(tfooter).find('.ft-table tr.ftmpl').clone().removeClass('ftmpl').addClass('frow').css('display', '');
                        $(tmpl).find('.fitem').text(row.title);
                        $(tmpl).find('.fsep').text(':');
                        $(tmpl).find('.fval').text(row.val);
                        $(tfooter).find('table.ft-table').append(tmpl);
                    }
                <?php } ?>
            }
        });

        dataTable.on( 'draw', function () {
            $("#player-table_wrapper .dataTable-instance").floatingScroll("init");


            // registration_ip_tags initial
            if( $('.json_in_ip_tag_list').length > 0 ){
                $('.json_in_ip_tag_list').each(function(indexNumber, currEl){
                    var curr$El = $(currEl);
                    if( curr$El.closest('td').find('.registration_ip_tags').length == 0 ){
                        curr$El.closest('td').append('<span class="registration_ip_tags">a</span>');
                    }
                });
            }
            // registration_ip_tags refresh and generate the colorful span
            if( $('.json_in_ip_tag_list').length > 0 ){
                $('.json_in_ip_tag_list').each(function(indexNumber, currEl){
                    var curr$El = $(currEl);

                    var curr_ip_tag_list = JSON.parse( curr$El.html() );

                    var currRegistration_ip_tags$El = curr$El.closest('td').find('.registration_ip_tags');
                    currRegistration_ip_tags$El.empty();

                    $.each(curr_ip_tag_list, function(_indexNumber, _curr){

                        // filter: invert(100%);
                        var _ip_tag$El = $('<span/>').addClass('color_ticket').css({'color': _curr.color, 'filter': 'invert(1)' }).html(_curr.name);
                        var _ip_tag_html = $('<div>').append(_ip_tag$El).html(); // like outerHTML via jquery
                        // padding: 2px 4px 2px 4px;
                        _ip_tag_wrapper$El = $('<span/>').addClass('color_ticket_wrapper').css({'background-color': _curr.color, 'filter': 'invert(0)' }).html(_ip_tag_html);
                        var _ip_tag_wrapper_html = $('<div>').append(_ip_tag_wrapper$El).html(); // like outerHTML via jquery

                        currRegistration_ip_tags$El.append( _ip_tag_wrapper_html );
                    });

                });
            }

        });

        dataTable.on( 'page.dt', function () {
            var info = dataTable.page.info();
            dt_page = info.page;
            dt_start = info.start;
            dt_length = info.length;
        } );

        $(document).on('click', 'a.buttons-colvis', function(){
          $('ul.dt-button-collection li').each(function(k,v){
            if(k==0) {
              $(this).addClass('disabled').off('click');
            }
          });
        });

        //############################SEND BATCH MESSAGE START############################################

        var batchIds = Array(), batchPlayers = Array();

        function attachMessageBtnEventListener() {
            $('#batch-message-btn').click(function(){
                var messagePlayers = {};
                batchPlayers.forEach(function(player){
                    messagePlayers[player.id] = player.username;
                });

                sbe_messages_send_message(messagePlayers);
            });

            $('#broadcast-message-btn').click(function(){
                $('#broadcast-message-box').modal('show');
                initBroadcastMessageModal();
            });

            $('#broadcast_message_subject').on('keyup',function(){
                checkBroadcastMessageModalVal();
            });

            $('#batch-sms-message-btn').click(function(){
                $('#batch-sms-message-box').modal('show');
                var double_submit_hidden_field = '<?=$double_submit_hidden_field?>';

                $('#batch-sms-message-box').append(double_submit_hidden_field);
                populateSelect2Users();
                validateSelect2();
            });

            $('#batch-update-sales-agent-btn').click(function(){
                $('#batch-update-sales-agent-box').modal('show');
                var double_submit_hidden_field = '<?=$double_submit_hidden_field?>';

                $('#batch-update-sales-agent-box').append(double_submit_hidden_field);
                populateSelect2UsersBatchUpdateSalesAgent();
                // validateSelect2SalesAgent();
                validateSelect2BatchUpdateSalesAgent();
            });

            $('#batch-send-mail-btn').click(function(){
                $('#batch-send-mail-box').modal('show');
                var double_submit_hidden_field = '<?=$double_submit_hidden_field?>';

                $('#batch-send-mail-box').append(double_submit_hidden_field);
                populateSelect2UsersBatchmail();
                validateSelect2Batchmail();
            });
        }

        $('#send_broadcast_message').click(function(){

            var message_html = $(sceditor_instance.getBody()).html();
            $('#broadcast_message_body').val(message_html);

            $('#broadcast-message-box-form').attr('action', "<?php echo site_url('/player_management/broadcast_message'); ?>")
                .attr('method', 'POST').submit();
        });

        $("#clear-member-selection").click(function(){
            clearSelections();
        });

        $('#cancel-send, #cancel-send-batch, #cancel-update-sales-agent').click(function(){
          removeDoubleSubmitInput();
        });

        $('#send-sms-message').click(function(){

            var message=false;

            if($('#message-body-sms').val() == '' ){
                $('#message-body-sms').next('span').html('<?=lang("cu.7")?> is required');
                message=false;
            }else{
                $('#message-body-sms').next('span').html('');
                message=true;
            }
            validateSelect2();

            if(!message){return}

            $('#cancel-send').prop("disabled", true);
            $(this).prop("disabled", true);

            var i, len =batchPlayers.length;

            for(i=0; i<len; i++){
                batchIds.push(batchPlayers[i].id)
            }

            var data = {
                message:$('#message-body-sms').val(),
                playerIds:batchIds,
                label: 'player_list_send_batch_sms',
                admin_double_submit_post: $('#admin_double_submit_post').val()
            };

            $.ajax({
                url : '<?php echo site_url('player_management/sendBatchSmsMessage') ?>',
                type : 'POST',
                data : data,
                dataType : "json"
            }).done(function (data) {
                $('#send-sms-message').prop("disabled", false);
                $('#cancel-send').prop("disabled", false);
                if (data.status == "success") {
                    $('#batch-sms-message-box').modal('hide');
                    $('#message-body-sms').val('');
                    $('.batch-message-cb').prop('checked',false);
                    batchIds = Array();
                    clearSelections();
                    alert(data.msg);
                }else{
                    alert(data.msg);
                }
                window.location.reload();
            }).fail(function (jqXHR, textStatus) {
                $(this).prop("disabled", false);
                alert('<?=lang("cms.smsSendFail")?>');
                $('#batch-sms-message-box').modal('hide');
                $('#message-body-sms').val('');
                $('#cancel-send').prop("disabled", false);
                $('.batch-message-cb').prop('checked',false);
                $('#send-sms-message').prop("disabled", false);
                batchIds = Array();
                clearSelections();
                window.location.reload();
            });
        });

        $("#player_username").select2({
            ajax: {
                url: '<?php echo site_url('player_management/getPlayerUsernames') ?>',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    var query = {
                        q: params.term,
                        page: params.page
                    }
                    // Query paramters will be ?search=[term]&page=[page]
                    return query;
                },
                allowClear: true,
                tags: true,
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.items,
                        pagination: {
                            more: (params.page *30) < data.total_count
                        }
                    };
                },
                cache: true
            },
            escapeMarkup: function (markup) { return markup; },
            minimumInputLength: 1,
            templateResult: formatOption,
            templateSelection: formatOptionSelection
        });

        $("#player_username").on("select2:select", function (e) {
            var p = e.params.data,
                username = p.username || p.text,
                playerId = p.id,
                player = {id:playerId, username:username};

            if(userExistInSelected(player.username)){
                batchPlayers.push(player);
                updateDatatableCheckbox();
            }
            validateSelect2();
        });

        $("#player_username").on("select2:unselect", function (e) {
            var p = e.params.data,
                username = p.username || p.text,
                playerId = p.id;
            findAndRemove('username', username);
            updateDatatableCheckbox();
            validateSelect2();
        });

        function removeDoubleSubmitInput(){
          $('#admin_double_submit_post').remove();
        }

        function formatOption (opt) {
            if (opt.loading){
                return opt.text;
            } else{
                return opt.username;
            }
        }

        function formatOptionSelection (opt) {
            return opt.username || opt.text;
        }

        function validateSelect2(){
            if(!batchPlayers.length){
              $(".player-username-help-block").html('<?=lang("system.word38").lang("lang.is.required")?>');
            } else {
               $(".player-username-help-block").html('');
            }
        }

        function clearSelections(){
            batchPlayers = Array();
            $("#player_username").val("").trigger("change");
            updateDatatableCheckbox();
        }

        function attachCheckboxEventListener(){
            $('.batch-message-cb').tooltip({placement : "right"});
            $('.batch-message-cb').on('click',function(){
                var playerId = $(this).attr('value');
                var playerUsername = $(this).attr('username');
                var player = {id:playerId, username:playerUsername}

                if($(this).prop('checked')){
                    if(userExistInSelected(playerUsername)){
                        batchPlayers.push(player);
                    }
                    $(this).parent().parent().addClass('row-selected');
                }else{
                    findAndRemove('username',playerUsername);
                    $(this).parent().parent().removeClass('row-selected');
                }
                show_selected_tip()
            });
        }

        function attachSelectAllSearchResultEventListener(recordsTotal, usernames, select_all_result_size_limit) {
            var select_all_tip = "<?=lang('player_list.select_all_tip');?>";
            $('#select-all-tip').html(select_all_tip);
            var page_length = dataTable.page.len();

            if(recordsTotal > page_length){
              if (!usernames.length) {
                $('#select-all-tip').append("<span><?=lang('player_list.select_all_tip.disabled');?></span>");
                $('#select_all_result_size_limit').text(select_all_result_size_limit);
              }
              else {
                $('#select-all-tip').append("<a class='select_all_search_result'><?=lang('player_list.select_all_tip.search_result');?></a>");
              }
              $('#all_result').html(recordsTotal);
            }

            $('.select_all_search_result').on('click',function(){
                batchPlayers = [];
                batchPlayers = usernames;
                $('#select_all_users_of_this_page').prop('checked', true);
                triggerSelectAllofThisPage();
            });

            $('.unselect_all_search_result').on('click',function(){
                batchPlayers = [];
                $('#select_all_users_of_this_page').prop('checked', false);
                triggerSelectAllofThisPage();
            });
        }

        $('#select_all_users_of_this_page').on('change',function(){
            triggerSelectAllofThisPage();
        });

        function initSelectAllofThisPage() {
            $('#select_all_users_of_this_page').prop('checked', false);
            $(".batch-message-cb").each(function(){
                if($(this).prop('checked')){
                    $(this).parent().parent().addClass('row-selected');
                }
            });
        }

        function initBroadcastMessageModal() {
            $('#broadcast_message_subject').val('');
            $('#broadcast_message_body').val('');
            $('#send_broadcast_message').prop('disabled',true);
            try {
                sceditor.instance($('#broadcast_message_body')[0]).destroy();
            } catch(e){

            }

            var sc_container = $('#broadcast_message_body')[0];
            var sceditor_message_custom_options = { ... sceditor_message_default_options };
            delete sceditor_message_custom_options.height;
            sceditor.create(sc_container, sceditor_message_custom_options);
            sceditor_instance = sceditor.instance(sc_container);

            sceditor_instance.keyUp(function(e) {
                checkBroadcastMessageModalVal();
            });
        }
        function checkBroadcastMessageModalVal() {
            var broadcast_message_subject = $('#broadcast_message_subject').val();
            var broadcast_message_body = sceditor_instance.getWysiwygEditorValue();
            if(broadcast_message_subject.trim() && broadcast_message_body.trim()) {
                $('#send_broadcast_message').prop('disabled',false);
            } else {
                $('#send_broadcast_message').prop('disabled',true);
            }
        }

        function triggerSelectAllofThisPage() {
            var select_all_users_of_this_page = $('#select_all_users_of_this_page');
            $('.batch-message-cb').prop('checked', select_all_users_of_this_page.prop('checked'));

            if(select_all_users_of_this_page.prop('checked')){
                $("#player-table>tbody tr").addClass('row-selected');
            }else{
                $("#player-table>tbody tr").removeClass('row-selected');
            }

            $(".batch-message-cb").each(function(){
                var playerId = $(this).attr('value');
                var playerUsername = $(this).attr('username');
                var player = {id:playerId, username:playerUsername}

                if(select_all_users_of_this_page.prop('checked')){
                    if(userExistInSelected(playerUsername)){
                        batchPlayers.push(player);
                    }
                }else{
                    findAndRemove('username',playerUsername);
                }
            });
            show_selected_tip();
        }

        function show_selected_tip() {
            var page_length = $("#player-table>tbody tr").length;
            var selected_count = $('.row-selected').length;
            $('#selected-count').html(selected_count);
            if(selected_count == page_length){
                $("#select_all_users_of_this_page").prop("checked", true);
                $("#select_all_users_of_this_page").prop("indeterminate", false);
                $('#select-all-tip').show();
            } else if(selected_count > 0) {
                $("#select_all_users_of_this_page").prop("indeterminate", true);
                $('#select-all-tip').hide();
            } else {
                $('#select-all-tip').hide();
            }
        }

        function userExistInSelected(username) {
            var id = batchPlayers.length + 1;
            var found = batchPlayers.some(function (el) {
              return el.username === username;
            });
            if (!found) {
                return true;
            }else{
                return false;
            }
        }

        function findAndRemove(property, value) {
            batchPlayers.forEach(function(result, index) {
                if(result[property] === value) {
                   batchPlayers.splice(index, 1);
                }
            });
        }

        function populateSelect2Users(){
            $("#player_username").empty();
            var  i=0, len = batchPlayers.length;
            if(len > 0){
                for(i=0; i<len; i++ ){
                    $("#player_username").append('<option value="'+batchPlayers[i].id+'" selected>'+batchPlayers[i].username+'</opton>').trigger('change');
                }
            }else{
                $("#player_username").empty();
            }
        }

        dataTable.on( 'draw.dt', function () {
            updateDatatableCheckbox();
        })

        function updateDatatableCheckbox(){
            var  i=0, len = batchPlayers.length;
            $('.batch-message-cb').prop('checked',false);
            if(len > 0){
                for(i=0; i<len; i++ ){
                    $('#cb-user-id-'+batchPlayers[i].username).prop('checked',true);
                }
            }else{
                $('.batch-message-cb').prop('checked',false);
            }
        }

        //############################SEND BATCH MESSAGE END############################################

        function dataToTable(data) {
            // INIT TABLE
            var table = '<table>';

            // GET THEAD VALUE
            var th = []
            var th_origin = data[0];

            table += '<thead>';
                table += '<tr>';
                $.each( th_origin, function( key, value ) {
                    th.push(key);
                    table += '<th>' + key + '</th>';
                });
            table += '</thead>';

            // GET TBODY VALUE
            table += '<tbody>';
            $.each(data, function( index, value ) {
                table += '<tr>';
                    $.each( th, function( key, value ) {
                        table += '<td>';
                            table += data[index][value];
                        table += '</td>';
                    });
                table += '</tr>';
            });
            table += '</tbody>';

            table += '</table>';

            return table;
        }

        $('#search-form input[type="text"], #search-form input[type="number"], #search-form input[type="email"]').keypress(function (e) {
            if (e.which == 13) {
                var id = $(this).attr('id');
                if (id != 'search_registration_date') {
                    $("#search_reg_date").prop('checked', false).trigger('change');
                }
                if (id != 'search_last_login_date') {
                    $("#search_last_log_date").prop('checked', false).trigger('change');
                }
                $('#search-form').submit();
            }
        });

        $("#search_reg_date").change(function() {
            if(this.checked) {
                $('#search_registration_date').prop('disabled',false);
                $('#registration_date_from').prop('disabled',false);
                $('#registration_date_to').prop('disabled',false);
            }else{
                $('#search_registration_date').prop('disabled',true);
                $('#registration_date_from').prop('disabled',true);
                $('#registration_date_to').prop('disabled',true);
            }
        }).trigger('change');

        $("#search_last_log_date").change(function() {
            if(this.checked) {
                $('#search_last_login_date').prop('disabled',false);
                $('#last_login_date_from').prop('disabled',false);
                $('#last_login_date_to').prop('disabled',false);
            }else{
                $('#search_last_login_date').prop('disabled',true);
                $('#last_login_date_from').prop('disabled',true);
                $('#last_login_date_to').prop('disabled',true);
            }
        }).trigger('change');

        <?php // OGP-11882 OGP-13367 "All Player" search function need to allow search DOB without year?>
        $('input[name="with_year"]').change(function() {

            // hide and show "with year field" or another.
            var withYear = $('input[name="with_year"]:checked').val();
            var inputsSelectorStr = 'input.dob_from,input.dob_to';

            // fsd = fields_search_dob
            var theWithoutYearFSD$El =$('.fields_search_dob:has(input[data-with-year="0"])');
            var theWithYearFSD$El =$('.fields_search_dob:has(input[data-with-year="1"])');
            if(withYear == 1){
                theWithoutYearFSD$El.addClass('hide');
                theWithYearFSD$El.removeClass('hide');

                theWithoutYearFSD$El.find(inputsSelectorStr).removeAttr('name');
                theWithYearFSD$El.find(inputsSelectorStr).each(function(){
                    $(this).attr('name', $(this).data('rename'));
                });

                /// Patch for MM-DD is default ,and will search YYYY-MM-DD (switch to "with year").
                // MM-DD transfor YYYY-MM-DD
                $.each(theWithYearFSD$El.find(inputsSelectorStr), function(){
                    var currInput$El = $(this);
                    if( moment(currInput$El.val(), 'MM-DD', true).isValid() ){ //
                        var patchedDate = moment(new Date()).format('YYYY-') + currInput$El.val();
                        currInput$El.val(patchedDate);
                    }
                });

            }else{
                theWithoutYearFSD$El.removeClass('hide');
                theWithYearFSD$El.addClass('hide');

                theWithoutYearFSD$El.find(inputsSelectorStr).each(function(){
                    $(this).attr('name', $(this).data('rename'));
                });
                theWithYearFSD$El.find(inputsSelectorStr).removeAttr('name');
            }

            dateInputAssignValue(theWithYearFSD$El.find('input.dateInput'));

            // assign to name="dob_from", name="dob_to"
            var theDateInput$El = $('.fields_search_dob input.dateInput:visible');
            dateInputAssignToStartAndEnd( theDateInput$El );

        }).trigger('change');

        $('#batch-send-mail-box').on('hide.bs.modal',function(){
            resetModal();
            resetSendBatchMailInput();
        });

        function resetModal() {
            $('#result-content').hide();
            $('#send-batch-mail-content').show();
            $('#send-batch-mail').show();
            $('.fail_report_container').empty();
        }

        function resetSendBatchMailInput(){
            clearSelectionsBatchmail();
            $('#batch_mail_subject').val('');
            $('#batch_mail_message_body').val('');
            $('#batch_mail_csv_data').val('');
            $("input[name='batch_mail_csv']").val('').trigger("change");
            $('#batch_mail_csv_data').val('');
        }

        // ======= BATCH UPDATE SALES AGENT =======
        $('#update-sales-agent').click(function(){

            // var agent_id=false;
            // if($('#sales-agent-id').val() == '' ){
            //     $('#sales-agent-id').next('span').html('<?=lang("sales_agent")?> is required');
            //     agent_id=false;
            // }else{
            //     $('#sales-agent-id').next('span').html('');
            //     agent_id=true;
            // }
            validateSelect2BatchUpdateSalesAgent();

            // if(!agent_id){return}

            $('#cancel-update-sales-agent').prop("disabled", true);
            $(this).prop("disabled", true);

            var i, len =batchPlayers.length;

            for(i=0; i<len; i++){
                batchIds.push(batchPlayers[i].id)
            }

            var data = {
                salesAgentId:$('#sales-agent-id').val(),
                playerIds:batchIds,
                label: 'player_list_send_batch_update_sales_agent',
                admin_double_submit_post: $('#admin_double_submit_post').val()
            };

            $.ajax({
                url : '<?php echo site_url('player_management/batchUpdatePlayerSalesAgent') ?>',
                type : 'POST',
                data : data,
                dataType : "json"
            }).done(function (data) {
                $('#update-sales-agent').prop("disabled", false);
                $('#cancel-update-sales-agent').prop("disabled", false);
                if (data.status == "success") {
                    $('#batch-update-sales-agent-box').modal('hide');
                    $('#sales-agent-id').val('');
                    $('.batch-message-cb').prop('checked',false);
                    batchIds = Array();
                    clearSelectionsBatchUpdateSalesAgent();
                    alert(data.msg);
                }else{
                    alert(data.msg);
                }
                window.location.reload();
            }).fail(function (jqXHR, textStatus) {
                $(this).prop("disabled", false);
                alert('<?=lang("cms.smsSendFail")?>');
                $('#batch-update-sales-agent-box').modal('hide');
                $('#sales-agent-id').val('');
                $('#cancel-update-sales-agent').prop("disabled", false);
                $('.batch-message-cb').prop('checked',false);
                $('#update-sales-agent').prop("disabled", false);
                batchIds = Array();
                clearSelectionsBatchUpdateSalesAgent();
                window.location.reload();
            });
        });

        $("#player_username_sales_agent").select2({
            ajax: {
                url: '<?php echo site_url('player_management/getPlayerUsernames') ?>',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    var query = {
                        q: params.term,
                        page: params.page
                    }
                    // Query paramters will be ?search=[term]&page=[page]
                    return query;
                },
                allowClear: true,
                tags: true,
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.items,
                        pagination: {
                            more: (params.page *30) < data.total_count
                        }
                    };
                },
                cache: true
            },
            escapeMarkup: function (markup) { return markup; },
            minimumInputLength: 1,
            templateResult: formatOption,
            templateSelection: formatOptionSelection
        });

        $("#player_username_sales_agent").on("select2:select", function (e) {
            var p = e.params.data,
                username = p.username || p.text,
                playerId = p.id,
                player = {id:playerId, username:username};

            if(userExistInSelected(player.username)){
                batchPlayers.push(player);
                updateDatatableCheckbox();
            }
            validateSelect2();
        });

        $("#player_username_sales_agent").on("select2:unselect", function (e) {
            var p = e.params.data,
                username = p.username || p.text,
                playerId = p.id;
            findAndRemove('username', username);
            updateDatatableCheckbox();
            validateSelect2();
        });

        function validateSelect2BatchUpdateSalesAgent(){
            if(!batchPlayers.length){
              $(".player-username-help-block").html('<?=lang("system.word38").lang("lang.is.required")?>');
            } else {
               $(".player-username-help-block").html('');
            }
        }

        function clearSelectionsBatchUpdateSalesAgent(){
            batchPlayers = Array();
            $("#player_username_sales_agent").val("").trigger("change");
            updateDatatableCheckbox();
        }

        function populateSelect2UsersBatchUpdateSalesAgent(){
            $("#player_username_sales_agent").empty();
            var  i=0, len = batchPlayers.length;
            if(len > 0){
                for(i=0; i<len; i++ ){
                    $("#player_username_sales_agent").append('<option value="'+batchPlayers[i].id+'" selected>'+batchPlayers[i].username+'</opton>').trigger('change');
                }
            }else{
                $("#player_username_sales_agent").empty();
            }
        }

        $("#clear-member-selection-sales-agent").click(function(){
            clearSelectionsBatchUpdateSalesAgent();
        });
        // ======= BATCH UPDATE SALES AGENT =======

        // ======= BATCH SEND MAIL =======
        $('#send-batch-mail').click(function(){

            $('.fail_report_container').empty();
            var csvList = document.getElementById('batch_mail_csv_data').value;
            if(!csvList && csvList == '') {
                validateSelect2Batchmail();
                var i, len =batchPlayers.length;

                for(i=0; i<len; i++){
                    if(batchIds.indexOf(batchPlayers[i].id)<0){
                        batchIds.push(batchPlayers[i].id)
                    }
                }

                if(!len>0 && !$('#send_to_all_player').prop('checked')){
                    $('#cancel-send-batch').prop("disabled", false);
                    $(this).prop("disabled", false);
                    $("#player_username_batch_mail").focus();
                    return;
                };
            }

            var template_id = false;
            var batch_subject = false;
            var message = false;
            var sending_via = false
            if($('#batch-sendgrid-template.active').length > 0){
                sending_via = 'SENDGRID';
                if($('#sendgrid_template_id').val() == '' ){
                    $('#sendgrid_template_id').next('span').html('<?=lang('Template ID is required')?>');
                    template_id=false;
                    if($('#sendgrid_template_id_list').val() == '') {
                        $('#sendgrid_template_id_list').next('span').html('<?=lang('Template ID is required')?>');
                    } else {
                        template_id = $('#sendgrid_template_id_list').val();
                    }
                }else{
                    $('#sendgrid_template_id').next('span').html('');
                    template_id = $('#sendgrid_template_id').val();
                }

                if(!template_id){
                    $('#cancel-send-batch').prop("disabled", false);
                    $(this).prop("disabled", false);
                    $('#sendgrid_template_id').focus();
                    return false;
                }
            } else if ($('#batch-smtp.active').length > 0) {
                sending_via = 'SMTP';

                batch_subject = $('#batch_mail_subject').val();
                if(!batch_subject.trim()){
                    $('#batch_mail_subject').next('span').html('<?=lang('Subject is required')?>');
                    $('#cancel-send-batch').prop("disabled", false);
                    $('#batch_mail_subject').focus();
                    $(this).prop("disabled", false);
                    return false;
                } else {
                    $('#batch_mail_subject').next('span').html('');
                }

                message = $('#batch_mail_message_body').val();
                if(!message.trim()){
                    $('#batch_mail_message_body').next('span').html('<?=lang('Message is required')?>');
                    $('#cancel-send-batch').prop("disabled", false);
                    $('#batch_mail_message_body').focus();
                    $(this).prop("disabled", false);
                    return false;
                } else {
                    $('#batch_mail_message_body').next('span').html('');
                }
            }

            $('#cancel-send-batch').prop("disabled", true);
            $(this).prop("disabled", true);

            var data = {
                sending_via : sending_via,
                template_id : template_id,
                batch_subject : batch_subject,
                batch_message : message,
                playerIds   : $('#send_to_all_player').prop('checked') ? 'ALL' : batchIds,
                csv_players : csvList,
                label       : 'player_list_send_batch_mails',
                admin_double_submit_post: $('#admin_double_submit_post').val()
            };

            $.ajax({
                url : '<?php echo site_url('player_management/sendBatchMail') ?>',
                type : 'POST',
                data : data,
                dataType : "json",
                beforeSend: function( xhr ) {
                    $('#result-content').show().append('<center id="loader"><img src="' + imgloader + '"></center>').delay(1000);
                     $('#send-batch-mail-content').hide();
                     $('#send-batch-mail').hide();
                }
            }).done(function (data) {
                $('#send-batch-mail').prop("disabled", false);
                $('#cancel-send-batch').prop("disabled", false);
                clearSelectionsBatchmail();
                if (data.status == "success") {
                    // $('#batch-send-mail-box').modal('hide');
                    $('.batch-message-cb').prop('checked',false);
                    batchIds = Array();
                    alert(data.msg);
                }else{
                    alert(data.msg);
                }
                // window.location.reload();
            }).fail(function (jqXHR, textStatus) {
                $(this).prop("disabled", false);
                alert('<?=lang("Send Batch Email Fail")?>');
                // $('#batch-send-mail-box').modal('hide');
                $('#cancel-send-batch').prop("disabled", false);
                $('.batch-message-cb').prop('checked',false);
                $('#send-batch-mail').prop("disabled", false);
                batchIds = Array();
                // clearSelectionsBatchmail();
                // window.location.reload();
            }).always(function(data){
                $('#loader').remove();
                if(data.link) {
                    $('.fail_report_container').append('<a href="'+data.link+'" ><?=lang('download report')?></a>');
                }
                resetSendBatchMailInput();
            });
        });

        $("#player_username_batch_mail").select2({
            ajax: {
                url: '<?php echo site_url('player_management/getPlayerUsernames') ?>',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    var query = {
                        q: params.term,
                        page: params.page
                    }
                    // Query paramters will be ?search=[term]&page=[page]
                    return query;
                },
                allowClear: true,
                tags: true,
                processResults: function(data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.items,
                        pagination: {
                            more: (params.page * 30) < data.total_count
                        }
                    };
                },
                cache: true
            },
            escapeMarkup: function(markup) {
                return markup;
            },
            minimumInputLength: 1,
            templateResult: formatOption,
            templateSelection: formatOptionSelection
        });

        $("#player_username_batch_mail").on("select2:select", function(e) {
            var p = e.params.data,
                username = p.username || p.text,
                playerId = p.id,
                player = {
                    id: playerId,
                    username: username
                };

            if (userExistInSelected(player.username)) {
                batchPlayers.push(player);
                updateDatatableCheckbox();
            }
            validateSelect2();
        });

        $("#player_username_batch_mail").on("select2:unselect", function(e) {
            var p = e.params.data,
                username = p.username || p.text,
                playerId = p.id;
            findAndRemove('username', username);
            updateDatatableCheckbox();
            validateSelect2();
        });
        function validateFieldBatchEmail(field) {

        }

        // function readURL(input) {
		// 	if (input.files && input.files[0]) {
		// 		var reader = new FileReader();
		// 		reader.onload = function (e) {
		// 			$('#select_badge').attr('src', e.target.result);
		// 		}
		// 		reader.readAsText(input.files[0]);
		// 	}
        //     console.log(reader);
        // }

        function clearSelectionsBatchmail() {
            batchPlayers = Array();
            $("#player_username_batch_mail").val("").trigger("change");
            updateDatatableCheckbox();
        }

        function populateSelect2UsersBatchmail() {
            $("#player_username_batch_mail").empty();
            var i = 0,
                len = batchPlayers.length;
            if (len > 0) {
                for (i = 0; i < len; i++) {
                    $("#player_username_batch_mail").append('<option value="' + batchPlayers[i].id + '" selected>' +
                        batchPlayers[i].username + '</opton>').trigger('change');
                }
            } else {
                $("#player_username_batch_mail").empty();
            }
        }

        function validateSelect2Batchmail(){
            if(!batchPlayers.length && !$('#send_to_all_player').prop("checked")){
              $(".player-username-help-block").html('<?=lang("system.word38").lang("lang.is.required")?>');
            } else {
               $(".player-username-help-block").html('');
            }
        }

        $("#clear-member-selection-batchmail").click(function(){
            clearSelectionsBatchmail();
        });
        // ======= EOF BATCH SEND MAIL =======

    }); // EOF $(document).ready(function(){...

    $(document).ready(function(){
        var theForm$El = $('#search-form_new');
        conditionsInput.initial(conditions, conditionsDefault, theForm$El);

        conditionsInput.onReady(conditions);
    }); // EOF $(document).ready(function(){...

    /// ==== conditionsInput START ====
    var conditionsInput = conditionsInput||{};

    conditionsInput.removedNameAttr = 'data-rename';
    /**
     * Initialize conditionsInput
     *
     * @param {object} theConditions The server resp. inputs for default. contain while No GET Params.
     * @param {object} theConditionsDefault The conditions for inputs while No GET Params.
     */
    conditionsInput.initial = function(theConditions, theConditionsDefault, theForm$El){
        var _this = this;
        _this.advancedConditions$El = $('#advancedConditions');
        _this.conditionsDefault = theConditionsDefault;
        _this.conditions = theConditions;
        _this.form$El = theForm$El;
    }

    /**
     * To select option by name and value
     *
     * @param {string} theSelectName The select name attr.
     * @param {string} theValue The value of select will to be.
     */
    conditionsInput.toSelectOptionBySelectName = function(theSelectName, theValue){
        var select$El = $('select[name="'+ theSelectName+ '"]');

        select$El.find('option').prop('selected', false).removeAttr('selected');
        $.each(select$El.find('option'), function(key, value){
            var curr$Option = $(this);
            if(curr$Option.val() == theValue){
                curr$Option.prop('selected', true);
                curr$Option.attr('selected', 'selected');
            }
        });
    } // EOF toSelectOptionBySelectName

    /**
     * Executed in document OnReady.
     *
     * @param {object} theConditions The json object from $conditions of server.
     *
     */
    conditionsInput.onReady = function(){
        var _this = this;
        var toOpenAdvanced = false;

        _this.onReadyEvents();

        _this.toSelectOptionBySelectName('registered_by',conditions.registered_by);
        $('[name="registered_by"]').trigger('change');

        // get params for advanced inputs rendering
        var theAdvancedNameList = _this.getAdvancedNameListFromGetParams();

        var isIgnoreNotExists = true;
        $.each( _this.conditions, function(propertyName, valueOfProperty){

            var isDefault = false;
            var isAdvancedInput = _this.isAdvancedInputByName(propertyName);

            var theForStr = _this.aGetParam2btnConditionForStr(propertyName);
            currBtn$El = $('[data-for="'+ theForStr+ '"]');

            if(isAdvancedInput){

                // to Detect is Default?
                isDefault = _this.isInputDefault(propertyName, isIgnoreNotExists);

                // add class active for default UI.
                if(isDefault){
                    currBtn$El.removeClass('active');
                }else{
                    currBtn$El.addClass('active'); // @todo total_balance_more_than / total_balance_less_than mapping to inputs-total_balance
                }
            } // EOF if(isAdvancedInput)

            if( isAdvancedInput && !isDefault ){
                toOpenAdvanced = toOpenAdvanced || true;
            }

            /// add rule for keep display Advanced input
            if( theAdvancedNameList.length > 0  ){
                toOpenAdvanced = toOpenAdvanced || true;
            }

        }); // EOF $.each( _this.conditions, function(){...}

        if( toOpenAdvanced
            // || true // for TEST
        ){ // open the Advanced Conditions.
            _this.scriptAdvancedCollapseToShow(_this.conditions);
        }else{// close the Advanced Conditions.
            _this.scriptAdvancedCollapseToHide();
        }

        /// initial ConditionsInputs
        var isByActived = true; // Dont toggle, keep UI by active class.

        /// convert GET params to .btn-conditions class list.
        var addActiveBtnConditionsClassList = [];
        $.each(theAdvancedNameList, function(indexNumber, currGetParamStr){
            var theForStr = _this.aGetParam2btnConditionForStr(currGetParamStr);
            if( ! $.isEmptyObject(theForStr)){
                addActiveBtnConditionsClassList.push(theForStr);
            }
        }); // EOF $.each(theAdvancedNameList, function(indexNumber, currGetParamStr){...
        /// add active class for scriptToggleRenderingConditionsInput() by GET param.
        $('.btn-conditions').each(function(indexNumber, currEl ){
            var forStr = $(currEl).data('for');
            if( addActiveBtnConditionsClassList.indexOf(forStr) > -1){
                $('[data-for="'+ forStr+ '"]').addClass('active');
            }
            var promise = _this.scriptToggleRenderingConditionsInput(forStr, isByActived);
            promise.always(function(){

            })
        }); // EOF $('.btn-conditions').each(function(){...
    } // EOF onReady

    conditionsInput.onReadyEvents = function(){
        var _this = $(this);

        // for OGP-15214
        $('body').on('show.daterangepicker', '#field_search_dob_without_year,#field_search_dob', function (e, picker) {
            var containerClass = 'field_search_dob_container';
            var container$El = picker['container'];
            if( ! container$El.hasClass(containerClass)){
                container$El.addClass(containerClass);
            }
        });

        $('body').on('click', '.btn-clear', function (e, settings) {
            conditionsInput.clicked_btn_clear(e)
            conditionsInput.scriptAdvancedCollapseToHide();
        });

        $('body').on('click', '.btn-conditions', function (e, settings) {
            conditionsInput.clicked_btn_conditions(e)
        });

        $('body').on('change', '[name="registered_by"]', function(){
            var currVal = $(this).val();
            conditionsInput.toSelectOptionBySelectName('registered_by',currVal)
        });


        $('body').on('submit', 'form#search-form_new', function(){
            <?php if($this->utils->getConfig('prevent_player_list_preload')): ?>
                $('#prevent_player_list_preload').val('1');
            <?php endif; // EOF if($prevent_player_list_preload): ?>

            <?php if($this->utils->getConfig('enabled_playerlist_search_cooldown_time')): ?>
                $('#submit-search-btn').attr('disabled',true);
            <?php endif; // EOF if($enabled_playerlist_search_cooldown_time): ?>

            // remove the hide inputs of Advanced Conditions.

            // filted not used condition for remove name attr. while on submit.
            var willRemoveNameAttrEls = conditionsInput.getFieldsByActivedBtnConditions( $('#advancedConditions').find('.btn-conditions:not(.active)') );
            conditionsInput.scriptRemoveNameAttrByEls( willRemoveNameAttrEls, conditionsInput.removedNameAttr);

            // Detect Checkbox for non-checked to GET param.
            if($('#search_reg_date').prop('checked') == false){
                $('#search_reg_date').val('off');
                $('#search_reg_date').prop('checked', true);
            }

            return true; // allow start to submit.
        });

        var disable_advanced_conditions_collapse_clear_featrue = '<?=$this->utils->getConfig('disable_advanced_conditions_collapse_clear_featrue');?>';

        $('body').on('hidden.bs.collapse', '#advancedConditions', function(){
          if (!disable_advanced_conditions_collapse_clear_featrue) {
            conditionsInput.scriptAdvancedCollapseToHide();
          }
        });
    } // EOF onReadyEvents

    /**
     * Get the grep class string of the (an) element.
     * @param {jquery(selector)} The (an) jquery wrappied element.
     * @param {string} The keyword for return class list. (optionial) If empty will return all class strings.
     * @return {array} The class array of the element.
     */
    conditionsInput.getClassListOf$El = function(the$El, grepClassStr){
        var classList = the$El[0].className.split(/\s+/);
        var returnClassList = [];
        if( typeof(grepClassStr) === 'undefined'){
            grepClassStr = '';
        }
        $.each(classList, function(index, classStr) {
            var isReturn = false;
            if(grepClassStr != ''){
                if (classStr.indexOf(grepClassStr) > -1) {
                    isReturn = true;
                }
            }else{
                isReturn = true;
            }
            if(isReturn){
                returnClassList.push(classStr);
            }
        });
        return returnClassList;
    }; // EOF getClassListOf$El

    /**
     * Move name attr. to spec. attr.(hidedAttr)
     * @param jquery(selector) the$Els The element object by jquery(selector).
     * @param string hidedAttr The attr. name.
     */
    conditionsInput.scriptRemoveNameAttrBy$Els = function(the$Els, hidedAttr, callback4IsIgnoreEl){

        if( typeof(callback4IsIgnoreEl) === 'undefined' ){
            callback4IsIgnoreEl = function(curr$El){
                return false;
            }
        }

        if( typeof(hidedAttr) === 'undefined' ){
            hidedAttr = 'data-rename';
        }
        the$Els.each(function(){
            var curr$El = $(this);

            if( ! callback4IsIgnoreEl(curr$El) ){
                var theName = curr$El.prop('name');
                curr$El.prop(hidedAttr, theName); // hide name attr. to  spec. hidedAttr
                curr$El.attr(hidedAttr, theName); // hide name attr. to  spec. hidedAttr
                curr$El.prop('name',false); // remove attr
                curr$El.removeAttr('name'); // remove attr
            }

        });
    }// EOF scriptRemoveNameAttrBy$Els

    conditionsInput.scriptRemoveNameAttrByEls = function(theEls, hidedAttr, callback4IsIgnoreEl){
        if( typeof(callback4IsIgnoreEl) === 'undefined' ){
            callback4IsIgnoreEl = function(curr$El){
                return false;
            }
        }

        if( typeof(hidedAttr) === 'undefined' ){
            hidedAttr = 'data-rename';
        }
        $.each(theEls, function(indexNumber, currEl){
            var curr$El = $(currEl);
            if( ! callback4IsIgnoreEl(curr$El) ){
                var theName = curr$El.prop('name');
                curr$El.prop(hidedAttr, theName); // hide name attr. to  spec. hidedAttr
                curr$El.attr(hidedAttr, theName); // hide name attr. to  spec. hidedAttr
                curr$El.prop('name',false); // remove attr
                curr$El.removeAttr('name'); // remove attr
            }

        });
    }// EOF scriptRemoveNameAttrByEls

    conditionsInput.callback4IsIgnoreEl = function(curr$El){
        // exception, to revert.
        var isIgnoreEl = false;
        var inputName = curr$El.prop('name');
        switch(inputName){
            case 'reg_web_search_by':
                if( $('input[name="registration_website"]:visible').length > 0){
                    isIgnoreEl = true;
                }else{
                    isIgnoreEl = false;
                }
            break;
            case 'dob_from':
            case 'dob_to':
            case 'with_year':
                if( $('input[name="'+ inputName+ '"]:visible').length > 0){
                    isIgnoreEl = true;
                }else{
                    isIgnoreEl = false;
                }
                break;

            case 'registered_by':
                isIgnoreEl = true;
                break;

            default:
                isIgnoreEl = true;
            break;
        }
        return isIgnoreEl;
    }

    /**
     * Revert the name attr. from spec. attr.(hidedAttr)
     * @param array(element,...) the$Els The element object .
     * @param string hidedAttr The attr. name.
     */
    conditionsInput.scriptRevertNameAttrByEls = function(theEls, hidedAttr, callback4IsIgnoreEl){
        if( typeof(callback4IsIgnoreEl) === 'undefined' ){
            callback4IsIgnoreEl = function(curr$El){
                return false;
            }
        }
        if( typeof(hidedAttr) === 'undefined' ){
            hidedAttr = 'data-rename';
        }
        $.each(theEls, function(indexNumber, currEl){
            var curr$El = $(currEl);

            if( ! callback4IsIgnoreEl(curr$El) ){
                var theName = curr$El.prop(hidedAttr);
                curr$El.prop(hidedAttr, false); // remove spec. hidedAttr
                curr$El.removeAttr(hidedAttr); // remove spec. hidedAttr
                curr$El.prop('name',theName); // revert name attr. from spec. hidedAttr
                curr$El.attr('name', theName); // revert name attr. from spec. hidedAttr
            }
        });
    } // EOF

    conditionsInput.getConditionsOfFormEl = function(theForm$El){
        var theConditions = {};
        var inputs = theForm$El.serializeArray();
        $.each(inputs, function(propertyName, valueOfProperty){
            var currInput = valueOfProperty;
            theConditions[currInput.name] = currInput.value;
        });
        return theConditions;
    }; // EOF getConditionsOfFormEl

    /**
     * Detect the input is default.
     * @param {string} inputName The input name.
     * @param {bool} isIgnoreNotExists Is ignore not exists element( for removed input while permission off, ex: "Contact Number" and "Contact No. Verify Status" by view_player_detail_contactinfo_cn)?
     */
    conditionsInput.isInputDefault = function(inputName, isIgnoreNotExists){
        var _this = this;
        var isDefault = false;
        if( typeof(isIgnoreNotExists) == 'undefined' ){
            isIgnoreNotExists = false;
        }
        var currInput$El = _this.form$El.find('[name="'+ inputName+ '"]');
        var currInputValue = currInput$El.val();

        var theConditions = $.extend(true, {}, _this.conditionsDefault);//, _this.conditions);

        var isInputDefault4DOB = _this.isInputDefault4DOB(inputName);
        var isInputDefault4total_balance = _this.isInputDefault4total_balance(inputName);
        var isInputDefault4total_deposit_count = _this.isInputDefault4total_deposit_count(inputName);
        var isInputDefault4total_deposit = _this.isInputDefault4total_deposit(inputName);

        if( ! _this.isNull(isInputDefault4total_balance) ){  // total_balance inputs
            isDefault = isInputDefault4total_balance;
        }else if( ! _this.isNull(isInputDefault4total_deposit_count) ){  // total_deposit_count inputs
            isDefault = isInputDefault4total_deposit_count;
        }else if( ! _this.isNull(isInputDefault4total_deposit_count) ){ // total_deposit inputs
            isDefault = isInputDefault4total_deposit;
        }else if( ! _this.isNull(isInputDefault4DOB) ){  // DOB inputs
            isDefault = isInputDefault4DOB;
        }else if(theConditions[inputName] == currInputValue ){   // NOT DOB inputs
            isDefault = true;
        }else if( (currInput$El.length == 0) && isIgnoreNotExists){
            isDefault = true;
        }
        return isDefault;
    }// EOF isInputDefault

    /**
     * To get the value from the GET parameters?
     * Ref. to https://stackoverflow.com/a/20097994
     *
     * usage,
     * <code>
     *  var fType = getUrlVars()["type"];
     * </code>
     * @param {string} This is the default setting, "window.location.href" and that is usually used.
     */
    conditionsInput.getUrlVarsArray = function(theUrl) {
        if( typeof(theUrl) === 'undefined'){
            theUrl = window.location.href;
        }

        var vars = {};
        var parts = theUrl.replace(/[?&]+([^=&]+)=([^&]*)/gi,
        function(m,key,value) {
        vars[key] = value;
        });
        return vars;
    }

    /**
     * Get Advanced Inputs Name List From GET params of URI.
     * @return {array} theAdvancedNameList The Name of Advanced Inputs at URI.
     */
    conditionsInput.getAdvancedNameListFromGetParams = function(){
        var _this = this;
        var theUrlVars = _this.getUrlVarsArray();
        var theAdvancedNameList = [];
        $.each(theUrlVars, function(currName, currValue){
            var isAdvancedInput = _this.isAdvancedInputByName(currName);
            if(isAdvancedInput){
                theAdvancedNameList.push(currName);
            }
        });
        return theAdvancedNameList;
    };
    /**
     * Reset the input for Signup Date
     *
     */
    conditionsInput.beInputDefault4SignupDate = function(){
        var _this = this;
        var search_reg_date$El = $('[name="search_reg_date"]');
        $('[name="registration_date_from"]').val(_this.conditionsDefault.registration_date_from);
        $('[name="registration_date_to"]').val(_this.conditionsDefault.registration_date_to);
        var search_registration_date$El = $('#search_registration_date[data-start][data-end]');
        dateInputAssignValue(search_registration_date$El, true);
        if(_this.conditionsDefault.search_reg_date == 'on'){
            search_reg_date$El.prop('checked',true);
            search_reg_date$El.attr('checked','checked');
            search_reg_date$El.trigger('change');
            // search_reg_date$El.prop('checked', true);
        }else{
            search_reg_date$El.prop('checked', false);
        }
    }

    /**
     * Reset the input for DOB
     *
     */
    conditionsInput.beInputDefault4fields_search_dob = function(){
        var _this = this;
        // text inputs applied daterangepicker.
        var fields_search_dob$El = $('[name="fields_search_dob"]');
        var fields_search_dob_without_year$El = $('[name="fields_search_dob_without_year"]');

        // 1:with year , 0:without year.
        $('[name="with_year"][value="'+ _this.conditionsDefault.with_year+ '"]').prop('checked', true)

        // text inputs
        var _currYear = moment().format('YYYY-');
        $('.dob_from.without_year').val(_currYear+ this.conditionsDefault.dob_from);
        $('.dob_from.with_year').val(_this.conditionsDefault.dob_from);
        $('.dob_to.without_year').val(_currYear+ _this.conditionsDefault.dob_to);
        $('.dob_to.with_year').val(_this.conditionsDefault.dob_to);
        dateInputAssignValue(fields_search_dob$El , true);
        dateInputAssignValue(fields_search_dob_without_year$El , true); // append without year.

        // radio inputs
        $('input[name="with_year"][value="'+ _this.conditionsDefault.with_year+ '"]').prop("checked", true).attr("checked", true);
    }// EOF beInputDefault4fields_search_dob

    /**
     * Reset the input for Total Balance fields.
     */
    conditionsInput.beInputDefault4intpus_total_balance = function(){
        var _this = this;
        $('input[name="total_balance_more_than"]').val(_this.conditionsDefault.total_balance_more_than);
        $('input[name="total_balance_less_than"]').val(_this.conditionsDefault.total_balance_less_than);
    };// EOF beInputDefault4intpus_total_balance

    /**
     * Reset the input for Total deposit count fields.
     */
    conditionsInput.beInputDefault4intpus_total_deposit_count = function(){
        var _this = this;
        $('input[name="total_deposit_count_more_than"]').val(_this.conditionsDefault.total_deposit_count_more_than);
        $('input[name="total_deposit_count_less_than"]').val(_this.conditionsDefault.total_deposit_count_less_than);
    };// EOF beInputDefault4intpus_total_deposit_count

    /**
     * Reset the input for Total deposit fields.
     */
    conditionsInput.beInputDefault4intpus_total_deposit = function(){
        var _this = this;
        $('input[name="total_deposit_more_than"]').val(_this.conditionsDefault.total_deposit_more_than);
        $('input[name="total_deposit_less_than"]').val(_this.conditionsDefault.total_deposit_less_than);
    };// EOF beInputDefault4intpus_total_deposit


    /**
     * Reset the input for Last Login Date.
     */
    conditionsInput.beInputDefault4last_login_date = function(){
        var _this = this;

        // hidden inputs
        var last_login_date_from$El = $('[name="last_login_date_from"]');
        var last_login_date_to$El =$('[name="last_login_date_to"]');
        // text input applied daterangepicker
        var search_last_login_date$El = $('#search_last_login_date');
        // checkbox input
        var search_last_log_date$El = $('[name="search_last_log_date"]');

        // text input applied daterangepicker
        last_login_date_from$El.val(_this.conditionsDefault.last_login_date_from);
        last_login_date_to$El.val(_this.conditionsDefault.last_login_date_to);
        dateInputAssignValue(search_last_login_date$El , true);

        // checkbox input
        if( _this.conditionsDefault.search_last_log_date === 'on'){ // checked
            search_last_log_date$El.prop('checked', true);
        }else{
            search_last_log_date$El.prop('checked', false);
        }
        search_last_log_date$El.trigger('change');
    } // EOF beInputDefault4last_login_date



    /**
     * Reset the input for Last Login Date.
     */
    conditionsInput.beInputDefault4deposit_approve_date = function(){
      var _this = this;

      var deposit_approve_date_from$El = $('[name="deposit_approve_date_from"]');
      var deposit_approve_date_to$El =$('[name="deposit_approve_date_to"]');
      var search_deposit_approve_date$El = $('#search_deposit_approve');

      deposit_approve_date_from$El.val(_this.conditionsDefault.deposit_approve_date_from);
      deposit_approve_date_to$El.val(_this.conditionsDefault.deposit_approve_date_to);
      dateInputAssignValue(search_deposit_approve_date$El , true);
    }

    conditionsInput.beInputDefault4latest_deposit_date = function(){
        var _this = this;

        var latest_deposit_date_from$El = $('[name="latest_deposit_date_from"]');
        var latest_deposit_date_to$El =$('[name="latest_deposit_date_to"]');
        var search_latest_deposit_date$El = $('#search_latest_deposit_date');

        latest_deposit_date_from$El.val(_this.conditionsDefault.latest_deposit_date_from);
        latest_deposit_date_to$El.val(_this.conditionsDefault.latest_deposit_date_to);
        dateInputAssignValue(search_latest_deposit_date$El , true);
    }


    /**
     * Reset the input for username.
     */
    conditionsInput.beInputDefault4username = function(){
        var _this = this;
        var search_by$El = $('[name="search_by"]'); // radio x2
        var username$El = $('[name="username"]'); // text input

        search_by$El.filter('[value="'+ _this.conditionsDefault.search_by+ '"]').prop("checked", true).attr("checked", true);
        $('[name="username"]').val(_this.conditionsDefault.username);
    }

    /**
     * Reset the input for game_username.
     */
    conditionsInput.beInputDefault4game_username = function(){
        var _this = this;

        $('[name="game_username"]').val(_this.conditionsDefault.game_username);
    }

    /**
     * Reset the input for tag_list.
     */
    conditionsInput.beInputDefault4tag_list = function(){
        var _this = this;
        var tag_list$El = $('[name="tag_list[]"],[name="include_tag_list[]"]');
        /// Ref. to http://davidstutz.de/bootstrap-multiselect/#methods

        tag_list$El.multiselect('deselectAll', false).multiselect('updateButtonText');
        if(_this.conditionsDefault.tag_list.length > 0){
            tag_list$El.multiselect('select', _this.conditionsDefault.tag_list);
            tag_list$El.multiselect('refresh');
        }
    } // EOF beInputDefault4tag_list

    /**
     * Reset the input for Under Affiliate.
     */
    conditionsInput.beInputDefault4UnderAffiliate = function(){
        var _this = this;
        var affiliate$El = $('[name="affiliate"]'); // text input
        var aff_include_all_downlines$El = $('[name="aff_include_all_downlines"]');// checkbox input

        affiliate$El.val(_this.conditionsDefault.affiliate);

        if( _this.conditionsDefault.aff_include_all_downlines === 'on'){ // checked
            aff_include_all_downlines$El.prop('checked', true);
        }else{
            aff_include_all_downlines$El.prop('checked', false);
        }
        aff_include_all_downlines$El.trigger('change');
    } // EOF beInputDefault4uUnderAffiliate

    /**
     * Reset the input for Under Agency.
     */
    conditionsInput.beInputDefault4UnderAgency = function(){
        var _this = this;
        var agent_name$El = $('[name="agent_name"]'); // text input
        // radio x2
        var own_downline_or_agency_line$El = $('[name="own_downline_or_agency_line"]'); // radio input

        // @todo own_downline_or_agency_line 並不存在，需要檢查 include_all_downlines、created_on_agency
        own_downline_or_agency_line$El.filter('[value="'+ _this.conditionsDefault.own_downline_or_agency_line+ '"]').prop("checked", true).attr("checked", true);

        agent_name$El.val(_this.conditionsDefault.agent_name);
    } // EOF beInputDefault4UnderAgency

    /**
     * Reset the input for VIP Level.
     */
    conditionsInput.beInputDefault4player_level = function(){
        var _this = this;
        var player_level$El = $('[name="player_level[]"]'); // select input
        // select input
        player_level$El.multiselect('deselectAll', false).multiselect('updateButtonText');
        if(_this.conditionsDefault.player_level.length > 0){
            player_level$El.multiselect('select', _this.conditionsDefault.player_level);
            player_level$El.multiselect('refresh');
        }
        // player_level$El.val(_this.conditionsDefault.player_level);
    } // EOF beInputDefault4player_level

    /**
     * Reset the input for Country.
     */
    conditionsInput.beInputDefault4residentCountry = function(){
        var _this = this;
        var residentCountry$El = $('[name="residentCountry"]'); // select input
        // select input
        residentCountry$El.val(_this.conditionsDefault.residentCountry);
    }
    /**
     * Reset the input for select tag.
     */
    conditionsInput.beInputDefault4selectInput = function(_name){
        var _this = this;
        var the$El = $('[name="'+ _name+ '"]'); // select input
        // select input
        the$El.val(_this.conditionsDefault[_name]);
    }

    // .btn-conditions.active
    /**
     * Get The input Fields by ".btn-conditions" element.
     * @param jquery(selector) The ".btn-conditions" element(s).
     * @return array The Field(s) by ".btn-conditions" element(s). (without jquery wrapped)
     */
    conditionsInput.getFieldsByActivedBtnConditions = function( btnConditions$El ){
        var returnFields = [];

        btnConditions$El.each(function(){
            var theCol$El = null;
            var currBtnConditions$El = $(this);
            var targetForStr = currBtnConditions$El.data('for');
            if( ! $.isEmptyObject(targetForStr) ){
                var theFieldDiv$El = $('.'+ targetForStr);
                if( theFieldDiv$El.length == 0 ){
                    theFieldDiv$El = $('#'+ targetForStr);
                }
                if( theFieldDiv$El.length > 0 ){
                    theCol$El = theFieldDiv$El.closest('[class*="col-md-"]');
                    if( typeof(currBtnConditions$El.data('col')) !== 'undefined'){
                        theCol$El = $('.'+ currBtnConditions$El.data('col'));
                    }
                }
            }

            var willReturnFields = null;
            switch(targetForStr){
                case 'registered_by':
                case 'registration_website':
                case 'ip_address':
                case 'lastLoginIp':
                case 'blocked':
                case 'friend_referral_code':
                case 'first_name':
                case 'last_name':
                case 'contactNumber':
                case 'phone_status':
                case 'cashback':
                case 'promotion':
                case 'priority':
                case 'email':
                case 'email_status':
                case 'im_account':
                case 'residentCountry':
                case 'city':
                case 'id_card_number':
                case 'cpf_number':
                case 'player_bank_account_number':
                case 'deposit_count': // deposit ipnut
                case 'deposit': // deposit select
                case 'friend_referral_code':
                case 'first_name':
                case 'last_name':
                case 'email':
                case 'email_status':
                case 'player_sales_agent':
                case 'daysSinceLastDeposit':
                case 'withdrawal_status':
                case 'affiliate_network_source':
                    var selectorList = [];
                    selectorList.push('input');
                    selectorList.push('select');
                    willReturnField$Els = theCol$El.find(selectorList.join(','));
                    break;

                case 'fields_search_dob':
                    var selectorList = [];
                    selectorList.push('input[name="with_year"]');
                    selectorList.push('input[name="fields_search_dob_without_year"]');
                    selectorList.push('input[name="fields_search_dob"]');
                    selectorList.push('input[name="dob_from"]'); // both without_year / with_year to filter for visiable
                    selectorList.push('input[data-rename="dob_from"]');
                    selectorList.push('input[name="dob_to"]'); // both without_year / with_year to filter for visiable
                    selectorList.push('input[data-rename="dob_to"]');
                    willReturnField$Els = theCol$El.find( selectorList.join(',') );
                    break;
                case 'total_balance_more_than':
                    var selectorList = [];
                    selectorList.push('input[name="total_balance_more_than"]');
                    willReturnField$Els = theCol$El.find( selectorList.join(',') );
                    break;
                case 'total_balance_less_than':
                    var selectorList = [];
                    selectorList.push('input[name="total_balance_less_than"]');
                    willReturnField$Els = theCol$El.find( selectorList.join(',') );
                    break;
                case 'inputs-total_balance':
                    var selectorList = [];
                    selectorList.push('input[name="total_balance_more_than"]');
                    selectorList.push('input[name="total_balance_less_than"]');
                    willReturnField$Els = theCol$El.find( selectorList.join(',') );
                    break;

                case 'total_deposit_count_more_than':
                    var selectorList = [];
                    selectorList.push('input[name="total_deposit_count_more_than"]');
                    willReturnField$Els = theCol$El.find( selectorList.join(',') );
                    break;
                case 'total_deposit_count_less_than':
                    var selectorList = [];
                    selectorList.push('input[name="total_deposit_count_less_than"]');
                    willReturnField$Els = theCol$El.find( selectorList.join(',') );
                    break;
                case 'inputs-total_deposit_count':
                    var selectorList = [];
                    selectorList.push('input[name="total_deposit_count_more_than"]');
                    selectorList.push('input[name="total_deposit_count_less_than"]');
                    willReturnField$Els = theCol$El.find( selectorList.join(',') );
                    break;

                case 'total_deposit_more_than':
                    var selectorList = [];
                    selectorList.push('input[name="total_deposit_more_than"]');
                    willReturnField$Els = theCol$El.find( selectorList.join(',') );
                    break;
                case 'total_deposit_less_than':
                    var selectorList = [];
                    selectorList.push('input[name="total_deposit_less_than"]');
                    willReturnField$Els = theCol$El.find( selectorList.join(',') );
                    break;
                case 'inputs-total_deposit':
                    var selectorList = [];
                    selectorList.push('input[name="total_deposit_more_than"]');
                    selectorList.push('input[name="total_deposit_less_than"]');
                    willReturnField$Els = theCol$El.find( selectorList.join(',') );
                    break;

                case 'search_deposit_approve':
                    var selectorList = [];
                    selectorList.push('input[name="search_deposit_approve"]');
                    selectorList.push('input[name="deposit_approve_date_from"]');
                    selectorList.push('input[name="deposit_approve_date_to"]');
                    willReturnField$Els = theCol$El.find( selectorList.join(',') );
                    break;
                case 'search_latest_deposit_date':
                    var selectorList = [];
                    selectorList.push('input[name="search_latest_deposit_date"]');
                    selectorList.push('input[name="latest_deposit_date_from"]');
                    selectorList.push('input[name="latest_deposit_date_to"]');
                    willReturnField$Els = theCol$El.find( selectorList.join(',') );
                    break;

                case 'affiliate_source_code':
                    var selectorList = [];
                    selectorList.push('input[name="affiliate_source_code"]');
                    willReturnField$Els = theCol$El.find( selectorList.join(',') );
                default:
                    break;
            } // EOF switch(targetForStr){...

            $.each(willReturnField$Els, function(index, currEl){
                returnFields.push(currEl);
            });

        }); // EOF btnConditions$El.each(function(){...})
        return returnFields;
    }; // EOF conditionsInput.getFieldsByActivedBtnConditions

    /**
     * set input to Default
     */
    conditionsInput.beInputDefault = function(inputName){
        var _this = this;
        var curr$El = $('[name="'+inputName+'"]');

        switch(inputName){
            // Signup Date:
            case 'search_reg_date': // checkbox
            case 'registration_date_from': // hidden input
            case 'registration_date_to': // hidden input
                _this.beInputDefault4SignupDate();
                break;

            // DOB
            case 'with_year': // radio, for keep value after setup default.
            case 'fields_search_dob_without_year':
            case 'fields_search_dob':
                _this.beInputDefault4fields_search_dob();
                break;

            // total_balance fields
            case 'inputs-total_balance': // for .btn-conditions clicked.
            case 'total_balance_more_than':
            case 'total_balance_less_than':
                _this.beInputDefault4intpus_total_balance();
                break;

            // total_deposit_count fields
            case 'inputs-total_deposit_count': // for .btn-conditions clicked.
            case 'total_deposit_count_more_than':
            case 'total_deposit_count_less_than':
                _this.beInputDefault4intpus_total_deposit_count();
                break;

            // total_deposit fields
            case 'inputs-total_deposit': // for .btn-conditions clicked.
            case 'total_deposit_more_than':
            case 'total_deposit_less_than':
                _this.beInputDefault4intpus_total_deposit();
                break;


            // last_login
            case 'search_last_log_date':
            case 'last_login_date_from':
            case 'last_login_date_to':
                _this.beInputDefault4last_login_date();
                break;

            // Username
            case 'search_by':
            case 'username':
                _this.beInputDefault4username();
                break;

            // game_username
            case 'game_username':
                _this.beInputDefault4game_username();
                break;

            // Exclude Players With Selected Tags:
            case 'include_tag_list[]':
            case 'tag_list[]':
                _this.beInputDefault4tag_list();
                break;

            // Under Affiliate:
            case 'affiliate':
            case 'aff_include_all_downlines':
                _this.beInputDefault4UnderAffiliate();
                break;

            // Under Agency
            case 'own_downline_or_agency_line':
            case 'agent_name':
                _this.beInputDefault4UnderAgency();
                break;

            // VIP Level:
            case 'player_level[]':
                _this.beInputDefault4player_level();
                break;
            // Country:
            case 'residentCountry':
            _this.beInputDefault4residentCountry();
                break;

            case 'deposit':
            case 'email_status':
            case 'phone_status':
            case 'blocked':
            case 'registered_by':
            case 'player_sales_agent':
            case 'daysSinceLastDeposit':
            case 'affiliate_network_source':
                $('select[name="'+ inputName+ '"]').val(_this.conditionsDefault[inputName]);
                break;
            case 'search_deposit_approve':
            case 'deposit_approve_date_from':
            case 'deposit_approve_date_to':
                _this.beInputDefault4deposit_approve_date();
                break;
            case 'search_latest_deposit_date':
            case 'latest_deposit_date_from':
            case 'latest_deposit_date_to':
                _this.beInputDefault4latest_deposit_date();
                break;
            case 'cashback':
            case 'promotion':
            case 'withdrawal_status':
            case 'priority':
                _this.beInputDefault4selectInput(inputName);
                break;
            default: // text input
                $('input[name="'+ inputName+ '"]').val(_this.conditionsDefault[inputName]);
                break;
            // @todo Clear the data in the field of conditions and set the default
        }
    } // EOF beInputDefault

    /**
     * Ref. to https://blog.xuite.net/dizzy03/murmur/46782052
     *
     */
    conditionsInput.isNull = function(exp){
        var returnBool = false;
        if (!exp && typeof exp != "undefined" && exp != 0){
            returnBool= true;
        }
        return returnBool;
    }

    /**
     * Check The input is DOB Default UI?
     * @param {string} inputName The input name attr. string.
     * @return {null|boolean} null means Not DOB inputs, true is DOB defalt input and false is Not.
     */
    conditionsInput.isEachInputDefault4DOB = function(inputName){
        var _this = this;
        var isDefault = null;
        var theConditions = $.extend(true, {}, _this.conditionsDefault);
        switch(inputName){

            case 'dob_to':
            case 'dob_from':
                isDefault = theConditions[inputName] == $('[name="'+inputName+'"]').val();
                break;

            case 'with_year':
                isDefault = $('[name="'+inputName+'"]:checked').val() == theConditions[inputName];
                break;
        }

        return isDefault;
    } // EOF isEachInputDefault4DOB

    conditionsInput.isInputDefault4DOB = function(inputName){
        var _this = this;
        var isDefault = null;
        var theConditions = $.extend(true, {}, _this.conditionsDefault);
        switch(inputName){
            case 'fields_search_dob':
            case 'fields_search_dob_without_year':
            case 'search_dob':
            case 'dob_to':
            case 'dob_from':
            case 'with_year':
                isDefault = true;
                isDefault = isDefault && _this.isEachInputDefault4DOB('dob_to');
                isDefault = isDefault && _this.isEachInputDefault4DOB('dob_from');
                isDefault = isDefault && _this.isEachInputDefault4DOB('with_year');
                break;
        }
        return isDefault;
    } // EOF isInputDefault4DOB

    /**
     * Convert a GET Param To .btn-condition attr., "data-for".
     * @param {string} theGetParamStr The GET Param Name OR conditionsInput.conditions/conditionsDefault element.
     * @return {string} forStr The ".btn-condition" element's attr., "data-for".
     */
    conditionsInput.aGetParam2btnConditionForStr = function(theGetParamStr){
        var _this = this;
        var forStr = '';
        var currBtn$El = $('[data-for="'+ theGetParamStr+ '"]');
        if(currBtn$El.length == 0){
            var for$El = $('[name="'+theGetParamStr+'"]');
            if(for$El.closest('[class*="inputs-"]').length > 0){
                var theClassList = _this.getClassListOf$El(for$El.closest('[class*="inputs-"]'),'inputs-');
                if( typeof(theClassList[0]) === 'string'){
                    currBtn$El = $('[data-for="'+ theClassList[0]+ '"]');
                }
            }
        }
        if( typeof(currBtn$El.data('for')) !== 'undefined' ){
            forStr = currBtn$El.data('for');
        }
        return forStr;
    }// EOF aGetParam2btnConditionForStr

    /**
     * Is The Fields, total_balance Default?
     * @param {string} inputName The input name attr. string.
     * @return {null|boolean} null means Not total_balance inputs, true is total_balance defalt input and false is Not.
     */
    conditionsInput.isInputDefault4total_balance = function(inputName){
        var _this = this;
        var isDefault = null;
        var theConditions = $.extend(true, {}, _this.conditionsDefault);//, _this.conditions);

        switch(inputName){
            case 'total_balance_more_than':
            case 'total_balance_less_than':
                isDefault = _this.conditionsDefault[inputName] == $('[name="'+inputName+'"]').val();
                break;
        }

        return isDefault;
    }; // EOF isInputDefault4total_balance

    /**
     * Is The Fields, total_deposit_count Default?
     * @param {string} inputName The input name attr. string.
     * @return {null|boolean} null means Not total_deposit_count inputs, true is total_deposit_count defalt input and false is Not.
     */
    conditionsInput.isInputDefault4total_deposit_count = function(inputName){
        var _this = this;
        var isDefault = null;
        var theConditions = $.extend(true, {}, _this.conditionsDefault);//, _this.conditions);

        switch(inputName){
            case 'total_deposit_count_more_than':
            case 'total_deposit_count_less_than':
                isDefault = _this.conditionsDefault[inputName] == $('[name="'+inputName+'"]').val();
                break;
        }

        return isDefault;
    }; // EOF isInputDefault4total_total_deposit_count

    /**
     * Is The Fields, total_deposit Default?
     * @param {string} inputName The input name attr. string.
     * @return {null|boolean} null means Not total_deposit inputs, true is total_deposit defalt input and false is Not.
     */
    conditionsInput.isInputDefault4total_deposit = function(inputName){
        var _this = this;
        var isDefault = null;
        var theConditions = $.extend(true, {}, _this.conditionsDefault);//, _this.conditions);

        switch(inputName){
            case 'total_deposit_more_than':
            case 'total_deposit_less_than':
                isDefault = _this.conditionsDefault[inputName] == $('[name="'+inputName+'"]').val();
                break;
        }

        return isDefault;
    }; // EOF isInputDefault4total_deposit

    /**
     * Show the Advanced Conditions Collapse.
     *
     * @param {object} theConditions The json object from $conditions of server.
     *
     */
    conditionsInput.scriptAdvancedCollapseToShow = function(theConditions){
        var _this = this;
        _this.advancedConditions$El.collapse('show');
    }// EOF scriptAdvancedCollapseToShow

    /**
     * Hide the Advanced Conditions Collapse.
     *
     * @param {object} theConditions The json object from $conditions of server.
     *
     */
    conditionsInput.scriptAdvancedCollapseToHide = function(){
        var _this = this;
        _this.advancedConditions$El.collapse('hide');
        setTimeout(function(){
            _this.scriptAdvancedInputsToHide();
        },200);
    } // EOF scriptAdvancedCollapseToHide

    /**
     * script for Hide the Advanced Inputs.
     */
    conditionsInput.scriptAdvancedInputsToHide = function(){
        var _this = this;
        var toDisplay = false;
        var conditionsBtn$Els = _this.form$El.find('.btn-conditions[data-for]');
        var toDisplay = false;
        conditionsBtn$Els.each(function(indexNumber, currEle){
            var curr$El = $(this);
            var theForStr = curr$El.data('for');
            _this.scriptRenderingConditionsInput(theForStr, toDisplay);
        });
    }; // EOF scriptAdvancedInputsToHide

    /**
     * Check the input name is in Advanced Contidion.
     * @param {string} conditionsKeyStr The name of input for detect.
     * @return {bool} returnBool If true means the input is in Advanced, else Not.
     */
    conditionsInput.isAdvancedInputByName = function(conditionsKeyStr){
        var returnBool = false;

        var advancedInputNames = [];
        advancedInputNames.push('registered_by');
        advancedInputNames.push('registration_website');
        advancedInputNames.push('ip_address');
        advancedInputNames.push('lastLoginIp');
        advancedInputNames.push('blocked');
        advancedInputNames.push('friend_referral_code');
        advancedInputNames.push('first_name');
        advancedInputNames.push('last_name');
        advancedInputNames.push('contactNumber');
        advancedInputNames.push('phone_status'); // 1/0
        advancedInputNames.push('email');
        advancedInputNames.push('email_status'); // 1/0
        advancedInputNames.push('im_account');
        advancedInputNames.push('residentCountry');
        advancedInputNames.push('city');
        advancedInputNames.push('id_card_number');
        advancedInputNames.push('cpf_number');
        advancedInputNames.push('deposit_count'); // deposit input
        advancedInputNames.push('deposit');// deposit select
        // DOB
        advancedInputNames.push('fields_search_dob');
        advancedInputNames.push('field_search_dob_without_year');
        advancedInputNames.push('dob_from'); // input:checkbox[name="search_dob"]:checked
        advancedInputNames.push('dob_to'); // input:checkbox[name="search_dob"]:checked
        advancedInputNames.push('with_year');
        // total_balance
        advancedInputNames.push('total_balance_more_than');
        advancedInputNames.push('total_balance_less_than');
        // total_deposit_count
        advancedInputNames.push('total_deposit_count_more_than');
        advancedInputNames.push('total_deposit_count_less_than');
        // total_deposit
        advancedInputNames.push('total_deposit_more_than');
        advancedInputNames.push('total_deposit_less_than');
        //arrpove date
        advancedInputNames.push('search_deposit_approve');
        advancedInputNames.push('deposit_approve_date_from');
        advancedInputNames.push('deposit_approve_date_to');
        // last deposit date
        advancedInputNames.push('search_latest_deposit_date');
        advancedInputNames.push('latest_deposit_date_from');
        advancedInputNames.push('latest_deposit_date_to');

        advancedInputNames.push('affiliate_network_source');
        advancedInputNames.push('player_bank_account_number');

        advancedInputNames.push('affiliate_source_code');
        advancedInputNames.push('player_sales_agent');
        advancedInputNames.push('daysSinceLastDeposit');
        advancedInputNames.push('daysSinceLastDepositRange');

        advancedInputNames.push('cashback');
        advancedInputNames.push('promotion');
        advancedInputNames.push('priority');
        advancedInputNames.push('withdrawal_status');


        if( $.inArray(conditionsKeyStr, advancedInputNames) != -1 ){
            // found it
            returnBool = returnBool|| true;
        }
        return returnBool;
    } // EOF isAdvancedInputByName

    /**
     * The script for clicked btn-clear
     */
    conditionsInput.clicked_btn_clear = function(e){
        var _this = this;
        var theTarget$El = $(e.target);
        var generalConditionsInputs$Els = theTarget$El.closest('form').find('input, select');

        $.each(generalConditionsInputs$Els, function(){
            var curr$El = $(this);
            var theForStr = curr$El.attr('name');
            /// 2. Clear the data in the field of conditions and set the default value. (Value setting follow:Trigger 4: Click each switch of Advanced Conditions).
            _this.beInputDefault(theForStr);
        });
    } // EOF clicked_btn_clear

    /**
     * The script for clicked btn-conditions
     */
    conditionsInput.clicked_btn_conditions = function(e){
        var _this = this;
        var theTarget$El = $(e.target);
        var targetForStr = theTarget$El.data('for');

        if( ! $.isEmptyObject(targetForStr) ){
            var promise = _this.scriptToggleRenderingConditionsInput(targetForStr);

        }
    }; // EOF clicked_btn_conditions

    /**
     * Toggle Rendering Conditions Input show/hidden.
     *
     * @param {string} targetForStr The value of data-for.
     * @param {boolean}  To keep by added class active.
     * @return {promise} $.promise() object
     */
    // function scriptDynamicRenderingConditionsInput(targetForStr){
    conditionsInput.scriptToggleRenderingConditionsInput = function(targetForStr, isByActived){
        var _this = this;
        if( typeof(isByActived) === 'undefined'){
            isByActived = false;
        }
        var isActive = _this.isActiveConditionsBtn(targetForStr);

        var toDisplay = true;
        if(isByActived){
            if(isActive){
                toDisplay = true;
            }else{
                toDisplay = false;
            }
        }else if( isActive ){ // for Toggle
            toDisplay = false;
        }
        return _this.scriptRenderingConditionsInput(targetForStr, toDisplay);
    }// EOF scriptDynamicRenderingConditionsInput

    /**
     * Detect the Conditions Button has active class for enabled.
     *
     * @param {string} theForStr The value of data-for.
     * @return {boolean} If true the Conditions Button had enabled, else not.
     */
    // function isActiveConditionsBtn(theForStr){
    conditionsInput.isActiveConditionsBtn = function(theForStr){
        var theBtn$El = $('button[data-for="'+ theForStr+ '"]');
        var isActive = theBtn$El.hasClass('active');
        return isActive;
    }// EOF scriptDetectConditionsInput

    /**
     * To Rendering inputs by theForStr, <input name="{theForStr}">.
     * @param {string} theForStr The input's value of attr. name.
     * @param {boolean} toDisplay If true do display the input and update button style, else to hide the input and update button style.
     */
    // function scriptRenderingConditionsInput(theForStr, toDisplay){
    conditionsInput.scriptRenderingConditionsInput = function(theForStr, toDisplay){

        var _this = this;
        var theFor$El = $('[name="'+ theForStr+ '"]');
        var theBtn$El = $('button[data-for="'+ theForStr+ '"]');
        var theCol$El = theFor$El.closest('[class*="col-md-"]'); //('.col-md-2,.col-md-4');
        if( typeof(theBtn$El.data('col')) !== 'undefined' ){ // OR spec Class for col-* element.
            theCol$El = $('.'+ theBtn$El.data('col'));
        }

        var referred = $.Deferred();
        if(toDisplay){ // display input
            // theCol$El.removeClass('hide');
            _this.animateConditionsInput(theForStr, toDisplay, theCol$El, function(){
                theBtn$El.addClass('active');
                referred.resolve();
            });

        }else{ // hide input
            // theCol$El.addClass('hide');
            _this.animateConditionsInput(theForStr, toDisplay, theCol$El, function(){
                theBtn$El.removeClass('active');

                /// 2. Clear the data in the field of conditions and set the default value. (Value setting follow:Trigger 4: Click each switch of Advanced Conditions).
                _this.beInputDefault(theForStr);

                referred.resolve();
            });
        }
        return referred.promise();
    }// EOF scriptRenderingInput

    /**
     * To Display OR hidden Conditions Input with animate eff., fadeIn() and fadeOut().
     * @param {string} theForStr The input's value of attr. name.
     * @param {boolean} toDisplay If true do display the input and update button style, else to hide the input and update button style.
     * @param {jquery(selector)} specCol$El The elements of jquery wrapped for apply animate eff.
     * @param {script} completeCB To Execute The Script after animate complete.
     */
    // function animateConditionsInput(theForStr, toDisplay){
    conditionsInput.animateConditionsInput = function(theForStr, toDisplay, specCol$El, completeCB){
        var theFor$El = $('[name="'+ theForStr+ '"]');
        var theCol$El = theFor$El.closest('[class*="col-md-"]'); //('.col-md-2,.col-md-4');
        if( typeof(specCol$El) !== 'undefined'){
            theCol$El = specCol$El;
        }

        var backgroundColor = 'lightgray';

        if( typeof(completeCB) === 'undefined' ){
            completeCB = function(){};
        }
        if(toDisplay){ // to display
            theCol$El.fadeIn({ // adjust opacity to show under lightgray background
                'start' : function(promiseAnimation){
                    theCol$El.css({
                        'visibility' : 'visible',
                        'display':'',
                        'background-color': backgroundColor
                    });
                    theCol$El.removeClass('hide');
                },
                'always' : function( promiseAnimation, jumpedToEnd ){
                    theCol$El.css({
                        'visibility' : '',
                        'display':'',
                        'background-color': ''
                    });
                    completeCB();
                },
            });
        }else{ // to hide
            theCol$El.css({'background-color': backgroundColor});
            theCol$El.fadeOut(400,function(){
                theCol$El.css({'background-color': ''});
                theCol$El.addClass('hide');
                completeCB();
            });
        } // EOF if(toDisplay)
    } // EOF animateConditionsInput
    /// ==== EOF conditionsInput  ====

    /**
     * Get attributes of daterangepicker for extend orig.( without year)
     *
     * Ref. to http://www.daterangepicker.com/#config
     *
     * @return {objet} The attributes of Date Range Picker.
     *
     */
    function getExtraAttr4field_search_dob_without_year(){
        var curr$El = $(this);
        var extraAttr = [];
        extraAttr['linkedCalendars'] = true; /// The calendars shift each or shift together. Check left or right arrows top of calendars.
        extraAttr['showDropdowns'] = false; // Show/Hide year and month select boxes above calendars.
        extraAttr['ranges'] = [];
        extraAttr['ranges']['<?=lang('dt.yesterday')?>'] = [moment().subtract(1, 'days'), moment().subtract(1, 'days')];
        extraAttr['ranges']['<?=lang('dt.lastweek')?>'] = [moment().subtract(1,'weeks').startOf('isoWeek'), moment().subtract(1,'weeks').endOf('isoWeek')];
        extraAttr['ranges']['<?=lang('dt.lastmonth')?>'] = [moment().subtract(1,'months').startOf('month'), moment().subtract(1,'months').endOf('month')];
        extraAttr['ranges']['<?=lang('dt.last30days')?>'] = [moment().subtract(29, 'days').startOf('day'), moment().endOf('day')];
        extraAttr['ranges']['<?=lang('lang.today')?>'] = [moment().startOf('day'), moment().endOf('day')];
        extraAttr['ranges']['<?=lang('cms.thisWeek')?>'] = [moment().startOf('isoWeek'), moment().endOf('day')];
        extraAttr['ranges']['<?=lang('cms.thisMonth')?>'] = [moment().startOf('month'), moment().endOf('day')];

        return extraAttr;
    }

    /**
     * Get attributes of daterangepicker for extend orig.( with year)
     *
     * Ref. to http://www.daterangepicker.com/#config
     *
     * @return {objet} The attributes of Date Range Picker.
     *
     */
    function getExtraAttr4field_search_dob_with_year(){
        var curr$El = $(this);
        var extraAttr = [];

        return extraAttr;
    }

    function modal(load, title) {
        var target = $('#mainModal .modal-body');
        $('#mainModalLabel').html(title);
        target.html('<center><img src="' + imgloader + '"></center>').delay(1000).load(load);
        $('#mainModal').modal('show');
    }

    function closeModal() {
        $('#mainModal').modal('hide');
    }

    function depositOrder(el) {
        var depositOrder = $(el).val();

        if (!depositOrder) {
            $('#search_deposit_date').prop('disabled',true);
            $('#deposit_date_from').prop('disabled',true);
            $('#deposit_date_to').prop('disabled',true);
        } else {
            $('#search_deposit_date').prop('disabled',false);
            $('#deposit_date_from').prop('disabled',false);
            $('#deposit_date_to').prop('disabled',false);
        }

        if (!depositOrder || depositOrder == '3') {
            $('#deposit_amount_from').val('').prop('disabled',true);
            $('#deposit_amount_to').val('').prop('disabled',true);
        } else {
            $('#deposit_amount_from').prop('disabled',false);
            $('#deposit_amount_to').prop('disabled',false);
        }
    }

    function walletOrder(el) {
        var walletOrder = $(el).val();

        if (!walletOrder) {
            $('#wallet_amount_from').val('').prop('disabled',true);
            $('#wallet_amount_to').val('').prop('disabled',true);
        } else {
            $('#wallet_amount_from').prop('disabled',false);
            $('#wallet_amount_to').prop('disabled',false);
        }
    }

    var ipSeg = function() {
        var seg = top.location.href.match(/([^\/]+)$/)[1];
        var validIp = /^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/;
        return seg.match(validIp);
    };

    var selectedList = [];
    selectedList.push('.buttons-columnVisibility');
    selectedList.push('.buttons-colvisRestore');
    selectedList.push('.buttons-colvisGroup');
    $(document).on("click",selectedList.join(','),function(){
        $("#player-table_wrapper .dataTable-instance").floatingScroll("init");
        var colspanNum = $(".dt-button.buttons-columnVisibility.active").length;
        columnVisibilityChange(colspanNum);
    });

    function columnVisibilityChange(colspanNum) {
        var isEnabledFeature =  "<?= $this->utils->isEnabledFeature('verification_reference_for_player') == true?>";
        if(isEnabledFeature == 1){
            colspanNum = colspanNum+1;
        }
        $("#ft-col").attr('colspan',colspanNum);
    }
</script>

