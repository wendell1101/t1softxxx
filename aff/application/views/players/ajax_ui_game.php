<div class="panel panel-default" style="border-radius: 0; border-top: none; margin-bottom: 0;">
    <div class="panel-body">
        <div class="text-right">
            <form class="form-inline" id="search-form">
                <input type="text" id="search_game_date" class="form-control input-sm dateInput inline" data-start="#by_date_from" data-end="#by_date_to" data-time="true"
                <?php if ($this->utils->getConfig('game_logs_report_date_range_restriction')): ?>
                    data-restrict-max-range="<?=$this->utils->getConfig('game_logs_report_date_range_restriction')?>" data-restrict-range-label="<?=sprintf(lang("restrict_date_range_label"),$this->utils->getConfig('game_logs_report_date_range_restriction'))?>" data-restrict-max-range-second-condition="<?=$this->utils->getConfig('game_logs_report_with_username_date_range_restriction')?>" data-override-on-apply="true"
                <?php endif ?>
                />
                <!-- <input type="hidden" id="game_date_from" name="game_date_from"/>
                <input type="hidden" id="game_date_to" name="game_date_to"/> -->
                <input type="hidden" id="by_date_from" name="by_date_from"/>
                <input type="hidden" id="by_date_to" name="by_date_to"/>
                <select name="game_platform_id" id="game_platform_id" class="form-control input-sm">
                    <option value=""><?=lang('lang.all') . ' ' . lang('cms.gameprovider')?></option>
                    <?php foreach ($game_platforms as $game_platform): ?>
                        <option value="<?=$game_platform['id']?>"><?=$game_platform['system_code']?></option>
                    <?php endforeach?>
                </select>
                <!-- <input type="button" class="btn btn-primary btn-sm" id="btn-submit" value="<?=lang('lang.searchby');?>"/> -->
            </form>
        </div>
        <hr/>
        <table id="gamehistory-table" class="table table-condensed">
            <thead>
                <tr>
<!--                     <th><?=lang('player.ug01');?></th>
                    <th><?=lang('system.word38');?></th>
                    <th><?=lang('cms.gameprovider');?></th>
                    <th><?=lang('cms.gametype');?></th>
                    <th><?=lang('cms.gamename');?></th>
                    <th><?=lang('cms.betAmount');?></th>
                    <th><?=lang('mark.resultAmount');?></th>
                    <th><?=lang('lang.bet.plus.result');?></th>
                    <th><?=lang('mark.afterBalance');?></th>
                    <th><?=lang('pay.transamount');?></th>
                    <th><?=lang('player.ut12');?></th> -->
                    <th><?= lang('#') ?></th>
                    <th><?= lang('player.ug06') ?></th>
                    <th><?= lang('Transaction Date / Payout Date') ?></th>
                    <th><?= lang('Updated Date') ?></th>
                    <th><?= lang('Player Username') ?></th>
                    <th><?= lang('Affiliate Username') ?></th>
                    <th><?= lang('Agent Username') ?></th>
                    <th><?= lang('Player Level') ?></th>
                    <th><?= lang('cms.gameprovider') ?></th>
                    <th><?= lang('cms.gametype') ?></th>
                    <th><?= lang('cms.gamename') ?></th>
                    <th><?= lang('Real Bet') ?></th>
                    <th><?= lang('Available Bet') ?></th>
                    <th><?= lang('mark.resultAmount') ?></th>
                    <th><?= lang('lang.bet.plus.result') ?></th>
                    <th><?= lang('Win Amount') ?></th>
                    <th><?= lang('Loss Amount') ?></th>
                    <th><?= lang('mark.afterBalance') ?></th>
                    <th><?= lang('pay.transamount') ?></th>
                    <th><?= lang('Round No') ?></th>
                    <th><?= lang('game_type') ?></th>
                    <th><?= lang('Note') ?></th>
                </tr>
            </thead>

            <tfoot>
                <tr>
                    <th colspan="10" style="text-align: right;"><?=lang('cms.totalBetAmount');?>: <span id="search-total"></span></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>