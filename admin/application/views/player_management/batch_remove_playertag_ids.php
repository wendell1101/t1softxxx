<div class="container">
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h4 class="panel-title"><span style="font-weight: bold;"><?php echo lang('Batch Remove Player Tags Result');?></span></h4>
        </div>
        <div class="panel-body">            
            <div style="height:20px;">
               <div class="progress" id="progress" >
                <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="05" aria-valuemin="1" aria-valuemax="100" >
                    <span class="progresslabel"></span> 
                </div>
            </div>
        </div>
        <div style="height:20px;">
            <span class="text-danger small" style="font-weight: bold;height:12px;" id="loader2"  ><i><?php echo lang('Processing please wait...')?></i></span>
        </div>   
        <div id="progress-details" class="alert alert-info" >
            <?=lang('Processed rows') ?>: <strong id="processedRows">0</strong>  <?=lang('Total Rows') ?>: <strong id="totalRows">0</strong>
            <?=lang('Status') ?>: <strong id="processStatus"><?php echo lang('Processing')?></strong>
        </div>
        <table class="table table-condensed">
            <tbody>
                <tr>
                    <td><?=lang('Total Players') ?></td>
                    <td><strong><span class="text-info" id="totalPlayers">0</span></strong></td>
                </tr>
                <tr>
                    <td><?=lang('Success Count') ?></td>
                    <td><strong><span class="text-info" id="successCount">0</span></strong></td>
                </tr>
                <tr>
                    <td><?=lang('Failed Count') ?></td>
                    <td><strong><span class="text-info" id="failedCount">0</span></strong></td>
                </tr>                
            </tbody>
        </table>



        <div class="row download_link" style="display:none;">
            <div class="col-md-12">
                <?php echo lang('Download Link is Ready'); ?>: <a href="" class="download_url"></a>
            </div>
        </div>
    </div>
    <div class="panel-footer"></div>
    </div>
</div>

<script type="text/javascript"> 

var timeoutRefresh=<?php echo $this->utils->getConfig('timeout_refresh_queue');?>;
var refreshUrl="<?php echo site_url('export_data/check_queue/'.$result_token);?>";
var stopQueueUrl ="<?php echo site_url('export_data/stop_queue/'.$result_token);?>";

$(function(){

    function showOriginalResult(data){ 

        $('#progress-details').show();
        var initial = '1';

        if(data['queue_result']){
            var queue_result=data['queue_result'];  
            var queue_result_done=queue_result.done; 

            if(queue_result.progress === undefined){
            $('#loader2').show();
            queue_result.progress = '1';
            $('.progress-bar').css('width', parseInt(queue_result.progress)+'%').attr('aria-valuenow', parseInt(queue_result.progress)); 
            $('.progresslabel').html("");
        }else{
                $('#loader2').hide();
                $('.progress-bar').css('width', parseInt(queue_result.progress)+'%').attr('aria-valuenow', parseInt(queue_result.progress)); 

                if(data.done || queue_result_done){ 
                    $('#processStatus').html('<?php echo lang('Done') ?>');  
                }else{
                    $('#processStatus').html('<?php echo lang('Processing') ?>');
                }
                $('#progress').show();   

                $('.progresslabel').html(queue_result.progress+'%');
                $('#totalCount').html(addCommas(queue_result.totalCount));
                
                $('#totalRows').html(parseInt(queue_result.totalCount));                
                $('#processedRows').html(parseInt(queue_result.totalCount));                
                $('#totalPlayers').html(parseInt(queue_result.playerCount));
                $('#successCount').html(parseInt(queue_result.successCount));
                $('#failedCount').html(parseInt(queue_result.failedCount));

                if( queue_result.download_link){
                    var url=queue_result.download_link;
                    $(".download_link .download_url").attr("href", url).text(url);
                    $(".download_link").show();
                }
            }        
        }
    }

    function stopQueue(){
        $.post(stopQueueUrl, function(data){
            utils.safelog(data);
        });
    }

    function addCommas(nStr){
        nStr += '';
        var x = nStr.split('.');
        var x1 = x[0];
        var x2 = x.length > 1 ? '.' + x[1] : '';
        var rgx = /(\d+)(\d{3})/;
        while (rgx.test(x1)) {
            x1 = x1.replace(rgx, '$1' + ',' + '$2');
        }
        return x1 + x2;
    }

    function refreshResult(){

        utils.safelog('refresh result:'+timeoutRefresh);
        
        $.post(refreshUrl, function(data){
            utils.safelog(data);
            showOriginalResult(data);
            var queue_result=data['queue_result'];  
            var _style= 'text-success';
            
            if(data.process_status == <?php echo Queue_result::STATUS_NEW_JOB  ?>){
                _style= 'text-warning';
                $(".progress").show();
            }else if(data.process_status == <?php echo Queue_result::STATUS_STOPPED ?>){   
                _style= 'text-danger'; 
                $(".progress").hide();
            }else if(data.process_status == <?php echo Queue_result::STATUS_ERROR ?>){    
                _style= 'text-danger';
                $(".progress").hide();
            }else{
            //
            }
            if (typeof queue_result.message == "undefined" || queue_result.message == null){
                $('#processStatus').html('Processing').addClass(_style);
            }else{
                $('#processStatus').html(queue_result.message).addClass(_style);
            }
            
        });
        setTimeout( refreshResult , 1000 * timeoutRefresh);
    }
    refreshResult();

});

</script>
