<style type="text/css">
    table.game-history-content {
        text-align: center;
    }
    table.game-history-content th {
        text-align: center;
    }
    table.game-history-content>thead>tr>th {
        border-bottom: 1px solid #000;
    }
    .box-border-bottom {
        border-bottom: 1px #000 solid;
    }
    table.game-history-content tbody tr td.vcenter {
        vertical-align: middle!important;
    }
    table.game-history-content tbody tr td.game-code-list {
        background: #e6f3fa;
        border-bottom: 1px #000 solid;
    }
    .game-b-b {
        border-bottom: 1px #000 solid;
    }
    @media (min-width: 768px) {
        .modal-xl {
            width: 90%;
        }
    }
</style>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h4 class="panel-title"><?=lang('Batch Add Iovation Evidence')?></h4>
            </div>
            <div class="panel-body">
                <?php if (!empty($message)): ?>
                    <div class="text-danger">
                        <?=$message?>
                    </div>
                <?php endif ?>
                
                <h4 class="text-info"><strong><?=lang('Total Added Tags')?>: <?=$countAdded;?></strong></h4>
                <div class="table-responsive">
                    
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th><?=lang('#')?></th> 
                               <th><?=lang('Applied To')?></th> 
                               <th><?=lang('Device ID')?></th> 
                               <th><?=lang('Username')?></th> 
                               <th><?=lang('Usertype')?></th> 
                               <th><?=lang('Evidence Type')?></th> 
                               <th><?=lang('Comments')?></th> 
                               <th><?=lang('Status')?></th>  
                               <th><?=lang('Message')?></th> 
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $i=0;
                            ?>
                            <?php foreach ($processed_evidence as $evidence) { ?>
                                <tr>
                                    <td><?= ++$i ?></td>
                                    <td><?= isset($evidence['applied_to']) ? $evidence['applied_to'] : '???' ?></td>
                                    <td><?= isset($evidence['device_id']) ? $evidence['device_id'] : '???' ?></td>
                                    <td><?= isset($evidence['username']) ? $evidence['username'] : '???' ?></td>
                                    <td><?= isset($evidence['user_type']) ? $evidence['user_type'] : '???' ?></td>
                                    <td><?= isset($evidence['evidence_type']) ? $evidence['evidence_type'] : '???' ?> : <?= isset($evidence['evidence_type_desc']) ? $evidence['evidence_type_desc'] : '???' ?></td>
                                    <td><?= isset($evidence['comments']) ? $evidence['comments'] : '???' ?></td>
                                    <td><?= isset($evidence['is_success']) && $evidence['is_success']==true ? 'Success' : 'Failed' ?></td>
                                    <td><?= isset($evidence['message']) ? $evidence['message'] : '???' ?></td>
                                </tr>
                            <?php } ?>                            
                        </tbody>
                    </table>
                </div>

                <a href="<?= base_url('report_management/viewIovationEvidence') ?>" class="btn btn-primary center"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span><?= lang('Return') ?></a>
           </div>
       </div>
   </div>
</div>
<script type="text/javascript" src="<?=site_url().'resources/datatables/dataTables.buttons.min.js'?>"></script>
<script type="text/javascript" src="<?=site_url().'resources/datatables/jszip.min.js'?>"></script>
<script type="text/javascript" src="<?=site_url().'resources/datatables/buttons.html5.min.js'?>"></script>
