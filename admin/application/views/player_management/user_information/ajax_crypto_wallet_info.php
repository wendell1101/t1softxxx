<div role="tabpanel" class="tab-pane active" id="cryptoWalletInfo">
    <div id="crypto-wallet-info-table-tab" class="tab-pane panel panel-default fade in active">
        <table class="table table-hover table-bordered table-condensed" id="crypto-wallet-info-table">
            <thead>
                <?php if ($this->permissions->checkPermissions('generate_player_crypto_wallet_address')):?>
                    <tr>
                        <th colspan="3">
                            <div class="pull-right">
                                <a href="/player_management/generateCryptoWalletAddress/<?=$player['playerId'];?>" class="btn btn-scooter btn-xs" onclick="return confirm('<?=lang("init.crypto.wallet.confirm.refresh")?>');">
                                    <i class="fa fa-refresh"></i> <?=lang('lang.refresh')?>
                                </a>
                            </div>    
                        </th>
                    </tr>
                <?php endif; ?>
                <tr>
                    <th class="col-md-2" ><?=lang('crypto_chain');?></th>
                    <!-- <th class="col-md-2" ><?=lang('financial_account.crypto_network')?></th> -->
                    <th class="col-md-8" ><?=lang('crypto_address')?></th>
                </tr>                            
            </thead>
            <tbody>
                <?php if (empty($playerCryptoWallets)): ?>
                    <tr>
                        <td colspan="3" align="center"><?=lang('No data available in table')?></td>
                    </tr>
                <?php else :?>
                    <?php foreach ($playerCryptoWallets as $info): ?>
                        <tr>
                            <td> <?=lang($info['chain'])?> </td>
                            <!-- <td> <?=$info['network']?> </td> -->
                            <td> <?=$info['address']?> </td>
                        </tr>
                    <?php endforeach?>
                <?php endif?>
            </tbody>
        </table>
    </div>
</div>