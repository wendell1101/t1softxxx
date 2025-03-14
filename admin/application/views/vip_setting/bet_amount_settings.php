<div class="modal fade" tabindex="-1" role="dialog" id="bet_amount_settings-modal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title"><?=lang('Bet Amount Settings')?></h4>
        </div>
        <div class="modal-body">
            <form id="form_bet_amount_settings" name="form_bet_amount_settings">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col col-md-3">
                            <?=lang('Setting Name')?>
                        </div>
                        <div class="col col-md-9">
                            <div class="label label-default title_setting_name"></div>
                        </div>
                    </div>
                    <div class="row padding-bottom-8px row-total_bet_amount">
                        <div class="col col-md-1">
                            <label for="bet_amount_type_total" class="col-sm-2 control-label">
                                <input type="radio" name="bet_amount_type" id="bet_amount_type_total" value="total">
                            </label>
                        </div>
                        <div class="col col-md-11">
                            <div class="input-field">
                                <?=lang('Total bet amount')?> <input type="text" name="total_bet_amount">
                            </div>
                            <div class="tip-field text-danger hide"></div>
                        </div>
                    </div>
                    <div class="row form-group row-default_bet_amount">
                        <div class="col col-md-1">
                            <label for="bet_amount_type_detail" class="col-sm-2 control-label">
                                <input type="radio" name="bet_amount_type" id="bet_amount_type_detail" value="detail">
                            </label>
                        </div>
                        <div class="col col-md-11">
                            <div class="row padding-bottom-8px">
                                <div class="col col-md-12">
                                    <div class="input-field">
                                        <?=lang('Default bet amount')?>
                                        <input name="default_bet_amount" value="">
                                    </div>
                                    <div class="tip-field text-danger hide"></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col col-md-12">
                                    <div class="input-field">
                                        <fieldset class="padding20px">
                                            <div class="game_type_tree"></div>
                                        </fieldset>
                                        <ul class="rules h5 text-primary">
                                            <li class="rules-item">
                                                <?=lang('The field,"Default bet amount" must required.')?>
                                            </li>
                                            <li class="rules-item">
                                                <?=lang('If the amount of the game platform is empty, that\'s will be reference to the field,"Default bet amount".')?>
                                            </li>
                                            <li class="rules-item">
                                                <?=lang('If the amount of the game type is empty, that\'s will be reference to the game platform\'s.')?>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="tip-field text-danger hide"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-primary remove_bet_amount_settings" ><?=lang('Cancel');?></button>
        <button type="button" class="btn btn-info save_bet_amount_settings" ><?=lang('Change to the formula');?></button>
    </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<style type="text/css">
.game_type_tree {
    max-height: 200px;
    overflow: auto;
    background-color: #eee;
    padding: 8px;
}
</style>