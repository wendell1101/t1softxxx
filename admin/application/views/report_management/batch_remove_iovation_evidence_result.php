<div class="container">
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h4 class="panel-title"><span style="font-weight: bold;"><?php echo lang('Batch Retract Iovation Evidence Result');?></span></h4>
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
            <?=lang('Processed rows') ?>: <strong id="processedRows">0</strong> <?=lang('Total Rows') ?>: <strong id="totalRows">0</strong> <?=lang('Status') ?>: <strong id="processStatus"><?php echo lang('Processing')?></strong>
        </div>
        <table class="table table-condensed">
            <tbody>
                <tr>
                    <td><?=lang('Total Players Affected') ?> <strong><span class="text-info" id="totalCount">0</span></strong></td>
                </tr>                
                <tr>
                    <td><?=lang('Success Count') ?>: <strong id="successCount">0</strong>  </td>
                </tr>                
                <tr>
                    <td><?=lang('Failed Count') ?>: <strong id="failedCount">0</strong></td>
                </tr>                
                                
            </tbody>
        </table>
    </div>
    <div class="panel-footer"></div>
    </div>
    <br>
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h4 class="panel-title"><span style="font-weight: bold;"><?php echo lang('Logs');?></span></h4>
        </div>
        <div class="panel-body">
            <table class="table table-condensed">
                <thead>
                    <tr>
                        <td>#</td>
                        <td><?=lang('External Evidence ID') ?></td>
                        <td><?=lang('Account Code') ?></td>
                        <td><?=lang('User Type') ?></td>
                        <td><?=lang('Evidence Type') ?></td>
                        <td><?=lang('Applied To') ?></td>
                        <td><?=lang('Comment') ?></td>
                        <td><?=lang('Status') ?></td>
                        <td><?=lang('Message') ?></td>
                    </tr>                
                </thead>
                <tbody id="tableLogs">
                                   
                </tbody>
            </table>
        </div>        
        <div class="panel-footer">
            <div class="">                
                <a href="/report_management/viewIovationEvidence" class="btn btn-sm btn-scooter"><?=lang('Back');?></a>
            </div>
        </div>
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

        $('#totalRows').html(queue_result.totalRows);
        $('#processedRows').html(queue_result.successCount+queue_result.failedCount);
        $('#successCount').html(queue_result.successCount);
        $('#failedCount').html(queue_result.failedCount);

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
      
            var failCount = parseInt(queue_result.failCount);
            if(data.done){
                
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

        if (queue_result.affected_records != null){
            $('#tableLogs').html('');
            var datas = queue_result.affected_records;
            $.each (datas, function (index) {       
                if(index!=0){
                    console.log(datas);
                    var message = datas[index]['message'];
                    let status = datas[index]['status'];
                    let status_msg = 'Failed';
                    if(datas[index]['status']==true){
                        status_msg = 'Success';
                    }
                    //var count = index+1;
                    var row = '<tr><td>'+index+'</td><td>'+datas[index]['external_evidence_id']+'</td>'+
                    '<td>'+datas[index]['account_code']+'</td>'+
                    '<td>'+datas[index]['user_type']+'</td>'+
                    '<td>'+datas[index]['evidence_type']+'</td>'+
                    '<td>'+datas[index]['applied_to']+'</td>'+
                    '<td>'+datas[index]['comment']+'</td>'+
                    '<td>'+status_msg+'</td>'+
                    '<td>'+message.join()+'</td></tr>';
                    $('#tableLogs').append(row);
                }
            });
        }
        
    });
    setTimeout( refreshResult , 1000 * timeoutRefresh);
}
    refreshResult();
     
    /*$('#btn_refresh_result').click(function(){
        refreshResult();
    });*/

});

</script>
