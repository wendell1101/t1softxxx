<?php
$playerId = $this->load->get_var('playerId');
// $big_wallet = $this->wallet_model->getOrderBigWallet($playerId);
// $player_first_login_page_button_setting = $this->utils->getConfig('player_first_login_page_button_setting');
// $total_no_frozen = $this->load->vars('total_no_frozen', $big_wallet['total'] - $big_wallet['main']['frozen']);
//
//
// $is_registered_popup_success_done = true;
// if (!empty($playerId)) {
//     $is_registered_popup_success_done = $this->player_model->getPlayerInfoDetailById($playerId, null)['is_registered_popup_success_done'];
// }
?>
<div class="modal fade join-priority-modal" id="join-priority-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header hide">
                <div class="modal-title text-center">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4><?= lang('Join Priority Players for a better experience.'); ?></h4>
                </div>
            </div>

            <div class="modal-body">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>

                <div class="container-fluid">
                    <div class="row">
                        <div class="col col-md-12 text-center">
                            <img src="/includes/images/c038/PopUpBanner4PriorityPlayer.PC.png">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col col-md-10 col-md-offset-1 text-center">
                            <label class="join-confirm-label container" for="is_join_show_done">
                                <input type="checkbox" name="is_join_show_done" id="is_join_show_done" value="1" checked="1"> <?=lang('lang.is_join_show_done.desc');?>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                    </div>
                </div> <!-- EOF .container-fluid -->

            </div> <!-- EOF .modal-body -->
        </div> <!-- EOF .modal-content -->
    </div>
</div> <!-- EOF #join-priority-modal -->

<style type="text/css">

#join-priority-modal .modal-content .modal-body img{
    width: 100%;
}

#join-priority-modal .join-confirm-label {
    font-size: 22px;
}
#join-priority-modal .join-confirm-label .highlight_desc {
    color: #3EDCFC;
}
#join-priority-modal .join-confirm-label .note{
    font-size: 20px;
}



/* Customize the label (the container) */
#join-priority-modal .container {
  display: block;
  position: relative;
  padding-left: 0px;
  /* margin-bottom: 12px; */
  cursor: pointer;
  /* font-size: 22px; */
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}

/* Hide the browser's default checkbox */
#join-priority-modal .container input {
  position: absolute;
  opacity: 0;
  cursor: pointer;
  height: 0;
  width: 0;
}

/* Create a custom checkbox */
#join-priority-modal .checkmark {
  position: absolute;
  top: 4px;
  left: 22px;
  height: 25px;
  width: 25px;
  background-color: #eee;
}

/* On mouse-over, add a grey background color */
#join-priority-modal .container:hover input ~ .checkmark {
  background-color: #ccc;
}

/* When the checkbox is checked, add a blue background */
#join-priority-modal .container input:checked ~ .checkmark {
  background-color: #2196F3;
}

/* Create the checkmark/indicator (hidden when not checked) */
#join-priority-modal .checkmark:after {
  content: "";
  position: absolute;
  display: none;
}

/* Show the checkmark when checked */
#join-priority-modal .container input:checked ~ .checkmark:after {
  display: block;
}

/* Style the checkmark/indicator */
#join-priority-modal .container .checkmark:after {
  left: 9px;
  top: 3px;
  width: 9px;
  height: 15px;
  border: solid white;
  border-width: 0 3px 3px 0;
  -webkit-transform: rotate(45deg);
  -ms-transform: rotate(45deg);
  transform: rotate(45deg);
}
</style>