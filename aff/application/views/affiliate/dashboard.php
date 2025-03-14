<style>
    .modal {
        text-align: center;
        padding: 0!important;
    }
    .modal:before {
        content: '';
        display: inline-block;
        height: 100%;
        vertical-align: middle;
        margin-right: -4px;
    }
    .modal-dialog {
        display: inline-block;
    }

    .modal-dialog {
        text-align: left;
        vertical-align: middle;
    }
</style>
<style>
        td.copy_bounce, p.holder {
            font-family: sans-serif;
            font-size: 14px;
            margin: 0;
        }
        td.copy_bounce {
            color: #333;
            transition: all .5s;
            text-align: center;
            position: relative;
            min-width: 100px;
            cursor: pointer;
        }
        td.copy_bounce:hover {
            color: #15AABF;
        }

        p.holder {
            position: absolute;
            white-space: nowrap;
            right: 0;
            left: 0;
            margin: 0 auto;
            display: none;
        }

        p.holder span {
            color: #fff;
            padding: 5px 7px;
            background: #333;
            border-radius: 4px;
            font-size: 12px;
        }

        td.copy_bounce:hover p.holder {
            display: block;
        }

        p.holder::before {
            content: '';
            position: absolute;
            z-index: 0;
            top: -7px;
            right: 0;
            left: 0;
            width: 0;
            height: 0;
            border-left: 10px solid transparent;
            border-right: 10px solid transparent;
            border-bottom: 10px solid #333;
            margin: 0 auto;
        }

        .slide-bottom {
            -webkit-animation: slide-bottom 0.3s cubic-bezier(0.250, 0.460, 0.450, 0.940) both;
                    animation: slide-bottom 0.3s cubic-bezier(0.250, 0.460, 0.450, 0.940) both;
        }

        @-webkit-keyframes slide-bottom {
        0% {
            -webkit-transform: translateY(0);
                    transform: translateY(0);
        }
        100% {
            -webkit-transform: translateY(10px);
                    transform: translateY(10px);
        }
        }
        @keyframes slide-bottom {
        0% {
            -webkit-transform: translateY(0);
                    transform: translateY(0);
        }
        100% {
            -webkit-transform: translateY(10px);
                    transform: translateY(10px);
        }
        }

        .bounce-top {
            -webkit-animation: bounce-top 0.9s both;
                    animation: bounce-top 0.9s both;
        }

        @-webkit-keyframes bounce-top {
        0% {
            -webkit-transform: translateY(-20px);
                    transform: translateY(-20px);
            -webkit-animation-timing-function: ease-in;
                    animation-timing-function: ease-in;
            opacity: 1;
        }
        24% {
            opacity: 1;
        }
        40% {
            -webkit-transform: translateY(-10px);
                    transform: translateY(-10px);
            -webkit-animation-timing-function: ease-in;
                    animation-timing-function: ease-in;
        }
        65% {
            -webkit-transform: translateY(-5px);
                    transform: translateY(-5px);
            -webkit-animation-timing-function: ease-in;
                    animation-timing-function: ease-in;
        }
        82% {
            -webkit-transform: translateY(-2px);
                    transform: translateY(-2px);
            -webkit-animation-timing-function: ease-in;
                    animation-timing-function: ease-in;
        }
        93% {
            -webkit-transform: translateY(-1px);
                    transform: translateY(-1px);
            -webkit-animation-timing-function: ease-in;
                    animation-timing-function: ease-in;
        }
        25%,
        55%,
        75%,
        87% {
            -webkit-transform: translateY(0px);
                    transform: translateY(0px);
            -webkit-animation-timing-function: ease-out;
                    animation-timing-function: ease-out;
        }
        100% {
            -webkit-transform: translateY(0px);
                    transform: translateY(0px);
            -webkit-animation-timing-function: ease-out;
                    animation-timing-function: ease-out;
            opacity: 1;
        }
        }
        @keyframes bounce-top {
        0% {
            -webkit-transform: translateY(-20px);
                    transform: translateY(-20px);
            -webkit-animation-timing-function: ease-in;
                    animation-timing-function: ease-in;
            opacity: 1;
        }
        24% {
            opacity: 1;
        }
        40% {
            -webkit-transform: translateY(-10px);
                    transform: translateY(-10px);
            -webkit-animation-timing-function: ease-in;
                    animation-timing-function: ease-in;
        }
        65% {
            -webkit-transform: translateY(-5px);
                    transform: translateY(-5px);
            -webkit-animation-timing-function: ease-in;
                    animation-timing-function: ease-in;
        }
        82% {
            -webkit-transform: translateY(-2px);
                    transform: translateY(-2px);
            -webkit-animation-timing-function: ease-in;
                    animation-timing-function: ease-in;
        }
        93% {
            -webkit-transform: translateY(-1px);
                    transform: translateY(-1px);
            -webkit-animation-timing-function: ease-in;
                    animation-timing-function: ease-in;
        }
        25%,
        55%,
        75%,
        87% {
            -webkit-transform: translateY(0px);
                    transform: translateY(0px);
            -webkit-animation-timing-function: ease-out;
                    animation-timing-function: ease-out;
        }
        100% {
            -webkit-transform: translateY(0px);
                    transform: translateY(0px);
            -webkit-animation-timing-function: ease-out;
                    animation-timing-function: ease-out;
            opacity: 1;
        }
        }


    </style>
    <style>
        .tracking_qrcode_96 {
            width: 120px;
            height: 120px;
            padding: 11px;
            margin-left: 10px;
            border: 1px solid rgba(0,0,0,.1);
            border-radius: 6px;
        }
        .tracking_qrcode_200 {
            width: 242px;
            height: 242px;
            padding: 20px;
            /*margin-left: 10px;*/
            margin: 20px auto;
            border: 1px solid rgba(0,0,0,.1);
            border-radius: 6px;
        }
        table.dataTable thead .sorting:after,
        table.dataTable thead .sorting_asc:after,
        table.dataTable thead .sorting_desc:after {
            content: none;
        }
        .tr_link_row {
            border: 1px solid rgba(0, 0, 0, .1);
        }
        .tr_link_row td {
            height: 128px;
        }
        .tr_link_row td.tr_link {
            min-width: 160px;
            /*white-space: nowrap;*/
        }
        .tr_link_row td.tr_link_qrcode_frame {
            width: 128px;
            padding-top: 18px;
        }
        .tr_link_row td div.vert_middle {
            margin: 35px 0;
        }
        .tr_link .tr_title, .tl_horiz .tr_title {
            font-weight: bold;
        }
        .btn.tr_link_go {
            width: 100%;
            margin: 10px 0;
        }

        .tl_horiz .tl_horiz_frame {
            padding: 0 43px;
        }

        .tl_horiz .tl_item_frame {
            border: 1px solid rgb(0, 0, 0, .1);
        }
        .tl_horiz .tl_url {
            text-overflow: ellipsis;
            overflow-x: hidden;
        }
        .tl_horiz .tl_text_frame {
            padding: 0 15px 15px 15px;
        }
        .tl_horiz .tl_footer {
            padding: 12px;
        }


        .table-striped > tbody > tr {
            background-color: #fefefe;
        }
    </style>

<div class="container">
    <div class="row">
        <?php if($this->utils->isEnabledFeature('enable_move_up_dashboard_statistic_in_affiliate_backoffice')) {
            /**
             * enable_new_dashboard_statistics: ENDS, calculate_affiliate_dashboard_by_cronjob: CADC
             * ENDS off:            Use the oldest non-ajax dashboard
             * ENDS on, CADC off:   Use older ajax-loaded dashboard
             * ENDS on, CADC on:    Use ajax-loaded, cronjob-calculated dashboard
             */
            if( !$this->utils->isEnabledFeature('enable_new_dashboard_statistics') ){
                $this->load->view('affiliate/dashboard_stats');
            }else{
                if ($this->utils->getConfig('calculate_affiliate_dashboard_by_cronjob')) {
                    $this->load->view('affiliate/dashboard_stats_new_2');
                }
                else {
                    $this->load->view('affiliate/dashboard_stats_new');
                }
            }
        } else {
            echo '<div class="clearfix"></div>';
        }?>

        <?php if (!empty($affiliate['trackingCode'])) {?>
            <?php if($this->utils->getConfig('editable_tracking_code_on_aff')){ ?>
            <form action="<?=site_url('affiliate/createCode/' . $affiliate['affiliateId'])?>" method="POST" class="form-horizontal">
                <div class="col-md-6 col-md-offset-3">
                    <div class="form-group col-xs-5">
                        <label for="tracking_code" class="control-label" style="text-align:right;"><?=lang('aff.ai40'); //lang('aff.ai37');?> </label>
                        <div>
                            <input type="text" name="tracking_code" id="tracking_code" class="form-control <?=$this->utils->isEnabledFeature('affiliate_tracking_code_numbers_only') ? 'number_only' : ''?>" minlength="5" maxlength="8" value="<?=(empty($affiliate['trackingCode'])) ? 'n/a' : $affiliate['trackingCode']?>"/>
                            <?php echo form_error('tracking_code', '<span style="color:#CB3838;">'); ?>
                        </div>
                    </div>
                    <div class="btn-group col-xs-7" role="group" aria-label="..." style="margin-top: 27px;">
                        <a href="#randomCode" class="btn btn-info hidden-xs" id="random_code" onclick="randomCode('8');"/> <?=lang('aff.ai38');?> </a>
                        <input type="submit" class="btn btn-primary" value="<?=lang('aff.ai39');?>"/>
                    </div>
                </div>

                <div class="clearfix"></div>
                <script>
                function randomCode(len)
                {
                    var text = '';

                    <?php if ($this->utils->isEnabledFeature('affiliate_tracking_code_numbers_only')): ?>
                        var charset = "1234567890";
                    <?php else: ?>
                        var charset = "abcdefghijklmnopqrstuvwxyz0123456789";
                    <?php endif ?>

                    for( var i=0; i < len; i++ ) {
                        text += charset.charAt(Math.floor(Math.random() * charset.length));
                    }

                    $('#tracking_code').val(text);
                }
                </script>
            </form>
            <?php }else{?>
            <!-- <div  col-md-offset-3"> -->
            <div class="col-md-6" style="">
                <label class="control-label" style="text-align:right;margin-top: 1%;background-color: #9a9a9a;color:#000;"><?php echo lang('aff.ai40'); ?> <?php echo (empty($affiliate['trackingCode'])) ? 'n/a' : $affiliate['trackingCode']; ?></label>
            </div><br>
            <div class="col-md-6">
                <div class="tl-list-mode pull-right">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-default btn-sm active tl-mode-list" data-mode="list">
                            <i class="fa fa-list"></i>
                        </button>
                        <button type="button" class="btn btn-light btn-sm tl-mode-horiz" data-mode="horiz">
                            <i class="fa fa-th-large"></i>
                        </button>
                    </div>
                </div>
            </div>
            <!-- </div> -->
            <?php }?>
        <div class="col-md-12">
            <table class="table table-striped table-hover" id="trackingTable" style="width:100%;">
                <thead>
<!--                     <th></th>
                    <th class="input-sm">#</th>
                    <th class="input-sm"><?=lang('mod.url');?></th>
                    <th class="input-sm"></th>
                    <th class="input-sm"><?=lang('mod.updateDate');?></th>
                    <th class="input-sm"><?=lang('lang.status');?></th> -->
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </thead>

                <tbody>
                    <?php
$cnt = 0;

// foreach ($domain as $key => $domain_value) {
foreach ($tracking_links as $key => $domain_value) {
$cnt++;
?>
                            <?php /*
                             <tr>
                                <td></td>
                                <td class="input-sm"><?=$cnt?></td>
                                <td data-type="aff" class="input-sm copy_bounce">
                                    <p data-type="copy_aff"><?=$domain_value['domainName'] . '/aff/' . $affiliate['trackingCode']?></p>
                                    <p class="holder slide-bottom"><span><?= lang('Copy link') ?></span></p>
                                </td>
                                <td class="input-sm copy_bounce">
                                    <p data-type="copy_aff"><?=$domain_value['domainName'] . '/aff.html?code=' . $affiliate['trackingCode']?></p>
                                    <p class="holder slide-bottom"><span><?= lang('Copy link') ?></span></p>
                                </td>
                                <!--td class="input-sm"><?=$domain_value['domainName'] . $player_register_uri . '?aff=' . $affiliate['trackingCode']?></td-->
                                <td class="input-sm"><?=$domain_value['updatedOn']?></td>
                                <td class="input-sm"><?=($domain_value['status'] == 0) ? lang('lang.active') : lang('Blocked')?></td>
                            </tr>
                            */ ?>
                            <tr class="tr_link_row">
                                <td style="display: none"></td>
                                <td class="tr_link_qrcode_frame">
                                    <div class="tracking_qrcode_96 <?= $domain_value['tr_qrid'] ?>"></div>
                                </td>
                                <td class="tr_link">
                                    <div class="vert_middle">
                                        <p class="tr_title"><?= lang('Tracking Link') ?></p>
                                        <p data-type="copy_aff" title="<?= lang('Copy link') ?>"><?= $domain_value['tracking_link'] ?></p>
                                        <p class="holder slide-bottom"><span><?= lang('Copy link') ?></span></p>
                                    </div>
                                </td>
                                <td class="tr_link">
                                    <div class="vert_middle">
                                        <p class="tr_title"><?= lang('mod.updateDate'); ?></p>
                                        <p><?=$domain_value['updatedOn']?></p>
                                    </div>
                                </td>
                                <td class="tr_link">
                                    <div class="vert_middle">
                                        <p class="tr_title"><?= lang('lang.status') ?></p>
                                        <p>
                                        <?php if ($domain_value['status'] == 0) : ?>
                                            <a class="btn btn-xs btn-success" href="javascript: void(0);"><?= lang('lang.active') ?></a>
                                        <?php else : ?>
                                            <a class="btn btn-xs btn-danger" href="javascript: void(0);"><?= lang('Blocked') ?></a>
                                        <?php endif; ?>
                                        </p>
                                    </div>
                                </td>
                                <td class="tr_link" style="width: 130px">
                                    <div class="vert_middle">
                                        <a class="btn btn-info btn-md tr_link_go" href="<?= $domain_value['tracking_link'] ?>" target="_blank">
                                            <?= lang('Go to link') ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>


                    <?php
}
?>
                </tbody>
            </table>
        </div>

        <div class="tl_horiz" style="display: none;">
            <div class="row">
                <div class="col-md-12 tl_horiz_frame">
                    <div class="row">
                        <?php $i = 0; ?>
                        <?php foreach ($tracking_links as $key => $domain_value) : ?>
                        <?php $i++; ?>
                            <div class="col-md-3 tl_item_frame">
                                <div class="tracking_qrcode_200 <?= $domain_value['tr_qrid'] ?>"></div>
                                <div class="tl_text_frame">
                                    <!-- tracking link -->
                                    <p class="tr_title"><?= lang('Tracking Link') ?></p>

                                    <p class="tl_url" data-type="copy_aff" title="<?= lang('Copy link') ?>"><?= $domain_value['tracking_link'] ?></p>
                                    <p class="holder slide-bottom"><span><?= lang('Copy link') ?></span></p>
                                    <!-- update date -->
                                    <p class="tr_title"><?= lang('mod.updateDate'); ?></p>
                                    <p><?=$domain_value['updatedOn']?></p>

                                    <!-- status -->
                                    <p class="tr_title"><?= lang('lang.status') ?>

                                    <?php if ($domain_value['status'] == 0) : ?>
                                        <a class="btn btn-xs btn-success" href="javascript: void(0);"><?= lang('lang.active') ?></a>
                                    <?php else : ?>
                                        <a class="btn btn-xs btn-danger" href="javascript: void(0);"><?= lang('Blocked') ?></a>
                                    <?php endif; ?>
                                    </p>
                                    <!-- go to link button -->
                                    <a class="btn btn-info btn-md tr_link_go" href="<?= $domain_value['tracking_link'] ?>" target="_blank">
                                                <?= lang('Go to link') ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="tl_footer">
                        <?= sprintf(lang('aff.list_footer'), 1, count($tracking_links), count($tracking_links)) ?>
                    </div>
                </div>
            </div>
        </div>
            <?php if($this->utils->isEnabledFeature("affiliate_source_code")){?>
                <p>&nbsp;</p>
                <div class="col-md-12 well">
                    <table class="table table-striped table-hover">
                        <thead>
                            <th><?php echo lang('Affiliate Source Code');?></th>
							<th><?php echo lang('Link Example');?></th>
                        </thead>
                        <tbody>
                        <?php if( ! empty($aff_source_code_list) ): ?>
                            <?php foreach($aff_source_code_list as $source_code){?>
                                <tr>
                                    <td><?php echo $source_code['tracking_source_code']; ?></td>
                                    <td><?php echo !empty($first_domain) ? $first_domain.'/aff/'.$affiliate['trackingCode'].'/'.$source_code['tracking_source_code'] : ""; ?><br>
                                    <?php echo !empty($first_domain) ? $first_domain.'/aff.html?code='.$affiliate['trackingCode'].'&source='.$source_code['tracking_source_code'] : ""; ?></td>
                                </tr>
                            <?php }?>
                        <?php else: // Else for if( ! empty($aff_source_code_list) ):... ?>
                            <tr>
                                <td><?=lang('N/A')?></td>
                                <td><?=lang('N/A')?></td>
                            </tr>
                        <?php endif; // EOF if( ! empty($aff_source_code_list) ):... ?>
                        </tbody>
                    </table>
                </div>

            <?php }?>
        <?php }?>

        <div class="col-md-12 well" style="overflow: auto;">
            <table class="table table-striped">
                <thead>
                    <th><?=lang('lang.affdomain');?></th>
                </thead>
                <tbody>
                    <tr>
                        <td><?=empty($affiliate['affdomain']) ? lang('lang.norecyet') : $affiliate['affdomain'];?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php if($this->utils->isEnabledFeature('affiliate_additional_domain')){?>
        <div class="col-md-12 well" style="overflow: auto;">
            <table class="table table-striped">
                <thead>
                    <th><?php echo lang('Affiliate Additional Domain');?></th>
                </thead>
                <tbody>
                <?php
                if($aff_additional_domain_list){
                    foreach($aff_additional_domain_list as $add_domain){?>
                        <tr>
                            <td><a href="http://<?php echo $add_domain['tracking_domain']; ?>" target='_blank'><?php echo $add_domain['tracking_domain']; ?></a></td>
                        </tr>
                    <?php
                    }
                }else{
                ?>
                    <tr><td><?php echo lang('N/A');?></td></tr>
                <?php }?>
                </tbody>
            </table>
        </div>
        <?php }?>

        <?php if( ($commonSettings['manual_open'] || $commonSettings['sub_link']) && !empty($sublink) && !empty($affiliate['trackingCode'])){ ?>
        <div class="col-md-12 well" style="overflow: auto;">
            <table class="table table-striped">
                <thead>
                    <th><?=lang('aff.asb11');?></th>
                </thead>

                <tbody>
                    <tr>
                        <td id="sublink"><?=$sublink;?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php }?>
    </div>

    <?php if( !$this->utils->isEnabledFeature('enable_move_up_dashboard_statistic_in_affiliate_backoffice') ) {
        /**
         * enable_new_dashboard_statistics: ENDS, calculate_affiliate_dashboard_by_cronjob: CADC
         * ENDS off:            Use the oldest non-ajax dashboard
         * ENDS on, CADC off:   Use older ajax-loaded dashboard
         * ENDS on, CADC on:    Use ajax-loaded, cronjob-calculated dashboard
         */
        if( !$this->utils->isEnabledFeature('enable_new_dashboard_statistics') ){
            $this->load->view('affiliate/dashboard_stats');
        }else{
            if ($this->utils->getConfig('calculate_affiliate_dashboard_by_cronjob')) {
                $this->load->view('affiliate/dashboard_stats_new_2');
            }
            else {
                $this->load->view('affiliate/dashboard_stats_new');
            }
        }
    }else{
        echo '<div class="clearfix"></div>';
    } ?>

</div>

<div class="modal fade" id="popup-notify-register-bankcard" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?= lang('Notifications') ?></h4>
            </div>
            <div class="modal-body">
                <p><?= lang('Hi honor affiliate, thanks for your login! Kindly bind your bank account for withdrawal!') ?></p>
            </div>
            <div class="modal-footer">
                <a href="/affiliate/modifyAccount#back-info" class="btn btn-primary"><?= lang('Bind the Bank Account') ?></a>
            </div>
        </div>
    </div>
</div>
<script>
$(function(){
    var isActive = "<? echo $isActive?>";
    if (isActive>0) {
        $("#sublink").show();
        $('#sublink').text("<?php echo $sublink;?>");
    }else {
        $("#sublink").hide();
        $('#sublink').text("");
    }

    $('#trackingTable').DataTable({
        "columnDefs": [ {
            // className: 'control',
            orderable: false,
            targets:   [ 0, 1, 2, 3, 4, 5 ]
        } ],
        paging: false ,
        searching: false
        // "order": [ 1, 'asc' ]
    } );

    let backcard_exists = <?= $bank_card_exists ?>,
        is_login_behavior = <?= $is_login_behavior ?>

    if (backcard_exists == 0 && is_login_behavior == 1) {
        $("#popup-notify-register-bankcard").modal('show')
    }

    setInterval(function(){
        $('p[data-type="copy_aff"').removeClass('bounce-top');
        $('.holder').find('span').text('<?= lang('Copy link') ?>');
    }, 3000);
});

    document.querySelectorAll('p[data-type="copy_aff"]')
        .forEach(function(button){
            button.addEventListener('click', function(){
                $(this).addClass('bounce-top');
                $(this).next('p').find('span').text('Copied!!');

                let aff = this.innerText;

                let tmp = document.createElement('textarea');
                    tmp.value = aff;
                    tmp.setAttribute('readonly', '');
                    tmp.style.position = 'absolute';
                    tmp.style.left = '-9999px';

                document.body.appendChild(tmp);
                tmp.select();

                document.execCommand('copy');
                document.body.removeChild(tmp);
                console.log(`${aff} copied.`);
            });
        });
</script>
<script>
    $(function () {
        var tracking_links_clean = <?= json_encode($tracking_links_clean) ?>;
        for (var i in tracking_links_clean) {
            var trlink = tracking_links_clean[i];
            $('.tracking_qrcode_96.' + trlink.tr_qrid).qrcode({width: 96, height: 96, text: trlink.url});
            $('.tracking_qrcode_200.' + trlink.tr_qrid).qrcode({width: 200, height: 200, text: trlink.url});
        }

        $('.tl-list-mode button').click(function () {
            var mode = $(this).data('mode');
            console.log('mode', mode);
            $(this).removeClass('btn-light btn-default');
            $(this).siblings('button').removeClass('btn-light btn-default');
            $(this).addClass('btn-default');
            $(this).siblings('button').addClass('btn-light');
            if (mode == 'list') {
                $('.tl_horiz').hide();
                $('#trackingTable_wrapper').show();
            }
            else {
                $('#trackingTable_wrapper').hide();
                $('.tl_horiz').show();
            }
        });

    });


</script>