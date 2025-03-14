<style type="text/css">
    .static_hint{
        color: #fff;
        text-align:center;
    }
    .cus_nuumber{
        font-size: 20px;
    }
    .cus_background {
        background: #8e0d1f;
        width: 80%;
    }
</style>
<div class="panel panel-default">
    <div class="panel-heading" style="margin-top: 0">
    	<center>
        	<h4><?=lang('Deposit Info')?></h4>
    		<hr>
            <div class="cus_bank_amt">
                <p class="cus_text_style"><?= lang('lang.amount'); ?></p>
                <span class="cus_nuumber"><?= lang($staticData['Amount']); ?></span>
                <span class="cus_currency"><?= lang('THB'); ?></span>
            </div>
            <br>
            <div class="cus_background"><span class="static_hint"><?=lang('pay.collection_account.static_hint')?></span></div>
    		<hr>
            <div class="cus_bank_acc">
                <p class="cus_text_style"><?= lang('Account No.'); ?></p>
                <p class="cus_nuumber" id="acc_btn"><?= lang($staticData['account no']); ?>
                    <button class="btn-copy btn btn-info" data-clipboard-action="copy" data-clipboard-target="#acc_btn" ><?=lang('Copy')?></button>
                </p>
            </div>
            <br>
            <div class="cus_bank_name">
                <p class="cus_text_style"><?= lang($staticData['account name']); ?></p>
                <p class="cus_text_style"><?= lang($staticData['bank name']) . '(' .lang($staticData['bank code']) . ')' ; ?></p>
            </div>
        </center>
        <?php if(!$is_not_display_recharge_instructions) : ?>
            <hr>
            <div class="notes-red">
                <center>
                    <strong><?= isset($collection_text_transfer['title'])? $collection_text_transfer['title'] :lang('cms.notes.1').':';?></strong><br>
                    <?php
                        if(!empty($collection_text_transfer) && isset($collection_text_transfer)) {
                            foreach ($collection_text_transfer as $key => $val){
                                if(preg_match("/hide/i", $key) || preg_match("/title/i", $key)){
                                    if (!preg_match("/title/i", $key)){
                                        echo $val."<br>";
                                        continue;
                                    }
                                }else{
                                    echo $key.$val."<br>";
                                }
                            }
                        } else {
                            echo lang('collection.text.transfer.1')."<br>";
                            echo lang('collection.text.transfer.2')."<br>";
                            echo lang('collection.text.transfer.3');
                        }
                    ?>
                </center>
            </div>
        <?php endif;?>
	</div>
</div>