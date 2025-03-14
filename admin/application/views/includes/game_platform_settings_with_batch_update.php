<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><font style="color:red;">*</font> <?=lang('Commission Setting');?></h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-6">
                <label class="control-label"><?=lang('Deposit Commission')?></label>
                <div class="input-group">
                    <input type="number" class="form-control" name="deposit_comm" id="deposit_comm" value="<?=set_value('deposit_comm', isset($agent) && isset($agent['deposit_comm']) ? number_format($agent['deposit_comm'],2) : 0);?>" step="any" min="0" required="required" <?php if (isset($view_only) && $view_only) echo 'disabled="disabled"' ?>/>
                    <div class="input-group-addon">%</div>
                </div>
                <span class="errors"><?php echo form_error('deposit_comm'); ?></span>
                <span id="error-deposit_comm" class="errors"></span>
            </div>
        </div>

        <br/>
        <table class="table table-condensed">
            <thead>
                <tr>
                    <th><?=lang('Enabled');?></th>
                    <th><?=lang('Game Platform');?></th>
                    <th><?=lang('Game Type');?></th>
                    <?php if (!isset($controller_name) || $controller_name == 'agency_management'): # OGP-3607 ?>
                    <th><?=lang('Game Platform Fee');?>%</th>
                    <?php endif ?>
                    <?php if (!isset($is_player) || empty($is_player)) { ?>
                    <th><?=lang('Rev Share');?>%</th>
                    <?php } ?>
                    <th><?=lang('Rolling Comm Basis');?></th>
                    <th><?=lang('Rolling Comm Income');?>%</th>
                    <!-- <th><?=lang('Rolling Comm Out');?></th> -->
                    <th><?=lang('Bet Threshold');?></th>
                    <th><?=lang('Min Rolling Comm');?>%</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($game_platform_list as &$game_platform): ?>

                <?php

                    $game_platform['all_comm_settings'] = [
                        'platform_fee' => 0.00,
                        'rev_share' => 0.00,
                        'rolling_comm_basis' => 'total_bets_except_tie_bets',
                        'rolling_comm' => 0.00,
                        'bet_threshold' => 0.00,
                        'min_rolling_comm' => 0.00
                    ];

                    //var_dump($agency_agent_game_platforms_comm_settings);exit;

                    if(isset($agency_agent_game_platforms_comm_settings)){
                        foreach($agency_agent_game_platforms_comm_settings as $all_comm_setting){
                            if($all_comm_setting['game_platform_id'] == $game_platform['id']){
                                $game_platform['all_comm_settings'] = [
                                    'platform_fee' => number_format($all_comm_setting['platform_fee'], 2),
                                    'rev_share' => number_format($all_comm_setting['rev_share'], 2),
                                    'rolling_comm_basis' => $all_comm_setting['rolling_comm_basis'],
                                    'rolling_comm' => number_format($all_comm_setting['rolling_comm'], 2),
                                    'bet_threshold' => number_format($all_comm_setting['bet_threshold'], 2),
                                    'min_rolling_comm' => number_format($all_comm_setting['min_rolling_comm'], 2)
                                ];
                            }
                        }
                    }

                    /*if(empty($agency_agent_game_platforms_comm_settings)){
                        $game_platform['all_comm_settings'] = [
                            'platform_fee' => isset($game_platform['game_types'][0]['platform_fee'])?$game_platform['game_types'][0]['platform_fee']:0.00,
                            'rev_share' => isset($game_platform['game_types'][0]['rev_share'])?$game_platform['game_types'][0]['rev_share']:0.00,
                            'rolling_comm_basis' => isset($game_platform['game_types'][0]['rolling_comm_basis'])?$game_platform['game_types'][0]['rolling_comm_basis']:'total_bets_except_tie_bets',
                            'rolling_comm' => isset($game_platform['game_types'][0]['rolling_comm'])?$game_platform['game_types'][0]['rolling_comm']:0.00,
                            'bet_threshold' => isset($game_platform['game_types'][0]['bet_threshold'])?$game_platform['game_types'][0]['bet_threshold']:0.00,
                            'min_rolling_comm' => isset($game_platform['game_types'][0]['min_rolling_comm'])?$game_platform['game_types'][0]['min_rolling_comm']:0.00
                        ];
                    }*/

                    $firstGameTypeId = (int)isset($game_platform['game_types'][0]['game_type_id'])?$game_platform['game_types'][0]['game_type_id']:0;

                    $firstGameType = [];
                    if(isset($conditions['game_types'])){
                        foreach($conditions['game_types'] as $gameTypeRow){
                            if($gameTypeRow['game_platform_id']==$game_platform['id']){
                                $firstGameType['platform_fee'] = number_format($gameTypeRow['platform_fee'], 2);
                                $firstGameType['rev_share'] = number_format($gameTypeRow['rev_share'], 2);
                                $firstGameType['rolling_comm_basis'] = $gameTypeRow['rolling_comm_basis'];
                                $firstGameType['rolling_comm'] = number_format($gameTypeRow['rolling_comm'], 2);
                                $firstGameType['bet_threshold'] = number_format($gameTypeRow['bet_threshold'], 2);
                                $firstGameType['min_rolling_comm'] = number_format($gameTypeRow['min_rolling_comm'], 2);
                                break;
                            }
                        }
                    }

                    if(empty($game_platform['all_comm_settings']['platform_fee']) || (float)$game_platform['all_comm_settings']['platform_fee']<=0){
                        $game_platform['all_comm_settings']['platform_fee'] = isset($firstGameType['platform_fee'])?number_format($firstGameType['platform_fee'],2):0.00;
                    }
                    if(empty($game_platform['all_comm_settings']['rev_share']) || (float)$game_platform['all_comm_settings']['rev_share']<=0){
                        $game_platform['all_comm_settings']['rev_share'] = isset($firstGameType['rev_share'])?number_format($firstGameType['rev_share'],2):0.00;
                    }
                    if(empty($game_platform['all_comm_settings']['rolling_comm_basis'])){
                        $game_platform['all_comm_settings']['rolling_comm_basis'] = isset($firstGameType['rolling_comm_basis'])?$firstGameType['rolling_comm_basis']:'total_bets_except_tie_bets';
                    }
                    if(empty($game_platform['all_comm_settings']['rolling_comm']) || (float)$game_platform['all_comm_settings']['rolling_comm']<=0){
                        $game_platform['all_comm_settings']['rolling_comm'] = isset($firstGameType['rolling_comm'])?number_format($firstGameType['rolling_comm'],2):0.00;
                    }
                    if(empty($game_platform['all_comm_settings']['bet_threshold']) || (float)$game_platform['all_comm_settings']['bet_threshold']<=0){
                        $game_platform['all_comm_settings']['bet_threshold'] = isset($firstGameType['bet_threshold'])?number_format($firstGameType['bet_threshold'],2):0.00;
                    }
                    if(empty($game_platform['all_comm_settings']['min_rolling_comm']) || (float)$game_platform['all_comm_settings']['min_rolling_comm']<=0){
                        $game_platform['all_comm_settings']['min_rolling_comm'] = isset($firstGameType['min_rolling_comm'])?number_format($firstGameType['min_rolling_comm'],2):0.00;
                    }


                ?>

                    <tr>
                        <td>
                            <input type="hidden" name="game_platforms[<?=$game_platform['id']?>][game_platform_id]" value="<?=$game_platform['id']?>"/>
                            <?php
                                $game_platform_input = array(
                                    'id' => 'game-platform-'.$game_platform['id'],
                                    'name' => 'game_platforms['.$game_platform['id'].'][enabled]',
                                    'class' => 'game-platform update_all_input',
                                    'data-game-platform-id' => $game_platform['id'],
                                    'value' => 1,
                                );

                                if (isset($view_only) && $view_only) {
                                    $game_platform_input['disabled'] = 'disabled';
                                }

                                if (isset($conditions['game_platforms'][$game_platform['id']])) {
                                    $game_platform_input['checked'] = 'checked';
                                }
                                echo form_checkbox($game_platform_input);
                            ?>
                        </td>
                        <td>
                            <label for="game-platform-<?=$game_platform['id']?>"><?=$game_platform['name']?></label>
                        </td>
                        <td>
                            <?=lang('All')?>
                        </td>
                        <?php if (!isset($controller_name) || $controller_name == 'agency_management'): # OGP-3607 ?>
                        <td>
                            <div class="input-group">
                            <?php

                                $id_platform_fee = 'game_platform-'. $game_platform['id'] ."-platform_fee";
                                $name_platform_fee = 'game_platforms['. $game_platform['id'] ."][platform_fee]";

                                $platform_fee_input = array(
                                    'type' => 'number',
                                    'id' => $id_platform_fee,
                                    'name' => $name_platform_fee,
                                    'class' => 'form-control input-sm platform-field-' . $game_platform['id'] . ' game-platform-fee-input-'.$game_platform['id'] . '  update_all_input update_all-platform_fee-'.$game_platform['id'],
                                    'data-class-group' => 'platform_fee-'.$game_platform['id'],
                                    'data-game-platform-id' => $game_platform['id'],
                                    'min' => 0,
                                    'step' => 'any',
                                );

                                if (isset($view_only) && $view_only) {
                                    $platform_fee_input['disabled'] = 'disabled';
                                }

                                $platform_fee_input['value'] = $game_platform['all_comm_settings']['platform_fee'];

                                echo form_input($platform_fee_input);

                            ?>
                            <!--<div class="input-group-btn">
                                    <button type="button" class="btn btn-default dropdown-toggle platform-field-<? echo $game_platform['id']?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" <?=isset($view_only) && $view_only ? 'disabled="disabled"' : ''?>><?php echo lang('Update')?> <span class="caret"></span></button>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                    <li><a href="#" class="game_platform-update_all_button" data-class-group="platform_fee-<?php echo $game_platform['id']?>" data-game-platform-id="<?php echo $game_platform['id']?>" id="game_platform-<? echo $game_platform['id']?>-update_all-platform_fee">Update All Unchanged</a></li>
                                    <li><a href="#" class="game_platform-update_all_button_force" data-class-group="platform_fee-<?php echo $game_platform['id']?>" data-game-platform-id="<?php echo $game_platform['id']?>" id="game_platform-<? echo $game_platform['id']?>-update_all-platform_fee-force">Force Update ALL</a></li>
                                    </ul>
                                </div>-->
                                <div class="input-group-btn">
                                    <button type="button" class="btn btn-sm btn-default dropdown-toggle platform-field-<? echo $game_platform['id']?> game_platform-update_all_button" data-class-group="platform_fee-<?php echo $game_platform['id']?>" data-game-platform-id="<?php echo $game_platform['id']?>" <?=isset($view_only) && $view_only ? 'disabled="disabled"' : ''?>><?php echo lang('Update All')?></button>
                                </div><!-- /btn-group -->
                            </div><!-- /input-group -->


                            <span id="error-<?=$id_platform_fee?>" class="errors"><?php echo form_error($name_platform_fee); ?></span>
                        </td>
                        <?php endif ?>

                        <?php if (!isset($is_player) || empty($is_player)) { ?>
                        <td>
                            <div class="input-group">
                            <?php

                                $id_rev_share = 'game_platform-'. $game_platform['id'] ."-rev_share";
                                $name_rev_share = 'game_platforms['. $game_platform['id'] ."][rev_share]";

                                $rev_share_input = array(
                                    'type' => 'number',
                                    'id' => $id_rev_share,
                                    'name' => $name_rev_share,
                                    'class' => 'update_all_input rev_share form-control input-sm platform-field-' . $game_platform['id'] . ' update_all-rev_share-'. $game_platform['id'],
                                    'data-class-group' => 'rev_share-'.$game_platform['id'],
                                    'data-game-platform-id' => $game_platform['id'],
                                    'min' => 0,
                                    'step' => 'any',
                                );

                                if (isset($view_only) && $view_only) {
                                    $rev_share_input['disabled'] = 'disabled';
                                }

                                $rev_share_input['value'] = $game_platform['all_comm_settings']['rev_share'];

                                if(isset($agent_level) && $this->utils->isEnabledFeature('set_rev_share_to_readonly_for_level_0_agent') && $agent_level == 0) {
                                    $rev_share_input['readonly'] = 'readonly';
                                }

                                $rev_share_input['max'] = '100.00';

                                echo form_input($rev_share_input);

                            ?>
                                <!--<div class="input-group-btn">
                                    <button type="button" class="btn btn-default dropdown-toggle platform-field-<? echo $game_platform['id']?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" <?=isset($view_only) && $view_only ? 'disabled="disabled"' : ''?>><?php echo lang('Update')?> <span class="caret"></span></button>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                    <li><a href="#" class="game_platform-update_all_button" data-class-group="rev_share-<?php echo $game_platform['id']?>" data-game-platform-id="<?php echo $game_platform['id']?>" id="game_platform-<? echo $game_platform['id']?>-update_all-rev_share">Update All Unchanged</a></li>
                                    <li><a href="#" class="game_platform-update_all_button_force" data-class-group="rev_share-<?php echo $game_platform['id']?>" data-game-platform-id="<?php echo $game_platform['id']?>" id="game_platform-<? echo $game_platform['id']?>-update_all-rev_share-force">Force Update ALL</a></li>
                                    </ul>
                                </div>--><!-- /btn-group -->
                                <div class="input-group-btn">
                                    <button type="button" class="btn btn-sm btn-default dropdown-toggle platform-field-<? echo $game_platform['id']?> game_platform-update_all_button" data-class-group="rev_share-<?php echo $game_platform['id']?>" data-game-platform-id="<?php echo $game_platform['id']?>" <?=isset($view_only) && $view_only ? 'disabled="disabled"' : ''?>><?php echo lang('Update All')?></button>
                                </div><!-- /btn-group -->
                            </div><!-- /input-group -->

                            <span id="error-<?=$id_rev_share?>" class="errors"><?php echo form_error($name_rev_share); ?></span>
                        </td>
                        <?php } ?>

                        <td>
                            <?php if ((isset($view_only) && $view_only) || (isset($parent_id) && $parent_id) || (isset($conditions['agent_level']) && $conditions['agent_level'] != 0)): ?>

                                <?php

                                    $rolling_comm_basis_input = array(
                                        'type' => 'hidden',
                                        'class' => 'update_all_input platform-field-' . $game_platform['id'],
                                        'name' => 'game_platforms['.$game_platform['id'].'][rolling_comm_basis]',
                                        'data-class-group' => 'rolling_comm_basis-'.$game_platform['id'],
                                        'data-game-platform-id' => $game_platform['id'],
                                    );

                                    if ( ! isset($conditions['game_platforms'][$game_platform['id']])) {
                                        $rolling_comm_basis_input['disabled'] = 'disabled';
                                    }

                                    $rolling_comm_basis_input['value'] = $game_platform['all_comm_settings']['rolling_comm_basis'];

                                    echo form_input($rolling_comm_basis_input);

                                    $rolling_comm_basis_input['type'] = 'text';
                                    $rolling_comm_basis_input['class'] = 'form-control input-sm';
                                    $rolling_comm_basis_input['value'] = lang($rolling_comm_basis_input['value']);
                                    $rolling_comm_basis_input['readonly'] = 'readonly';
                                    unset($rolling_comm_basis_input['name']);
                                    echo form_input($rolling_comm_basis_input);

                                ?>

                            <?php else: ?>
                                <select name="game_platforms[<?=$game_platform['id']?>][rolling_comm_basis]" class="update_all_input form-control input-sm platform-field-<?=$game_platform['id']?>" <?=isset($view_only) && $view_only ? 'disabled="disabled"' : ''?>>
                                    <option value="total_bets_except_tie_bets"
                                    <?=(isset($game_platform['all_comm_settings']['rolling_comm_basis']) && $game_platform['all_comm_settings']['rolling_comm_basis'] == 'total_bets_except_tie_bets') ? 'selected':''?> >
                                    <?=lang('Bets Except Tie Bets');?>
                                    </option>
                                    <option id="basis_total_bets" value="total_bets"
                                    <?=(isset($game_platform['all_comm_settings']['rolling_comm_basis']) && $game_platform['all_comm_settings']['rolling_comm_basis'] == 'total_bets') ? 'selected':''?> >
                                    <?=lang('Total Bet');?>
                                    </option>
                                    <option id="basis_total_bets" value="winning_bets"
                                    <?=(isset($game_platform['all_comm_settings']['rolling_comm_basis']) && $game_platform['all_comm_settings']['rolling_comm_basis'] == 'winning_bets') ? 'selected':''?> >
                                    <?=lang('Winning Bets');?>
                                    </option>
                                    <option value="total_lost_bets"
                                    <?=(isset($game_platform['all_comm_settings']['rolling_comm_basis']) && $game_platform['all_comm_settings']['rolling_comm_basis'] == 'total_lost_bets') ? 'selected':''?> >
                                    <?=lang('Lost Bets');?>
                                    </option>
                                    <option value="total_lost_amount"
                                    <?=(isset($game_platform['all_comm_settings']['rolling_comm_basis']) && $game_platform['all_comm_settings']['rolling_comm_basis'] == 'total_lost_amount') ? 'selected':''?> >
                                    <?=lang('Lost Amount');?>
                                    </option>
                                </select>

                            <?php endif ?>

                        </td>
                        <td>
                            <div class="input-group">
                            <?php

                                $id_rolling_comm = 'game_platform-'. $game_platform['id'] ."-rolling_comm";
                                $name_rolling_comm = 'game_platforms['. $game_platform['id'] ."][rolling_comm]";

                                $rolling_comm_input = array(
                                    'type' => 'number',
                                    'id' => $id_rolling_comm,
                                    'name' => $name_rolling_comm,
                                    'class' => 'update_all_input rolling_comm form-control input-sm platform-field-' . $game_platform['id'] . ' game-type-field-' . $game_platform['id'] .' update_all-rolling_comm-'.$game_platform['id'],
                                    'data-class-group' => 'rolling_comm-'.$game_platform['id'],
                                    'data-game-platform-id' => $game_platform['id'],
                                    'min' => 0,
                                    'step' => 'any',
                                );

                                if (isset($view_only) && $view_only) {
                                    $rolling_comm_input['disabled'] = 'disabled';
                                }

                                $rolling_comm_input['value'] = $game_platform['all_comm_settings']['rolling_comm'];

                                if (((isset($parent_id) && $parent_id) || (isset($conditions['agent_level']) && $conditions['agent_level'] != 0)) && isset($game_platform['all_comm_settings']['max_rolling_comm'])) {
                                    $rolling_comm_input['max'] = $game_platform['all_comm_settings']['max_rolling_comm'];
                                }

                                echo form_input($rolling_comm_input);

                            ?>
                                <!--<div class="input-group-btn">
                                    <button type="button" class="btn btn-default dropdown-toggle platform-field-<? echo $game_platform['id']?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" <?=isset($view_only) && $view_only ? 'disabled="disabled"' : ''?>><?php echo lang('Update')?> <span class="caret"></span></button>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                    <li><a href="#" class="game_platform-update_all_button" data-class-group="rolling_comm-<?php echo $game_platform['id']?>" data-game-platform-id="<?php echo $game_platform['id']?>" id="game_platform-<? echo $game_platform['id']?>-update_all-rolling_comm">Update All Unchanged</a></li>
                                    <li><a href="#" class="game_platform-update_all_button_force" data-class-group="rolling_comm-<?php echo $game_platform['id']?>" data-game-platform-id="<?php echo $game_platform['id']?>" id="game_platform-<? echo $game_platform['id']?>-update_all-rolling_comm-force">Force Update ALL</a></li>
                                    </ul>
                                </div>--><!-- /btn-group -->
                                <div class="input-group-btn">
                                    <button type="button" class="btn btn-sm btn-default dropdown-toggle platform-field-<? echo $game_platform['id']?> game_platform-update_all_button" data-class-group="rolling_comm-<?php echo $game_platform['id']?>" data-game-platform-id="<?php echo $game_platform['id']?>" <?=isset($view_only) && $view_only ? 'disabled="disabled"' : ''?>><?php echo lang('Update All')?></button>
                                </div><!-- /btn-group -->
                            </div><!-- /input-group -->


                            <span id="error-<?=$id_rolling_comm?>" class="errors"><?php echo form_error($name_rolling_comm); ?></span>
                        </td>
                        <td>
                            <div class="input-group">
                            <?php

                                $id_bet_threshold = 'game_platform-'. $game_platform['id'] ."-bet_threshold";
                                $name_bet_threshold = 'game_platforms['. $game_platform['id'] ."][bet_threshold]";

                                $bet_threshold_input = array(
                                    'type' => 'number',
                                    'id' => $id_bet_threshold,
                                    'name' => $name_bet_threshold,
                                    'class' => 'update_all_input form-control input-sm platform-field-' . $game_platform['id'] . ' game-type-field-' . $game_platform['id'] . ' update_all-bet_threshold-'.$game_platform['id'],
                                    'data-class-group' => 'bet_threshold-'.$game_platform['id'],
                                    'data-game-platform-id' => $game_platform['id'],
                                    'min' => 0,
                                    'step' => 'any',
                                );

                                if (isset($view_only) && $view_only) {
                                    $bet_threshold_input['disabled'] = 'disabled';
                                }

                                $bet_threshold_input['value'] = number_format($game_platform['all_comm_settings']['bet_threshold']);

                                if (((isset($parent_id) && $parent_id) || (isset($conditions['agent_level']) && $conditions['agent_level'] != 0)) && isset($game_platform['all_comm_settings']['max_bet_threshold'])) {
                                    $bet_threshold_input['max'] = number_format($game_platform['all_comm_settings']['max_bet_threshold']);
                                }

                                echo form_input($bet_threshold_input);

                            ?>
                                <!--<div class="input-group-btn">
                                    <button type="button" class="btn btn-default dropdown-toggle platform-field-<? echo $game_platform['id']?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" <?=isset($view_only) && $view_only ? 'disabled="disabled"' : ''?>><?php echo lang('Update')?> <span class="caret"></span></button>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                    <li><a href="#" class="game_platform-update_all_button" data-class-group="bet_threshold-<?php echo $game_platform['id']?>" data-game-platform-id="<?php echo $game_platform['id']?>" id="game_platform-<? echo $game_platform['id']?>-update_all-bet_threshold">Update All Unchanged</a></li>
                                    <li><a href="#" class="game_platform-update_all_button_force" data-class-group="bet_threshold-<?php echo $game_platform['id']?>" data-game-platform-id="<?php echo $game_platform['id']?>" id="game_platform-<? echo $game_platform['id']?>-update_all-bet_threshold-force">Force Update ALL</a></li>
                                    </ul>
                                </div>--><!-- /btn-group -->
                                <div class="input-group-btn">
                                    <button type="button" class="btn btn-sm btn-default dropdown-toggle platform-field-<? echo $game_platform['id']?> game_platform-update_all_button" data-class-group="bet_threshold-<?php echo $game_platform['id']?>" data-game-platform-id="<?php echo $game_platform['id']?>" <?=isset($view_only) && $view_only ? 'disabled="disabled"' : ''?>><?php echo lang('Update All')?></button>
                                </div><!-- /btn-group -->
                            </div><!-- /input-group -->

                            <span id="error-<?=$id_bet_threshold?>" class="errors"><?php echo form_error($name_bet_threshold); ?></span>
                        </td>
                        <td>
                            <div class="input-group">
                            <?php

                                $id_min_rolling_comm = 'game_platform-'. $game_platform['id'] ."-min_rolling_comm";
                                $name_min_rolling_comm = 'game_platforms['. $game_platform['id'] ."][min_rolling_comm]";

                                $min_rolling_comm_input = array(
                                    'type' => 'number',
                                    'id' => $id_min_rolling_comm,
                                    'name' => $name_min_rolling_comm,
                                    'class' => 'update_all_input form-control input-sm platform-field-' . $game_platform['id'] . ' game-type-field-' . $game_platform['id'] . ' update_all-min_rolling_comm-'.$game_platform['id'],
                                    'data-class-group' => 'min_rolling_comm-'.$game_platform['id'],
                                    'data-game-platform-id' => $game_platform['id'],
                                    'min' => 0,
                                    'step' => 'any',
                                );

                                if (isset($view_only) && $view_only) {
                                    $min_rolling_comm_input['disabled'] = 'disabled';
                                }

                                $min_rolling_comm_input['value'] = number_format($game_platform['all_comm_settings']['min_rolling_comm']);

                                echo form_input($min_rolling_comm_input);

                            ?>
                             <!--<div class="input-group-btn">
                                    <button type="button" class="btn btn-default dropdown-toggle platform-field-<? echo $game_platform['id']?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" <?=isset($view_only) && $view_only ? 'disabled="disabled"' : ''?>><?php echo lang('Update')?> <span class="caret"></span></button>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                    <li><a href="#" class="game_platform-update_all_button" data-class-group="min_rolling_comm-<?php echo $game_platform['id']?>" data-game-platform-id="<?php echo $game_platform['id']?>" id="game_platform-<? echo $game_platform['id']?>-update_all-min_rolling_comm">Update All Unchanged</a></li>
                                    <li><a href="#" class="game_platform-update_all_button_force" data-class-group="min_rolling_comm-<?php echo $game_platform['id']?>" data-game-platform-id="<?php echo $game_platform['id']?>" id="game_platform-<? echo $game_platform['id']?>-update_all-min_rolling_comm-force">Force Update ALL</a></li>
                                    </ul>
                                </div>--><!-- /btn-group -->
                                <div class="input-group-btn">
                                    <button type="button" class="btn btn-sm btn-default dropdown-toggle platform-field-<? echo $game_platform['id']?> game_platform-update_all_button" data-class-group="min_rolling_comm-<?php echo $game_platform['id']?>" data-game-platform-id="<?php echo $game_platform['id']?>" <?=isset($view_only) && $view_only ? 'disabled="disabled"' : ''?>><?php echo lang('Update All')?></button>
                                </div><!-- /btn-group -->
                            </div><!-- /input-group -->
                            <span id="error-<?=$id_min_rolling_comm?>" class="errors"><?php echo form_error($name_min_rolling_comm); ?></span>
                        </td>
                    </tr>


                    <?php foreach ($game_platform['game_types'] as $index => $game_type): ?>
                        <tr>
                            <td>
                                <input type="hidden" name="game_types[<?=$game_type['id']?>][game_platform_id]" value="<?=$game_platform['id']?>"/>
                            </td>
                            <td>

                            </td>
                            <td>
                                <?=lang($game_type['name'])?>
                            </td>
                            <?php if (!isset($controller_name) || $controller_name == 'agency_management'): # OGP-3607 ?>
                            <td>
                                <?php

                                    $id_platform_fee = "game_types-". $game_type['id'] ."-platform_fee";
                                    $name_platform_fee = "game_types[". $game_type['id'] ."][platform_fee]";

                                    $platform_fee_input = array(
                                        'type' => 'number',
                                        'id' => $id_platform_fee,
                                        'name' => $name_platform_fee,
                                        'class' => 'game-type-input platform_fee-'.$game_platform['id'].' form-control input-sm platform-field-' . $game_platform['id'] . ' game-type-field-' . $game_type['id'],
                                        'data-update-all-class-input' => 'update_all-platform_fee-'.$game_platform['id'],
                                        'min' => 0,
                                        'step' => 'any',
                                    );

                                    if (isset($view_only) && $view_only) {
                                        $platform_fee_input['disabled'] = 'disabled';
                                    }

                                    if (isset($conditions['game_types'][$game_type['id']]['platform_fee'])) {
                                        $platform_fee_input['value'] = number_format($conditions['game_types'][$game_type['id']]['platform_fee'], 2);
                                    } else {
                                        $platform_fee_input['value'] = '0.00';
                                    }

                                    echo form_input($platform_fee_input);

                                ?>
                                <span id="error-<?=$id_platform_fee?>" class="errors"><?php echo form_error($name_platform_fee); ?></span>
                            </td>
                            <?php endif ?>

                            <?php if (!isset($is_player) || empty($is_player)) { ?>
                            <td>
                                <?php

                                    $id_rev_share = "game_types-". $game_type['id'] ."-rev_share";
                                    $name_rev_share = "game_types[". $game_type['id'] ."][rev_share]";

                                    $rev_share_input = array(
                                        'type' => 'number',
                                        'id' => $id_rev_share,
                                        'name' => $name_rev_share,
                                        'class' => 'game-type-input rev_share-'.$game_platform['id'].' rev_share form-control input-sm platform-field-' . $game_platform['id'] . ' game-type-field-' . $game_type['id'],
                                        'data-update-all-class-input' => 'update_all-rev_share-'.$game_platform['id'],
                                        'min' => 0,
                                        'step' => 'any',
                                    );

                                    if (isset($view_only) && $view_only) {
                                        $rev_share_input['disabled'] = 'disabled';
                                    }

                                    if (isset($conditions['game_types'][$game_type['id']]['rev_share'])) {
                                        $rev_share_input['value'] = number_format($conditions['game_types'][$game_type['id']]['rev_share'], 2);
                                    } else if(isset($agent_level) && $agent_level == 0) {
                                        $rev_share_input['value'] = '100.00';
                                    } else {
                                        $rev_share_input['value'] = '0.00';
                                        #$rev_share_input['disabled'] = 'disabled';
                                    }

                                    if(isset($agent_level) && $this->utils->isEnabledFeature('set_rev_share_to_readonly_for_level_0_agent') && $agent_level == 0) {
                                        $rev_share_input['readonly'] = 'readonly';
                                    }

                                    if (((isset($parent_id) && $parent_id) || (isset($conditions['agent_level']) && $conditions['agent_level'] != 0)) && isset($conditions['game_types'][$game_type['id']]['max_rev_share'])) {
                                        $rev_share_input['max'] = $conditions['game_types'][$game_type['id']]['max_rev_share'];
                                    }

                                    echo form_input($rev_share_input);

                                ?>
                                <span id="error-<?=$id_rev_share?>" class="errors"><?php echo form_error($name_rev_share); ?></span>
                            </td>
                            <?php } ?>

                            <td>
                                <?php if ((isset($view_only) && $view_only) || (isset($parent_id) && $parent_id) || (isset($conditions['agent_level']) && $conditions['agent_level'] != 0)): ?>

                                    <?php

                                        $rolling_comm_basis_input = array(
                                            'type' => 'hidden',
                                            'class' => 'game-type-input rolling_comm_basis-'.$game_platform['id'].' platform-field-' . $game_platform['id'],
                                            'name' => 'game_types['.$game_type['id'].'][rolling_comm_basis]',
                                            'data-update-all-class-input' => 'update_all-rolling_comm_basis-'.$game_platform['id'],
                                        );

                                        if ( ! isset($conditions['game_platforms'][$game_platform['id']])) {
                                            $rolling_comm_basis_input['disabled'] = 'disabled';
                                        }

                                        if (isset($conditions['game_types'][$game_type['id']]['rolling_comm_basis'])) {
                                            $rolling_comm_basis_input['value'] = $conditions['game_types'][$game_type['id']]['rolling_comm_basis'];
                                        } else {
                                            $rolling_comm_basis_input['value'] = 'total_bets_except_tie_bets';
                                        }

                                        echo form_input($rolling_comm_basis_input);

                                        $rolling_comm_basis_input['type'] = 'text';
                                        $rolling_comm_basis_input['class'] = 'form-control input-sm';
                                        $rolling_comm_basis_input['value'] = lang($rolling_comm_basis_input['value']);
                                        $rolling_comm_basis_input['readonly'] = 'readonly';
                                        unset($rolling_comm_basis_input['name']);
                                        echo form_input($rolling_comm_basis_input);

                                    ?>

                                <?php else: ?>
                                    <select name="game_types[<?=$game_type['id']?>][rolling_comm_basis]" class="form-control input-sm platform-field-<?=$game_platform['id']?> rolling_comm_basis-<?=$game_platform['id']?>" data-update-all-class-input="update_all-rolling_comm_basis-<?=$game_platform['id']?>" <?=isset($view_only) && $view_only ? 'disabled="disabled"' : ''?>>
                                        <option value="total_bets_except_tie_bets"
                                        <?=(isset($conditions['game_types'][$game_type['id']]['rolling_comm_basis']) && $conditions['game_types'][$game_type['id']]['rolling_comm_basis'] == 'total_bets_except_tie_bets') ? 'selected':''?> >
                                        <?=lang('Bets Except Tie Bets');?>
                                        </option>
                                        <option id="basis_total_bets" value="total_bets"
                                        <?=(isset($conditions['game_types'][$game_type['id']]['rolling_comm_basis']) && $conditions['game_types'][$game_type['id']]['rolling_comm_basis'] == 'total_bets') ? 'selected':''?> >
                                        <?=lang('Total Bet');?>
                                        </option>
                                        <option id="basis_total_bets" value="winning_bets"
                                        <?=(isset($conditions['game_types'][$game_type['id']]['rolling_comm_basis']) && $conditions['game_types'][$game_type['id']]['rolling_comm_basis'] == 'winning_bets') ? 'selected':''?> >
                                        <?=lang('Winning Bets');?>
                                        </option>
                                        <option value="total_lost_bets"
                                        <?=(isset($conditions['game_types'][$game_type['id']]['rolling_comm_basis']) && $conditions['game_types'][$game_type['id']]['rolling_comm_basis'] == 'total_lost_bets') ? 'selected':''?> >
                                        <?=lang('Lost Bets');?>
                                        </option>
                                        <option value="total_lost_amount"
                                        <?=(isset($conditions['game_types'][$game_type['id']]['rolling_comm_basis']) && $conditions['game_types'][$game_type['id']]['rolling_comm_basis'] == 'total_lost_amount') ? 'selected':''?> >
                                        <?=lang('Lost Amount');?>
                                        </option>
                                    </select>
                                <?php endif ?>
                            </td>
                            <td>
                                <?php

                                    $id_rolling_comm = "game_types-". $game_type['id'] ."-rolling_comm";
                                    $name_rolling_comm = "game_types[". $game_type['id'] ."][rolling_comm]";

                                    $rolling_comm_input = array(
                                        'type' => 'number',
                                        'id' => $id_rolling_comm,
                                        'name' => $name_rolling_comm,
                                        'class' => 'game-type-input rolling_comm form-control input-sm platform-field-' . $game_platform['id'] . ' game-type-field-' . $game_type['id'] . ' rolling_comm-'.$game_platform['id'],
                                        'data-update-all-class-input' => 'update_all-rolling_comm-'.$game_platform['id'],
                                        'min' => 0,
                                        'step' => 'any',
                                    );

                                    if (isset($view_only) && $view_only) {
                                        $rolling_comm_input['disabled'] = 'disabled';
                                    }

                                    if (isset($conditions['game_types'][$game_type['id']]['rolling_comm'])) {
                                        $rolling_comm_input['value'] = number_format($conditions['game_types'][$game_type['id']]['rolling_comm'], 2);
                                    } else {
                                        $rolling_comm_input['value'] = '0.00';
                                        #$rolling_comm_input['disabled'] = 'disabled';
                                    }

                                    if (((isset($parent_id) && $parent_id) || (isset($conditions['agent_level']) && $conditions['agent_level'] != 0)) && isset($conditions['game_types'][$game_type['id']]['max_rolling_comm'])) {
                                        $rolling_comm_input['max'] = $conditions['game_types'][$game_type['id']]['max_rolling_comm'];
                                    }

                                    echo form_input($rolling_comm_input);

                                ?>
                                <span id="error-<?=$id_rolling_comm?>" class="errors"><?php echo form_error($name_rolling_comm); ?></span>
                            </td>
                            <td>
                                <?php

                                    $id_bet_threshold = "game_types-". $game_type['id'] ."-bet_threshold";
                                    $name_bet_threshold = "game_types[". $game_type['id'] ."][bet_threshold]";

                                    $bet_threshold_input = array(
                                        'type' => 'number',
                                        'id' => $id_bet_threshold,
                                        'name' => $name_bet_threshold,
                                        'class' => 'game-type-input form-control input-sm platform-field-' . $game_platform['id'] . ' game-type-field-' . $game_type['id'] . ' bet_threshold-'.$game_platform['id'],
                                        'data-update-all-class-input' => 'update_all-bet_threshold-'.$game_platform['id'],
                                        'min' => 0,
                                        'step' => 'any',
                                    );

                                    if (isset($view_only) && $view_only) {
                                        $bet_threshold_input['disabled'] = 'disabled';
                                    }

                                    if (isset($conditions['game_types'][$game_type['id']]['bet_threshold'])) {
                                        $bet_threshold_input['value'] = number_format($conditions['game_types'][$game_type['id']]['bet_threshold'], 0);
                                    } else {
                                        $bet_threshold_input['value'] = '0';
                                        #$bet_threshold_input['disabled'] = 'disabled';
                                    }

                                    if (((isset($parent_id) && $parent_id) || (isset($conditions['agent_level']) && $conditions['agent_level'] != 0)) && isset($conditions['game_types'][$game_type['id']]['max_bet_threshold'])) {
                                        $bet_threshold_input['max'] = $conditions['game_types'][$game_type['id']]['max_bet_threshold'];
                                    }

                                    echo form_input($bet_threshold_input);

                                ?>
                                <span id="error-<?=$id_bet_threshold?>" class="errors"><?php echo form_error($name_bet_threshold); ?></span>
                            </td>
                            <td>
                                <?php

                                    $id_min_rolling_comm = "game_types-". $game_type['id'] ."-min_rolling_comm";
                                    $name_min_rolling_comm = "game_types[". $game_type['id'] ."][min_rolling_comm]";

                                    $min_rolling_comm_input = array(
                                        'type' => 'number',
                                        'id' => $id_min_rolling_comm,
                                        'name' => $name_min_rolling_comm,
                                        'class' => 'game-type-input form-control input-sm platform-field-' . $game_platform['id'] . ' game-type-field-' . $game_type['id'] . ' min_rolling_comm-'.$game_platform['id'],
                                        'data-update-all-class-input' => 'update_all-min_rolling_comm-'.$game_platform['id'],
                                        'min' => 0,
                                        'step' => 'any',
                                    );

                                    if (isset($view_only) && $view_only) {
                                        $min_rolling_comm_input['disabled'] = 'disabled';
                                    }

                                    if (isset($conditions['game_types'][$game_type['id']]['min_rolling_comm'])) {
                                        $min_rolling_comm_input['value'] = number_format($conditions['game_types'][$game_type['id']]['min_rolling_comm'], 0);
                                    } else {
                                        $min_rolling_comm_input['value'] = '0';
                                    }

                                    echo form_input($min_rolling_comm_input);

                                ?>
                                <span id="error-<?=$id_min_rolling_comm?>" class="errors"><?php echo form_error($name_min_rolling_comm); ?></span>
                            </td>
                        </tr>
                    <?php endforeach ?>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<script>
$(document).ready(function(){
    var els = document.getElementsByClassName('game-platform');
    //console.log(els);
    //console.log(els.length);
    for (var i = 0; i < els.length; i++) {
        var el = els[i];
        if(! el.checked) {
            var game_platform_id = el.dataset.gamePlatformId;
            $('.platform-field-' + game_platform_id).prop('disabled', ! this.checked).trigger('change');
        }
    }

    $('.game-platform').change(function() {
        var game_platform_id = $(this).data('gamePlatformId');
        $('.platform-field-' + game_platform_id).prop('disabled', ! this.checked).trigger('change');
        if(!this.checked){
            $('.platform-field-' + game_platform_id).css("background-color","#eeeeee");
        }else{
            $('.platform-field-' + game_platform_id).css("background-color","#ffffff");
        }
    });


    $( ".game_platform-update_all_button" ).click(function(e) {
        e.preventDefault();
        var gamePlatformId = $( this ).data( "game-platform-id" );
        var classGroup = $( this ).data( "class-group" );
        var updateAllInput = $( this ).parents('td').find('.update_all_input');
        var updateAllInputVal = $(updateAllInput).val();
        console.log('gamePlatformId : ' +gamePlatformId);
        console.log('classGroup : ' +classGroup);
        console.log('updateAllInputVal : ' +updateAllInputVal);
        //set all input group to same value
        if(confirm("<?= lang('Are you sure you want to update all game type value? Click Save for the changes to take effect.')?>")){
            console.log('classGroup : ' +classGroup);
            let elements = document.getElementsByClassName(classGroup);
            for (var i = 0; i < elements.length; i++) {
                console.log(elements[i].value);
                elements[i].value=updateAllInputVal;
                $(elements[i]).css("background-color","#FFFFFF");
            }
        }

    });


    /*$( ".game_platform-update_all_button_force" ).click(function(e) {
        e.preventDefault();
        var gamePlatformId = $( this ).data( "game-platform-id" );
        var classGroup = $( this ).data( "class-group" );
        var updateAllInput = $( this ).parents('td').find('.update_all_input');
        var updateAllInputVal = $(updateAllInput).val();
        console.log('gamePlatformId : ' +gamePlatformId);
        console.log('classGroup : ' +classGroup);

        //set all input group to same value
        if(confirm("<?= lang('Are you sure you want to update all game type value?')?>")){
            console.log('classGroup : ' +classGroup);
            let elements = document.getElementsByClassName(classGroup);
            for (var i = 0; i < elements.length; i++) {
                console.log(elements[i].value);
                elements[i].value=updateAllInputVal;
            }
        }

    });*/

    $('input.game-type-input').change(function() {

        var thisInputVal = $(this).val();
        var inputAllClass = $( this ).data( "update-all-class-input" );
        var inputAllClassVal = $('.'+ inputAllClass ).val();
        console.log('thisInputVal: '+ thisInputVal);
        console.log('inputAllClassVal: '+ inputAllClassVal);
        if(thisInputVal!=inputAllClassVal){
            $(this).css("background-color","#FFDA33");
        }else{
            $(this).css("background-color","#FFFFFF");
        }

    });

    function numberFormat(num){
        return (Math.round(num * 100) / 100).toFixed(2);;
    }

    $('input.update_all_input').change(function() {
        if($(this).attr('type') == 'checkbox'){
            return;
        }
        console.log($(this).attr('type'));
        var gamePlatformId = $( this ).data( "game-platform-id" );
        var classGroup = $( this ).data( "class-group" );
        var updateAllInput = $( this ).parents('td').find('.update_all_input');
        var updateAllInputVal = numberFormat($(updateAllInput).val());

        $(this).val(updateAllInputVal)
        //console.log(updateAllInputVal);
        //console.log('classGroup: ' + classGroup);
        let elements = document.getElementsByClassName(classGroup);
        for (var i = 0; i < elements.length; i++) {
            console.log(elements[i].value);
            if(elements[i].value!=updateAllInputVal){
                $(elements[i]).css("background-color","#FFDA33");
            }else{
                $(elements[i]).css("background-color","#FFFFFF");
            }
        }
    });

    function setInputBoxColor(){

        var elements = document.getElementsByClassName('update_all_input');
        for (var i = 0; i < elements.length; i++) {
            var isDisabled = $(elements[i]).prop('disabled');
            if(!isDisabled){
                //$(elements[i]).css("background-color","#FFDA33");
                $(elements[i]).trigger("change");
            }
        }

    }

    setInputBoxColor();


});
</script>
