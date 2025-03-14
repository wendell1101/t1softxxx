$(document).ready(function () {
	var gameDescriptionHistoryTable = $("#gameDescriptionHistoryTable").DataTable({
		autoWidth: false,
		dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i><'dt-information-summary2 pull-right' f>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
	        buttons: [
	            {
	                extend: 'colvis',
	                collectionLayout: 'two-column',
					className: 'btn-linkwater',
	            }
	        ],

		columnDefs: [
			{ sortable: false, targets: [ 0 ] },
			{ visible: false, targets: [ 0,1,12,13,14,17,18,19,20,21,22,23,24,26,27,32,33,34 ] }
		],
		searching: true,
	});

	var showGameDescriptionHistoryModal = function(){
		var rowId = $(this).data('row-id');
		console.log(rowId)
		$.ajax({
			url : GET_GAME_DESCRIPTION_HISTORY + "/" + rowId,
			type : 'GET',
			dataType : 'json',
		}).done(function(data){
			gameDescriptionHistoryTable.clear().draw();
			if (data.status == "success") {
				if (data.gameDescriptionHistory) {
					$.each(data.gameDescriptionHistory, function(key,gameDescriptionHistory){
						gameDescriptionHistoryTable.rows.add( [ {
					        "0":gameDescriptionHistory.id,
							"1":gameDescriptionHistory.game_description_id,
							"2":gameDescriptionHistory.game_platform_id,
							"3":gameDescriptionHistory.action,
							"4":gameDescriptionHistory.game_type,
							"5":gameDescriptionHistory.game_name,
							"6":gameDescriptionHistory.game_code,
							"7":gameDescriptionHistory.attributes,
							"8":gameDescriptionHistory.note,
							"9":gameDescriptionHistory.english_name,
							"10":gameDescriptionHistory.external_game_id,
							"11":gameDescriptionHistory.clientid,
							"12":gameDescriptionHistory.moduleid,
							"13":gameDescriptionHistory.sub_game_provider,
							"14":gameDescriptionHistory.flash_enabled,
							"15":gameDescriptionHistory.status,
							"16":gameDescriptionHistory.flag_show_in_site,
							"17":gameDescriptionHistory.no_cash_back,
							"18":gameDescriptionHistory.void_bet,
							"19":gameDescriptionHistory.game_order,
							"20":gameDescriptionHistory.related_game_desc_id,
							"21":gameDescriptionHistory.dlc_enabled,
							"22":gameDescriptionHistory.progressive,
							"23":gameDescriptionHistory.enabled_freespin,
							"24":gameDescriptionHistory.offline_enabled,
							"25":gameDescriptionHistory.mobile_enabled,
							"26":gameDescriptionHistory.enabled_on_android,
							"27":gameDescriptionHistory.enabled_on_ios,
							"28":gameDescriptionHistory.flag_new_game,
							"29":gameDescriptionHistory.html_five_enabled,
							"30":gameDescriptionHistory.demo_link,
							"31":gameDescriptionHistory.md5_fields,
							"32":gameDescriptionHistory.deleted_at,
							"33":gameDescriptionHistory.created_on,
							"34":gameDescriptionHistory.updated_at,
							"35":gameDescriptionHistory.username,
							"36":gameDescriptionHistory.user_ip_address
					    }] )
					    .draw();
					});
				}
			}
		});
	};

    $('#my_table').delegate(".viewGameDescriptionHistory",'click',showGameDescriptionHistoryModal);


	var gamePlatform = $("#gamePlatform"),
		gamePlatformId = gamePlatform.val();

	$('.multi-select-filter').select2();

	if(show_non_active_game_api_game_list==0 && gameCodes){
		processFilters(gameCodes,'selectGameCode');
	}

	if(filters){
		processFilters(filters,'filters');
	}

	gamePlatform.on("change", function (){
		LoadGameTypesByGamePlatformId($("#gamePlatform :selected").val(),'#gameType');

	});

	$( window ).on( "load", function(){
 		if (gamePlatformId) {
		 	LoadGameTypesByGamePlatformId(gamePlatformId,'#gameType');
 		}
	});

	$('#game_platform_id').on("change", function (){
		LoadGameTypesByGamePlatformId($("#game_platform_id :selected").val(),'#game_type_id');

	});

    function LoadGameTypesByGamePlatformId(gamePlatformId,id) {
    	var option = '';

        if(gamePlatformId != "N/A") {
            $.post(baseUrl + 'game_type/getGameTypeByPlatformId/' + gamePlatformId,function(data){
                var dataList = data;
                option += '<option value="">'+message.gameType+'</option>';

                for(var i in dataList) {
                    // console.log(data[i].id);
                    var selected = '';
                    if(gameTypeId == data[i].id) {
                        selected = 'selected';
                    }
                    option += '<option value="'+data[i].id+'" '+selected+'>'+data[i].game_type+'</option>';
                }
                $(id).html(option);
        		$(id).removeAttr('disabled');
            },'json');
        }else{
        	$(id).attr('disabled',true);
        }
    }

    function processFilters(filters,$id){
        $('#'+$id+' option').prop('selected', false);
        if ($.isArray(filters)) {
			$.each(filters,function(key,filter){
	            $('#'+$id+' option[value="'+filter+'"]').prop('selected', true);
			});
        }else{
	        $('#'+$id+' option[value="'+filters+'"]').prop('selected', true);
        }
        $('#'+$id+'').trigger('chosen:updated');
    	$('#'+$id+'').trigger('change');
    }

    $("#game-update-active-form input").change(function() {
        var fileExtension = ['csv'];
        if ($.inArray($(this).val().split('.').pop().toLowerCase(), fileExtension) == -1) {
            $(this).val(null);
            alert("Only format are allowed : "+fileExtension.join(', '));
        }
    });

    $('#batchUpdateModal').on('hidden.bs.modal', function () {
    	//reset form once modal is hide
	    $('#game-update-active-form').trigger("reset");
	});

});

function download_csv() {
    var data = [
       ['gamecode1'],
       ['gamecode2'],
       ['gamecode3']
    ];
    var csv = 'game_code\n';
    data.forEach(function(row) {
            csv += row.join(',');
            csv += "\n";
    });

    console.log(csv);
    var hiddenElement = document.getElementById('download_csv');
    hiddenElement.href = 'data:text/csv;charset=utf-8,' + encodeURI(csv);
    hiddenElement.target = '_blank';

    var fileName = 'sample_batch_update_active_games_' + $.now();
    var fileExtension = '.csv';
    hiddenElement.download = fileName + fileExtension;
    // hiddenElement.click();
}

function download_batch_tag_csv() {
    var optionalHeaders = ['tag_code', 'game_description_id', 'tag_game_order', 'action', 'game_name'];
    var csvContentsObj = {
        headers: [],
        dummyData: []
    };
    
    optionalHeaders.forEach(function(header) {
        csvContentsObj.headers.push(header);
    });
    
    var csvContent = "";
    csvContent += csvContentsObj.headers.join(',') + "\n";
    csvContent += csvContentsObj.dummyData.join(',') + "\n";

    var hiddenElement = document.getElementById('download_batch_tag_csv');
    hiddenElement.href = 'data:text/csv;charset=utf-8,' + encodeURI(csvContent);
    hiddenElement.target = '_blank';

    var fileName = 'sample_batch_tag_games_' + $.now(); 
    var fileExtension = '.csv';
    hiddenElement.download = fileName + fileExtension;
}

function download_csv_update_game_des_field() {
    var optionalHeaders = ['status', 'flag_show_in_site', 'locked_flag', 'game_order', 'mobile_enabled', 'note', 'attributes', 'html_five_enabled', 'english_name', 'sub_game_provider', 'enabled_on_android', 'enabled_on_ios', 'flag_new_game', 'flag_hot_game', 'rtp'];
    var csvContentsObj = {
        headers: ['game_code'],
        dummyData: []
    };
    optionalHeaders.forEach(function(header) {
        var selectedHeader = $("[name='sample_csv_columns[" + header + "]']");
        if (selectedHeader.is(':checked')){
            csvContentsObj.headers.push(header);
        }
    });

    csvContentsObj.headers.forEach(function(header) {
        var $col = header;
        csvContentsObj.dummyData.push(optionalHeaderDummyDataObj[$col]);
    });
    
    var csvContent = "";
    csvContent += csvContentsObj.headers.join(',') + "\n";
    csvContent += csvContentsObj.dummyData.join(',') + "\n";

    var hiddenElement = document.getElementById('download_csv_update_game_des_field');
    hiddenElement.href = 'data:text/csv;charset=utf-8,' + encodeURI(csvContent);
    hiddenElement.target = '_blank';

    var fileName = 'sample_batch_update_game_descriptions_' + $.now();
    var fileExtension = '.csv';
    hiddenElement.download = fileName + fileExtension;
    // hiddenElement.click();
}

function download_csv_add_update_game_des_field() {
    var csvContent = "Game Name,Chinese,Korean,Indonesian,Vietnamese,Game Code,External Game Id,Game Type,Html5,Mobile,IOS,Android,Status,Flag,Flash,Free Spin,Release Date,Hot Game,Note,Sub Game Provider,Related Game Desc Id,Client Id,Module Id,Attributes,Game Order,Offline,Download Pc,Progressive,Demo Link,Flag New Game\n";

    var hiddenElement = document.createElement('a');
    hiddenElement.href = 'data:text/csv;charset=utf-8,' + encodeURI(csvContent);
    hiddenElement.target = '_blank';

    var fileName = 'sample_batch_add_game_descriptions_'  + $.now();;
    var fileExtension = '.csv';
    hiddenElement.download = fileName + fileExtension;
    hiddenElement.click();
}

var optionalHeaderDummyDataObj = {
    game_code: 'gamecode1',
    status: 1,
    flag_show_in_site: 1,
    locked_flag: 1,
    game_order: 1,
    mobile_enabled: 1,
    note: 'sample note',
    attributes: '{ attribute: "sample"}',
    html_five_enabled: 1,
    english_name: 'game code 1',
    sub_game_provider: 'sample sub game provider',
    enabled_on_android: 1,
    enabled_on_ios: 1,
    flag_new_game: 1,
    flag_hot_game: 1,
    rtp: null,
};