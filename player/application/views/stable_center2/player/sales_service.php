<?php if ($enabled_sales_agent) : ?>
<div id="sales-agent" class="panel sales-agent">
    <div class="panel-heading">
        <h1 class="hidden-xs hidden-sm"><?= lang("sales_agent.sales_service_information") ?></h1>
    </div>
    <div class="panel-body">
        <div class="clearfix sales-agent-name sales-agent-name-item" id="sales-agent-name-block">
            <div class="col-md-12" id="sales-agent-name-title">
                <p><strong><?= lang("sales_agent.name") ?> :</strong></p>
            </div>
            <div class="col-md-10">
                <p id="sales-agent-name" class="sales-agent-content"><?= $sales_agent_name ?></p>
            </div>
        </div>
        <div class="clearfix sales-agent-chat_platform1-item" id="sales-agent-chat_platform1-block">
            <div class="col-md-12" id="sales-agent-chat_platform1-title">
                <p><strong><?= lang("sales_agent.chat_platform1") ?> :</strong></p>
            </div>
            <div class="col-md-10">
                <p id="sales-agent-chat_platform1-content" class="sales-agent-content"><?= $chat_platform1 ?></p>
            </div>
             <div class="col-md-2">
                <a href="javascript:void(0)" class="btn btn-info btn-sm" onclick="return PlayerSalesAgent.pcCopyToclipboard('sales-agent-chat_platform1-content');"><?= lang('Copy') ?></a>
            </div>
        </div>
        <div class="clearfix sales-agent-chat_platform2-item" id="sales-agent-chat_platform2-block">
            <div class="col-md-12" id="sales-agent-chat_platform2-title">
                <p><strong><?= lang("sales_agent.chat_platform2") ?> :</strong></p>
            </div>
            <div class="col-md-10">
                <p id="sales-agent-chat_platform2-content" class="sales-agent-content"><?= $chat_platform2 ?></p>
            </div>
            <div class="col-md-2">
                <a href="javascript:void(0)" class="btn btn-info btn-sm" onclick="return PlayerSalesAgent.pcCopyToclipboard('sales-agent-chat_platform2-content');"><?= lang('Copy') ?></a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<style type="text/css">
    .sales-agent .sales-agent-content {
        border: 1px solid #e6e6e6;
        border-radius: 4px;
        margin-bottom: 0;
        padding: 10px 15px;
        background: #f3f3f3;
    }
</style>
<script type="text/javascript">
    const PlayerSalesAgent = {
        msg_success_copy: "",
        pcCopyToclipboard: function (id) {
            this.copyToClipboard(id);
            MessageBox.success(this.msg_success_copy);
        },
        copyToClipboard: function (id) {
            var copyElement = document.getElementById(id);
            var range = document.createRange();
            range.selectNode(copyElement);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(range);
            document.execCommand('copy');
        }
    }

    $(function() {
        PlayerSalesAgent.msg_success_copy = '<?= lang('Successfully copied to clipboard') ?>';
    });
</script>