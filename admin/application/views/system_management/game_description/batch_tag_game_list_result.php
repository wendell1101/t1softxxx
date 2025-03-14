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
                <h4 class="panel-title"><?=lang('Batch Update Games')?></h4>
            </div>
            <div class="panel-body">
                <?php if (!empty($message)): ?>
                    <div class="text-danger">
                        <?=$message?>
                    </div>
                <?php endif ?>
                
                <h4 class="text-info">                    
                    <strong><?=lang('Total Inserted')?>: <?=$countInserted;?></strong><br>
                    <strong><?=lang('Total Updated')?>: <?=$countUpdated;?></strong><br>
                    <strong><?=lang('Total Removed')?>: <?=$countRemoved;?></strong><br>
                </h4>
                <div class="table-responsive">
                    
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th><?=lang('#')?></th> 
                               <th><?=lang('Game ID')?></th> 
                               <th><?=lang('Game name')?></th> 
                               <th><?=lang('Game Code')?></th> 
                               <th><?=lang('Tag Code')?></th> 
                               <th><?=lang('Tag Game Order')?></th> 
                               <th><?=lang('Status')?></th> 
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $i=0;
                            ?>
                            <?php foreach ($tagged_games as $updatedGame) { ?>
                                
                                <?php
                                    $status='-';
                                    $order = $updatedGame['game_order'];                                    
                                ?>
                                <tr>
                                    <td><?= ++$i ?></td>
                                    <td><?= isset($updatedGame['game_description_id']) ? $updatedGame['game_description_id'] : '???' ?></td>
                                    <td><?= isset($updatedGame['game_name']) ? $updatedGame['game_name'] : '???' ?></td>
                                    <td><?= isset($updatedGame['game_code']) ? $updatedGame['game_code'] : '???' ?></td>
                                    <td><?= isset($updatedGame['tag_code']) ? $updatedGame['tag_code'] : '???' ?></td>
                                    <td><?= $order ?></td>
                                    <td>
                                    <?php if(isset($updatedGame['message'])&&is_array($updatedGame['message'])){ ?>
                                        <?php foreach ($updatedGame['message'] as $msg) { ?>
                                            <?= $msg ?><br>
                                        <?php } ?>
                                    <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>                            
                        </tbody>
                    </table>
                </div>

                <a href="<?= base_url('game_description/viewGameDescription') ?>" class="btn btn-primary center"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span><?= lang('Return') ?></a>
           </div>
       </div>
   </div>
</div>
<script type="text/javascript" src="<?=site_url().'resources/datatables/dataTables.buttons.min.js'?>"></script>
<script type="text/javascript" src="<?=site_url().'resources/datatables/jszip.min.js'?>"></script>
<script type="text/javascript" src="<?=site_url().'resources/datatables/buttons.html5.min.js'?>"></script>
<script type="text/javascript">
    $("#collapseSubmenuGameDescription").addClass("in");
    $("a#view_game_description").addClass("active");
    $("a#viewGameListSettings").addClass("active");
    $("#myTable").DataTable({
         dom: "<'panel-body' <'pull-right'B><'pull-right'f><'pull-right progress-container'>l>" +
            "<'dt-information-summary1 text-info pull-left' i>t<'text-center'r>" +
            "<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
        buttons: [
            {
                extend: 'csvHtml5',  // csvHtml5 , copyHtml5, excelHtml5
                exportOptions: {
                    columns: ':visible'
                },
                className:'btn btn-sm btn-primary',
                text: '<?=lang('CSV Export')?>',
                filename:  '<?=lang('Active Player Report')?>'
            }
        ]
    });
</script>
