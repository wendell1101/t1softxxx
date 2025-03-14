
<style type="text/css">
  .btn-conditions.select{
      background: #6f6f6f;
      color: #fff;
      box-shadow: none;
  }
  .btn-conditions.select:after{
      font: normal normal normal 14px/1 FontAwesome;
      text-rendering: auto;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      content: '\f058';
      font-size: 19px;
      display: block;
      position: absolute;
      right: 6px;
      top: 0;
      line-height: 34px;
  }
  .form_selection.hide {
    visibility: hidden;
  }
  .loading-container {
      position: absolute;
      width: 100%;
      height: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 10;
      background: rgba(0, 0, 0, .4);
      margin: -1rem;
      margin: -20px
  }

  .spinner {
      position: relative;
      top: 0;
      left: 0;
      width: 100%;
      text-align: center;
      min-height: -4px;
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: -1;
  }

  .spinner>div {
      width: 18px;
      height: 18px;
      background-color: #eee;
      border-radius: 100%;
      display: inline-block;
      -webkit-animation: sk-bouncedelay 1.4s infinite ease-in-out both;
      animation: sk-bouncedelay 1.4s infinite ease-in-out both
  }

  .spinner .bounce1 {
      -webkit-animation-delay: -.32s;
      animation-delay: -.32s
  }

  .spinner .bounce2 {
      -webkit-animation-delay: -.16s;
      animation-delay: -.16s
  }

  @-webkit-keyframes sk-bouncedelay {

      0%,
      80%,
      100% {
          -webkit-transform: scale(0)
      }

      40% {
          -webkit-transform: scale(1)
      }
  }

  @keyframes sk-bouncedelay {

      0%,
      80%,
      100% {
          -webkit-transform: scale(0);
          transform: scale(0)
      }

      40% {
          -webkit-transform: scale(1);
          transform: scale(1)
      }
  }
</style>
<h4 class="m-t-0"><?= $title ?> <?=lang('League Bet Setting')?></h4>
<div class="panel panel-primary">
  <div class="panel-body advanced-conditions-panel">
    <div class="row">
      <a class="btn btn-primary btn-xs search_league"><span> <?=lang("Search league")?></span></a>
      <div class="clearfix col-md-12 p-0 m-b-5">
        <div class="col-md-12 league_container">
        </div>
      </div>
    </div>
  </div>
</div> 


<div class="modal fade" id="modal_search_league" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-body">
        <div>
          <div class="row">
            <div class="col-md-12 modal_search_league form_selection">
              <fieldset>
                <legend>
                 <a class="btn btn-primary btn-xs" name="show_multiselect_filter" id="show-multiselect-filter" style="text-decoration:none; border-radius:2px;"><span class="fa fa-search"> <?=lang("Search League")?></span></a>
                </legend>
                <div class="" style="padding:20px;">    
                  <div id="form_search_league">
                    <div class="form-group">
                      <label for="keyword">Choose Date (Default current month)</label>
                      <div class="input-group"> 
                        <input id="search_date" class="form-control input-sm dateInput" data-start="#date_from" data-end="#date_to" data-time="false" disabled/>
                        <span class="input-group-addon input-sm">
                          <input type="checkbox" name="custom_date" id="custom_date" />
                        </span>
                        <input type="hidden" name="date_from" id="date_from" value="<?=$conditions['date_from'];?>" />
                        <input type="hidden" name="date_to" id="date_to" value="<?=$conditions['date_to'];?>" />
                      </div>
                    </div>
                    <div class="form-group">
                      <label for="keyword">League Name Keyword (Default keyword "cup")</label>
                      <input class="form-control form-control-sm" type="text" id="keyword" value="cup" placeholder="League Name Keyword" maxlength="50">
                    </div>
                    <div class="form-group">
                      <label for="keyword">Choose sports (Default soccer)</label>
                      <select class="form-control" id="sport_option">
                        <?php if (!empty($sports)): ?>
                            <?php foreach ($sports as $key => $value): ?>
                                <option value="<?=$key?>"><?=$value?></option>
                            <?php endforeach ?>
                        <?php endif ?>
                      </select>
                    </div>
                  </form>
                </div>
              </fieldset>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal"><?=lang('Close')?></button>
        <button type="button" class="btn btn-primary btn-sm" id="btn_search_league"><?=lang('Search')?></button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="view_league" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-body">
        <div class="row">
          <div class="col-md-12">
            <fieldset>
              <legend>
               <a class="btn btn-primary btn-xs" style="text-decoration:none; border-radius:2px;"><span> <?=lang("League Information")?></span></a>
              </legend>
              <div class="row" style="padding:20px;"> 
                <div class="col-md-12">
                    <h5></h5>
                </div>
                <div class="col-md-12">
                  <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="is_live">
                    <label class="form-check-label" for="is_live">Is Live</label>
                  </div>
                  <div class="form-group">
                    <label for="min_bet">Minimum Bet</label>
                    <input class="form-control form-control-sm" type="number" id="min_bet" placeholder="Minimum Bet">
                  </div>
                  <div class="form-group">
                    <label for="max_bet">Max Bet</label>
                    <input class="form-control form-control-sm" type="number" id="max_bet" placeholder="Maximum Bet">
                  </div>
                  <div class="form-group">
                    <label for="max_bet_ratio">Max Bet Ratio</label>
                    <input class="form-control form-control-sm" type="number" id="max_bet_ratio" step="0.1" placeholder="Maximum Bet Ratio">
                  </div>
                  <div class="form-group">
                    <label for="group_type">Group Type</label>
                    <select class="form-control" id="group_type">
                        <option value="BIG">BIG</option>
                        <option value="MEDIUM">MEDIUM</option>
                        <option value="SMALL">SMALL</option>
                    </select>
                  </div>
                </div>
              </div>
            </fieldset>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary btn-sm" id="btn_league_update">Update Bet Setting</button>
      </div>
    </div>
  </div>
</div>
<script type="text/javascript">
  var game_platform_id = <?= $game_platform_id ?>;
  $('#view_league').on('hidden.bs.modal', function () {
    $(".league_container .league[class*='select']").removeClass('select');
    $("#view_league input[type=text], textarea").val("");
    $("#is_live").prop("checked", false);
  });

  $(document).on("click",".search_league",function() {
      $('#modal_search_league').modal('show');
  });

  $(document).on("click","#btn_league_update",function() {
      showModalLoading('view_league');
      var leagueId = $(".league_container .league[class*='select']").attr("data-id");
      var params = {
        leagueId:leagueId,
        isLive:$('#is_live').is(":checked"),
        minBet:$('#view_league #min_bet').val(),
        maxBet:$('#view_league #max_bet').val(),
        maxBetRatio:$('#view_league #max_bet_ratio').val(),
        groupType: $("#group_type :selected").val()
      }
      updateLeagueBetSetting(params);
  });

  $(document).on("click",".league",function() {
      $(".league_container .league[class*='select']").removeClass('select');
      $(this).addClass('select');
      var leagueId = $(this).attr("data-id");
      var name = $(this).attr("data-name");
      var params = {
        leagueId:leagueId,
      }
      showModalLoading('view_league');
      $('#view_league').modal('show');
      $('#view_league h5').text('【'+name+'】');
      getLeagueBetSetting(params);
  });

  $(document).on("click","#btn_search_league",function(event) {
    $(this).prop('disabled', true);
    showModalLoading('modal_search_league');
    var params = {
      fromDate:$("#date_from").val(),
      endDate:$("#date_to").val(),
      leagueNameKeyWord:$("#keyword").val(),
      sportType:$("#sport_option :selected").val(),
    }
    console.log(params);
    getLeagueIdAndName(params);
  });

  $('#is_live').change(function() {
    var leagueId = $(".league_container .league[class*='select']").attr("data-id");
    var params = {
      leagueId:leagueId,
      isLive: this.checked
    }
    showModalLoading('view_league');
    getLeagueBetSetting(params);    
  });

  function getLeagueBetSetting(params){
    $.ajax({
      url: '/async/getLeagueBetSetting/'+game_platform_id,
      type: 'post',
      dataType: 'json',
      contentType: 'application/json',
      success: function (data) {
        if(data.success){
          removeModalLoading();
          console.log(data);
          if(data.result.length > 0){
            $('#view_league #min_bet').val(data.result[0].min_bet);
            $('#view_league #max_bet').val(data.result[0].max_bet);
            $('#view_league #max_bet_ratio').val(data.result[0].max_bet_ratio);
            $("#group_type").val(data.result[0].group_type).change(); 
          } else {
            $('#view_league #min_bet').val(0);
            $('#view_league #max_bet').val(0);
            $('#view_league #max_bet_ratio').val(0.0);
          }
        }
      },
      data: JSON.stringify(params)
    });
  }

  function updateLeagueBetSetting(params){
    $.ajax({
      url: '/async/setLeagueBetSetting/'+game_platform_id,
      type: 'post',
      dataType: 'json',
      contentType: 'application/json',
      success: function (data) {
        removeModalLoading();
        if(data.success){
          alert("Update Success.");
        } else {
          alert("Update failed. Please try again.");
        }
      },
      error: function(XMLHttpRequest, textStatus, errorThrown) {
         alert("Got an error on request.");
      },
      data: JSON.stringify(params)
    });
  }

  function getLeagueIdAndName(params){
    $.ajax({
      url: '/async/getLeagueIdAndName/'+game_platform_id,
      type: 'post',
      dataType: 'json',
      contentType: 'application/json',
      success: function (data) {
        if(data.success){
          removeModalLoading();
          hideModal('modal_search_league');
          appendLeagueIdAndName(data.result);
        } else {
          alert("Search failed. Please try again.");
        }
      },
      error: function(XMLHttpRequest, textStatus, errorThrown) {
         alert("Got an error on request.");
      },
      data: JSON.stringify(params)
    });
  }

  function appendLeagueIdAndName(results){
    $(".league_container").empty();
    if(results.length > 0){
      var sport = $("#sport_option :selected").text();
      $('.league_container').append('<h5 class="text-nowrap">【 Choose '+sport+' league】</h5>');
      $.each( results, function( key, value ) { 
        var league_name = value.league_name;
        if(league_name.length > 35) league_name = league_name.substring(0,35) + "...";

        $( ".league_container" ).append(  '<div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">'+
                '<button type="button" class="btn btn-block btn-conditions league" data-id="'+value.league_id+'" data-name="'+value.league_name+'">'+league_name+'</button>'+
              '</div>'
            );
      });         
    } else {
      $( ".league_container" ).append(  'No League available on current date and sports.' );
    }
  }

  function showModalLoading(modalId){
    $('#btn_league_update').prop('disabled', true);
    $("#"+modalId+" .modal-body").prepend( ' <div class="loading-container"><div class="spinner"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div></div>' );
  }

  function hideModal(modalId){
    $('#'+modalId).modal('hide');
  }

  function removeModalLoading(){
    $('#btn_league_update').prop('disabled', false);
    $('#btn_search_league').prop('disabled', false);
    $(".loading-container").remove();
  }

  

  $(document).ready(function() {
    $('.search_league').click();
    $("#custom_date").change(function() {
        if(this.checked) {
            $('#search_date').prop('disabled',false);
            $('#date_from').prop('disabled',false);
            $('#date_to').prop('disabled',false);
        }else{
            $('#search_date').prop('disabled',true);
            $('#date_from').prop('disabled',true);
            $('#date_to').prop('disabled',true);
        }
    }).trigger('change');
  });
</script>


