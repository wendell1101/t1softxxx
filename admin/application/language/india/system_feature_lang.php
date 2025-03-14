<?php
/* -------------------------------------------------------------- */
/* System feature - start
/* -------------------------------------------------------------- */
$lang['system_feature_type_deposit'] = 'Deposit';
$lang['system_feature_type_withdrawal'] = 'Withdrawal';
$lang['system_feature_type_bank'] = 'Bank';
$lang['system_feature_type_promo'] = 'Promo';
$lang['system_feature_type_profile'] = 'Player Center';
//$lang['system_feature_type_partner'] = 'Partner';
$lang['system_feature_type_agency'] = 'Agency';
$lang['system_feature_type_affiliate'] = 'Affiliate';
$lang['system_feature_type_transfer'] = 'Transfer';
$lang['system_feature_type_kyc_riskscore'] = 'KYC and Risk Score';
$lang['system_feature_type_sms'] = 'SMS';
$lang['system_feature_type_lottery'] = 'Lottery';
$lang['system_feature_type_other'] = 'Other';

/* -------------------------------------------------------------- */
/* Deposit Tab
/* -------------------------------------------------------------- */
$lang['system_feature_desc_add_security_on_deposit_transaction'] = 'There should be a Security Authentication pop up window asking for Withdrawal Password <br><b>Note:</b>  For new player accounts / newly signed up players, if this is enabled, Player will be requested to enter new withdrawal password (redirected to Security sidebar at first for withdrawal password creation). Once withdrawal password was already set up, player will be requested to bind the deposit account before making the deposit.<ul><li>Player Center &gt; Cashier Center sidebar &gt; Deposit Tab &gt; See Deposit Bank button </li><li>In depositing is required withdrawal password.</li></ul>';
$lang['system_feature_desc_confirm_manual_deposit_details'] = 'Confirm the payment account information befroe manual deposit.';
$lang['system_feature_desc_default_settled_status_on_player_deposit_list']='If enabled, Deposit List report in Player&apos;s Log will initially show all settledDeposit Requests. <ul><li>SBE &gt; Player Information &gt; Player&apos;s Logs &gt; Deposit List tab</li></ul>';
$lang['system_feature_desc_disable_player_deposit_bank'] = 'If enabled, Player&apos;s current/existing deposit account will not be shown in the Deposit Tab. <ul><li>Player Center &gt; Cashier Center sidebar &gt; Deposit Tab &gt; Confirm Submission button</li></ul>';
$lang['system_feature_desc_enable_3dparty_payment_in_modal']='If enabled, Player should be able to select 3rd Party as mode of payment.';
$lang['system_feature_desc_enable_change_deposit_transaction_ID_start_with_date'] = 'If enabled, the deposit transaction ID  of the succeeding requests will start with the date when the deposit request was made. <br><b>(Example: D2018050250127)</b><ul><li>SBE &gt; Payment tab &gt; Deposit List sidebar &gt; Pending Request &gt; ID</li></ul>';
$lang['system_feature_desc_enable_deposit_amount_note'] = 'If enabled, Player should be able to see a note under deposit amount portion <b>(exactly under The maximum amount of each deposit).</b> <ul><li>Player Center &gt; Cashier Center sidebar &gt; Deposit Tab &gt; Deposit Amount portion</li></ul>';
$lang['system_feature_desc_enable_deposit_category_view'] = 'If enabled, format of deposit will be changed to deposit category view.<ul><li>Player Center &gt; Cashier Center sidebar &gt; Deposit Tab</li></ul>';
$lang['system_feature_desc_enable_upload_deposit_receipt'] = 'If enabled, Deposit Receipt portion should be seen by Players as an option to upload receipt AND by Admins as an option to check/delete uploaded receipt.
	<ol><li>Player: Player Center &gt; Account History sidebar &gt; Deposit List tab &gt; Upload portion</li>
	<li>Admin: SBE &gt; Payment tab &gt; Deposit List sidebar &gt; View one of Order Details &gt; Deposit Invoice</li></ol>';
$lang['system_feature_desc_enable_deposit_datetime']='If enabled, Deposit Date Time and Mode of Deposit should be included on information required to be filled out by Players.<ul><li>Player Center &gt; Cashier Center sidebar &gt; Deposit Tab &gt; Deposit Date Time portion</li></ul>';
$lang['system_feature_desc_enable_deposit_upload_documents']='If enabled, Upload Attachment portion should be seen by Players as an option to upload an attachment. The SBE user can view the attached file under deposit request detail.
<ul><li>Player Center > Cashier Center sidebar > Deposit Tab > Upload Attachment portion</li><li>SBE > Payment tab > Deposit List > Deposit Request List > Detail button > Deposit Invoice portion</li></ul>';
$lang['system_feature_desc_enable_manual_deposit_detail'] = 'If enabled, admin user should be able to see additional fields &apos;Deposit Time&apos; and &apos;Deposit Method&apos; when performing manual deposit.';
$lang['system_feature_desc_enable_manual_deposit_realname'] = 'If enabled, real name of the Player should be shown under Real Name portion. <ul><li>Player Center &gt; Cashier Center sidebar &gt; Deposit Tab &gt; Real Name portion</li></ul>';
$lang['system_feature_desc_enable_manually_deposit_cool_down_time']='If enabled, Player shouldn&apos;t be able to deposit manually again within the set cooldown time as long as the first deposit request has not been approved yet. Expected Error Message (Chinese): <b>"对不起，%s 分钟内只能发起一次存款请求，如果想取消请联系客服"</b><ul><li> Cooldown time should be set thru config ($config[&apos;manually_deposit_cool_down_minutes&apos;]=10;)</li>	<li> Player Center &gt Online Deposits Button (在线存款) &gt Manual Deposit Recharge Immediately button (立即充值)</li>	<li><b>Note:</b> You will be redirected to manual payment page</li><li> Provide correct details to required fields &gt Submit button</li></ul>';
$lang['system_feature_desc_enable_mobile_3rdparty_deposit_close_1_btn_and_append_redirecturl'] = "If enabled, after successful 3rd party deposit, show a single <b>'Close' button instead of two buttons (Complete/Finished Payment, Choose other payment method)</b>.<ul><li>Player Center (Mobile) &gt; Deposit button &gt; Input necessary details ></li></ul>";
$lang['system_feature_desc_enable_mobile_manual_deposit_redirect_to_dashboard_after_deposit'] = "If enabled, Player should be redirected to dashboard after clicking &apos;Finished Payment<ul><li>Player Center &gt; Deposit tab/button &gt; Select Payment Account &gt; Click on Immediate payment button &gt; Input Deposit Amount &gt; Select Promotion (if necessary) &gt; Click on Deposit button (you will be redirected to payment api page) &gt; Go back to previous tab and click on &apos;Finished Payment&apos;</li></ul>";
$lang['system_feature_desc_enable_note_input_field_in_the_deposit'] ='If enabled, Note input field should be shown. <ul><li>Player Center &gt; Cashier Center &gt; Deposit Tab</li></ul>';
$lang['system_feature_desc_enable_pc_player_back_to_dashboard_after_submit_deposit'] = "If enabled, Player should be redirected to dashboard after clicking <b>'Finished Payment'</b>. <ul><li>Player Center &gt Deposit tab &gt Select Payment Account &gt Input Deposit Amount &gt Select Promotion (if necessary) &gt Add  notes (if necessary) &gt Click on Deposit button</li></ul>";
$lang['system_feature_desc_enable_using_last_deposit_account'] = 'If enabled, the form under "Please select deposit account" step will be changed from auto payment format to manual payment format and &apos;Click here to import the last data&apos; button will appear above the form.<ul><li>Admin: SBE &gt; System Tab &gt; System Settings &gt; Deposit Process &gt; Mode 2</li><li>Player: Player Center &gt; Cashier Center Deposit Tab</li></ul>';
$lang['system_feature_desc_force_setup_player_deposit_bank_when_if_it_is_empty']='If enabled, a pop up message asking to bind a deposit bank account should pop up. <br><b>Note:</b> Bank Deposit should be enabled as Payment Account Type on Default Collection Account. <ul><li>Player Center &gt Cashier Center sidebar &gt Deposit Tab</li></ul>';
$lang['system_feature_desc_hidden_mobile_deposit_MaxDailyDeposit_field'] = "If enabled, <i>Max daily deposit</i> field should be shown in the player center. <ul><li>Player Center &gt;  Deposit tab/button</li></ul>";
$lang['system_feature_desc_hidden_mobile_deposit_TimeOfArrival_field'] = "If enabled, <i>The Time of arrival</i> field should be shown in the player center. <ul><li>Player Center &gt;  Deposit tab/button</li></ul>";
$lang['system_feature_desc_hidden_print_deposit_order_button'] = 'If enabled, the print this pagebutton should not be shown in the Payment Information pop up window. <ul><li>Player Center &gt; Cashier Center sidebar &gt; Deposit Tab &gt; Confirm submission button &gt; Payment Information pop up window</li></ul>';
$lang['system_feature_desc_hidden_secure_id_in_deposit'] = 'If enabled, the Secure ID field should not be shown/visible.
<b>Note:</b> This is only applicable for Mode 2 Deposit Process
<ul><li>SBE > System tab > System Settings > Deposit Process > Mode 2 (should be chosen)</li><li>Player Center > Cashier Center sidebar > Secure ID field/li></ul>';
$lang['system_feature_desc_hide_deposit_approve_decline_button_on_timeout'] = 'Hide deposit approve decline button on timeout';
$lang['system_feature_desc_highlight_deposit_id_in_list'] = 'If enabled, all pending deposit transaction IDs would be highlighted depending on the color code per timeout. <br><b>Note:</b> This varies per client requirement.<ul><li>SBE &gt; Payment tab &gt; Deposit List sidebar &gt; Pending</li></ul>';
$lang['system_feature_desc_ignore_bind_transaction_with_player_promo_when_trigger_collection_account_promo'] ='If enabled, any promo bound/linked to the collection account won&apos;t be triggered.';
$lang['system_feature_desc_only_allow_one_pending_deposit'] = 'If enabled, 2nd deposit request should not be allowed unless the previous deposit request is already approved or declined.
<ul><li>Player Center > Cashier Center > Deposit (twice)</li><li>SBE > Payment tab > Deposit List</li></ul>';
$lang['system_feature_desc_show_decimal_amount_hint'] = 'If enabled, Please enter amount with decimal values for faster processing. message should be shown above <b>Payment Select Deposit Bank.</b><ul><li>Player Center &gt; Cashier Center Sidebar &gt; Deposit tab</li></ul>';
$lang['system_feature_desc_show_declined_deposit'] = 'If enabled, declined deposit requests should be shown in player&apos;s transaction reportd and must also be included in the deposit history. <ul><li>Player Center (Mobile) &gt Account History &gt Deposit History</li></ul>';
$lang['system_feature_desc_show_deposit_3rdparty_on_top_bar'] = 'If enabled, <b>3rd Party</b> should be shown in the drop down notification portion. <ul><li>SBE &gt; Notification Bar &gt; 3rd Party</li></ul>';
$lang['system_feature_desc_show_deposit_bank_details'] = 'If enabled, deposit bank&apos;s detail info including account number, name and branch should be shown. <ul><li>Player Center &gt; Cashier Center &gt; Deposit tab</li></ul>';
$lang['system_feature_desc_show_deposit_bank_details_first'] = 'Display deposit bank&apos;s detail info';
$lang['system_feature_desc_show_payment_account_image'] = 'Show the qrcode image in the manual deposit.';
$lang['system_feature_desc_show_pending_deposit'] = 'Show pending deposit requests in player&apos;s transaction report <ul><li>Player Center (Mobile) &gt; Account History &gt; Deposit History</li></ul>';
$lang['system_feature_desc_show_sub_total_for_deposit_list_report'] = 'If enabled, Subtotal should be shown below the transactions.<ul><li>SBE &gt; Payment tab &gt; Deposit List</li></ul>';
$lang['system_feature_desc_show_tag_for_unavailable_deposit_accounts'] = 'Show tag for unavailable deposit accounts';
$lang['system_feature_desc_show_time_interval_in_deposit_processing_list'] = 'If enabled, time interval in deposit process should be shown under Total Processing Time column.<br><ul><li>SBE &gt; Payment tab &gt; Deposit List sidebar &gt; Deposit Processing List button &gt; Total Processing Time</li></ul>';
$lang['system_feature_desc_show_total_deposit_amount_today'] = 'If enabled, dashboard for <b>Total deposit amount top 10 today</b> should be shown.<br><ul><li>SBE &gt; Dashboard</li></ul>';
$lang['system_feature_desc_trigger_deposit_list_send_message'] = 'If enabled, <b>select message template</b> drop down menu should be shown.<br><ul><li>SBE &gt; Payment tab &gt; Deposit List sidebar &gt; Deposit Request List &gt; Detail button</li></ul>';
$lang['system_feature_desc_untick_3rd_party_payment_if_load_deposit_list'] = 'If enabled, "<b>3rd Party Payment</b>" checkbox will be unticked as default.<ul><li>SBE &gt; Payment tab &gt; Deposit List sidebar &gt; 3rd Party Payment checkbox</li></ul>';
$lang['system_feature_desc_untick_atm_cashier_if_load_deposit_list'] = 'If enabled, <b>ATM/Cashier</b> checkbox will be unticked as default.<br><ul><li>SBE &gt; Payment tab &gt; Deposit List sidebar &gt; ATM/Cashier checkbox</li></ul>';
$lang['system_feature_desc_untick_enabled_date_if_load_deposit_list'] = 'If enabled, <b>Enabled date</b> checkbox will be unticked as default.<br><ul><li>SBE &gt; Payment tab &gt; Deposit List sidebar &gt; Enabled date checkbox</li></ul>';
$lang['system_feature_desc_untick_time_out_deposit_request_if_load_deposit_list'] = 'If enabled, <b>Hide timeout deposit request</b> checkbox will be unticked as default.<br><ul><li>SBE &gt; Payment tab &gt; Deposit List sidebar &gt; Hide timeout deposit request checkbox</li></ul>';
$lang['system_feature_desc_use_self_pick_promotion'] = 'If enabled, <b>Select Promo</b> portion should be shown under Deposit tab.<br><ul><li>Player Center &gt; Cashier Center Sidebar &gt; Deposit tab</li></ul>';
$lang['system_feature_desc_enable_collection_account_delete_button'] = 'If enabled, the delete button should be visible in action column.<ul><li>SBE &gt; System &gt; Payment Settings Tab &gt; Collection Account</li></ul>';
$lang['system_feature_desc_redirect_immediately_after_manual_deposit'] = 'If enabled, the confirmation message after successful manual deposit will be skipped, effectively redirect to next page immediately.<ul><li>Player Center &gt; Cashier Center &gt; Deposit tab &gt; Fill out deposit form &gt; Submit deposit by clicking the Deposit button</li></ul>';
$lang['system_feature_desc_enable_deposit_page_make_manual_deposit_upload_helptext_always_showing'] = "If enabled, manual deposit upload file helptext will be shown.<br><b>Note:</b>enable_deposit_upload_documents must be enabled first for this to work as expected.<ul><li>Player Center &gt; Deposit tab &gt; Bank Deposit &gt; Upload Attachment</li></ul>";
$lang['system_feature_desc_enable_display_manual_deposit_datetime_step_hint'] = "If enabled, date time step hint will show in manual deposit page.<br><b>Note:</b>enable_deposit_datetime must be enabled first for this to work as expected.<ul><li>Player Center &gt; Cashier Center sidebar &gt; Deposit Tab &gt; Deposit Date and Time portion</li></ul>";

$lang['system_feature_desc_enable_display_manual_deposit_upload_documents_step_hint'] = "If enabled,upload document step hint will show in manual deposit page.<br><b>Note:</b>enable_deposit_upload_documents must be enabled first for this to work as expected.<ul><li>Player Center &gt; Cashier Center tab &gt; Deposit tab &gt; Select Collection Bank &gt; Upload Attachment portion</li></ul>";

$lang['system_feature_desc_enable_manual_deposit_input_depositor_name'] = "If enabled, manual input of depositor name will be shown. <br><ul type = '1'><li>Player Center > Cashier Center sidebar > Deposit tab > Enter the name of the depositor section</li></ul>";

$lang['system_feature_desc_filter_payment_accounts_by_player_dispatch_account_level'] = "If enabled, filtering of Payment Accounts in Player Center will be applied. <br><ul type = '1'><li>SBE > System tab > Payment Settings > Dispatch Account</li><li>Player Center > Cashier Center sidebar > Deposit tab > list of banks will be defined according to settings in Dispatch Account</li></ul>";

$lang['system_feature_desc_hide_deposit_selected_bank_and_text_for_ole777'] = "If enabled, selected bank in Player Center deposit page will be hidden. <br><b>Note:</b> enable_deposit_category_view should be enabled for this to work as expected.<ul><li>Player Center &gt; Cashier Center sidebar &gt; Deposit tab &gt; Select Collection Account &gt; Deposit type tab</li></ul>";

$lang['system_feature_desc_ole777_on_first_popup_after_register'] = 'If enabled, Welcome message shows \'Go Home\' and \'Deposit Immediately\'.<br>Player Center (after registration)';

$lang['system_feature_desc_only_showing_atm_deposit_upload_attachment_in_deposit_list'] = "If enabled, only attachment from deposit through ATM/Cashier will be shown. <br><ul type = '1'><li>SBE > Payment tab > Deposit List > Detail button > Deposit Invoice portion</li></ul>";

$lang['system_feature_desc_required_deposit_upload_file_1'] = "	If enabled, uploading at least 1 file as proof of deposit will be required.<br><b>Note:</b> enable_deposit_upload_documents system feature should be enabled first.<br><ul type = '1'><li>Player Center > Cashier Center > Deposit tab > Upload Attachment</li></ul>";

$lang['system_feature_desc_show_last_manually_deposit_order_status'] = "If enabled, deposit status will be shown after confirmation message for a successful manual deposit request.<br><ul type = '1'><li>Player Center > Cashier Center > Deposit tab > Fill out deposit form > Submit deposit by clicking the Deposit button > Confirmation message after successful manual deposit request</li></ul>";

$lang['system_feature_desc_show_player_complete_withdrawal_account_number'] = "If enabled, bank account number of player should be shown in full in the Withdrawal page of the Player Center.<br><ul type = '1'><li>Player Center > Cashier Center > Withdrawal tab > Accounts receivable</li></ul>";


$lang['system_feature_desc_enable_display_manual_deposit_note_step_hint'] = "If enabled, note step hint will show in manual deposit page.<br><b>Note:</b>enable_note_input_field_in_the_deposit must be enabled first for this to work as expected.<ul><li>Player Center &gt; Cashier Center tab &gt; Deposit tab &gt; Select Collection Bank &gt; Player Deposit Note portion</li></ul>";
$lang['system_feature_desc_enable_preset_amount_helper_button_in_deposit_page'] = "If enabled, Reset button will be shown in Player Center Deposit page. Pre-requisite: Selected Collection Account in Deposit Request shown have Preset Amount setup in SBE > System tab > Payment Settings > Collection Account > Collection Account Information > Preset amount field</br>
<b>Note:</b> Reset button will allow the user to reset the Deposit Amount to blank.Player Center > Cashier Center Sidebar > Deposit Tab";
$lang['system_feature_desc_enable_preset_amount_helper_button_in_withdrawal_page'] = "If enable, display reset button and withdrawal amount can be reset to zero when player press the button, display All Amount button and withdrawal amount can auto filled in the balance in Main wallet when player press the button<ul><li> Player Center > Cashier Center Sidebar > Withdrawal Tab</li></ul>";
// UPDATED =======================================================

/* -------------------------------------------------------------- */
/* Withdrawal Tab
/* -------------------------------------------------------------- */
$lang['system_feature_desc_auto_deduct_withdraw_condition_from_bet'] = 'Deduct withdrawal condition from cashback betting amount, only for promotion rule which setup it';
$lang['system_feature_desc_check_withdrawal_conditions'] = 'If enabled, player will be able to continue withdrawal as long as there is no un-finished bet requirement or Current Total Bet is equal to or greater than Total Required Bet (Current Total Bet => Total Required Bet).<br> <ul><li>SBE > Player tab > All Players > Search Username > Player Information > Withdrawal Condition > Bet Amount Withdrawal Condition/Total Required Bet</li></ul>';
$lang['system_feature_desc_check_withdrawal_conditions_foreach'] = 'If enabled, player will be able to continue withdrawal as long as there is one bet amount withdrawal condition that has been met. <ul><li>SBE &gt; Player tab &gt; All Players &gt; Search Username &gt; Player Information &gt; Withdrawal Condition &gt; Bet Amount Withdrawal Condition (check each if met)</li></ul>';
$lang['system_feature_desc_check_deposit_conditions_foreach_in_withdrawal_conditions'] = "If enabled, player won't be able to continue withdrawal unless all minimum deposit amount withdrawal condition has been met.
<ul><li>SBE > Player tab > Player List > Search Username > Player Information > Withdrawal Condition > Minimum Deposit Tab (check each if met)</li></ul>";
$lang['system_feature_desc_clear_withdraw_condition_when_add_player_bonus'] = 'If enabled, Withdrawal Condition should be cleared provided that it met the clear withdraw condition setting in SBE > System tab > Payment Settings sidebar > Clear Withdraw Condition Setting and the promo request of the player is approved. The existing withdrawal condition should be cleared and the new promo withdraw condition should be created.
<ul><li>SBE > Player tab > All Players > Search Username > Player Information > Withdrawal Condition > Bet Amount Withdrawal Condition</li></ul>';
$lang['system_feature_desc_display_locked_status_column'] = 'If enabled, Locked Status column should be shown.<br> <ul><li>SBE > Payment tab > Withdrawal List sidebar > Withdrawal Request List table > Locked Status column</li></ul>';
$lang['system_feature_desc_enable_change_withdrawal_transaction_ID_start_with_date'] = 'If enabled, the withdraw code  of the succeeding requests will start with the date when the withdrawal request was made.
(example: W2018050264145).<br> <ul><li>SBE > Payment tab > Withdrawal List sidebar > Pending Request > Withdraw Code</li></ul>';
$lang['system_feature_desc_enable_confirm_birthday_before_setting_up_withdrawal_bank_account'] = 'If enabled, Players will be requested to add birth date in the account information settings.<br>Notes: This feature only works if<br>1. Player has no withdrawal bank account set.<br>2. Player has no birth date set. <br><ul><li>Player Center > Cashier Center > Withdrawal tab</li></ul>';

$lang['system_feature_desc_enable_currency_symbol_in_the_withdraw'] = 'If enabled, currency symbol for Amount Limit Per Transaction  and Daily Limit should be shown.<br><ul><li>Player Center > Cashier Center > Withdrawal tab > Please Input Withdrawal Amount portion</li></ul>';
$lang['system_feature_desc_enable_withdrawal_amount_note'] = 'If enabled, a note regarding withdrawal amount should be shown after withdrawal amount portion.<br><ul><li>Player Center > Cashier Center Sidebar > Withdrawal tab > Withdrawal amount portion</li></ul>';
$lang['system_feature_desc_enable_withdrawal_pending_review'] = 'If enabled, a Pending Review stage should be shown under Withdrawal Processing Stages Setting. To use this, Admin should add a Player Tag of the Players that needs to be reviewed first before approval. Everytime those Players (having the same tag) would request for withdrawal, their request should be added to the Pending Review dashboard instead of falling to Pending Request dashboard.<br><ul><li>SBE > System tab > Payment Settings sidebar > Withdrawal Workflow sidebar > Pending Review</li><li>SBE > Payment tab > Withdrawal List sidebar > Pending Review dashboard</li></ul>';
$lang['system_feature_desc_enable_withdrawal_pending_review_in_risk_score'] = 'If enabled, Players with risk score within the set range for <b>Pending for Review</b>, who have withdrawal requests should be added to the Pending Review dashboard instead of falling to Pending Request dashboard.<br>Note: enable_withdrawal_pending_review should be enabled first to show Pending Review dashboard.<br><ul><li> SBE > System tab > Risk Score sidebar > Risk Score Chart tab</li>Note: <b>withdrawal_pending_review</b> should be indicated in the Risk Score Chart to be able to use this System Feature.<br><li>SBE > Payment tab > Withdrawal List sidebar > Pending Review dashboard</li></ul>';
$lang['system_feature_desc_enabled_auto_check_withdraw_condition'] = 'If enabled, the Player should not be able to transfer from the specified subwallet to main wallet and/or other subwallet and should encounter, "Locked Balance because withdraw condition is not finished" message.<ul><li>SBE > Marketing tab > Promo Rules Settings > Edit Promo Rule</li><br>Note: Ensure that Promo Rule was set<br><ul><li>To lock balance transfer from subwallet to subwallet. (Trigger on transfer to subwallet + Release to same sub-wallet)</li><li>To be played within specific game type. (Allowed Game Type)</li><li>To allowed player levels only (Allowed Player Level)</li><li>To release bonus to specific wallet/subwallet (Bonus Release)</li></ul><br><li>SBE > Player tab > All Players > Search Username > Player Information > Withdrawal Condition</li><br>Note: Ensure that the Promo has been applied or issued to the player.';
$lang['system_feature_desc_enabled_auto_clear_withdraw_condition']='If enabled, Withdrawal Condition and Transfer Condition should be cleared provided that it met the clear withdraw condition setting (SBE > System tab > Payment Settings sidebar > Clear Withdraw Condition Setting)
<br>Notes:
<br>i. For new deposit, existing withdrawal condition should be cleared and new non promo withdraw condition should be created.
<br>ii. Transfer Condition will be cleared.
<ol>
	<li>SBE &gt; System Tab &gt; Payment Settings sidebar  &gt; Clear Withdraw Condition sidebar</li>
	<li>SBE &gt; Player Tab &gt; Player List &gt; Search Username &gt;  Player Information &gt;  Withdrawal Condition</li>
	<li>SBE &gt; Player tab &gt; Player List &gt; Search Username &gt; Player Information &gt; Transfer Condition</li>
</ol>';
$lang['system_feature_desc_enable_pending_vip_show_3rd_and_manualpayment_btn'] = 'If enabled, Players with tag within the set range for "Pending VIP", who have withdrawal requests should be added to the Pending VIP dashboard instead of falling to Pending Request dashboard.
	<ol>
	<li>SBE &gt; System tab &gt; Payment Settings sidebar &gt; Withdrawal Workflow sidebar &gt; Pending VIP</li>
	<li>SBE &gt; Payment tab &gt; Withdrawal List sidebar &gt; Pending VIP dashboard</li></ol>';
$lang['system_feature_desc_enabled_display_change_withdrawal_password_message_note']='If enabled, <b>Please type in 4-12 digits of numbers or letters</b> will appear as note in textbox field.<ul><li>Player Center > Security Tab > Withdrawal Password</li></ul>';
$lang['system_feature_desc_enabled_withdrawal_password'] = 'If enabled, withdrawal password create/change must be shown in the Security sidebar and require withdrawal password input in the Cashier Center of the Player Center.<br>Note: As of now to make this feature work as expected, Withdrawal Password under Withdrawal Verification (CMS tab > Player Center Settings) must be ticked. If set on Off, Withdrawal won&apos;t require withdrawal password input but would still show withdrawal password create/change under Security sidebar. Will have to request for RFE on this one.<ul><li>Player Center > Security sidebar</li></ul>';
$lang['system_feature_desc_hidden_player_bank_account_number_in_the_withdraw'] = "If enabled, Account Number will be hidden in Withdrawals.<ul><li>Player Center > Cashier Center > Withdrawal tab</li></ul>";
$lang['system_feature_desc_enable_batch_withdraw_process_apporve_decline'] = 'If enabled, ""Process Selected"", ""Approve Selected"" and ""Decline Selected"" buttons should be shown in Withdrawal Request List panel heading along with the checkboxes under Action column.<ol><li> SBE > Payment tab > Withdrawal List sidebar > Withdrawal Request List panel heading</li><li> SBE > Payment tab > Withdrawal List sidebar > Withdrawal Request List > Action column</li></ol>';
$lang['system_feature_desc_hide_bonus_withdraw_condition_in_vip'] = 'If enabled, it will hide:<br/ >SBE > Player tab > VIP Settings > Group Name > Edit Settings<ul><li>1st Time Deposit (Bonus) and 1st Time Deposit Bonus Withdraw Condition</li><li>Succeeding Deposit Bonus and Succeeding Deposit Bonus</li></ul>';
$lang['system_feature_desc_hide_paid_button_when_condition_is_not_ready'] = 'If enabled, Paid button should not be shown whenever there is a Not Ready status in any of the Withdrawal Conditions.<br>Note: This is not affected by check_withdrawal_conditions or check_withdrawal_conditions_foreach.<ul><li>SBE > Payment tab > Withdrawal List sidebar > Pending Request > Detail</li></ul>';
$lang['system_feature_desc_highlight_withdrawal_code_in_list'] = 'If enabled, withdraw code under Withdraw Code column should be highlighted. <br>Note: The highlight color is defined in config withdraw_code_highlight_sequence.<ul><li>SBE > Payment tab > Withdrawal List sidebar > Pending Request > Detail</li></ul>';
$lang['system_feature_desc_separate_approve_decline_withdraw_pending_review_and_request_permission']='If enabled,<br>1. <b>Approve/Decline Withdraw</b> role permission would be separated and should be shown as<ul><li>i. Approve/Decline Withdraw (Pending Request)</li><li>ii. Approve/Decline Withdraw (Pending Review)</li></ul>2. Admin user should be able to enable/disable the two(2) separate role permissions. For disabled role permission, Action column should be hidden.<br><ul><li>1. SBE > System tab > View Roles sidebar > Select a role that can alter <b>Approve/Decline Withdraw</b> role permission.</li><li>2. SBE > Payment tab > Withdrawal List sidebar > Pending Request / Pending Review dashboard > Withdrawal Request List</li></ul><br>Note: <b>enable_withdrawal_pending_review</b> system features must be ticked/enabled.';
$lang['system_feature_desc_show_total_withdrawal_amount_today'] = 'If enabled, dashboard for "Total withdrawal amount top 10 today" should be shown.<ul><li>SBE > Dashboard</li></ul>';
$lang['system_feature_desc_use_new_account_for_manually_withdraw'] = 'If enabled, "New Account" should be shown under "Please choose bank to withdraw" as an option.<ul><li>SBE > Payment tab > New Withdrawal sidebar</li></ul>';

/* -------------------------------------------------------------- */
/* Transfer Tab
/* -------------------------------------------------------------- */
$lang['system_feature_desc_enabled_mobile_transfer_input_amount_button'] = "Enable mobile transfer input amount button";
$lang['system_feature_desc_always_auto_transfer_if_only_one_game'] = "If enabled, all Player balance should automatically be </br><b>i.</b> Transferred from Main Wallet to Game Wallet  as soon as the game has been accessed. </br><b>ii.</b> Transferred back to Main Wallet once Withdrawal is successfully requested. </br><b>Note:</b> </br>i. No. will still be working even if this feature is not ticked as long as enabled_single_wallet_switch is enabled. </br><b>ii.</b> The whole Transfer tab would be hidden. </br> <b>iii.</b> This System Feature should be enabled first before game access to show immediate result. If no changes, try to reload game and refresh Cashier Center page.<ul><li>Player Center > Cashier Center Sidebar > Game Wallet</li></ul>";
$lang['system_feature_desc_disable_account_transfer_when_balance_check_fails'] = "If enabled, whenever query balance on game API failed, player shouldn't be able to transfer from sub wallet to sub wallet or main wallet to sub wallet and vice versa.<ul><li>Player Center &gt; Cashier Center Sidebar &gt; Transfer tab</li></ul>";
$lang['system_feature_desc_enabled_mobile_transfer_input_amount_button'] = "If enabled, input transfer money box/form should appear in Transfer Money Page.<ul><li>Mobile Version :  Player Center > Member Center tab/button  > Transfer Money</li></ul>";

$lang['system_feature_desc_enabled_single_wallet_switch'] = "If enabled, all Player balance should automatically be<br><ol type = '1'><li>Transferred from Main Wallet to Game Wallet as soon as the game has been accessed</li><li>Transferred back to Main Wallet once Withdrawal is successfully requested.</li></ol><b>Note:</b><ol type = '1'><li>No.2 will still be working even if this feature is not ticked as long as enabled_single_wallet_switch is enabled</li><li>The whole Transfer tab would be hidden.</li><li>This System Feature should be enabled first before game access to show immediate result. If no changes, try to reload game and refresh Cashier Center page.</li></ol>";

$lang['system_feature_desc_disabled_manually_transfer_on_player_center'] = "If enabled, hide manual transfer buttons on Player Center.<ul><li>Player Center > Balance Details</li></ul>";
$lang['system_feature_desc_show_inactive_subwallet_in_balance_adjustment'] = "If enabled, disabled Game APIs will be shown in the Manual Balance Adjustment.<ul><li>SBE &gt; Player tab &gt; Player List &gt; Search Username &gt; Player Information &gt; Account Info tab &gt; Adjust Balance button</li></ul>";
$lang['system_feature_desc_enabled_transfer_all_and_refresh_button_on_new_transfer_ui'] = "Temporary feature, removed after two sprints";

/* -------------------------------------------------------------- */
/* Bank Tab
/* -------------------------------------------------------------- */
$lang['system_feature_desc_allow_only_bank_account_limit'] = "If enabled, Player should only be able to see or is only allowed to use two(2) bank accounts for deposit and/or another 2 bank accounts for withdrawal. <br>Note: Bank account limit depends on the config set up per client. As of now, the default bank account limit is two(2). <ul><li>Player Center &gt; Cashier Center &gt; Bank Info tab &gt; Deposit/Withdraw Bank tab</li></ul>";
$lang['system_feature_desc_disable_chinese_province_city_select'] = 'If enabled, Admin should be able to input province and city manually instead of selecting from the drop down menu of the list of chinese provinces and cities. <ul><li>SBE &gt; Player tab &gt; All Players &gt; Search Username &gt; Player Information &gt; Financial Acc Info &gt; Add/Edit Deposit/Withdrawal Bank Info</li></ul>';
$lang['system_feature_desc_player_bankAccount_input_numbers_limit'] = "If enabled, Player should only be able to input numbers (including 'e' which represents the number in exponential form but should only appear once. Example: 11e2, 123456e2, etc.) <ul><li>Player Center &gt; Cashier Center &gt; Bank Info tab &gt; Deposit/Withdraw Bank tab</li></ul>";
$lang['system_feature_desc_player_bank_show_detail_form_validation_results'] = "If enabled, below error message should be encountered. For bank account number field left blank (not set as required field) Add: &apos;This is required&apos;.<br>Edit: &apos;Some bank information was entered incorrectlyBank card number Please fill in the complete information&apos;<br><b>For bank account number field provided with same bank account number:</b> <br>Add/Edit: &apos;Some bank information was entered incorrectly bank card number cannot be duplicated&apos;<br>Note: This also applies when 'player_bankAccount_input_numbers_limit' system features is enabled. <ul><li>Player Center &gt; Cashier Center &gt; Deposit tab &gt; Add new deposit account</li><li>Player Center &gt; Cashier Center &gt; Withdrawals tab &gt; Add a collection account</li><li>Player Center &gt; Cashier Center &gt; Bank information tab &gt; Deposit bank tab &gt; Add bank account information or edit an existing bank account</li><li>Player Center &gt; Cashier Center &gt; Bank information tab &gt; Withdrawal bank tab &gt; Add bank account information or edit an existing bank account</li><li>Leave bank account number field as blank</li><li>Input an exact similar number with the existing bank.</li></ul>";
$lang['system_feature_desc_player_bind_one_bank'] = 'If enabled, Player should only be allowed to have one bank account for deposit and one bank account for withdrawal. <br>Notes: <ul><li>If no bank account has been added yet, Player should contact Customer Service for manual adding of Deposit/Withdrawal Bank Account Information.</li><li>Player Center &gt; Cashier Center &gt; Bank Info tab &gt; Deposit/Withdraw Bank tab</li></ul>';
$lang['system_feature_desc_player_can_delete_bank_account'] = 'If enabled, Player should be able or allowed to delete Deposit/Withdrawal bank account as long as the bank account is not set to Default. Note: <br>For single registered bank account, this wouldn&apos;t be able to be deleted since it is automatically set as defailt. <ul><li>Player Center &gt; Cashier Center &gt; Bank Info tab &gt; Deposit/Withdraw Bank tab &gt; Delete button</li></ul>';
$lang['system_feature_desc_player_can_edit_bank_account'] = 'If enabled, Player should be able or allowed to edit details of registered bank accounts. <ul><li>Player Center &gt; Cashier Center &gt; Bank Info tab &gt; Deposit/Withdraw Bank tab &gt; Edit button </li></ul>';
$lang['system_feature_desc_duplicate_bank_account_number_verify_status_active'] = 'If enabled, duplicated bank account number verification will append verify status is active.<br>If enabled, duplicate bank account number verification will only check for account numbers which are active. If this feature is disabled, verification will be for both active and deactivated account numbers.<ul><li>SBE &gt; Player Information &gt; Bank Information &gt; Deposit/Withdrawal Bank List &gt; Status column: Active/Blocked</li><li>2. Player Center &gt; Cashier Center &gt; Bank Info tab &gt; Deposit/Withdraw Bank tabNote: Duplicate account number verification will apply if either of the numbers 1-5 Account Validator Mode is selected. (SBE &gt; System tab &gt; Payment Settings sidebar &gt; Player Center Financial Account Setting sidebar &gt; Others tab)</li></ul>';

/* -------------------------------------------------------------- */
/* Promo Tab
/* -------------------------------------------------------------- */
$lang['system_feature_desc_disabled_show_promo_detail_on_list'] = 'If enabled. View Details button of Promotion should be hidden. <ul><li>Player Center > Promotions</li></ul>';
$lang['system_feature_desc_enabled_request_promo_now_on_list'] = 'If enabled, <b>Claim Now</b> button should be shown in every promotion in player center.
Note: A Player can only join a promo once. Though there is <b>Claim Now</b> appearing in the promotion, it doesn&apos;t necessarily mean that he/she would be able to join the same promo again.
Instead, a pop up message, <b>You cannot join this promo again you already joined this promo already.</b> should be shown. <ul><li>Player Center > Promotions</li></ul>';
$lang['system_feature_desc_show_promotion_view_all'] = 'If enabled, ""View All"" button/tab must be visible as a category for listing of promotions. <ul><li>Player Center > Promotions > View All</li></ul>';
$lang['system_feature_desc_enabled_transfer_condition'] = 'If enabled, Generate Transfer Condition step should be shown in Edit/Create Promo Rule and Transfer Condition panel should be shown in Player Informations.<ul><li>1. SBE > Marketing tab > Promo Rules Settings sidebar > Add New Promo Rule or Edit Promo Rule</li><li>2. SBE > Player tab > All Players > Player List > Click any player > Player Informations > Transfer Condition tab</li></ul>';
$lang['system_feature_desc_enabled_use_deposit_amount_in_check_transfer_promo'] = 'If enabled, bonus amount will be computed based on the deposited amount of the player instead of transferred amount.<ul><li>SBE &gt; Marketing tab &gt; Promo Rules Settings sidebar &gt; Add New Promo Rule or Edit Promo Rule with transfer condition</li></ul>';
$lang['system_feature_desc_enable_player_tag_in_promorules'] = 'If enabled, players with the selected tags on their account that is the same with tags in Promo Rules > Prohibited Tag will not be allowed to claim/join the promotion.<ul><li>SBE > Marketing tab > Promo Rules > Prohibited Tag</li></ul>';
$lang['system_feature_desc_hide_promotion_if_player_doesnt_meet_the_conditions'] = "If enabled, the promo will be hidden and won't be available for claiming if the player does not meet the conditions.<ul><li>Player Center &gt; Promotions page</li></ul>";

/* -------------------------------------------------------------- */
/* Player Tab
/* -------------------------------------------------------------- */
$lang['system_feature_desc_disable_display_affiliate_code_on_player_center_affiliate_register_page'] = 'Disable display of affiliate code when player registers using affiliate url';
$lang['system_feature_desc_disable_display_affiliate_code_on_player_center_affiliate_register_page'] = "If enabled, referral code display should not be shown in the player registration page accessed thru an Affiliate Tracking Link.<ul><li>SBE > Affiliate tab > Affiliate List > Search Affiliate > Go to Affiliate Information > Go to one of Affiliate Tracking Links</li></ul>";
$lang['system_feature_desc_disable_display_agent_code_on_player_center_agent_register_page'] = 'If enabled, agent code display should not be shown in the player registration page accessed thru an Agent Tracking Link<ul><li>SBE > Agency tab > Agent List > Search Agent > Go to Agent Information > Go to one of Agent Tracking Links</li></ul>';
$lang['system_feature_desc_disable_player_multiple_upgrade'] = 'If enabled, player should not be able to have multiple upgrade in VIP level even if he/she meets the upgrade condition for a higher vip level.<ul><li>Player Center &gt; VIP Level portion</li><li>SBE &gt; Player tab &gt; VIP Settings sidebar &gt; VIP Group &gt; Edit Settings &gt; Level Up/Down Setting</li></ul>';
$lang['system_feature_desc_disabled_player_reply_message'] = 'If enabled, Reply button should not be shown upon clicking the message.<ul><li>Player Center > Messages sidebar > Message List > Messages or</li></ul><ul><li>Player Center > Dashboard Overview > Messages</li></ul>';
$lang['system_feature_desc_disabled_player_send_message'] = 'If enabled, Compose Message icon/button should not be shown (located beside search button)<ul><li>Player Center > Messages sidebar > Message List > Messages or</li></ul> <ul><li>Player Center > Dashboard Overview > Messages</li></ul>';
$lang['system_feature_desc_disabled_send_email_contactus'] = 'If enabled, email_contact_us_template under ID column should not be shown in the list.<ul><li>Player Center > CMS tab > Email Manager sidebar > Email template</li></ul>';
$lang['system_feature_desc_disabled_send_email_upon_aff_registration'] = 'If enabled, email_aff_registration_template / email_aff_registration_template_cn under ID column should not be shown in the list.<ul><li>Player Center > CMS tab > Email Manager sidebar > Email template</li></ul>';
$lang['system_feature_desc_disabled_send_email_upon_change_withdrawal_password'] = 'If enabled, email_change_withdrawal_password_template_en / email_change_withdrawal_password_template_cn under ID column should not be shown in the list.<ul><li>Player Center > CMS tab > Email Manager sidebar > Email template</li><ul>';
$lang['system_feature_desc_disabled_send_email_upon_player_registration'] = 'If enabled, email_registration_template / email_registration_template_cn under ID column should not be shown in the list.<ul><li>Player Center > CMS tab > Email Manager sidebar > Email template </li></ul>';
$lang['system_feature_desc_disabled_send_email_upon_promotion'] = 'If enabled, email_promotion_template / email_promotion_template_cn under ID column should not be shown in the list.<ul><li>Player Center > CMS tab > Email Manager sidebar > Email template</li></ul>';
$lang['system_feature_desc_display_last_login_timezone_in_overview'] = 'If enabled, Last Login Time will include timezone.<ul><li>Player Center > Dashboard</li></ul>';
$lang['system_feature_desc_display_total_bet_amount_in_overview'] = 'If enabled. ""Today turnover"" / Today\'s wager amount"" will appeare in VIP dashboard.<ul><li>Player Center > Dashboard</li></ul>';
$lang['system_feature_desc_eanble_display_mobile_user_icon'] = 'eanble display mobile user icon';
$lang['system_feature_desc_enable_player_center_live_chat'] = 'If enabled, Live Chat icon should be available in the quick navigation portion<ul><li>Player Center > Quick Navigation icons</li></ul>';
$lang['system_feature_desc_enable_player_center_mobile_footer_menu_games'] = 'enable player center mobile footer menu games';
$lang['system_feature_desc_enable_player_prefs_auto_transfer'] = 'If enabled, auto transfer option for player should be visible under player account information.<ul><li>Player Center > Account Information sidebar > Account Information</li></ul>';
$lang['system_feature_desc_enable_trasfer_all_quick_transfer'] = 'If enabled, Transfer All button should be shown in the quick transfer bar.<ul><li>Player Center > Quick Transfer</li></ul>';
$lang['system_feature_desc_enable_upload_income_notes'] = 'If enabled, a note icon for Proof of Income upload should be shown beside Proof of Income Verification. Note: Hover over the icon to see the complete note regarding proof of income upload.<ul><li>Player Center > Security sidebar > Proof  of Income Verification</li></ul>';
$lang['system_feature_desc_enabled_auto_switch_to_mobile_on_www'] = 'If enabled, Client site should be switched to ""m"" instead of ""www"" Note:  Was allowed to switch to ""m."" but not automatically in Juwang and automatically switched to ""m"" from ""www"" in Entaplay staging.';
$lang['system_feature_desc_enable_username_cross_site_checking'] = 'If enabled, the player username registered in the website cannot be used again in another website.<br><b>Note:  Configurations for specific website are set in "username_cross_site_url" by developers depending on the client.</b><ul><li>Player Center &gt; Registration</li></ul>';
$lang['system_feature_desc_enabled_display_placeholder_hint_require'] = 'All required fields in Account Information. will have (Require) in text field placeholder. Note: "(Required)" text appears in the unfilled form/input box of the required fields.<ul><li>Player Center > Account Information</li></ul>';
$lang['system_feature_desc_enabled_account_fields_display_first_name_input_hint'] = 'Account field which has input hint in Account Information will show hint near field. <ul><li>Player Center > Account Information</li></ul>';
$lang['system_feature_desc_disabled_player_to_change_security_question'] = 'If enabled, Change Security Question in Player Center will be hidden.<br><b>Note:</b>This feature only apply for players with an existing Security Question. Security Question under Registration tab under Player tab of the Registration Settings should be enabled first for this to work as expected.<ul><li>Player Center &gt; Security Tab &gt; Security Question &gt; Change Secret Question</li></ul>';
$lang['system_feature_desc_enabled_forgot_withdrawal_password_use_livechat_to_reset'] = 'If enabled, Contact Customer Service should appear in Withdrawal Password section.<ul><li>Player Center > Security</li></ul>';
$lang['system_feature_desc_enabled_show_player_obfuscated_bank_acctno'] = 'Obfuscated bank account in player center.<ul><li>Player Center > Cashier Center sidebar > Bank Info</li></ul>';
$lang['system_feature_desc_enabled_show_player_obfuscated_email'] = 'If enabled, Email Address in player center  should be encrypted.<ul><li>1. SBE > System tab > Registration Settings > Player tab > Account Information tab</br> Note: Ensure that the field is enabled in the Account Information tab. </br>2. Player Center > Account Information sidebar > Account Information (Email)</li></ul>';
$lang['system_feature_desc_enabled_show_player_obfuscated_im'] = 'If enabled, IM (ex: Skype, Facebook, etc.) in player center  should be encrypted.<ul><li>1. SBE > System tab > Registration Settings > Player tab > Account Information tab</br>Note: Ensure that the field is enabled in the Account Information tab.</br>2. Player Center > Account Information sidebar > Account Information (IM ex: Skype, Facebook, etc.)</li></ul>';
$lang['system_feature_desc_enabled_show_player_obfuscated_phone'] = 'If enabled, Contact Number in player center  should be encrypted.<ul><li>1. SBE > System tab > Registration Settings > Player tab > Account Information tab</br>Note: Ensure that the field is enabled in the Account Information tab.</br>2. Player Center > Account Information sidebar > Account Information (Contact Number)</li></ul>';
$lang['system_feature_desc_hidden_accounthistory_friend_referral_status'] = 'If enabled, Friend Referral Status tab should not be shown under Account History in the Player Center.<ul><li>Player Center > Account History sidebar</li></ul>';
$lang['system_feature_desc_hidden_avater_upload'] = 'If enabled, Profile Picture upload should not be available/shown under Account Information in the Player Center.<ul><li>Player Center > Account Information sidebar</li></ul>';
$lang['system_feature_desc_hidden_login_page_contact_customer_service_area'] = 'If enabled, Contact Customer Service hyperlink should not be shown.</br>Hide: "Login problems? Contact Contact Customer Service".<ul><li>Player Center > Log in Page</li></ul>';
$lang['system_feature_desc_hidden_player_center_pending_withdraw_balance_tab'] = 'If enabled, Pending Withdrawals tab should not be shown.<ul><li>Player Center > Cashier Center > Dashboard</li></ul>';
$lang['system_feature_desc_hidden_player_center_promotion_page_title_and_img'] = 'If enabled, Promotion title and image should not be shown; only the promo name and \'see details\' button shoud be shown.<ul><li>Player Center > Promotion sidebar</li></ul>';
$lang['system_feature_desc_enabled_forgot_withdrawal_password_use_email_to_reset'] = 'If enabled, Forgot Password?" should appear in Withdrawal Password section. Note: Email to be provided should be the same with the email used by the player in his/her account.<ul><li>Player Center > Security</li></ul>';
$lang['system_feature_desc_hidden_msg_sender_from_sysadmin'] = 'Hidden msg sender from sysadmin. Always use the last defined sender name.';
$lang['system_feature_desc_hidden_player_center_total_deposit_amount_tab'] = 'If enabled, Total Deposits tab should not be shown.<ul><li>Player Center > Cashier Center > Dashboard</li></ul>';
$lang['system_feature_desc_hidden_player_center_total_withdraw_amount_tab'] = 'If enabled, Total Withdrawals tab should not be shown.<ul><li>Player Center > Cashier Center > Dashboard</li></ul>';
$lang['system_feature_desc_hidden_player_first_login_welcome_popup'] = 'If enabled, Welcome pop-up message for first time players (New players who logged in for the first time in the player center) should be hidden/ should not be shown.Notes: Welcome pop-up message is the one that offers the player to join a vip group. Welcome pop-up message is not applicable on all Clients.VIP Groups shown in the Welcome pop-up message can be set thru SBE > Player tab > VIP Settings > VIP Group Manager > Edit icon > Tick/Untick "Player can choose to join group" > Edit.<ul><li>Player Center >  Player Profile</li></ul>';
$lang['system_feature_desc_hidden_referFriend_referralcode'] = 'If enabled, Referral Code shoud not be shown under Refer a Friend in the Player Center.<ul><li>Player Center >  Refer a Friend sidebar</li></ul>';
$lang['system_feature_desc_hidden_vip_betting_Amount_part'] = 'If enabled, Betting amount part (under Deposit Amount) should not be shown.<ul><li>Player Center > Dashboard Overview > VIP Level Dashboard</li></ul>';
$lang['system_feature_desc_hidden_vip_icon_LevelName'] = 'Case #1: If enabled, VIP icon and Level Name should not be shown.<ul><li>Player Center > Dashboard Overview > VIP Level Dashboard</li></ul>';
$lang['system_feature_desc_hidden_vip_status_ExpBar'] = 'Case #2: If enabled, VIP status experience status bar, deposit and betting amount part should not be shown.<ul><li>Player Center > Dashboard Overview > VIP Level Dashboard</li></ul>';
$lang['system_feature_desc_mobile_player_center_realtime_cashback'] = 'If enabled, Realtime Cashback button should be shown.';
$lang['system_feature_desc_player_center_realtime_cashback'] = 'If enabled, Realtime Cashback sidebar should be shown.<ul><li>Player Center > Player Account</li></ul>';
$lang['system_feature_desc_player_center_sidebar_deposit'] = 'If enabled, Fund Management (Deposit) icon should be available in the quick navigation portion.<ul><li>Player Center > Quick Navigation icons</li></ul>';
$lang['system_feature_desc_player_center_sidebar_message'] = 'If enabled, Message icon should be available in the quick navigation portion.<ul><li>Player Center > Quick Navigation icons</li></ul>';
$lang['system_feature_desc_player_center_sidebar_transfer'] = 'If enabled, Quick Transfer icon should be available in the quick navigation portion.<ul><li>Player Center > Quick Navigation icons</li></ul>';
$lang['system_feature_desc_show_game_lobby_in_player_center']='If enabled, Game Lobby will appear in Dashboard of Player Center in mobile mode.<ul><li>Mobile Mode : Player Center &gt; Dashboard</li></ul>';
$lang['system_feature_desc_show_player_messages_tab'] = 'If enabled, Messages sidebar should be shown in the player center.<ul><li>Player Center > Sidebars</li></ul>';
$lang['system_feature_desc_show_player_promo_report_note'] = 'If enabled, Notes column should be shown in the Promo History report.<ul><li>Player Center > Account History sidebar > Promo History tab > Promo History report</li></ul>';
$lang['system_feature_desc_show_player_vip_tab'] = 'If enabled, VIP Group sidebar should be shown in the player center.<ul><li>Player Center > Sidebars</li></ul>';
$lang['system_feature_desc_hidden_bet_amount_col_on_the_referral_report'] = 'If enabled, the Total Bets column will be hidden in Friend Referral Status in Player Center.<ul><li>Player Center &gt; Account History Sidebar &gt; Friend Referral Status</li></ul>';
$lang['system_feature_desc_disabled_display_sub_total_row_in_player_center_game_history_report'] = 'If enabled, the Total row in player center game history report will be hidden.<ul><li>Player Center &gt; Account History sidebar &gt; Game History tab</li></ul>';
$lang['system_feature_desc_hidden_bonus_col_on_the_referral_report'] = 'If enabled, the Bonus column will be hidden in Friend Referral Status in Player Center.<ul><li>Player Center &gt; Account History Sidebar &gt; Friend Referral Status</li></ul>';
$lang['system_feature_desc_enable_communication_preferences'] = 'If enabled, [IOM] Communication Preference will be enabled in the both Player Center and Smartbackend. Shown in Player Informations (Communication Preference panel and Player&apos;s Communication Preference History) under Player tab and Communication Preference Report under Report tab, and Player Center (Registration and Communication Preference sidebar).<ol><li>Player Center &gt; Registration &gt; Communication Preference Section</li><li>Player Center &gt; Communication Preference sidebar</li><li>SBE &gt; Player tab &gt; Player List &gt; Player List &gt; Click any player &gt; Player Information &gt; Basic Info tab &gt; Communication Preference panel</li><li>SBE &gt; Player tab &gt; Player List &gt; Player List &gt; Click any player &gt; Player Information &gt; Player&apos;s Logs panel &gt; Communication Preference History</li><li>SBE &gt; Report tab &gt; Communication Preference Report sidebar</li></ol>';
$lang['system_feature_desc_enabled_switch_to_mobile_dir_on_www'] = 'If enabled, Client site directory should be switched to "m" instead of "www".';
$lang['system_feature_desc_hidden_affiliate_code_on_player_center_when_exists_referral_code'] = 'If enabled, whenever a user register in Player Center using the referral url <b>(that can be seen in: Player Center &gt; Refer a friend Sidebar &gt; Referral url)</b>, the affiliate code textbox will be hidden.<ul><li>Player Center &gt; Registration</li></ul>';
$lang['system_feature_desc_hidden_agent_code_on_player_center_when_exists_referral_code'] = 'If enabled, whenever a user register in Player Center using the referral url <b>(that can be seen in: Player Center &gt; Refer a friend Sidebar &gt; Referral url)</b>, the agent code textbox will be hidden.<ul><li>Player Center &gt; Registration</li></ul>';
$lang['system_feature_desc_hidden_promotion_on_navigation'] = 'If enabled, the Promotion sidebar will hide in Player Center.<ul><li>Player Center &gt; Dashboard</li></ul>';
$lang['system_feature_desc_block_emoji_chars_in_real_name_field'] = 'If enabled, real name will be checked of Emoji chars on registration and updating player profile.<ul><li>Player Center &gt; Account Information &gt; First Name and Last Name</li><li>Player Center &gt; Registration &gt; First Name and Last Name</li></ul>';
$lang['system_feature_desc_cashier_custom_error_message'] = 'If enabled, Cashier Notification Manager sidebar should be shown under CMS tab.<ul><li>SBE &gt; CMS tab &gt; Cashier Notification Manager</li></ul>';

$lang['system_feature_desc_enable_player_message_request_form'] = 'If enabled, request form button for message function should be seen floating on the lower right corner of the Player Center and Request Form tab on Messages Settings under CS Management tab on SBE.<ul><li>Player Center &gt; Request Form button (floating button on the lower right corner of the page)</li><li>SBE &gt; CS tab &gt; Messages sidebar &gt; Messages panel heading &gt; Messages Settings button &gt; Request Form tab</li></ul>';

$lang['system_feature_desc_auto_popup_announcements_on_the_first_visit'] = "If enabled, modal pop-up should be displayed upon first visit of the Client's website.<br>
<b>Note:</b> Modal pop-up window should be ticked under WWW / M Announcement Board Options through Player Center tab in System Settings sidebar under System tab in SBE.<ul type = '1'><li>SBE > System tab > System Settings sidebar > Player Center tab > WWW / M Announcement Board Options > Modal pop-up in window</li><li>Client Website > Announcement Pop-up Message</li></ul>";

$lang['system_feature_desc_enable_auto_binding_agency_agent_on_player_registration'] = "If enabled, when player registers with an agent's tracking code, this newly registered player will become this agent's direct downline player.<ul type = '1'><li>Player Center > Registration page</li></ul>";

$lang['system_feature_desc_enable_mobile_logo_add_link'] = "If enabled, Player should be redirected to Home Page after clicking the Header Logo.<ul type = '1'><li>Player Center (Mobile) > Header Logo</li></ul>";

$lang['system_feature_desc_force_refresh_all_subwallets'] = "If enabled, when player loads or refreshes their wallet balances, all subwallet balances will be updated.<br><b>Note:</b> This one is more of backend, you will not be sure whether a force update happened";

$lang['system_feature_desc_switch_to_player_center_promo_on_first_popup_after_register'] = "If enabled, show promotion page through 'View Promotions' hyperlink after player successfully registered.<ul type = '1'><li>Player Center (after registration)</li></ul>";

$lang['system_feature_desc_enable_player_register_form_keep_error_prompt_msg'] = 'If enabled, Prompt Message will stay <b>OPEN</b> even when user moves to the next field in Registration Form if there has invalid item. <ul><li>Player Center &gt; Registration Form</li></ul>';


/* -------------------------------------------------------------- */
/* Agency Tab
/* -------------------------------------------------------------- */
$lang['system_feature_desc_adjust_rolling_for_low_odds'] = 'If enabled, when odds is less than one (1) rolling computation will be:
<br>turnover &times; odds &times; commission rate
<br>if disabled, when odds is less than one (1) rolling computation will be:
<br>turnover &times; commission rate
<ul><li>Agency page &gt; Report tab &gt; Agent Win Lose Comm Settlement</li></ul>';
$lang['system_feature_desc_agency'] = 'If enabled, the whole Agency tab should be shown.<ul><li>SBE > Agency Tab</li></ul>';
$lang['system_feature_desc_agency_hide_binding_player'] = 'If enabled, binding player should not be shown.<ul><li>SBE &gt; Agency Tab &gt; Agent List &gt; Search Agent Username &gt; Click on Agent Username &gt; Agent Information &gt; Basic Information panel &gt; Binding Player row</li></ul>';
$lang['system_feature_desc_agency_hide_sub_agent_list_action'] = 'If enabled, action buttons under Agent List panel (Activate Selected / Suspend Selected / Freeze Selected) and action buttons/icons under Action column should not be shown.<ul><li>Agency Page &gt; Listing tab &gt; Agents List dropdown option &gt; Agent List panel &gt; Action buttons</li><li>Agency Page &gt; Listing tab &gt; Agents List dropdown option &gt; Agent List panel &gt; Action column &gt; Action buttons/icons</li><li>Agency Page &gt; Setting tab &gt; Account Info &gt; Basic Information panel &gt; Reset button in Password and Withdrawal Password field</li></ul>';
$lang['system_feature_desc_agency_information_self_edit'] = 'If enabled, agent should be able to reset his own account and withdrawal password and should be able to view and edit sub-agent information (except for the Agent Username)<ul><li>Agency Page &gt; Settings tab &gt; Account Info</li><li>Agent Page &gt; Listing tab &gt; Agents List &gt; Click on Agent Username &gt; Agent Information &gt; Basic Information &gt; Edit/Suspend/Freeze</li><li>Agent Page &gt; Listing tab &gt; Agents List &gt; Click on Agent Username &gt; Agent Information &gt; Basic Information &gt; Adjust Credit Limit</li></ul>';
$lang['system_feature_desc_agent_can_have_multiple_bank_accounts'] = 'If enabled, agent should be able to add new bank account.<ul><li>Agency Page > Cashier > Register New Account</li></ul>';
$lang['system_feature_desc_agent_can_use_balance_wallet'] = 'Agency Page > Cashier > Withdraw From Locked Wallet/Transfer Balance To Main/Transfer Balance From Main<ul><li>Agency Page > Cashier > If enabled, agent should be able to </li><ul><li>1. Withdraw From Locked Wallet</li><li>2. Transfer Balance To Main wallet</li><li>3. Transfer Balance From Main wallet</li></ul></ul>';
$lang['system_feature_desc_agent_hide_export'] = 'If enabled, Export button should not be shown in:<ol><li>A. Agent Report</li><li>B. Agent Win Lose Comm</li><li>Settlement</li><ol><li>-Export in excel(Agent)
</li><li>-Export in excel(Subagent)</li></ol><li>C. Games Report</li></ol>Agency page &gt; Report tab &gt;<ol><li>A. Agent Report &gt; Search field &gt; Export Report in Csv button</li><li>B. Agent Win Lose Comm Settlement &gt; Search field &gt; Export in Excel (Agent) button and Export in Excel (Subagent) button</li><li>C. Games Report > Search field > Export in Excel button</li></ol>';
$lang['system_feature_desc_agent_tracking_code_numbers_only'] = 'If enabled, Admin user should not be able to save changes when alphanumeric format for agent tracking code was provided.<br>i. Create Agent:<ul><li>SBE > Agency tab > Agent List > Add New Agent button > Agent Form Tracking Code</li></ul><br>ii. Modify Agent Tracking Code:<ul><li>SBE > Agency tab > Search Agent > Go to Agent Information > Agent Tracking Code > Unlock > Random Code</li></ul><br>iii. Modify Agent Tracking Code:<ul><li>Agency page > Setting tab > Account Information > Agent promotion link > Tracking Code > Unlock > Random Code</li></ul>';
$lang['system_feature_desc_allow_negative_platform_fee'] = 'If enabled, agent allowed to be charged a negative platform fee according to the set Game Platform Fee in Commission Setting.
<br>Note: If this is not ticked, lowest platform fee paid to an agent is zero(0).
<br><b>Additional Info: Platform fee is either paid or charged to an agent whenever a player is losing or winning.</b>
<br>A. SBE &gt; Agency tab &gt; Agency List &gt; Search Agent &gt; Go to Agent Information &gt; Edit &gt; Agent Form &gt; Commission Setting
<br>B. Agency Page &gt; Report &gt; Agent Win Lose Comm Settlement';
$lang['system_feature_desc_alwasy_create_agency_settlement_on_view'] = 'If enabled, agent settlement details would get updated.</b><ul><li>SBE > Agency tab > Settlement sidebar > Agent Settlement Detail</li></ul>';
$lang['system_feature_desc_always_update_subagent_and_player_status'] = 'If enabled, when main agent (Level 1 agent) account has been frozen/suspended/activated, all sub-agents&apos; account will also be frozen/suspended/activated.</b><ul><li>SBE > Agency tab > Agent List > Search Agent</li></ul>';
$lang['system_feature_desc_enable_country_blocking_agency'] = 'If enabled, Agency column and check box to enable/disable Agency on selected Country should be shown<ul><li>SBE &gt; System tab &gt; Website IP Rules sidebar &gt; Country Rules panel &gt; Block a country</li><li>SBE &gt; System tab &gt; Website IP Rules sidebar &gt; Country Rules panel &gt; Agency column &gt; Check box (Ticked - Allowed, Unticked - Blocked)</li></ul>';
$lang['system_feature_desc_enable_player_report_generator'] = 'If enabled, system will generate data and save it to table in database for ease in viewing huge data in Player Report.<br><b>Note:</b>For T1 Developers use<br>1. Generate player report hourly should be enabled. (SBE &gt; System tab &gt; System Settings sidebar &gt; Cron jobs Settings tab<br>2.<ol><li>a. Agency page &gt; Report tab &gt; Player Report</li><li>b. Affiliate page &gt; Report tab &gt; Player Report</li></ol><b>Note:</b>affiliate_player_report system feature should be enabled to show Player Report on Affiliate page.';

$lang['system_feature_desc_enable_agency_player_report_generator'] = 'If enabled, system will generate data and save it to table in database for ease in viewing huge data in Player Report.<br><b>Note: This feature is intended for developers use.</b><ol><li>Generate player report hourly should be enabled. (SBE > System tab > System Settings sidebar > Cron jobs Settings tab)</li><li> Agency page > Report tab > Player Report</li></ol>';
$lang['system_feature_desc_hide_transfer_on_agency'] = 'If enabled, Transfer from/to subwallet should not be shown under Action column.</b><ul><li>Agency > Players List > Search Player > Action column</li></ul>';
$lang['system_feature_desc_login_as_agent'] = 'If enabled, <b>Login as Agent</b> should be shown in Password portion beside Reset.</b><ul><li>SBE > Agency tab > Agent List > Search Agent Username > Open Agent Information > Password portion</li></ul>';
$lang['system_feature_desc_rolling_comm_for_player_on_agency'] = 'If enabled, <b>Show Rolling Commision</b> checkbox should be shown in Permission Setting portion of Agent Form.</b><ul><li>SBE > Agency Tab > Template List > Edit Agent Template</li></ul>';
$lang['system_feature_desc_show_agent_name_on_game_logs'] = 'If enabled, <b>Agent Username</b> search field should be shown in Search section.</b><ul><li>SBE > Marketing Tab > Game Logs > Search section</li></ul>';
$lang['system_feature_desc_hide_agency_t1lotterry_link'] = 'If enabled, <b>T1 Lottery BO</b> link should not be visible.</b><ul><li>Agency > Navigation Bar > Setting</li></ul>';
$lang['system_feature_desc_hide_bet_limit_on_agency'] = '1.) if enabled, Show Bet Limit Template checkbox will be hidden in Permission Setting Section.<br>2.) if enabled, Bet Limit Template will be hidden in Setting Dropdown item.</b><ul><li>1.) SBE > Agent List > Add New Agent</li><li>2.) Agency > Settings</li></ul>';
$lang['system_feature_desc_hide_header_logo_in_agency'] = 'If enabled, the header logo in Agency will not display';
$lang['system_feature_desc_hide_registration_link_in_login_form'] = 'If enabled, the registration should not be visible in Agency login form. <ul><li>Agency > Login</li></ul>';
$lang['system_feature_desc_use_https_for_agent_tracking_links'] = 'If enabled, agent tracking list will use https instead http.<br>Note: This is only applicable for Agent Level 1 and above.<ul><li>SBE &gt; Agency Management &gt; Agent Information &gt; Tracking Code</li></ul>';
$lang['system_feature_desc_enable_agency_auto_logon_on_player_center'] = 'if enabled, Agency account are automatic logged in Player Center.<br><ul><li>Agency to Player Center</li></ul>';
$lang['system_feature_desc_agent_settlement_to_wallet'] = 'If enabled,<br>
A. Agent&apos;s settlement will be credited to agent&apos;s wallet. Note: Controls where agent perform settlement.
<ul><li>Agency page > Report tab > Agent Win Lose Comm Settlement</li></ul>
B. Allows zero value in Credit Limt and Available Credit when creating an agent&apos;s account. > Credit Limt and Available Credit field will be hidden.
<ul><li>Agency page > Report tab > Agent Win Lose Comm Settlement</li><li>SBE > Agency tab > Agency List > Search Agent > Go to Agent Information > Edit > Agent Form > Credit Limt and Available Credit</li></ul>
C. Hides the Credit Transaction sidebar on Agency tab.
<ul><li>SBE > Agency Tab > Credit Transactions sidebar</li></ul>';
$lang['system_feature_desc_daily_agent_rolling_disbursement'] = 'If enabled, agent rolling commission will be credited to agent&apos;s wallet daily.<ul><li>Agency page &gt; Report tab &gt; Agent Win Lose Comm Settlement</li></ul>';
$lang['system_feature_desc_agent_player_cannot_use_deposit_withdraw'] = "If enabled, the agent player cannot use and access deposit/withdrawal page of the player center";
$lang['system_feature_desc_deduct_agent_rolling_from_revenue_share'] = 'If enabled, agent&apos;s rolling amount will be deducted from agent&apos;s revenue share during settlement.<br>Note: The deduction will only occur if agent has a non-zero revenue share.<br>daily_agent_rolling_disbursement (System Features->Agency) should be enabled first for this feature to work as expected.<ul><li>Agency page &gt; Report tab &gt; Agent Win Lose Comm Settlement</li></ul>';
$lang['system_feature_desc_enable_create_player_in_agency']='if enabled, add player button will appeared in Player List.<br><ul><li>Agency &gt; Listing &gt; Player List</li></ul>';
$lang['system_feature_desc_enable_reset_player_password_in_agency']='if enabled, reset button will appeared in player information.<br><ul><li>Agency > Listing > Player List > Select Player (Username)</li></ul>';
$lang['system_feature_desc_variable_agent_fee_rate'] = "If enabled, Fees section should be shown in Agent Information.<br>Note: This is only applicable for Agent Level 1 and above.</b><ul><li>SBE > Agency Tab > Agent List > Select Agent</li></ul>";
$lang['system_feature_desc_settlement_include_all_downline'] = 'If enabled, generate settlement will include all the downline of an agent.<ul><li>Agency page &gt; Report tab &gt; Agent Win Lose Comm Settlement</li></ul>';

$lang['system_feature_desc_agent_tier_comm_pattern'] = "If enabled, agency commission settings by tier will be available.<br><b>Note:</b><ul><li>Feature <i>settlement_include_all_downline</i> needs to be enabled for tier calculation to work.</li><li>Only <b>Weekly</b> and <b>Monthly</b> settlement period settings are supported.</li></ul>";

$lang['system_feature_desc_disable_agency_game_report_in_sbe'] = 'If enabled, the Game Report in SBE under Agency Tab should be hidden.<ul><li>SBE &gt; Agency Tab &gt; Game Report</li></ul>';
$lang['system_feature_desc_disable_agency_player_report_in_sbe'] = 'If enabled, the Player Report in SBE under Agency Tab should be hidden.<ul><li>SBE &gt; Agency Tab &gt; Player Report</li></ul>';
$lang['system_feature_desc_enabled_agency_adjust_player_balance'] = 'If enabled, a button for Deposit and Withdraw will appear in Action Column in Agency System.<ul><li>Agency System &gt; Player Lists under Listing Tab</li></ul>';
$lang['system_feature_desc_hidden_domain_tracking'] = 'If enabled, the New Agent Additional Domain feature will be hidden in Agency System.<ul><li>Agency System &gt; Account Info under Settings Tab &gt; Click the Agent Tracking Code</li></ul>';

$lang['system_feature_desc_use_deposit_withdraw_fee'] = 'If enabled, Transaction Fee setting will be replaced with Deposit Fee and Withdraw Fee setting.<ul><li>SBE &gt; Agency tab &gt; Agency List &gt; Search Agent &gt; Go to Agent Information &gt; Fees panel</li></ul>';
$lang['system_feature_desc_allow_transfer_wallet_balance_to_binding_player'] = 'If enabled, agents can transfer their wallet balance to main wallet of its binding player account, so they can withdraw money using player\'s withdrawal facility.<ul><li>Agency page &gt; Cashier tab &gt; Payment Requests panel &gt; Transfer Balance to Linked Player button</li></ul>';
$lang['system_feature_desc_enable_agency_prefix_for_game_account']='If enabled, Game Account Prefix panel will be visible in Agent Information.<ul><li>SBE &gt; Agency tab &gt; Agent List &gt; Search Agent Username &gt; Edit Agent Information &gt; Game Account Prefix panel</li></ul>';
$lang['system_feature_desc_hide_reg_page_for_subagent_link_if_parent_agent_cannot_have_subagents'] = "When an agent is disabled of subagents, and:<ul><li>this feature is <b>enabled:</b> agent register page will not show for the agent's subagent register link. Instead, registering agent will be redirected to login page and will show \'Message: Subagent registration is disabled for agent\'</li><li>this feature is <b>disabled:</b> agent register page still shows up, but agents registered by the link will not be the agent's subagent.</li></ul>Agency sub agent registration page";

$lang['system_feature_desc_enable_agency_support_on_player_center'] = 'If enabled, will load resources needed for agency functions<br>Player Center<ul><li>f12 &gt; Sources Tab &gt; common &gt; js &gt; agency</li></ul>';

$lang['system_feature_desc_enabled_readonly_agency']='If enabled, Readonly Account button in Agent Basic Information will be shown where the user can add an account which can have viewing access in agent program <ul type = "1"><li>SBE > Agency tab > Agency List > Select any agent > Basic Information panel > Readonly Account button</li></ul>';

$lang['system_feature_desc_disable_agent_dashboard'] = 'If enabled, dashboard on agency page will still be shown but will show no results.<ul type = "1"><li>Agency page > Dashboard (or click "Agency System" to show the Dashboard)</li></ul>';

$lang['system_feature_desc_disable_agent_hierarchy'] = 'If enabled, Agent Hierarchy panel on SBE will be hidden.<ul type = "1"><li>SBE > Agency tab > Agency List > Search Agent > Go to Agent Information > Agent Hierarchy panel</li></ul>';

$lang['system_feature_desc_enable_player_center_style_support_on_agency'] = "If enabled, Agency Page theme will adopt Player Center's theme";

$lang['system_feature_desc_notify_agent_withdraw'] =  'If enabled, "Agency Withdraw" will be added under notification drop-down menu on SBE.<ul type = "1"><li>SBE > Notification drop-down menu (globe icon)</li></ul>';

$lang['system_feature_desc_use_new_agent_tracking_link_format'] =  'If enabled, agent tracking link format will be changed from <a href = "http://test/ag/">http://test/ag/</a> to <a href = "http://test?ag=">http://test?ag=</a><br><b>Example:</b> <a href = "http://test/ag/testbaagent">http://test/ag/testbaagent</a> to <a href = "http://test?ag=testbaagent">http://test?ag=testbaagent</a><ul type = "1"><li>SBE > Agency tab > Agent List > Search Agent Username > Open Agent Information > Agent Tracking Code panel</li></ul>';

/* -------------------------------------------------------------- */
/* Affiliate Tab
/* -------------------------------------------------------------- */
$lang['system_feature_desc_enable_aff_custom_css'] = 'If enabled, T1 designers should be able to customize the color scheme of affiliate page.<ul><li>Affiliate Page design should be changed according to Client&apos;s request.</li></ul>';
$lang['system_feature_desc_aff_enable_read_only_account'] = "If enabled, \"\"Add Read-only Account\"\" will appear in Affiliate Page &gt; Account tab &gt; Read-only Accounts panel. <ul><li>Affiliate Page &gt; Account tab &gt; Read-only Accounts panel</li></ul>";
$lang['system_feature_desc_aff_hide_changed_balance_in_cashier'] = "If enabled, \"\"Changed balance\"\" Column will be hidden in Cashier Table.<ul><li>Affiliate Page &gt; Cashier tab &gt; Payment Requests List &gt; Change Balance column</li></ul>";
$lang['system_feature_desc_aff_hide_payment_request_notes_in_cashier'] = "If enabled, \"\"Notes\"\" Column will be hidden in Cashier. <ul><li>Affiliate Page &gt; Cashier tab &gt; Payment Requests List &gt; Notes column</li></ul>";
$lang['system_feature_desc_aff_hide_traffic_stats'] = "If enabled, Traffic Statistic tab will be hidden in Navigation. <ul><li> Affiliate Page &gt; Navigation</li></ul>";
$lang['system_feature_desc_aff_no_admin_fee_for_negative_revenue'] = "If enabled, Admin fee equal to zero.<ul><li>SBE &gt; While getting gross revenue and platform fee</li></ul>";
$lang['system_feature_desc_aff_show_real_name_on_reports'] ="if enabled, Real Name Column will appear in Player Report table.<ul><li>Affiliate page &gt; Report tab &gt; Player Report</li></ul>";
$lang['system_feature_desc_affiliate_commision_check_deposit_and_bet'] = "If enabled, active player message will change from: <br><b>Active Player</b> means total number of players that has above minimum Deposit or Betting amount based on the Current Month. If a game provider is selected, then active players should be above or equal to total active players on selected game provider. <br>To:<br> <b>Active Player</b> to those players have above Minimum Deposit and Betting amount based on the Current <ul><li>SBE &gt; Affiliate Tab &gt; Affiliate Commission Seting</li></ul>";
$lang['system_feature_desc_affiliate_credit_transactions'] = "If enabled, Transactions will appear in Report. <ul><li>Affiliate Page &gt; Report &gt; Transactions</li></ul>";
$lang['system_feature_desc_affiliate_game_history'] = "If enabled, Game History will appear in Report List.<ul><li>Affiliate Page &gt; Report &gt; Game History</li></ul>";
$lang['system_feature_desc_affiliate_monthly_earnings'] = "If enabled, Earnings will appear in Report List. <ul><li>Affiliate Page &gt; Report &gt; Earningss</li></ul>";
$lang['system_feature_desc_affiliate_second_password'] = "If enabled, change secondary Password will appear in account information.<ul><li>Affiliate Page &gt; Account &gt; Account Information panel &gt; Password portion</li></ul>";
$lang['system_feature_desc_affiliate_tracking_code_numbers_only'] = "If enabled, tracking code will autogenerate numbers only.<ul><li>Affiliate Registration</li></ul>";
$lang['system_feature_desc_disable_account_name_letter_format'] = "If disabled, not only letters can enter in account name text field. <ul><li>SBE &gt; Affiliate &gt; Affiliate Information : Bank Information : Add</li></ul>";
$lang['system_feature_desc_display_sub_affiliate_earnings_report'] = "If enabled, sub affiliate earnings report should display in Sub-affiliates page <ul><li>Affiliate &gt; Sub-affiliates</li></ul>";
$lang['system_feature_desc_enable_affiliate_downline_by_level'] = "If enabled, only until the level with set sub-affiliate percentage (Sub Affiliate Commission Setting) should be computed and shown in the report and if 'Include All Downlines Affiliate' is also enabled.
<ul><li>SBE > Player > Player List</li>
<li>SBE > Report > Player Report</li>
<li>SBE > Report > Transactions</li></ul>";
$lang['system_feature_desc_enable_affiliate_player_report_generatorenable_affiliate_player_report_generator'] = 'If enabled, system will generate data and save it to table in database for ease in viewing huge data in Player Report.<br><b>Note: This feature is intended for developers use.</b><ol><li>Generate player report hourly should be enabled. (SBE > System tab > System Settings sidebar > Cron jobs Settings tab)</li><li> Affiliate page > Report tab > Player Report<br><b>Note: affiliate_player_report system feature should be enabled to show Player Report on Affiliate page.</b></li></ol>';
$lang['system_feature_desc_enable_country_blocking_affiliate'] = 'If enabled, affiliate column should display in country rules table. Button will also visible if country status has been set to <b>Blocked</b><ul><li>SBE > System > Website IP Rules</li></ul>';
$lang['system_feature_desc_enable_exclude_platforms_in_player_report'] = 'If enabled, only those game platform that selected in affiliate commission setting should display in player report. <ul><li>Aff > Report > Player Report<br><b>Note: The settings commission by tier should also enable.</b></li></ul>';
$lang['system_feature_desc_enable_new_dashboard_statistics'] = 'If enabled, 8 portlets should display on affiliate dashboard. <ul><li> Affiliate > Dashboard </li></ul>';
$lang['system_feature_desc_enable_reset_affiliate_list'] = 'If enabled, selected affiliate/s on affiliate list should be cleared in every refresh or leave the page. <ul><li>SBE &gt; Affiliate &gt; Affiliate List</li></ul>';
$lang['system_feature_desc_enable_sub_affiliate_commission_breakdown'] = 'If enabled, the sub affiliate commission will be clickable and will display the breakdown of sub-affiliate commission.<ul><li>SBE &gt; Affiliate tab &gt; Earnings Report sidebar &gt; Commission From Sub-affiliates column</li></ul>';
$lang['system_feature_desc_enable_tracking_remarks_field'] = 'If enabled, remarks input field should display on clicking the add/update of affiliate source code. <ul><li>SBE > Affiliate > Affiliate List > Affiliate Information : Affiliate Tracking Code</li></ul> If enabled, filter remarks and column remarks should be visible on Traffic Statistics page. <ul><li>Affiliate > Traffic Statistics</li></ul>';
$lang['system_feature_desc_promorules.allowed_affiliates'] = "Promorules allowed affiliates.";
$lang['system_feature_desc_player_list_on_affiliate'] = 'If enabled, Player List will appear in navigation bar.<ul><li>Aff > Dashboard</li></ul>';

$lang['system_feature_desc_switch_to_player_secure_id_on_affiliate'] = "If enabled, player's username in affiliate page Player Statistics tab will be converted into secure code.<ul type = '1'><li>Affiliate Page</li></ul>";
$lang['system_feature_desc_enable_player_benefit_fee'] = "If enabled, the player's benefit fee colum will display in affiliate Earnings Report.<ul type = '1'><li>SBE > Affiliate > Earnings Report</li></ul><ul type = '1'><li>Aff BO > Report > Earnings</li></ul>";
$lang['system_feature_desc_enable_addon_affiliate_platform_fee'] = "If enabled, the addon platform fee colum will display in the Affiliate Earnings Report.<ul type = '1'><li>SBE > Affiliate > Earnings Report</li></ul>";
$lang['system_feature_desc_enable_registration_time_aff_tracking_code_validation'] = "If enabled, will enable the tracking Code validation when player registers.";

$lang['system_feature_desc_show_player_info_on_affiliate'] = "Show player info on affiliate.";
$lang['system_feature_desc_show_transactions_history_on_affiliate'] = 'if enabled. Transaction History will appear in Navigation Bar.<ul><li>Aff > Navigation</li></ul>';
$lang['system_feature_desc_show_player_contact_on_aff'] = '	if enabled, email address of the Player will be unmasked.<ul><li>Affiliate Page &gt; Player List &gt; email column</li></ul>';
$lang['system_feature_desc_affiliate_additional_domain']  = "If enabled, Affliliate Additional Domain will appeared in Dashboard. <ul><li>Affiliate Page &gt Dashboard &gt Affiliate Additional Domain panel</li></ul>";
$lang['system_feature_desc_individual_affiliate_term'] = "If enabled, fees form will be visible in Affiliate information. <ul><li>SBE > Affiliate > Affiliate List > Affiliate Information : Operator Settings</li></ul>";
$lang['system_feature_desc_affiliate_source_code'] = "If enabled, Earnings will appear in Report List.<ul><li>Affiliate Page &gt; Report &gt; Earnings</li></ul>";
$lang['system_feature_desc_player_stats_on_affiliate'] = 'If enabled, Player Statistic will appear in navigation bar.<ul><li>Aff > Dashboard</li></ul>';
$lang['system_feature_desc_affiliate_player_report'] = "If enabled, Player Report will appear in Report List. <ul><li>Affiliate Page &gt; Report &gt; Player Report</li></ul>";
$lang['system_feature_desc_hide_total_win_loss_on_aff_player_report'] = 'if enabled, Total win and Total Loss Column will be hidden in Player Report Table.<ul><li>Aff > Report > Player Report</li></ul>';
$lang['system_feature_desc_notify_affiliate_withdraw'] =  "If enabled, affiliate withdrawal will have notification count information.<br>
<b>Note:</b> Affiliate Withdraw permission should be enabled
<ul><li>SBE > Notification > Affiliate Withdraw</li></ul>";
$lang['system_feature_desc_parent_aff_code_on_register']  = "Parent affiliate code on register.";
$lang['system_feature_desc_show_affiliate_list_on_search_player'] = "Show affiliate list on search player.";
$lang['system_feature_desc_show_cashback_and_bonus_on_aff_player_report'] = 'If enabled, Total Cashback and Total Bonus column in Player Report table should be shown.<ul><li>Affiliate page &gt; Report tab &gt; Player Report &gt; Total Cashback and Total Bonus column</li></ul>';
$lang['system_feature_desc_dashboard_count_direct_affiliate_player'] = "If enabled, Count Player portlet will only show number of players directly bound (or registered under) the Affiliate.<br><b>Note: Default is that Count Player portlet shows the total count of players bound directly and under its sub-affiliate</b><ul><li>Affiliate page &gt; Dashboard &gt; Count Player portlet</li></ul>";
$lang['system_feature_desc_hide_sub_affiliates_on_affiliate'] = 'if enabled, Subaffiliate / Subagent button will be hidden in navigation bar.<ul><li>Aff > Navigation bar</li></ul>';
$lang['system_feature_desc_enable_sortable_columns_affiliate_statistic'] = "If enabled, Affiliate Statistic Report will have sorting option in each column.<br>
<b>Note:</b> switch_old_aff_stats_report_to_new should be enabled too for this to work should be enabled first for this system feature to work as expected.
<ul><li>SBE > Affiliate tab > Affiliate Statistic sidebar</li></ul>";
$lang['system_feature_desc_masked_player_username_on_affiliate'] = 'If enabled, the player username will be masked (showing only the first four letter) under Player Username column of the Player Report and Games Report.<br><b>Note:</b>To verify this, enable both features show_player_info_on_affiliate <br>masked_player_username_on_affiliate<ul><li>Affiliate page &gt; Report tab &gt; Player Report &gt; Affiliate Username</li><li>Affiliate page &gt; Report tab &gt; Games Report &gt; Affiliate Username</li></ul>';
$lang['system_feature_desc_masked_realname_on_affiliate'] = "If enabled, the affiliate realname will be masked (showing only the first four letter) in the Sub Affiliate Tab.<br><b>Note: You need to activate the 'aff_show_real_name_on_reports' systems features located in: SBE > System Tab > System Features Sidebar > Affiliate tab.</b><ul><li>Affiliate Program &gt; Player Lists Tab</li></ul>";
$lang['system_feature_desc_match_wild_char_on_affiliate_domain'] = 'If enabled, will get tracking code via Affiliate Domain Name or Affiliate Tracking Link.<ul><li>Upon registration in affiliate</li></ul>';
$lang['system_feature_desc_enabled_active_affiliate_by_email'] = "Enabled active affiliate by email. <ul><li>After Registration in Affiliate</li></ul>";
$lang['system_feature_desc_disable_aff_gross_rev_formula_dep_minus_withdraw'] = "	If enabled, the Deposit - Withdrawal radio button in Gross Formula will be hidden.<ul><li>SBE &gt; Affiliate Tab &gt; Affiliate Commission Setting sidebar &gt; Gross Formula panel &gt; Deposit - Withdrawal radio button</li></ul>";
$lang['system_feature_desc_show_search_affiliate'] = 'If enabled, Affiliate should be shown as option under Group By search field, Affiliate form/input box with Include All Downlines Affiliate tick box should be shown in the search section and Affiliate column should be shown in the result table.<ul><li>SBE > Report > Player Report</li></ul>';
$lang['system_feature_desc_show_search_affiliate_tag'] = 'If enabled, Affiliate Tag search option should be shown in search section. <ul><li>SBE > Report > Player Report</li></ul>';

$lang['system_feature_desc_switch_to_affiliate_daily_earnings'] = "If enabled, Client's earnings will be switched to daily.<br><b>Note: </b>switch_to_affiliate_platform_earnings feature should be enabled along with this feature to make this work as expected. If not, no data will be shown.<ul type = '1'><li>SBE > Affiliate > Earnings Report</li><li>Affiliate page > Report > Earnings<br><b>Note: </b>affiliate_monthly_earnings feature should be enabled to show Earnings Report on Affiliate page.</li></ul>";

$lang['system_feature_desc_switch_to_affiliate_platform_earnings'] = "	If enabled, Client's earnings will be based on platform earnings.<br><b>Note: </b>switch_to_affiliate_daily_earnings feature should be enabled along with this feature to make this work as expected. If not, no data will be shown.<ul type = '1'><li>SBE > Affiliate > Earnings Report</li><li>Affiliate page > Report > Earnings<br><b>Note: </b>affiliate_monthly_earnings feature should be enabled to show Earnings Report on Affiliate page.</li></ul>";

$lang['system_feature_desc_enable_commission_from_subaffiliate'] = "If enabled, commission from subaffiliate will include in computation of generating of montly earnings.";
$lang['system_feature_desc_disabled_game_logs_in_aff'] = "If enabled, Game Report will be hidden in Report tab in Affiliate page.<ul><li>Affiliate page &gt; Report tab &gt; Games Report</li></ul>";
$lang['system_feature_desc_hide_affiliate'] = "If enabled, Affiliate Tab will be hidden in SBE. <ul><li>SBE &gt; Settings  Tab &gt; System Features</li></ul>";
$lang['system_feature_desc_hide_affiliate_message_login_form'] = 'If enabled, the notification message in affiliate login form after the successful registration of affiliate user should be hidden.<ul><li>Affiliate page &gt; First Login on page &gt; Notification message</li></ul>';
$lang['system_feature_desc_ignore_subaffiliates_with_negative_commission'] = "If enabled, upon calculation of earning report. sub affiliate with negative commission will equal to zero.<ul><li>SBE &gt; AFF &gt; earning report</li></ul>";
$lang['system_feature_desc_display_aff_beside_playername_daily_balance_report'] = "Display affiliate beside playername daily balance report. <ul><li>Report &gt Daily Player Balance Report</li></ul>";
$lang['system_feature_desc_display_aff_beside_playername_gamelogs'] = "Display affiliate username beside player username under Player Username column.<ul><li>Marketing &gt; Game Logs &gt; Game History &gt; Player Username column</li></ul>";
$lang['system_feature_desc_enable_rake_column_in_commission_details'] = "Display total rake column in commission details. <ul><li>SBE > Affiliate > Earnings Report : Click Commission Amount</li><li>SBE > Affiliate > Affiliate Information > Affiliate Earnings : Click Commission Amount</li></ul>";
$lang['system_feature_desc_switch_old_aff_stats_report_to_new'] = 'If enabled, he affiliate statistics report 2 will show. <ul><li>1. SBE > Affiliate > Affiliate Statistics 2 </li></ul>';
$lang['system_feature_desc_masked_affiliate_username_on_affiliate'] = 'If enabled, the affiliate username will be masked (showing only the first four letter) under Affiliate Username column of the Sub-affliates tab, Player Report and Games Report.<ul><li>Affiliate page &gt; Sub-affiliates tab &gt; Affiliate Username</li><li>Affiliate page &gt; Report tab &gt; Player Report &gt; Affiliate Username</li><li>Affiliate page &gt; Report tab &gt; Games Report &gt; Affiliate Username</li></ul>';
$lang['system_feature_desc_hide_affiliate_registration_link_in_login_form'] = 'If enabled, the registration should not be visible in Affiliate login form. <ul><li>Affiliate > Login</li></ul>';
$lang['system_feature_desc_enable_move_up_dashboard_statistic_in_affiliate_backoffice'] = 'If enabled, dashboard statistic in affiliate back office will move up. <ul><li>Affiliate > Dashboard</li></ul>';
$lang['system_feature_desc_aff_disable_logo_link'] = 'Disabled affiliate logo link. <ul><li>Affiliate > Upper left logo > Disabled the link </li></ul>';
$lang['system_feature_desc_hide_affiliate_language_dropdown'] = 'If enabled, the language dropdown in the header in the Affiliate will be hidden.';
$lang['system_feature_desc_only_compute_fees_from_bet_of_valid_game_platforms'] = 'If enabled, affiliate commission calculation under \'Commission Computation By Tier\' setting will not count those games that are not ticked under \'Select Game Platform\' checkboxes.<ul><li>SBE &gt; Affiliate tab &gt; Affiliate Commission Setting &gt; Commission Computation By Tier</li><li>Affiliate page &gt; Report dropdown menu &gt; Earnings dropdown option</li></ul>';
$lang['system_feature_desc_hide_aff_cashier_navbar'] = 'If enabled, affiliate cashier navbar will hide. <ul>Affiliate &gt; Navigation Bar</ul>';

/* -------------------------------------------------------------- */
/* KYC and Risk Score
/* -------------------------------------------------------------- */
$lang['system_feature_desc_enable_pep_gbg_api_authentication'] = "<b>Case #1:</b> If enabled, PEP Authentication button must be shown provided that 'show_pep_authentication' system feature is also enabled.
<ul><li>SBE > Player tab > Player List sidebar > Search Username > Player Information > PEP Authentication button > Generate New PEP Authentication</li></ul>
<b>Case #2:</b> If enabled, Automatic PEP Authentication through ID3 should be set up to identify Current PEP Status provided that 'show_pep_status' system feature is also enabled.
<ul><li>SBE > System tab > Risk Score sidebar > PEP (C6) tab</li></ul>
<b>Note:</b> Automatic PEP Authentication should be set according to ID3 Global scoring
<ul><li>SBE > Player tab > Player List sidebar > Search Username > Player Information > PEP status</li></ul>";
$lang['system_feature_desc_show_allowed_withdrawal_status'] = "If enabled, Allowed Withdrawal Status must be shown. By clicking the Allowed Withdrawal status, the KYC Level and Risk Level must be shown.<br>
<b>Note:</b> Both 'show_risk_score' and 'show_kyc_status' must be enabled to enable showing of this System Feature in the Status portion of Player Information page.
<ul><li>SBE > Player tab > Player List sidebar > Search Username > Player Information > Allowed Withdrawal Status</li><ul>";
$lang['system_feature_desc_show_kyc_status'] = "If enabled, KYC status should be shown. By clicking the KYC status, the current KYC Rate must be shown.<br>
<b>Note:</b> [Only if admin has a permission] Admin may opt to choose current Player KYC Status manually or generate automatic Player KYC Status based on the documents uploaded.
<ul><li>SBE > Player tab > Player List sidebar > Search Username > Player Information > PEP Authentication button > Generate New PEP Authentication</li></ul>
";
$lang['system_feature_desc_show_pep_authentication'] = "If enabled, PEP Authentication button must be shown.<br>
<b>Note:</b> Through PEP Authentication, Admin may request to automatically Generate New PEP Authentication for the specific player. This system feature will only work when 'enable_pep_gbg_api_authentication' system feature is also enabled.
<ul><li>SBE > Player tab > Player List sidebar > Search Username > Player Information > PEP Authentication button > Generate New PEP Authentication</li></ul>
";
$lang['system_feature_desc_show_pep_status'] = "<b>Case #1:</b> If enabled, PEP status should be shown provided that 'show_risk_score' system feature is also enabled. Note: [Default] When this system feature is enabled, admin may opt to choose and update current PEP status manually.
<ul><li>SBE > Player tab > Player List sidebar > Search Username > Player Information > PEP status</li></ul>
<b>Case #2:</b> If enabled, PEP status should be shown provided that 'show_risk_score' system feature is also enabled.<br>
<b>Note:</b> When 'enable_pep_gbg_api_authentication' is aslo ticked, automatic PEP Authentication through ID3 should be set up to identify Current PEP Status.
<ul><li>SBE > System tab > Risk Score sidebar > PEP (C6) tab</li></ul>
<b>Note:</b> Automatic PEP Authentication should be set according to ID3 Global scoring
<ul><li>SBE > Player tab > Player List sidebar > Search Username > Player Information > PEP status</li></ul>";
$lang['system_feature_desc_show_player_upload_proof_of_address'] = 'If enabled, Proof of Address Verification upload must be shown in the Security sidebar of the Player Center. <ul><li>Player Center &gt; Security sidebar</li></ul>';
$lang['system_feature_desc_show_player_upload_proof_of_deposit_withdrawal'] = 'If enabled, Proof of Deposit/Withdrawal upload must be shown in the Security sidebar of the Player Center. <ul><li>Player Center &gt; Security sidebar</li></ul>';
$lang['system_feature_desc_show_player_upload_proof_of_income'] = 'If enabled, Proof of Income Verification (Proof of Income upload) must be shown in the Security sidebar of the Player Center. <ul><li>Player Center &gt; Security sidebar</li></ul>';
$lang['system_feature_desc_show_player_upload_realname_verification'] = 'If enabled, Real Name Verification must be shown in the Security section of the Player Center. <ul><li>Player Center &gt; Security sidebar</li></ul>';
$lang['system_feature_desc_show_risk_score'] = "If enabled, Risk Score status must be shown. By clicking the Risk Score status, a summary of each category must be shown.
<ul><li>SBE > Player tab > Player List sidebar > Search Username > Player Information > Risk Score status</li></ul>";
$lang['system_feature_desc_show_upload_documents'] = 'If enabled, KYC Attachment button must be shown in the Actions portion of the Player Information or on the upper right corner of the Player KYC pop up window and must be shown in the Security sidebar of the Player Center as Real Name Verification upload.
<ol>
<li>SBE > Player tab > Player List sidebar > Search Username > Player Information > Actions</li>
<li>SBE > Player tab > Player List sidebar > Search Username > Player Information > KYC status > Player KYC pop up window</li>
<li>Player Center > Security sidebar</li>
</ol>';
$lang['system_feature_desc_enable_c6_acuris_api_authentication'] =
"<b>Case #1:</b> If enabled, C6 Authentication button must be shown provided that 'show_c6_authentication' system feature is also enabled.
<ul><li>SBE > Player tab > Player List sidebar > Search Username > Player Information > C6 Authentication button > Generate New C6 Authentication</li></ul>
<b>Case #2:</b> If enabled, Automatic C6 Authentication through Acuris should be set up to identify Current C6 Status provided that 'show_c6_status' system feature is also enabled.
<ul><li>SBE > System tab > Risk Score sidebar > C6 tab</li></ul>
<b>Note:</b> Automatic PEP Authentication should be set according to Acuris Authentication
<ul><li>SBE > Player tab > Player List sidebar > Search Username > Player Information > C6 status</li></ul>";
$lang['system_feature_desc_show_c6_authentication'] = "If enabled, C6 Authentication button must be shown.<br>
<b>Note:</b> Through C6 Authentication, Admin may request to automatically Generate New C6 Authentication for the specific player. This system feature will only work when 'enable_c6_acuris_api_authentication' system feature is also enabled.
<ul><li>SBE > Player tab > Player List sidebar > Search Username > Player Information > C6 Authentication button > Generate New C6 Authentication</li></ul>";
$lang['system_feature_desc_show_c6_status'] = "<b>Case #1:</b> If enabled, C6 status should be shown provided that 'show_risk_score' system feature is also enabled.<br>
<b>Note:</b> [Default] When this system feature is enabled, admin may opt to choose and update current C6 status manually.
<ul><li>SBE > Player tab > Player List sidebar > Search Username > Player Information > C6 status</li></ul>
<b>Case #2:</b> If enabled, C6 status should be shown provided that 'show_risk_score' system feature is also enabled.<br>
<b>Note:</b> When 'enable_c6_acuris_api_authentication' is also ticked, automatic C6 Authentication through Acuris should be set up to identify Current C6 Status.
<ul><li>SBE > System tab > Risk Score sidebar > C6 tab</li></ul>
<b>Note:</b> Automatic C6 Authentication should be set according to Acuris Authentication
<ul><li>SBE > Player tab > Player List sidebar > Search Username > Player Information > C6 status</li></ul>";
/* -------------------------------------------------------------- */
/* SMS
/* -------------------------------------------------------------- */
$lang['system_feature_desc_display_all_numbers_of_mobile'] = 'If enabled, all the mobile numbers must be shown.<ul><li>SBE &gt; Report tab &gt; SMS Verification Code sidebar &gt; Mobile Number Column</li></ul>';
$lang['system_feature_desc_enable_player_registered_send_msg'] = 'If enabled, an SMS will be sent to the player after successful registration.<br>Note: SMS Verification Code should be set as required in registration for this to work as expected.<ul><li>Player Center &gt; Registration</li></ul>';
$lang['system_feature_desc_enable_restrict_sms_send_num_in_player_center_phone_verification'] = 'If enabled, sending of SMS verification code will be limited. Once the limit is reached, the player will not receive SMS verification code during registration.<br>Note: Config for sms_restrict_send_num should be setup first for this to work as expected.<ul><li>Player Center &gt; Registration</li></ul>';
$lang['system_feature_desc_enable_sms_withdrawal_prompt_action_declined'] = 'If enabled, the player will receive an SMS once his withdrawal request is declined.<br>Note: Phone number must be verified.';
$lang['system_feature_desc_enable_sms_withdrawal_prompt_action_request'] = 'If enabled, the player will receive an SMS once his send a withdrawal request thru player center.<br>Note: Phone number must be verified.';
$lang['system_feature_desc_enable_sms_withdrawal_prompt_action_success'] = 'If enabled, the player will receive an SMS once his withdrawal request is approved.<br>Note: Phone number must be verified.';
$lang['system_feature_desc_enabled_send_sms_use_queue_server'] = 'If enabled, the SMS will be on queue and will not be sent to the player instantly.<br>Note: Phone number must be verified.';
$lang['system_feature_desc_send_sms_after_registration'] = 'If enabled, an SMS will be sent to the player after successful registration.<br><b>Note:</b> SMS Verification Code should be set as required in registration for this to work as expected.';
$lang['system_feature_desc_disable_captcha_before_sms_send'] = 'If enabled, the SMS will be no captcha verification';

/* -------------------------------------------------------------- */
/* Lottery Tab
/* -------------------------------------------------------------- */
$lang['system_feature_desc_enabled_lottery_agent_navigation'] = 'If enabled, Lottery Agent sidebar should appear in Player Center Dashboard.<ul><li>Player Center > Dashboard > Sidebar</li></ul>';
$lang['system_feature_desc_close_aff_and_agent'] = "1. If enabled, Affiliate tab should be hidden in Registration Settings.
<ul><li>SBE > Player Tab > Registration Settings</li></ul>
2. If enabled, Under Affiliate, Affiliate Tag, Include All Downline Affiliate, No affiliate only search fields should be hidden. Agent and Affiliate Columns in the Player List should also be hidden.
<ul><li>SBE > Player Tab > Player List > Search</li></ul>
3. If enabled, Affiliate search field will be hidden in Search and Player Level column should be hidden in Deposit Request List.
<ul><li>SBE > Payment Tab > Deposit List</li></ul>
4. If enabled, Add Cashback, No affiliate only (under Player Username search field), Include All Downlines Affiliate checkboxes and Belongs To Affiliate search field should be hidden in Search.
<ul><li>SBE > Payment Tab > Transactions</li></ul>
";
$lang['system_feature_desc_close_level_upgrade_downgrade'] = 'If enabled, Level Upgrade and player.levelDowngrade section should be hidden in Edit VIP Group Level Setting.<ul><li>SBE > Player Tab >  VIP Settings > Select Group Name > Edit Settings</li></ul>';

/* -------------------------------------------------------------- */
/* OTHER
/* -------------------------------------------------------------- */
$lang['system_feature_desc_add_close_status'] = "Add close status.";
$lang['system_feature_desc_add_notes_for_player'] = "Add notes for player.";
$lang['system_feature_desc_add_suspended_status'] = "If enabled, 'Suspended' button must be added as an action when Block button (under Actions) is clicked. <ul><li>SBE &gt; Player tab &gt; All Players sidebar &gt; Search Username &gt; Player Information &gt; Actions</li><li><b>Note:</b>To ensure that this will work, a reason of suspension should be selected. To add a reason, see below steps: <br>SBE &gt; Player tab &gt; Tag Management sidebar &gt; Add new tag icon &gt; Input details and tick on 'Use this tag for blocked players.'</li></ul>";
$lang['system_feature_desc_agency_count_bonus_and_cashback'] = "Agency count bonus and cashback.";
$lang['system_feature_desc_agency_tracking_code_numbers_only'] = "If enabled, tracking code input will accept number only. <ul><li>Aff &gt; Registration</li></ul>";
$lang['system_feature_desc_allow_duplicate_contact_number'] = "Allow duplicate contact number.";
$lang['system_feature_desc_allow_player_same_number'] = "If enabled, registration of an already registered number should be allowed. <ul><li>Player Center &gt; Account Information &gt; Contact Number or Player Center Registration page</li><ul>";
$lang['system_feature_desc_always_calc_before_pay_cashback'] = "If enabled. this will calculate the cashback before paying cashback.";
$lang['system_feature_desc_auto_add_reason_in_adjustment_main_wallet_to_player_notes'] = "If enabled, reason inputted in the Manual add balance or Manual subtract balance should be shown in the player notes. <ul><li>1. SBE &gt; Player tab &gt; All Players &gt; Search Username &gt; Player Information &gt; Account Info tab &gt; Adjust Balance button &gt; Manual add balance or Manual subtract balance</li><li>2. SBE &gt; Player tab &gt; All Players &gt; Search Username &gt; Player Information &gt; Actions &gt; Player Remarks button</li></ul>";
$lang['system_feature_desc_auto_fix_2_days_cashback'] = "Auto fix 2 days cashback.";
$lang['system_feature_desc_auto_pay_cashback_when_regenerate'] = "Auto pay cashback when regenerate.";
$lang['system_feature_desc_auto_refresh_balance_on_cashier'] = "If enabled, player's balance will be refreshed every 30 seconds on Player Center > Cashier Center.<ul><li>Player Center > Cashier Center</li></ul>";
$lang['system_feature_desc_batch_decline_promo'] = 'If enabled, "Batch Decline Promo" action button must be visible above the Promo Request List table if Pending Request portlet is clicked.<ul><li>SBE &gt; Marketing Tab &gt; Promo Request List sidebar &gt; Pending Request portlet</li></ul>';
$lang['system_feature_desc_batch_finish_promo'] = "If enabled, &quot;Batch Finish Promo&quot; action button must be visible above the Promo Request List table.<br><b>Note:</b> This is only accessible under <b>Released</b> portlet.<ul><li>SBE &gt; Marketing Tab &gt; Promo Request List sidebar &gt;  Released portlet</li></ul>";
$lang['system_feature_desc_batch_release_promo'] = "If enabled, &quot;Batch Release Promo&quot action button must be visible above the Promo Request List table.<br><b>Note:</b> This is only accessible under <b>Pending Requests</b> portlet.<ul><li>SBE &gt; Marketing Tab &gt; Promo Request List sidebar &gt;  Pending Request portlet</li></ul>";
$lang['system_feature_desc_bind_promorules_to_friend_referral'] = "If enabled, an automatic promo should be available/applicable on both the referrer and referred player. <ul><li>SBE &gt; Marketing tab &gt; Friend Referral Settings &gt; Bind Promo CMS &gt; Select Promo </li><b>Note:</b><br> i. Promo Title (SBE > Marketing tab > Promo Manager) should be the same with the Promo Name (SBE > Marketing tab > Promo Rules Settings)<br>ii.Promo Name (SBE > Marketing tab > Promo Rules Settings) to be selected should be set to <ul><li>Release bonus automatically</li><li>Non-Deposit Promo</li><li>Registered Account (By joining in this promo, player may receive bonus by registration in the website.)</ul></li><li>Player Center &gt; Promotions sidebar &gt; Promotions</li></ul>";
$lang['system_feature_desc_bonus_games__enable_bonus_game_settings'] = 'If enabled, Bonus Game Settings must be visible on the side bar. <ul><li>SBE &gt; Marketing tab &gt; Bonus Game Settings</li></ul>';
$lang['system_feature_desc_bonus_games__support_bonus_game_in_promo_rules_settings'] = 'If enabled, Bonus Game as a bonus release type must be visible under 3. *Bonus Release. <br><b>Note:</b> There should be at least 1 Bonus Game created under Bonus Game settings to enable the selection of the bonus release type: Bonus Game. <ul><li>SBE &gt; Marketing tab &gt; Promo Rules Settings &gt; Add New Promo Rule &gt; Create New Promo Rule &gt; 3. *Bonus Release</li></ul>';
$lang['system_feature_desc_cashier_multiple_refresh_btn'] = 'If enabled, a refresh button under each of the wallet balance numbers must be visible. <ul><li>Player Center &gt; Cashier Center </li></ul>';
$lang['system_feature_desc_check_disable_cashback_by_promotion'] = "Check disable cashback by promotion.";
$lang['system_feature_desc_check_player_session_timeout'] = "Check player session timeout.";
$lang['system_feature_desc_column_visibility_report'] = "If enabled, changes on column visibilities on data tables will be saved. <ul><li>SBE &gt; Almost every table/listings &gt; Column Visibility Action</li></ul>";
$lang['system_feature_desc_contact_customer_service_for_forgot_password'] = "If enabled, steps for retrieving password by security question will not be visible/available except for contacting the customer service. <ul><li>Player Center &gt Forgot Password &gt Find Password by Security Section</li></ul>";
$lang['system_feature_desc_create_ag_demo'] = "Create AG demo";
$lang['system_feature_desc_create_agin_demo'] = "If enabled, change AGIN to Demo Account button should be shown in Game Info tab in Player Information <ul><li>SBE &gt; Player Tab &gt; Player list &gt; Player Information (select player to view ) &gt; Game Info tab</li></ul>";
$lang['system_feature_desc_create_sale_order_after_player_confirm'] = "Create sale order after player confirm.";
$lang['system_feature_desc_declined_forever_promotion'] = "If enabled, &apos;Declined Forever&apos; portlet and &apos;Decline Forever&apos; button under Action column of Declined Promo List  should be shown. This button is used to permanently decline a declined promotion.<br><b>Note:</b> This is related to Allow to decline forever promotion permission.<ul><li>1. SBE > Marketing Tab > Promo Request List > Declined Promo List</li><li>2. SBE > Marketing Tab > Declined Forever Portlet</li></ul>";
$lang['system_feature_desc_declined_withdrawal_add_transaction'] = "Declined withdrawal add transaction. <ul><li>SBE &gt; Payment tab &gt; Withdrawal List &gt; Withdrawal Request List &gt; Details of Request</li></ul>";
$lang['system_feature_desc_default_search_all_players'] = "If enabled, by default, all players will be listed on the Player List without any query. <ul><li>SBE &gt; Player Tab &gt; All Players &gt; Player List</li></ul>";
$lang['system_feature_desc_default_search_all_players'] = "If enabled, by default, all players will be listed on the Player List without any query. <ul><li>SBE &gt; Player Tab &gt; All Players &gt; Player List</li></ul>";
$lang['system_feature_desc_deposit_withdraw_transfer_list_on_player_info'] = "If enabled, tabs for <b>'Deposit List'</b>, <b>'Withdraw List'</b>, and <b>'Transfer List'</b> will be visible from the list of tabs under Player's Logs on Player Information.<br><ul><li>SBE > Player Tab > Player List > Select a Player to view Player Information > Player's Logs</li></ul>";
$lang['system_feature_desc_disable_action_buttons_in_player_list_table'] = "If enabled, action buttons will be disabled in Player List Table. <ul><li>SBE &gt; Player Tab &gt; All Players &gt; Player List</li></ul>";
$lang['system_feature_desc_disable_frequently_use_country_in_registration']="If enabled, frequently used countries will not be shown on top of the list.<ul><li>Player Center &gt; Free Registration &gt; Select Country<br><b>Note:</b>Resident Country should be enabled under registration settings on SBE for this feature to work as expected.</li></ul>";
$lang['system_feature_desc_disable_mobile_access_comp_link'] = 'If enabled, footer of mobile website will have link \'Access computer version\'.<br>In Mobile Version website footer.<br><b>Note:</b>enable_mobile_copyright_footer system feature should be enabled for this to work as expected.<ul><li>Player Center (Mobile version) &gt; Footer</li><li>Player Center (Mobile version) &gt; Account Information &gt; Footer</li><li>Player Center (Mobile version) &gt; Promo Request List &gt; Footer</li></ul>';
$lang['system_feature_desc_disable_player_change_withdraw_password'] = "If enabled, players won't be able to change their withdrawal passwords without contacting the contact support.<ul><li>Player Center &gt; Security Tab &gt; Withdrawal Password</li></ul>";
$lang['system_feature_desc_disabled_auto_create_game_account_on_registration'] = "Disabled auto create game account on registration.";
$lang['system_feature_desc_disabled_login_trial_agin_game'] = "If enabled, trial game link in login box will be hidden. <br><b>Note:</b> Tried enabled this features. the trial game link still appeared in login box.<ul><li>Player Center &gt; Login box.</li></ul>";
$lang['system_feature_desc_display_earning_reports_schedule'] = "If enabled, earning report schedule button will appeared in earning list. <ul><li>SBE &gt; Affiliate Tab &gt; Earnings Report</li></ul>";
$lang['system_feature_desc_display_exclude_player_tag'] = 'Display the search box to exclude player tag in summary report. <ul><li>SBE &gt; Report &gt; Summary Report</li></ul>';
$lang['system_feature_desc_display_newsletter_subscribe_btn'] = "If enabled, Subscribed/Unsubscribed button will be displayed in Newsletter Subscription portion in Signup Info tab. <ul><li>SBE &gt; Player Tab &gt; Player list &gt; Player Information (select player to view ) &gt; Signup Info tab</li></ul>";
$lang['system_feature_desc_display_player_bets_per_game'] = "If enabled and bet count is not 0; Table bets per game will display in Game Report. <ul><li>SBE &gt; Report &gt; Games Report</li></ul>";
$lang['system_feature_desc_display_referral_code_in_player_details'] = "Display referral code in player details.";
$lang['system_feature_desc_display_vip_upgrade_schedule_in_player'] = "If enabled, VIP upgrade schedule will be shown beside the Level Name label.
<ul><li>Player Center > Dashboard</li></ul>
<b>Note:</b>
<ul><li>hidden_vip_icon_LevelName and hidden_vip_status_ExpBar under System Features > Player center must be disabled</li>
<li>Player's VIP level must have an upgrade setup</li></ul>";
$lang['system_feature_desc_donot_show_registration_verify_email'] = "If enabled, verifying email will be disabled. <ul><li>After registration in player center.</li></ul>";
$lang['system_feature_desc_enable_adjustment_category'] = "If enabled,
<ol>
	<li>Adjustment Category sidebar should be shown.</li>
		<ul><li>SBE > System Tab > Adjustment Category sidebar</li></ul>
	<li>Adjustment Category dropdown options should be shown.
		<ul><li>A. SBE > Player Tab > Player List > Search Player > Player Information > Adjust Balance </li>
			i. Manual subtract balance<br>
			ii. Manual add balance<br>
			iii. Cashback</ul>
		<ul><li>B. SBE > Marketing Tab > Batch Balance Adjustment</li>
		i. Manual subtract balance <br>
		ii. Manual add balance</ul>
	</li>
	<li>Adjustment Category column should be shown in Transactions List.</li>
		<ul><li>SBE > Payment Tab > Transactions List</li></ul>
</ol>";
$lang['system_feature_desc_enable_batch_approve_and_decline'] =  "If enabled, Approve Selected, Decline Selected buttons and checkbox (under Action column) will be shown in Deposit Request List.
<ul><li>1. SBE > Payment tab > Deposit List sidebar > Pending Requests portlet > Deposit Request List > Checkbox under Action Column
</li><li>2. SBE > Payment tab > Deposit List sidebar > Pending Requests portlet > Deposit Request List > Approve Selected / Decline Selected buttons</li></ul>";
$lang['system_feature_desc_enable_cashback_after_withdrawal_deposit'] = "Enable cashback after withdrawal deposit";
$lang['system_feature_desc_enable_cashback_weekly_period']  = "If enabled, the Weekly radio button will displayed in Cashback Period Settings Form in Cashback Period selections.<ul><li>SBE &gt; Marketing Tab &gt; Cashback Period Settings &gt; Cashback Period Setting Form</li></ul>";
$lang['system_feature_desc_enable_choose_dialing_code_in_register'] = "Enable choose dialing code in register.";
$lang['system_feature_desc_enable_clear_withdrawal_condition_on_success_withdraw'] = "Enable clear withdrawal condition on success withdraw.";
$lang['system_feature_desc_enable_contact_us'] = "If enabled, sending inquiries with Contact Us will work.";
$lang['system_feature_desc_enable_custom_script_mobile'] = "Enable custom script mobile.";
$lang['system_feature_desc_enable_default_logic_transaction_period'] = "If enabled, date range / results for using Transaction Period filter will be limited to Request Time only. <ul><li>SBE > Payment Tab > Deposit / Withdrawal list</li></ul>";
$lang['system_feature_desc_enable_daterangepicker_last30days_item'] = "If enabled,In SBE all date range picker will add a last 30 days item. <ul><li>SBE > Payment Tab > All date range picker</li></ul>";
$lang['system_feature_desc_enable_dynamic_footer'] = "If enabled, the Footer Template must be accessible in the side bar under Theme Management module. <ul><li>SBE &gt; Themes Tab &gt; Footer Template</li></ul>";
$lang['system_feature_desc_enable_dynamic_header'] = "If enabled, the Header Template must be accessible in the side bar under Theme Management module. <ul><li>SBE &gt; Themes Tab &gt; Header Template</li></ul>";
$lang['system_feature_desc_enable_dynamic_javascript'] =  "If enabled, the Javascript Template must be accessible in the side bar under Theme Management module. <ul><li>SBE &gt; Themes Tab &gt; Javascript Template</li></ul>";
$lang['system_feature_desc_enable_dynamic_mobile_login'] = "If enabled, Mobile Login Template must be accessible in the side bar under Theme Management module. This also enables the use of mobile login template in the Player Center. <ul><li>SBE &gt; Themes Tab &gt; Mobile Login Template</li></ul>";
$lang['system_feature_desc_enable_dynamic_registration'] = "If enabled, Registration Template must be accessible in the side bar under Theme Management module. <ul><li>SBE &gt; Themes Tab &gt; Registration Template</li></ul>";
$lang['system_feature_desc_enable_dynamic_theme_host_template'] = "If enabled. Theme Host Templete will appeared in Side bar of Theme Management Tab.<ul><li>SBE &gt; Theme Tab</li></ul>";
$lang['system_feature_desc_enable_edit_upload_referral_detail'] = "1. If enabled, the friend referral settings page can set referral details.<ul><li>SBE &gt; Marketing &gt; Friend Referral Settings</li></ul>2. If there are settings, the referral details will be displayed in<ul><li>Player center &gt; Refer a Friend</li></ul>";
$lang['system_feature_desc_enable_friend_referral_cashback'] = " If enabled, Cashback Rate (%) field should be displayed in Referrer's Bonus and Cashback Type should be shown in the search field and Cashback Type and Referred Player for Cashback columns should be shown in the results table. <ul><li>SBE &gt; Marketing Tab &gt; Friend Referral Settings</li><li>SBE &gt; Report Tab &gt; Cashback Report</li></ul>";

$lang['system_feature_desc_switch_ag_round_and_notes'] = 'If enabled, Round No and Note column position will be switched.<br><ul type = "1"><li>SBE > Maketing > Game Logs > Game History Panel</li></ul>';

$lang['system_feaure_desc_show_bet_time_column'] = 'if enabled. the Bet Time column will appeared it Game History Table.<ul><li>SBE > Marketing Tab > Game Logs</li></ul>';
$lang['system_feature_desc_enable_gamelogs_v2'] = 'If enabled, additional search fileds will be displayed.<br>Additional Search Fields:<ul><li>Transaction ID</li><li>Bet Type (changed dropdown: Cash, Credit)</li><li>Debit - (Player Loss) &gt;=</li><li>Debit - (Player Loss) &lt;=</li><li>Credit + (Player Win) &gt;=</li><li>Credit + (Player Win) &lt;=</li><li>Player Type (dropdown: Real, Test)</li><li>Data for API</li></ul><ul><br><b>Note:</b> Game Type search option is hidden if this feature is enabled. Data are manually inputted in this version via CSV.<li>SBE &gt; Marketing Tab &gt; Game Logs &gt; Search Field</li></ul>';
$lang['system_feature_desc_enable_mobile_acct_login'] = 'if enabled. Mobile Number login will appeared in Login form.<ul><li>Player Center > Login (Mobile version)</li></ul>';
$lang['system_feature_desc_enable_mobile_copyright_footer'] = 'If enabled, the copyright footer will be visible on mobile devices.<ul><li>Mobile Player Center > Homepage</li><li>Mobile Player Center > My Documents</li><li>Mobile Player Center > Account Information</li></ul>';

$lang['system_feature_desc_enable_multi_lang_promo_manager'] = 'If enabled, Select default language drop-down options and Setup Multi Language button should be shown when adding new / editing promo.<ul><li>SBE > Marketing Tab > Promo Manager > Add New / Edit Promo</li></ul>';

$lang['system_feature_desc_enable_player_center_mobile_live_chat'] = 'If enabled, the Live Setting tab will be visible under Syste, Management&apos;s side bar. This will also enable access to mobile player center&apos;s live chat.<ul><li>SBE > System Tab> Live Setting</li></ul>';
$lang['system_feature_desc_enable_player_center_mobile_main_menu_live_chat'] = 'If enabled, the Live Chat icon will be visible in Player Center Mobile Main Menu.<ul><li>Player Center (Mobile)</li></ul>';

$lang['system_feature_desc_enable_registered_show_success_popup'] = 'If Enabled, successful registration message will be hidden after a registration
<ul><li>Player Registration</li></ul>';

$lang['system_feature_desc_enable_remove_country_in_list_if_blocked_country_rules'] = 'If enabled, the country will not be displayed in registration page in Player Center if blocked by the admin.<br><ol type = "1"><li>SBSBE > System Tab > Website IP Rules</li><li>Player Center > Registration Page > Country</li></ol>';

$lang['system_feature_desc_enable_shop'] = 'If enabled, both Shopping Manager and Shopping Points Request List must be accessible in the side bar under Marketing Management module. This also enables access to the shop in the Player Center.<ul><li>SBE > Marketing tab > Shopping Manager</li><li>SBE > Marketing tab > Shopping Points Request List</li><li>Player Center > Sidebar > Shop</li></ul>';
$lang['system_feature_desc_enable_super_report'] = 'If enabled, Super Report tab will be accessible upon login to the SBE (only if the user has the right to access the module).<ul><li>SBE > Super Report Tab</li></ul>';
$lang['system_feature_desc_enable_upload_depostit_slip']  = "Enable upload depostit slip";
$lang['system_feature_desc_enable_withdrawal_declined_category'] = 'If enabled, Withdrawal Declined Category sidebar should be shown under System Tab.<ul><li>SBE &gt; System Tab &gt; Withdrawal Declined Category sidebar</li></ul>';

$lang['system_feature_desc_enabled_batch_upload_player'] = 'If enabled, "Upload Batch Player" will appear in Side bar of Player Tab.<ul><li>SBE > Player Tab > Side Bar</li></ul>';

$lang['system_feature_desc_enabled_cashback_period_in_vip'] = 'If enabled, Cashback Period box will appeared in Edit VIP Group Level Settings form.<ul><li>SBE > Player Tab >  VIP Settings > Select Group Name > Edit Settings</li></ul>';
$lang['system_feature_desc_enabled_change_lang_tutorial'] = 'If enabled, the language that will be used for new player tutorials will be set by the chosen language upon registration. (Only if the selected language was either English or Chinese)<ul><li></li></ul>';
$lang['system_feature_desc_enabled_check_frondend_block_status'] = "If enabled, it will check the the country rules, IP validation whether IP is included in the block lists. If IP is included user will be redirected to block page site when accessing the player center.
<ul><li>System > Website IP Rules > IP Rules > Add block list IP</li></ul>";
$lang['system_feature_desc_enabled_edit_affiliate_bank_account'] = 'If enabled, Action Column will appear in Bank Information Table.<br><b>Note: <i>edit_affiliate_bank_account</i> permission should be enabled first to use this feature.<ul><li>SBE &gt; Affiliate Tab &gt; Affiliate List &gt; Affiliate Informations</li></ul>';
$lang['system_feature_desc_enabled_favorites_and_rencently_played_games'] = 'If enabled, players&apos; favorite and recently played games will be available in the Player Center.<ul><li>Player Center > Sidebar > Favorite Games</li></ul>';
$lang['system_feature_desc_enabled_feedback_on_admin'] = 'If enabled. error handling will appeared in the lower right of the screen. I didn&apos;t know how to test it. <ul><li>Player Center > Sidebar > Favorite Games</li></ul>';
$lang['system_feature_desc_enabled_freespin'] ='If enabled, slot promo game button should be visible on the Edit Promo Rule form.<ul><li>SBE > Marketing Tab > Promo Rules Settings > Edit a Promo Rule > Slot game promo</li></ul>';
$lang['system_feature_desc_enabled_login_password_on_withdrawal'] = "Enabled login password on withdrawal.";
$lang['system_feature_desc_enabled_maintaining_mode'] = "Enabled maintaining mode.";

$lang['system_feature_desc_enabled_oneworks_game_report'] = 'If enabled, SBE user should be able to generate Oneworks Game Report.<br><ul type = "1"><li>SBE > Report > Oneworks Game Report</li></ul>';

$lang['system_feature_desc_enabled_player_center_preloader'] = 'if enabled. preloader image will show eachtime when website is after loading / refresh.<ul><li>Player center</li></ul>';
$lang['system_feature_desc_enabled_player_center_spinner_loader'] = 'if enabled. spinner animation will show eachtime when website is after loading / refresh.<ul><li>Player center</li></ul>';
$lang['system_feature_desc_enabled_player_referral_tab'] = 'If enabled, Refer a Friend sidebar should appear on Player Center.<ul><li>Player Center &gt; Dashboard</li></ul>';
$lang['system_feature_desc_enabled_player_registration_restrict_min_length_on_first_name_field'] = 'If enabled, Player registration will restrict min length on first name field on Player Center.<ul><li>Player Center &gt; Registration</li></ul>';
$lang['system_feature_desc_enabled_display_withdrawal_password_notification'] = 'If enabled, Withdrawal Password Note will show in Change Withdrawal Password Modal. <ul><li>Player Center > Security Tab > Withdrawal Password > Change Password.</li></ul>';
$lang['system_feature_desc_disabled_withdraw_condition_share_betting_amount'] = 'If enabled, withdrawal condition will not share betting amount. Once this is disabled and there is an existing withdrawal condition, bet amount should automatically sync and shared with other withdawal conditions. <ul><li>SBE &gt; All Players &gt; Search Username &gt; Player Information &gt; Withdraw Condition &gt; Betting Amount.</li></ul>';
$lang['system_feature_desc_enable_player_report_2'] = 'If enabled, Player Report 2 will display.<ul><li>SBE &gt; Report &gt; Player Report 2</li></ul>';
$lang['system_feature_desc_enabled_realtime_cashback'] ='If enabled. <b>Your cashback request is in progress. Please wait, thank you.</b> message will appear. when requesting cashback.<ul><li>Upon requesting cashback.</li></ul>';
$lang['system_feature_desc_enabled_refresh_message_on_player'] = "Enabled  refresh message on player.";
$lang['system_feature_desc_enabled_vipsetting_birthday_bonus'] = 'if enabled, Birthday Bonus will appeared in Edit VIP Group Level Settings under Bonus Mode.<ul><li>SBE > Player Tab >  VIP Settings > Select Group Name > Edit Settings</li></ul>';
$lang['system_feature_desc_enabled_weekly_cashback'] = "If enabled, the Weekly radio button should be displayed in Cashback Period Settings Form in Cashback Period selections and Cashback should be calculated weekly in CRON.<ul><li>SBE &gt; Marketing Tab &gt; Cashback Period Settings &gt; Cashback Period Settings Form</li></ul>";
$lang['system_feature_desc_enabled_whitelist_duplicate_record'] = "Enabled whitelist duplicate record.";

$lang['system_feature_desc_exclude_ips_in_duplicate_account'] = 'If enabled, duplicate accounts with IPs included in the list_of_ips_to_be_excluded_in_duplicate_account configuration will be excluded from the reports.<br>Configurations are set by developers depending on the client.
<br><ul type = "1"><li>SBE > Report Tab> Duplicate Account Report</li></ul>';

$lang['system_feature_desc_export_excel_on_queue'] = 'If enabled, admin should be able to export report with huge data for exporting. Report export should be on queue and should be seen on a separate tab.<ul><li>SBE > Report > Export CSV button</li></ul>';
$lang['system_feature_desc_exporting_on_queue'] = "Exporting on queue.";
$lang['system_feature_desc_force_disable_all_promotion_dropdown_on_player_center'] = "Force disable all promotion dropdown on player center.";
$lang['system_feature_desc_force_refresh_cache'] = 'If enabled, the system will always refresh its cache upon every load.<ul><li></li></ul>';
$lang['system_feature_desc_generate_player_token_login'] = 'If enabled, this will allow retrieval/generation of player token upon login.<ul><li></li></ul>';
$lang['system_feature_desc_hide_contact_on_player_center'] = "Hide contact on player center.";
$lang['system_feature_desc_hide_dates_filter_in_promo_history'] = 'If enabled, the date filter for promo history will be hidden and will not work. <ul><li>SBE &gt; Player tab &gt; Player&apos;s Logs &gt; Promo History</li></ul>';
$lang['system_feature_desc_hide_deposit_and_withdraw_on_aff_player_report'] = "If enabled, Total Deposit Column and Total Withdraw Column will be hidden in Player Report Table.<br />
Note: affiliate_player_report system feature should be enabled first for this to work as expected.<br />
	<ul><li>Affiliate page > Report > Player Report</li></ul>";
$lang['system_feature_desc_hide_retype_email_field_on_registration'] = 'If enabled, the field for retyping the email address will be displayed on the registration form.<br>Note: The email address field should also be visible. This can be changed via SBE > System Tab > Registration Settings > Tick the <b>Email</b> to <b>Show</b><ul><li>Player Center > Registration Page</li></ul>';
$lang['system_feature_desc_hide_second_deposit_in_summary_report'] = 'If enabled, the column <b>Second Deposit</b> will be hidden from the table.<ul><li>SBE > Report Tab > Summary Report</li></ul>';
$lang['system_feature_desc_hide_taggedlist_email_column'] = 'If enabled, the email address column will be hidden from the list of tagged players.<ul><li>SBE > Player Tab > Tagged Players</li></ul>';
$lang['system_feature_desc_ignore_notification_permission'] = 'On newer version of Chrome, notification and sound may fail to work on HTTP sites due to permission. Turning this option ON will ignore the permission check and still play sound.';
$lang['system_feature_desc_include_company_name_in_title'] = 'If enabled, the company name will be visible on the Title bar of all pages.<ul><li>Access SBE > Browser&apos;s Title Bar</li></ul>';
$lang['system_feature_desc_iovation_fraud_prevention'] = "Iovation fraud prevention.";
$lang['system_feature_desc_kickout_game_when_kickout_player'] = "If enabled, the player will be logged out of the game he's currently in if he was kicked out by the admin.<br>
<b>Note:</b> Not all games support this feature. Some games do not have the feature to be logged out when kicked out. Given below are the list of the games which currently do not support the logout feature:<br>
<ul>
*ag, agency, betmaster, bs, dg, ebet, ebet_ag, ebet_dt, evolution gaming, extreme live gaming, finance, fishinggame, sbtech, genesism4, ggpoker, gsag, gsmg, hg, hrcc, ibc, ig, isb, isb_seamless, lapis, lb, ld_casino, ld_lottery, mg, mg_quickfire, mwg, og, one88, onesgame, oneworks, png, rtg, rwb, sbtech, seven77, spadegaming, ttg, ultraplay, whitelabel, win9777, xhtdlottery, xhtdlottery_cod, yungu, lottery_t1, t1_common
</ul>
<b>Note:</b> Make sure that the Singe Player Session is toggled on. This can be found under
SBE > System Tab > System Settings:
<ol>
<li>Player Center > Log in an account > Play a game</li>
<li>SBE > Player Tab > Player List > Select a player > Sign up Information > Select the 'Kick out' action button.</li>
</ol>
<b>Expected Result:</b> The player currently logged in should be logged out immediately both from the game and the player center
";
$lang['system_feature_desc_link_account_in_duplicate_account_list'] = 'If enabled,  Link Account column and Link Account button should be shown in Duplicate Account List<ul><li>SBE > Player Tab > All Players > Search Player > Player Information > Player&apos;s Logs > Duplicate Account List</li></ul>';
$lang['system_feature_desc_linked_account'] = "If enabled, Linked Account sidebar under Player tab and Linked Account Details under Player's Log tab should be shown in Player Information.
<ol>
<li>SBE > Player tab > Linked Account sidebar</li>
<li>SBE > Player Tab > Player List > Search Player > Player Information > Player's Logs > Duplicate Account List > Linked Account button</li></ol>";
$lang['system_feature_desc_mobile_show_vip_referralcode'] = "If enabled, VIP group name and level name will be displayed on mobile payment center.
<ul><li>Mobile Payment Center > Dashboard</li></ul>";
$lang['system_feature_desc_mobile_winlost_column'] = 'mobile win/lost use single column';
$lang['system_feature_desc_notification_local_bank'] = "Notification local bank.";
$lang['system_feature_desc_notification_messages'] = "Notification messages.";
$lang['system_feature_desc_notification_new_player'] = "Notification new player.";
$lang['system_feature_desc_notification_promo'] = "Notification promo.";
$lang['system_feature_desc_notification_thirdparty'] = "Notification thirdparty.";
$lang['system_feature_desc_notification_thirdparty_settled_on_top_bar'] = "Notification thirdparty settled on top bar.";
$lang['system_feature_desc_notification_withdraw'] = "Notification withdraw.";
$lang['system_feature_desc_notify_cashback_request'] = "Notify cashback request.";
$lang['system_feature_desc_only_6_hours_game_records'] = "Only 6 hours game records";

$lang['system_feature_desc_only_admin_modified_role'] = "If enabled, only an admin/superadmin can modify or edit roles.<br><ul type = '1'><li>SBE > System Tab > View Roles > Edit a Role</li></ul>";

$lang['system_feature_desc_only_allow_one_for_adminuser'] = "If enabled, when the admin try to log in where there are currently other admin logged in (beside superadmin), the other admin will be forced kickout in the SBE.<br><ul type = '1'><li>SBE > After logged in</li></ul>";

$lang['system_feature_desc_only_manually_add_active_promotion'] = 'If enabled, only the available (active) promos will be available for manually adding balances / bonuses.<ol><li>SBE > Marketing Tab > Batch Balance Adjustment > Manual Add Balance / Manual Subtract Balance / Batch Add Bonus / Add Cashback </li><li>SBE > Player Tab > VIP Setting > Select a vip group from the list to edit > Downgrade Bonus</li></ol>';
$lang['system_feature_desc_only_use_dropdown_list_for_notification'] = "The feature is still being used but apparently, the changes do not apply to any page. <ul><li>SBE &gt; Notification Bar</li></ul>";
$lang['system_feature_desc_player_center_hide_time_in_remark'] = 'If enabled, time will not be displayed from the remarks on player&apos;s account history.<ul> <li>Player Center &gt; Account History</li></ul>';
$lang['system_feature_desc_player_deposit_reference_number'] = "Player deposit reference number.";
$lang['system_feature_desc_popup_window_on_player_center_for_mobile'] = "Popup window on player center for mobile.";
$lang['system_feature_desc_promorules.allowed_players'] = "Promorules allowed players";
$lang['system_feature_desc_refresh_player_balance_before_pay_cashback'] = 'If enabled, refresh the balance of player before cashback.';
$lang['system_feature_desc_register_page_show_login_link'] = 'If enabled, a direct link for login should be visible from the registration page. <br><b>Note:</b> This only applies for Registration Templates <b>"Recommended"</b> and <b>"template_4"</b>. You can change Registration themes via <b>SBE &gt; Themes tab &gt; Registration Template. </b> If the Registration Template tab is not visible, please check if the feature <b>enable_dynamic_registration</b> under Others tab of System Features is enabled. <ul><li>Player Center &gt; Registration Form</li></ul>';
$lang['system_feature_desc_responsible_gaming'] = "If enabled, Responsible Gaming tab will appeared in Player Information.
<ul>
<li>SBE > Player Tab > Player List > View user information (click any player)</li>
<li>SBE > Player Tab > Responsible Gaming Settings</li>
<li>SBE > Report Tab > Responsible Gaming Report</li>
<li>Player Center > Responsible Gaming Tab</li></ul>";
$lang['system_feature_desc_disable_responsible_gaming_auto_approve'] = 'If disabled, responsible Gaming section will disable auto approve responsible gaming\'s request.<br><b>Note:</b>Request Status will be \'Requesting\'<ul><li>SBE &gt; Player Tab &gt; All Players &gt; Select a Player to view Player Informations &gt; Responsible Gaming panel</li></ul>';

$lang['system_feature_desc_hide_permanent_self_exclusion_cancel_button'] = "If enabled, User Information will hide permanent self exclusion's Cancel button<br><ul type = '1'><li>SBE > Player tab > Search Player > Player Information > Responsible Gaming panel > Self Exclusion, Permanent row</li></ul>";

$lang['system_feature_desc_send_message'] = 'If enabled, Send Message side bar should be available under CS tab.<ul><li>SBE > CS Tab > Send Message</li></ul>';
$lang['set_enabled_permission_all'] = "Permission all";
$lang['system_feature_desc_show_admin_support_live_chat'] = 'If enabled, Livechat setting must be visible and accessible. It will also enable access to admin support live chat.</br>Note: Access to Livechat setting is not applicable to xpj/macaopj branch. Also, Livechat setting may still be visible on the sidebar even after disabling the feature if the features enable_player_center_live_chat and enable_player_center_mobile_live_chat were both enabled.<ul><li>"1. SBE > System Tab > Livechat Setting </br> 2. SBE > The floating live chat box must be visible at the bottom right corner of the page."</li></ul>';
$lang['system_feature_desc_show_bet_detail_on_game_logs'] = "Show bet detail on game logs.";
$lang['system_feature_desc_show_bet_time_column'] = 'If enabled, the Bet Time column should appear in Game History Table.<ul><li>SBE &gt; Marketing Tab &gt; Game Logs</li></ul>';
$lang['system_feature_desc_show_game_history_of_deleted_player'] = "Show game history of deleted player.";
$lang['system_feature_desc_show_id_card_number_in_list'] = '<ul><li>If enabled, the ID Card Number column must not be visible from the Player List table.</li><li>If enabled, the ID Card Number must appear under Personal Information of the selected player.</li><li>If enabled, the ID Card Number must appear on the Personal Information form of the selected player.</li><li>SBE > Player Tab &gt; All Players </li><li>SBE &gt; Player Tab &gt; All Players &gt; Select a player &gt; Personal Information </li><li>SBE &gt; Player Tab &gt; All Players &gt; Select a player &gt; Personal Information &gt; Edit Personal Information</li></ul>';
$lang['system_feature_desc_show_new_games_on_top_bar'] = 'If enabled, the total count of "New Games" must be displayed on the Notification Bar.<ul><li>SBE > Notification Bar > New Games</li></ul>';
$lang['system_feature_desc_show_player_address_in_list'] = 'If enabled, the Address 1, 2, and 3 columns must appear from the Player List table.<br>If enabled, the Address 2 and Address 3 must appear under Personal Information of the selected player.<br> If enabled, the Address 2 and Address 3 must appear on the Personal Information form of the selected player.<ul><li> SBE > Player Tab > All Players </li><li> SBE > Player Tab > All Players > Select a player > Personal Information </li><li> SBE > Player Tab > All Players > Select a player > Personal Information > Edit Personal Information</li></ul>';
$lang['system_feature_desc_show_sports_game_columns_in_game_logs'] = 'Show the Sports Game columns in Game History : Bet Type, Match Type, Match Details, Handicap and Odds. <ul><li>SBE &gt; Reports &gt; Game Logs &gt; Game History</li></ul>';
$lang['system_feature_desc_show_player_deposit_withdrawal_achieve_threshold'] = '1. If enabled, the D/W Achieve Threshold button will be shown on Player Information Action panel. Deposit and withdrawal threshold can be set per player and when player‘s total deposit or withdrawal amount (since registration) exceeds the set threshold, the SBE user will receive a one-time notification.<ul><li>SBE &gt; Player Tab &gt; All Players &gt; Select a player &gt; Personal</li></ul>2. If enabled, SBE user can set a notification for D/W Achieve Threshold.<ul><li>SBE &gt; System &gt; Notification Management &gt; Settings</li></ul>3. If enabled, D/W Achieve Threshold will be shown under notification drop-down. Note: Notification Setting for D/W Achieve Threshold should be setup first for this to work.<ul><li>SBE &gt; Notification drop-down</li></ul>4. If enabled, D/W Achieve Threshold Report will be shown.<ul><li>SBE &gt; Report &gt; D/W Achieve Threshold Report</li></ul>';
$lang['system_feature_desc_show_sub_total_for_game_logs_report'] = 'If enabled, Sub Total row must be included in the Game History table.<ul><li>SBE > Marketing Tab > Game Logs > Game History</li></ul>';
$lang['system_feature_desc_show_total_for_player_report'] = 'If enabled, Sub Total row should be shown in the Player Report table.<ul><li>SBE > Report tab > Player Report</li></ul>';
$lang['system_feature_desc_show_unsettle_game_logs'] = 'For 1 and 2, if enabled, Search Unsettle Game check box will appear in Search Section. For 3, Unsettle Game History report tab should be shown under player logs. <ul><li> Aff > Report > Games Report </li> <li>SBE > Report Tab > Games Report</li> <li>SBE > Player Tab > All Players > Search Player > Player Information > Player\'s Logs</li> </ul>';
$lang['system_feature_desc_show_zip_code_in_list'] = '<ul><li> If enabled, the ZIP Code column must not be visible from the Player List table. </li><li> If enabled, the ZIP Code must appear under Personal Information of the selected player. </li><li> If enabled, the ZIP Code must appear on the Personal Information form of the selected player. </li><li> SBE > Player Tab > All Players </li><li> SBE > Player Tab > All Players > Select a player > Personal Information </li><li> SBE > Player Tab > All Players > Select a player > Personal Information > Edit Personal Information</li></ul>';
$lang['system_feature_desc_strictly_cannot_login_player_when_block'] = "If enabled, the account that has been blocked cannot be opened thru SBE - Log in as Player or Player Center > Login.
<ul><li>SBE > Player Tab > All Player Sidebar > Choose a player that has been blocked > Login as Player button</li><li>Player Center > Login</li></ul>";

$lang['system_feature_desc_summary_report_2'] = 'If enabled, Summary Report 2 sidebar must be shown in the Reports tab.<br><b>Note: </b>Summary Report 2 permission must be enabled for this to work as expected<ul><li>SBE > Report Tab > Summary Report 2</li></ul>';

$lang['system_feature_desc_support_ticket_link'] = 'If enabled, the support icon at the right side of the menu bar should be visible. Also, Support Ticket sidebar must also be available under CS Tab.<ul><li> SBE > Menu Bar > Support button at the right side of the menu bar </li><li>SBE > CS</li></ul>';

$lang['system_feature_desc_sync_api_password_on_update'] = 'If enabled, once a password was reset for an agency or a player account, the new password will be synced to all game APIs.<ul><li>SBE > System > Registration Settings > Player > Login > Forget Password Settings:</li><li>Toggle on, Forget Password</li><li>Toggle on, Find password by security question or Find password by email</li><li>SBE > System > Registration Settings > Player > Registration:</li><li>Toggle hide, Security Question</li><li>Player Center > Login Page >Forgot Password?</li></ul>';

$lang['system_feature_desc_transaction_request_notification'] = "Transaction request notification.";
$lang['system_feature_desc_try_disable_time_ranger_on_cashback'] = "Try disable time ranger on cashback.";
$lang['system_feature_desc_use_mobile_number_as_username'] = 'If enabled, mobile registered customers will have their contact numbers set as their username';
$lang['system_feature_desc_use_new_player_center_mobile_version'] = 'If enabled, the new player center template for mobile will be used instead of the old one.<ul><li>Player Center > Dashboard</li></ul>';
$lang['system_feature_desc_use_self_pick_group'] = "If enabled, <b><i>'Group Level'</i></b> drop-down option will appear in Deposit tab in Cashier Center.
<ul><li>Player Center > Cashier center tab > Deposit tab</li></ul>
Note: available in Online Payment only";
$lang['system_feature_desc_use_self_pick_subwallets'] = "If enabled, <b><i>'Select game to transfer money to'</i></b> drop-down option will appear in Deposit tab in Cashier Center.
<ul><li>Player Center > Cashier center tab > Deposit tab</li></ul>";
$lang['system_feature_desc_verification_reference_for_player'] = "1. If enabled, the 'Verified' column must be visible from the Player List table.
<ul><li>SBE > Player Tab > Player List</li></ul>
2. If enabled, 'Verified' must appear under Signup Information of the selected player. ('Player Status', otherwise)
<ul><li>SBE > Player Tab > Player List > Select a player > Personal Information</li></ul>";
$lang['system_feature_desc_www_deposit_sidebar'] = 'If enabled, access to "Deposit" must be visible in the Quick Navigation Bar. </br> Note: www_sidebar feature must also be enabled (under System Features > Others tab)';
$lang['system_feature_desc_www_live_chat_sidebar'] = "If enabled, access to <b>Live Chat</b> must be visible in the Quick Navigation Bar. <br><b>Note:</b> www_sidebar feature must also be enabled (under System Features > Others tab) <ul><li>Client Website > Homepage > Quick Navigation Bar</li></ul>";
$lang['system_feature_desc_www_quick_transfer_sidebar'] = "If enabled, access to <b>Quick Transfer</b> must be visible in the Quick Navigation Bar.<b><b>Note:</b> www_sidebar feature must also be enabled (under System Features > Others tab)";
$lang['system_feature_desc_www_sidebar'] = "If enabled, Quick Navigation Bar must be visible, unless none of these are enabled: www_deposit_sidebar, www_live_chat_sidebar, www_quick_transfer_sidebar. <ul><li>Client Website > Homepage > Quick Navigation Bar</li></ul>";
$lang['system_feature_desc_display_player_bets_per_game'] = 'If enabled and bet count is not 0; Table <b>bets per game</b> will display in Game Report.<ul><li>SBE > Report > Games Report</li></ul>';
$lang['system_feature_desc_enable_player_center_search_unsettle'] = 'If enabled, a checkbox will appear to search unsettle game in Player Center.<ul><li>Player Center > Account History Sidebar > Game History Tab</li></ul>';
$lang['system_feature_desc_hide_bonus_group_on_agency']='If enabled, Tracking Links and Sub Agent Link will be shown under Agent Tracking Code.<ul><li>Agency page &gt; Setting dropdown option &gt; Account Info &gt; Click on Agent Tracking Code</li></ul>';
$lang['system_feature_desc_allow_special_characters_on_account_number'] = "If enabled, allow to input special characters in account number in collection account.<ul><li>SBE &gt; System Tab &gt; Collection Account under Payment Settings Sidebar &gt; Add/Edit Collection Account</li></ul>";
$lang['system_feature_desc_hide_disabled_games_on_game_tree'] = "Hide disabled games on game tree";
$lang['system_feature_desc_ignore_player_analysis_permissions'] = 'If enabled, Linked Accounts column in Player Analysis Report will show link account details of the Player regardless of system permission setup for Linked Account.<ul><li>Report > Player Analysis Report > Linked Accounts column<br><b>Note:</b><br>Applicable only when Linked Account Permission is unticked</li><li>if enabled and Linked Account Permission is unticked, the Linked Accounts detail in Player Analysis Report will still be available</li><li>if disabled and Linked Account Permission is unticked, the Linked Accounts detail in Player Analysis Report will be "N/A" </li></ul>';

$lang['system_feature_desc_enabled_sync_game_logs_stream'] = "Sync every Game Log to a stream type table.<br><b>Note: This feature is intented only for developers.</b>";
$lang['system_feature_desc_enable_show_bet_details_gamelogs_report'] = "If enabled, Bet details will be shown in CSV Report instead of N/A.<ul><li>SBE &gt; Marketing Tab &gt; Game Logs &gt; Export CSV Report</li></ul>";
$lang['system_feature_desc_send_email_after_verification'] = "If enabled, email_after_verification_template email will be sent to the player upon verifying his account through his email.<ul><li>Player Email &gt; Verify Account Link</li></ul>";
$lang['system_feature_desc_send_email_promotion_template_after_verification'] = "If enabled, email_promotion_template email will be sent to the player upon verifying his account through his email.<ul><li>Player Email &gt; Verify Account Link</li></ul>";
$lang['system_feature_desc_dont_allow_disabled_game_to_be_launched'] = "If enabled, disabled games will not launched.<br><b>Affected module:</b><ul><li>Game list Auto sync: If enabled all games that is in disabled status will not be updated</li><li>Player Center &gt; Game &gt; Game Launch</li></ul>";
$lang['system_feature_desc_enabled_show_rake'] = "If enabled, this will show the column rake on game history.<ul><li>SBE &gt; Player Tab &gt; Search Player &gt; Player Information &gt; Player&apos;s Logs &gt; Game History</li></ul>";
$lang['system_feature_desc_enable_income_access'] = "If enabled, Income Access integration will run for the system, including <b>BTAG</b> tracking and uploading of daily registration and sales report to specified <b>SFTP</b> server and Income Access Report sidebar should be shown under Report tab.<ul><li>SBE &gt; Report tab &gt; Income Access Report sidebar</li></ul>";
$lang['system_feature_desc_enabled_switch_language_also_set_to_static_site'] = "When the language setting is executed, the www / m website is also set.";


$lang['system_feature_desc_close_cashback'] = '<ul><li>1.) If enabled, Add Cashback button should be hidden in Batch Balance Adjustment.<li>2.) If enabled, Cashback, Add credit to agent, Subtract credit on agent, Agent add credit to sub agent, Agent subtract credit to sub agent, Agent deposit to player, Agent withdraw from player checkboxes should be hidden from Search.<li>3.) If enabled, Cashback bonus and Allowed Cashback Game List portion should be hidden in Bonus Mode section on Edit VIP Group Level Setting<li>4.) If enabled, No Cash Back Column should be hidden in Game Description table.<li>5.) If enabled. Auto Add Cashback should be hidden in Game Type table.</li> <br><b>Applicable for T1 Superadmin accounts only:</b><br><li>6.) If enabled, Sync Cashback portion should be hidden in Dev Functions.</li></ul><br><ul><li>1.) SBE > Marketing Tab > Batch Balance Adjustment</li><li>2.) SBE > Payment Tab > Transactions</li><li>3.) SBE > Player Tab >  VIP Settings > Select Group Name > Edit Settings</li><li>4.) SBE > System Tab > Game Description</li><li>5.) SBE > System Tab > Game Type</li><br><b>Applicable for T1 Superadmin accounts only:</b><br><li>6.) SBE > System Tab > Dev Functions</li></ul>';
$lang['system_feature_desc_allow_to_launch_non_existing_games_on_sbe'] = "If enabled, non-existing games should be able to launch. <br><b>Note:</b> the system feature '<b>dont_allow_disabled_game_to_be_launched</b>' must be enabled first.<ul><li>Player Center / Website &gt; Game Launch</li></ul>";
$lang['system_feature_desc_allow_generate_inactive_game_api_game_lists'] = "If enabled, inactive game api game list can be generated.<ul><li>For T1 Developers Only</li></ul>";

$lang['system_feature_desc_hide_old_adjustment_history'] = "If enabled, the old adjustment history logs of a player will be hidden.<ul><li>SBE > Player Information > Player Logs > Old Adjustment History must not be visible from the navigation bar of Player Logs</li></ul>";

$lang['system_feature_desc_enable_tag_column_on_transaction'] = "If enabled, Tag Column And Tab Select Option will appear in Transaction List Report <br><ul><li>Report Tab > Transactions > Transactions List</li></ul>";

$lang['system_feature_desc_hide_point_setting_in_vip_level'] = "If enabled, Point Setting pannel will be hidden in VIP Group Level Setting.
<ul><li>SBE > Player tab > VIP Settings > Select Group > Edit level Settings</li></ul>";

$lang['system_feature_desc_hide_empty_game_type_on_game_tree'] = "If enabled, all empty game type in game tree will be hidden.<br>Affected Modules:<ul><li>Vip levels: <br>Player Tab > Vip Settings > Vip Level > Cashback game tree</li><li>General cashback settings: <br>Marketing Tab > Cashback Period Settings > Add/Edit Cashback Rule</li><li>Promotion Rules: <br>Marketing Tab > Promo Rules Settings > Add/Edit Promo rule > Allowed Game Type </li></ul>";
$lang['system_feature_desc_hide_free_spin_on_game_history'] = "If enabled, Free Spin logs will be hidden <br>Note: only for PGSOFT and Table and Card games<br><ul><li>Player Information > Player's Logs > Game History Tab</li><li>Report Tab > Game Logs</li></ul>";

$lang['system_feature_desc_use_role_permission_management_v2'] = "If enabled, the new version of role managment will be used instead of the old one. The new version includes a proper and a better categorization of the system permissions with better design / user interface. <br> <ul><li>View Roles > Select a role > You should be seeing the new version now.</li></ul>";
$lang['system_feature_desc_use_new_super_report'] = "If enabled, new version of super report will be shown.
<b>Note:</b> Applicable for mdb clients only. <ul><li>SBE > Super Report tab</li></ul>";
$lang['system_feature_desc_use_pwa_loader'] = "Use PWA loader to speed network";

$lang['system_feature_desc_enable_otp_on_adminusers'] = 'If enabled, two-factor authentication settings will be shown under Personal Settings and Reset 2FA buttons under VIew Users in SBE.<ul><li>SBE &gt; View Users &gt; Reset 2FA button</li><li>SBE &gt; Login 2FA code
</li></ul>';


$lang['system_feature_desc_enable_otp_on_player']='Enable 2FA for player';
$lang['system_feature_desc_enable_otp_on_agency']='Enable 2FA for agency';
$lang['system_feature_desc_enable_otp_on_affiliate']='Enable 2FA for affiliate';

$lang['system_feature_desc_disable_auto_add_cash_back']='If enabled, unknown games on cashback tree (new games on game list with unticked status and flag show in site) and cashback will be disabled.<br><ol type = "1"><li>SBE > System tab > Game List > View Game List > Check Game Status and Flag Show in Site were not ticked</li><li><ol type = "a"><li>SBE > Player Tab >  VIP Settings > Select Group Name > Edit Settings > Bonus Mode > Cashback Bonus is enabled > Allowed Cashback Game List > Click on Edit button >  Unknown game should not be ticked</li><li>SBE > Marketing tab > Cashback Period Setting > Common Cashback Rules > Select Rule to Edit > Click on Edit Settings > Click on Edit > Unknown game should not be ticked</li></ol></ol>';

$lang['system_feature_desc_enable_isolated_promo_game_tree_view'] = 'If enabled, Edit Settings button will be displayed on Allowed Game Type upon adding or editing promo rule and if clicked, will show modal for Allowed Promo Game Setting Form.<br><ol type = "1"><li>SBE > Marketing > Promo Rules Settings > Add New Promo Rule > Find ""Edit Settings"" under "4. Allowed Game Type"</li><li>SBE > Marketing > Promo Rules Settings > Click any Promo Name for Edit > Find "Edit Settings" under "4. Allowed Game Type"</li></ol>';

$lang['system_feature_desc_enable_isolated_vip_game_tree_view'] = 'If enabled, Edit Settings button will be displayed on Allowed Cashback Game List and if clicked, will show modal for Cashback Setting Form.<br><ul type = "1"><li>SBE > Player > VIP Settings > Click any Group Name > Click Specific Edit Settings Button > Find "Edit Settings" under "Allowed Cashback Game List"</li></ul>';

$lang['system_feature_desc_enable_payment_status_history_report'] = 'If enabled, Payment Status History Report tab will be accessible (only if the user has the permission).<br><ul type = "1"><li>SBE > Report tab > Payment Status History Report sidebar</li></ul>';

$lang['system_feature_desc_enable_show_trigger_XinyanApi_validation_btn'] = 'If enabled, the SBE user can process Xinyan Validation on Player.<br><ul type = "1"><li>SBE > Player tab > All Players sidebar > Search Username > Player Information > Actions > Trigger Xinyan Validation</li></ul>';

$lang['system_feature_desc_enabled_cashback_of_multiple_range'] = 'If enabled, settings in the Multiple Range tab will be followed instead of the Default.<br><ul type = "1"><li>Cashback Settings. SBE > Marketing Tab > Cashback Period Settings sidebar > Common Cashback Rules > Multiple Range tab</li></ul>';

$lang['system_feature_desc_enabled_vr_game_report'] = 'If enabled, SBE user should be able to generate VR Games Report.<br><ul type = "1"><li>SBE > Report > VR Games Report</li></ul>';

$lang['system_feature_desc_enabled_afb88_sports_game_report'] = 'If enabled, SBE user should be able to generate AFB88 Game Report.<br><ul type = "1"><li>Report > AFB88 Game Report</li></ul>';

$lang['system_feature_desc_enabled_sbobet_sports_game_report'] = 'If enabled, SBE user should be able to generate SBObet Game Report.<br><ul type = "1"><li>SBE > Report > SBObet Game Report</li></ul>';

$lang['system_feature_desc_ole777_wager_sync'] = 'If enabled, "OLE777 Wager Sync" sidebar will be shown.<br><ul type = "1"><li>SBE > Marketing tab</li></ul>';

$lang['system_feature_desc_vip_level_maintain_settings'] = "if enabled, VIP level settings > grace period settings will switch to level maintain settings.";
$lang['system_feature_desc_enabled_use_decuct_flag_to_filter_withdraw_condition_when_calc_cackback'] = "If enabled, the system will get the total required bet amount from the withdrawal conditions that are not yet deducted from the cashback computation. The total required bet amount will be deducted in cashback computation.
<b>Note:</b></br>
A. Applicable to promotions where Disable cashback if donot finish withdraw condition is ticked</br>
B. exclude_wc_available_bet_after_cancelled_wc config should be enabled too for this to workC. When use_accumulate_deduction_when_calculate_cashback config is enabled, the system will get all pending withdrawal condition with Cashback betting amount status of Accumulating Decution and Not Deduct.";

$lang['system_feature_desc_enabled_backendapi'] = "Enabled Backend API";
$lang['system_feature_desc_enabled_transactions_daily_summary_report'] = "If enabled, SBE user should be able to generate Transaction Summary Report
<ul><li>SBE > Report > Transaction Summary Report</li></ul>";
$lang['system_feature_desc_enabled_quickfire_game_report'] = "Enabled Quickfire Custom Report";
$lang['system_feature_desc_enabled_iovation_in_registration'] = "Enabled Iovation in Registration";
$lang['system_feature_desc_enabled_iovation_in_promotion'] = "Enabled Iovation in Promotion";
$lang['system_feature_desc_refresh_player_balance_before_userinformation_load'] = "If enabled, refresh the balance of player before loading of player information page.
<ul><li>SBE > Player tab > Player List sidebar > Search Username > Player Information > Account Info tab</li></ul>";
$lang['system_feature_desc_player_main_js_enable_game_preloader'] = "If enabled, the system will use a javascript in game preloader. A javascript file will be created in
<tt>'~/Code/&lt;client folder&gt;/pub/&lt;server name&gt;/player_pub/&lt;the javascript file&gt;'</tt><br>
<b>Note:</b> 'T1LOTTERY_API(1004)' game API should be enabled first in SBE for this to work as expected. <b>For T1 Developers/SA Only</b>";
$lang['system_feature_desc_force_using_referral_code_when_register'] = 'If enabled, new players will be registered as referred by the player who provided the referral link and under the affiliate who owns the dedicated domain used in referral link.<ul><li>SBE > Player Tab> Player Information > Signup info tab > Referred By and Under Affiliate field</li></ul>';
$lang['system_feature_desc_enabled_png_freegame_api'] = 'If enabled, SBE user should be able to create/view and cancerl free games offer for PNG game.<br><ul type = "1"><li>SBE > Marketing Management > PNG Free Games Offer</li></ul>';
/**
 * NOTICE Devs, listed below are newly added system feature deescriptions
 * please put them to their respective tabs.
 * -New system features-
 */
/* -------------------------------------------------------------- */
/* System feature - end
/* -------------------------------------------------------------- */
