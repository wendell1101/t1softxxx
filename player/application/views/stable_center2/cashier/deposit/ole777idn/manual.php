
<div class="row cus-div-row">
    <div class="col-md-12 cus-div-first">
        <?php
            include __DIR__ . '/select_deposit_bank.php';
        ?>
    </div>
    <div class="col-md-7 cus-div-second">
        <?php
            include __DIR__ . '/../manual/deposit_amount.php';
            include __DIR__ . '/real_money.php';
        ?>
    </div>
    <div class="col-md-5 cus-div-third">
        <?php
            include __DIR__ . '/player_deposit_bank_account.php';
            // include __DIR__ . '/../manual/secure_id.php';
            include __DIR__ . '/../manual/deposit_realname.php';
            include __DIR__ . '/../manual/select_wallet.php';
            include __DIR__ . '/../manual/deposit_datetime.php';
            include __DIR__ . '/../manual/select_promo.php';
            include __DIR__ . '/../manual/attached_documents.php';
            include __DIR__ . '/../manual/note.php';
        ?>
    </div>
</div>

<style type="text/css">
    .deposit_btn_wrap{
        display: flex;
        align-items: center;
    }

    .deposit_btn_wrap button{
        background: orange;
        width: 170px;
        height: 40px;
        border-radius: 5px;
        margin-top: 30px;
        font-weight: 700;
    }

    .deposit_btn_wrap .deposit_warning{
        display: flex;
        align-items: center;
        background: #bfbfbf;
        width: 340px;
        border-radius: 5px;
        margin-top: 30px;
        margin-left: 35px;
    }
    .warning_icon{
        padding: 10px 20px;
    }

    .warning_text{
        padding: 10px;
            font-size: 10px;
            font-weight: 600;
    }

    #form-deposit .setup-deposit-presetamount{
        margin: 10px 0px;
    }

    #form-deposit .setup-deposit-presetamount button{
        width: 80px;
        padding: 5px;
        color: #9a9a9a;
        background: #cdcaca;
        border-radius: 5px;
        font-weight: 600;
    }

    .deposit-process-mode-2.select-payment-account .payment-account-detail{
        font-size: 14px;
    }
</style>