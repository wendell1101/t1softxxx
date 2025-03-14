//general
var base_url = "/";
var imgloader = "/resources/images/ajax-loader.gif";

function GetXmlHttpObject() {
    if (window.XMLHttpRequest) {
        // code for IE7+, Firefox, Chrome, Opera, Safari
        return new XMLHttpRequest();
    }
    if (window.ActiveXObject) {
        // code for IE6, IE5
        return new ActiveXObject("Microsoft.XMLHTTP");
    }
    return null;
}

function closeDetails() {
    $("#toggleView").show();
    // $('#toggleView').removeClass('col-md-5');
    // $('#toggleView').addClass('col-md-12');

    // if ($('#toggleView').hasClass('col-md-5')) {
    //     $('table#myTable td#visible').hide();
    //     $('table#myTable th#visible').hide();
    // } else {
    //     $('table#myTable td#visible').show();
    //     $('table#myTable th#visible').show();
    // }
    $(".modal .close").click();
    // document.getElementById('player_details').style.display = "none";
}


function viewPlayer(playerId, path) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "player_management/" + path + "/" + playerId;

    // $("#toggleView").hide();
    // $('#toggleView').removeClass('col-md-12');
    // $('#toggleView').addClass('col-md-5');

    document.getElementById('player_details').style.display = "block";

    if ($('#toggleView').hasClass('col-md-5')) {
        $('table#myTable td#visible').hide();
        $('table#myTable th#visible').hide();
    } else {
        $('table#myTable td#visible').show();
        $('table#myTable th#visible').show();
    }

    var div = document.getElementById("player_details");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
            $(".edit_note").tooltip({
                placement: "top",
                title: "Edit this Note",
            });

            $(".delete_note").tooltip({
                placement: "top",
                title: "Delete this Note",
            });

            //$('html, body').animate({scrollTop: $("#toggleView").offset().top}, 1500);
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function adjustPlayerLevel(playerId, path) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "player_management/" + path + "/" + playerId;

    $('#toggleView').removeClass('col-md-12');
    $('#toggleView').addClass('col-md-5');

    document.getElementById('player_details').style.display = "block";

    if ($('#toggleView').hasClass('col-md-5')) {
        $('table#myTable td#visible').hide();
        $('table#myTable th#visible').hide();
    } else {
        $('table#myTable td#visible').show();
        $('table#myTable th#visible').show();
    }

    var div = document.getElementById("player_details");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
            $(".edit_note").tooltip({
                placement: "top",
                title: "Edit this Note",
            });

            $(".delete_note").tooltip({
                placement: "top",
                title: "Delete this Note",
            });

            //$('html, body').animate({scrollTop: $("#toggleView").offset().top}, 1500);
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function viewPlayerTagOptions(path) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "player_management/" + path;

    $('#toggleView').removeClass('col-md-12');
    $('#toggleView').addClass('col-md-5');

    document.getElementById('player_details').style.display = "block";

    // if ($('#toggleView').hasClass('col-md-5')) {
    //     $('table#myTable td#visible').hide();
    //     $('table#myTable th#visible').hide();
    // } else {
    //     $('table#myTable td#visible').show();
    //     $('table#myTable th#visible').show();
    // }

    var div = document.getElementById("player_details");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function viewGameDetails(playerId) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "player_management/gameDetails/" + playerId;

    document.getElementById('player_details').style.display = "block";

    var div = document.getElementById("player_details");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
            $(".edit_note").tooltip({
                placement: "top",
                title: "Edit this Note",
            });

            $(".delete_note").tooltip({
                placement: "top",
                title: "Delete this Note",
            });

            //$('html, body').animate({scrollTop: $("#toggleView").offset().top}, 1500);
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function get_gameDetails_pages(segment) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    var gameHistoryId = document.getElementById('gameHistoryId').value;

    url = base_url + "player_management/getGameDetailsPages/" + gameHistoryId + "/" + segment;

    var div = document.getElementById("gameHistoryDetailsList");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function get_tag_pages(segment) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "player_management/get_tag_pages/" + segment;

    var div = document.getElementById("tag_table");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function get_vipgroupsetting_pages(segment) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "vipsetting_management/get_vipgroupsetting_pages/" + segment;

    var div = document.getElementById("tag_table");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function sortTag(sort) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "player_management/sortTag/" + sort;

    var div = document.getElementById("tag_table");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }

    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function searchTag() {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    var search = document.getElementById('search').value;

    url = base_url + "player_management/searchTag/" + search;

    var div = document.getElementById("tag_table");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }

    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function sortVipGroup(sort) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "player_management/sortVipGroup/" + sort;

    var div = document.getElementById("tag_table");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }

    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function viewPlayerWithCurrentPage(playerId, path, page) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "player_management/" + path + "/" + playerId + "/" + page;

    // $('#toggleView').removeClass('col-md-12');
    // $('#toggleView').addClass('col-md-5');

    document.getElementById('player_details').style.display = "block";

    if ($('#toggleView').hasClass('col-md-5')) {
        $('table#myTable td#visible').hide();
        $('table#myTable th#visible').hide();
    } else {
        $('table#myTable td#visible').show();
        $('table#myTable th#visible').show();
    }
    //$("#toggleView").hide();
    var div = document.getElementById("player_details");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {

            div.innerHTML = xmlhttp.responseText;
            $(".edit_note").tooltip({
                placement: "top",
                title: "Edit this Note",
            });

            $(".delete_note").tooltip({
                placement: "top",
                title: "Delete this Note",
            });

            //$('html, body').animate({scrollTop: $("#toggleView").offset().top}, 1500);
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function viewPlayerWithCurrentPageBlocked(playerId, gameId, path, page) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "player_management/" + path + "/" + playerId + "/" + gameId + "/" + page;

    $('#toggleView').removeClass('col-md-12');
    $('#toggleView').addClass('col-md-5');

    document.getElementById('player_details').style.display = "block";

    if ($('#toggleView').hasClass('col-md-5')) {
        $('table#myTable td#visible').hide();
        $('table#myTable th#visible').hide();
    } else {
        $('table#myTable td#visible').show();
        $('table#myTable th#visible').show();
    }

    var div = document.getElementById("player_details");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
            $(".edit_note").tooltip({
                placement: "top",
                title: "Edit this Note",
            });

            $(".delete_note").tooltip({
                placement: "top",
                title: "Delete this Note",
            });

            //$('html, body').animate({scrollTop: $("#toggleView").offset().top}, 1500);
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function get_player_pages(segment) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "player_management/getPlayerPages/" + segment;

    var div = document.getElementById("playerList");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;

            if ($('#toggleView').hasClass('col-md-5')) {
                $('table#myTable td#visible').hide();
                $('table#myTable th#visible').hide();
            } else {
                $('table#myTable td#visible').show();
                $('table#myTable th#visible').show();
            }
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function get_vip_player_pages(segment) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "player_management/getVIPPlayerPages/" + segment;

    var div = document.getElementById("playerList");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;

            if ($('#toggleView').hasClass('col-md-5')) {
                $('table#myTable td#visible').hide();
                $('table#myTable th#visible').hide();
            } else {
                $('table#myTable td#visible').show();
                $('table#myTable th#visible').show();
            }
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function get_blacklist_pages(segment) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "player_management/getBlacklistPages/" + segment;

    var div = document.getElementById("playerList");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;

            if ($('#toggleView').hasClass('col-md-5')) {
                $('table#myTable td#visible').hide();
                $('table#myTable th#visible').hide();
            } else {
                $('table#myTable td#visible').show();
                $('table#myTable th#visible').show();
            }
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function searchPlayerList(type) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    var search = document.getElementById('search').value;

    url = base_url + "player_management/searchPlayerList/" + search + "/" + type;

    var div = document.getElementById("playerList");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;

            if ($('#toggleView').hasClass('col-md-5')) {
                $('table#myTable td#visible').hide();
                $('table#myTable th#visible').hide();
            } else {
                $('table#myTable td#visible').show();
                $('table#myTable th#visible').show();
            }
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }

    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function sortPlayerList(sort, type) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "player_management/sortPlayerList/" + sort + "/" + type;

    var div = document.getElementById("playerList");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;

            if ($('#toggleView').hasClass('col-md-5')) {
                $('table#myTable td#visible').hide();
                $('table#myTable th#visible').hide();
            } else {
                $('table#myTable td#visible').show();
                $('table#myTable th#visible').show();
            }
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }

    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function get_gameHistory_pages(segment) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "player_management/getGameHistoryPages/" + segment;

    var div = document.getElementById("gameHistoryList");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function actionType(action) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "player_management/actionType/" + action;

    var div = document.getElementById("action");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
        }

        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}
// end of general

// ----------------------------------------------------------------------------------------------------------------------------- //

// sidebar.php
$(document).ready(function() {
    var url = document.location.pathname;
    var res = url.split("/");
    for (i = 0; i < res.length; i++) {
        switch (res[i]) {
            case 'searchAllPlayer':
            case 'viewAllPlayer':
            case 'viewAddPlayer':
            case 'lockPlayer':
            case 'userInformation':
            case 'searchMain':
            case 'selectedPlayers':
            case 'resetPassword':
                $("a#view_player_list").addClass("active");
                break;

            case 'viewOnlinePlayerList':
                $("a#view_online_player_list").addClass("active");
                break;

            case 'vipGroupSettingList':
            case 'searchMainVIP':
                $("a#view_vipsetting_list").addClass("active");
                break;

            case 'taggedlist':
                $("a#view_taggedlist").addClass("active");
                break;

            case 'linkedAccount':
                $("a#view_linkedAccount").addClass("active");
                break;

            case 'playerTagManagement':
                $("a#view_player_tag_management").addClass("active");
                break;
            case 'iptaglist':
                $("a#view_iptaglist").addClass("active");
                break;
            case 'viewRankingSettings':
                $("a#view_ranking_settings").addClass("active");
                break;

            case 'accountProcess':
                $("a#view_account_process").addClass("active");
                break;

            case 'friendReferral':
                $("a#view_friend_referral").addClass("active");
                break;

            case 'gameHistory':
                $("a#view_game_history").addClass("active");
                break;

            case 'chatHistory':
                $("a#view_chat_history").addClass("active");
                break;

            case 'viewRegistrationSettings':
                $("a#view_registration_setting").addClass("active");
                break;

            case 'responsibleGamingSetting':
                $("a#view_responsible_gaming_setting").addClass("active");
                break;

            case 'ManualSubtractBalanceTagManagement':
                $("a#view_manual_adjust_tag_management").addClass("active");
                break;

            case 'accountAutoProcess':
                $("a#view_upload_player").addClass("active");
                break;

            case 'player_tag_history':
                $("a#view_player_tag_history").addClass("active");
                break;
            case 'playerRemarks':
                    $("a#view_player_remarks_page").addClass("active");
                    break;
            default:
                break;
        }
    }
});
// end of sidebar.php

// ----------------------------------------------------------------------------------------------------------------------------- //


//Tags
function showOthers(e) {
    if (e.value == 'Others') {
        document.getElementById('specify').style.display = "block";
    } else {
        document.getElementById('specify').style.display = "none";
    }
}

function showDescription(e) {
    if (e.value) {
        $.ajax({
            'url': base_url + 'player_management/getTagDescription/' + e.value,
            'type': 'GET',
            'dataType': "json",
            'success': function(data) {
                // console.log(data[0]);
                $('#tagDescription').html(data[0].tagDescription);
            }
        }, 'json');
        document.getElementById('description').style.display = "block";
        return false;
    } else {
        document.getElementById('description').style.display = "none";
    }
}
//end Tags

//Telephone call
function makeTeleCall(getUrl) {
    $.ajax({
        url: getUrl,
        type: 'GET',
        dataType: "json"
    }).done(function(data) {
        // console.log(data);
        if(data.hasOwnProperty('redirect_url')) {
            window.open(data.redirect_url, '_blank');
        }
        else if(data.hasOwnProperty('success')) {
            if(data.success) {
                BootstrapDialog.show({
                    type: BootstrapDialog.TYPE_SUCCESS,
                    title: 'Success',
                    message: data.msg,
                    buttons: [{
                        label: 'Close',
                        action: function(dialogRef){
                            dialogRef.close();
                        }
                    }]
                });
            }
            else {
                BootstrapDialog.show({
                    type: BootstrapDialog.TYPE_DANGER,
                    title: 'Failed',
                    message: data.msg,
                    buttons: [{
                        label: 'Close',
                        action: function(dialogRef){
                            dialogRef.close();
                        }
                    }]
                });
            }
        }
        // no need to do anything so far
    }).fail(function(jqXHR, textStatus) {
        // no need to do anything so far
    });
}

function hangupTeleCall(getUrl) {
    $.ajax({
        url: getUrl,
        type: 'GET',
        dataType: "text"
    }).done(function(data) {
        // console.log(data);
        if(data == "200") {
            BootstrapDialog.closeAll();
        }
    }).fail(function(jqXHR, textStatus) {
        // no need to do anything so far
    });
}
//end Telephone call


//Specfiy
function specify(e) {
    if (e.value == 'specify') {
        $("#start_date").attr("disabled", false);
        $("#end_date").attr("disabled", false);
    } else {
        $("#start_date").attr("disabled", true);
        $("#end_date").attr("disabled", true);
    }
}
//end Specfiy

//Locked
function specifyLocked(e) {
    if (e.value == 'specify') {
        $("#start_date_locked").attr("disabled", false);
        $("#end_date_locked").attr("disabled", false);
    } else {
        $("#start_date_locked").attr("disabled", true);
        $("#end_date_locked").attr("disabled", true);
    }
}
//end Locked

//Blocked
function specifyBlocked(e) {
    if (e.value == 'frozen') {
        $("#start_date_block").attr("disabled", false);
        $("#end_date_block").attr("disabled", false);
        $("#start_date_block").attr("required", true);
        $("#end_date_block").attr("required", true);
    } else {
        $("#start_date_block").attr("disabled", true);
        $("#end_date_block").attr("disabled", true);
        $("#start_date_block").attr("required", false);
        $("#end_date_block").attr("required", false);
    }
}
//end Blocked

//showGame
function showDivs(e) {
    // if (e.value == 'blocked') {
    //     document.getElementById('block_lock').style.display = "block";
    //     document.getElementById('game').style.display = "block";
    //     document.getElementById('tag').style.display = "none";
    //     document.getElementById('level').style.display = "none";
    // } else
    if (e.value == 'tag') {
        document.getElementById('tag').style.display = "block";
        // document.getElementById('block_lock').style.display = "none";
        // document.getElementById('game').style.display = "none";
        document.getElementById('level').style.display = "none";
    }
    // else if(e.value == 'locked') {
    //     document.getElementById('block_lock').style.display = "block";
    //     document.getElementById('tag').style.display = "none";
    //     document.getElementById('game').style.display = "none";
    //     document.getElementById('level').style.display = "none";
    // }
    else {
        // document.getElementById('block_lock').style.display = "none";
        document.getElementById('tag').style.display = "none";
        // document.getElementById('game').style.display = "none";
        document.getElementById('level').style.display = "block";
    }
}
//end showGame

//Blocked
function specifyDate(e) {
    if (e.value == 'frozen') {
        $("#start_date").attr("disabled", false);
        $("#end_date").attr("disabled", false);
    } else {
        $("#start_date").attr("disabled", true);
        $("#end_date").attr("disabled", true);
    }
}
//end Blocked

// ----------------------------------------------------------------------------------------------------------------------------- //


// ----------------------------------------------------------------------------------------------------------------------------- //

//check if passwords match

function checkPass() {
    //Store the password field objects into variables ...
    var pass1 = document.getElementById('password');
    var pass2 = document.getElementById('cpassword');
    //Store the Confimation Message Object ...
    var message = document.getElementById('lcpassword');
    //Set the colors we will be using ...
    var goodColor = "#66cc66";
    var badColor = "#ff6666";
    //Compare the values in the password field
    //and the confirmation field
    if (pass1.value == "" || pass2.value == "") {
        pass2.style.backgroundColor = "";
        message.innerHTML = "";
    } else if (pass1.value == pass2.value) {
        //The passwords match.
        //Set the color to the good color and inform
        //the user that they have entered the correct password
        //pass2.style.backgroundColor = goodColor;
        message.style.color = goodColor;
        message.innerHTML = "Passwords Match!";
    } else {
        //The passwords do not match.
        //Set the color to the bad color and
        //notify the user.
        //pass2.style.backgroundColor = badColor;
        message.style.color = badColor;
        message.innerHTML = "Passwords Do Not Match!";
    }

}
//end of check if passwords match

// ----------------------------------------------------------------------------------------------------------------------------- //


// ----------------------------------------------------------------------------------------------------------------------------- //

// view_account_process_list.php
function get_account_process_pages(segment) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "player_management/getAccountProcessPages/" + segment;

    var div = document.getElementById("accountProcessList");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function searchAccountProcessList() {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    var search = document.getElementById('search').value;

    url = base_url + "player_management/searchAccountProcessList/" + search;

    var div = document.getElementById("accountProcessList");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }

    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function sortAccountProcessList(sort) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "player_management/sortAccountProcessList/" + sort;

    var div = document.getElementById("accountProcessList");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }

    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function addAccountProcess() {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "player_management/addAccountProcess";

    $('#toggleView').removeClass('col-md-12');
    $('#toggleView').addClass('col-md-6 col-lg-6');
    $('#account_process_details').show();

    var div = document.getElementById("account_process_details");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
            $("#password").strength();
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }

    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}



function viewAccountProcess(id) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "player_management/viewAccountProcess/" + id;

    $('#toggleView').removeClass('col-md-12');
    $('#toggleView').addClass('col-md-6 col-lg-6');

    var div = document.getElementById("account_process_details");

    $('#account_process_details').show();

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            // div.innerHTML = xmlhttp.responseText;

            var data = JSON.parse(xmlhttp.responseText);

            if ( ! $.fn.DataTable.isDataTable('#tblBatchAccounts') ) {
                $('#tblBatchAccounts tbody').empty();
            }else{
                $('#tblBatchAccounts tbody').empty();
                $('#tblBatchAccounts').DataTable().clear();
                $('#tblBatchAccounts').DataTable().destroy();

            }

            var rows = '';
            for (var i = 0; i < data.response.length; i++) {

                if(data.allow_user){
                    rows += '<tr><td>'+data.response[i]['username']+'</td></tr>';
                }else{
                    rows += '<tr><td>'+data.response[i]['username']+'</td><td><a href="#" data-toggle="tooltip" class="edit_account" onclick="editAccountProcessDetails('+data.response[i]['playerId']+');"><span class="glyphicon glyphicon-pencil"></span></a></tr>';
                }
            }

            $('#tblBatchAccounts tbody').append(rows);

            $('#tblBatchAccounts').DataTable({
                "pageLength": 25,
                "ordering": false
            })

            $(".edit_account").tooltip({
                placement: "top",
                title: "Edit this Account",
            });

            $(".delete_account").tooltip({
                placement: "top",
                title: "Delete this Account",
            });
        }
        // if (xmlhttp.readyState != 4) {
        //     div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        // }
    }

    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function get_account_process_list_pages(segment) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    var id = document.getElementById('batch_id').value;

    url = base_url + "player_management/getAccountProcessListPages/" + segment + "/" + id;

    var div = document.getElementById("viewAccountProcess");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function verifyEditAccountProcess(player_affiliate_id) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    var name = document.getElementById('name').value;
    var description = document.getElementById('description').value;

    var error = true;
    $('#error').html('');
    $('#error').hide();

    if (!name) {
        $('#error').html('Name field is required.');
        $('#error').show();
        error = false;
    }

    if (error == true) {
        url = base_url + "player_management/verifyEditAccountProcess/" + player_affiliate_id;

        var poststr =
            "&name=" + encodeURI(name) +
            "&description=" + encodeURI(description);

        var div = document.getElementById("account_process_details");

        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == 4) {
                //div.innerHTML = xmlhttp.responseText;
                window.location = base_url + 'player_management/accountProcess';
            }
            if (xmlhttp.readyState != 4) {
                div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
            }
        }

        xmlhttp.open("POST", url, true);
        xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xmlhttp.send(poststr);
    }
}

function editAccountProcessDetails(id, type) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "player_management/editAccountProcessDetails/" + id + "/" + type;

    var div = document.getElementById("account_process_details");

    $('#account_process_details').show();

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }

    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function verifyEditAccountProcessDetails(player_id, type) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    var username = document.getElementById('username').value;
    var password = document.getElementById('password').value;

    var error = true;
    $('#error').html('');
    $('#error').hide();

    if (!username || !password) {
        $('#error').html('All fields are required.');
        $('#error').show();
        error = false;
    }

    if (error == true) {
        url = base_url + "player_management/verifyEditAccountProcessDetails/" + player_id + "/" + type;

        var poststr =
            "&username=" + encodeURI(username) +
            "&password=" + encodeURI(password);

        var div = document.getElementById("account_process_details");

        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == 4) {
                //div.innerHTML = xmlhttp.responseText;
                window.location = base_url + 'player_management/accountProcess';
            }
            if (xmlhttp.readyState != 4) {
                div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
            }
        }

        xmlhttp.open("POST", url, true);
        xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xmlhttp.send(poststr);
    }
}
// end of view_account_process_list.php

// ----------------------------------------------------------------------------------------------------------------------------- //

// view_chat_history.php
function viewChatHistoryDetails(session) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "player_management/viewChatHistoryDetails/" + session;

    $('#toggleView').removeClass('col-md-12');
    $('#toggleView').addClass('col-md-5');

    var div = document.getElementById("player_details");

    $('#player_details').show();

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }

    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function get_chat_history_pages(segment) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "player_management/getChatHistoryPages/" + segment;

    var div = document.getElementById("chatHistoryList");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function searchChatHistoryList() {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    var search = document.getElementById('search').value;

    url = base_url + "player_management/searchChatHistoryList/" + search;

    var div = document.getElementById("chatHistoryList");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }

    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function sortChatHistoryList(sort) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "player_management/sortChatHistoryList/" + sort;

    var div = document.getElementById("chatHistoryList");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }

    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}
// end of view_chat_history.php

function sortGameHistory(sort) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "player_management/sortGameHistory/" + sort;

    var div = document.getElementById("gameHistoryList");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }

    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function isNumberKey(evt) {
    var charCode = (evt.which) ? evt.which : event.keyCode

    if (charCode > 31 && (charCode < 48 || charCode > 57))
        return false;

    return true;
}

function get_user_information_chat_pages(segment) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    var player_id = $('#player_id').val();

    url = base_url + "player_management/chatSessionPages/" + segment + "/" + player_id;

    var div = document.getElementById("player_info");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;

            if ($('#toggleView').hasClass('col-md-5')) {
                $('table#myTable td#visible').hide();
                $('table#myTable th#visible').hide();
            } else {
                $('table#myTable td#visible').show();
                $('table#myTable th#visible').show();
            }
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function get_user_information_nonawr_pages(segment) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    var player_id = $('#player_id').val();

    url = base_url + "player_management/nonAWRPages/" + segment + "/" + player_id;

    var div = document.getElementById("player_info");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function information(info, segment) {
    if (info == null)
        return;

    var xmlhttp = GetXmlHttpObject();
    var player_id = $('#player_id').val();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    $('#toggleView').removeClass('col-md-5');
    $('#toggleView').addClass('col-md-12');
    document.getElementById('player_details').style.display = "none";

    if (info == "signup") {
        url = base_url + "player_management/viewSignupInformation/" + player_id + "/" + segment;
    }

    if (info == "personal") {
        url = base_url + "player_management/viewPersonalInformation/" + player_id + "/" + segment;
    }

    if (info == "balance_i") {
        url = base_url + "player_management/viewBalanceInformation/" + player_id + "/" + segment;
    }

    if (info == "bonus") {
        url = base_url + "player_management/viewBonusInformation/" + player_id + "/" + segment;
    }

    if (info == "account") {
        url = base_url + "player_management/viewAccountInformation/" + player_id + "/" + segment;
    }

    if (info == "other") {
        url = base_url + "player_management/viewOtherInformation/" + player_id + "/" + segment;
    }

    if (info == "sent") {
        url = base_url + "player_management/viewSentMessages/" + player_id + "/" + segment;
    }

    if (info == "chat") {
        url = base_url + "player_management/viewChatSessions/" + player_id + "/" + segment;
    }

    if (info == "user_pma") {
        url = base_url + "player_management/viewUserPMA/" + player_id + "/" + segment;
    }

    if (info == "user_cc") {
        url = base_url + "player_management/viewUserCC/" + player_id + "/" + segment;
    }

    if (info == "non") {
        url = base_url + "player_management/viewNonAWR/" + player_id + "/" + segment;
    }

    if (info == "transactions") {
        url = base_url + "player_management/viewTransactions/" + player_id + "/" + segment;
    }

    if (info == "game_session") {
        url = base_url + "player_management/viewGameSession/" + player_id + "/" + segment;
    }

    if (info == "unfinished") {
        url = base_url + "player_management/viewUnfinishedGames/" + player_id + "/" + segment;
    }

    if (info == "saved") {
        url = base_url + "player_management/viewSavedGS/" + player_id + "/" + segment;
    }

    if (info == "auto") {
        url = base_url + "player_management/viewAutoBC/" + player_id + "/" + segment;
    }

    if (info == "pending") {
        url = base_url + "player_management/viewPendingBonuses/" + player_id + "/" + segment;
    }

    if (info == "game") {
        url = base_url + "player_management/viewGameTransactions/" + player_id + "/" + segment;
    }

    if (info == "balance_c") {
        url = base_url + "player_management/viewBalanceCorrection/" + player_id + "/" + segment;
    }

    if (info == "session") {
        url = base_url + "player_management/viewSessionTimer/" + player_id + "/" + segment;
    }

    if (info == "deposit") {
        url = base_url + "player_management/viewDepositLimits/" + player_id + "/" + segment;
    }

    var div = document.getElementById("player_info");

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
            $('#randomPassword').click(function() {
                if ($(this).attr("value") == "randomPassword") {
                    $("#passwordField").toggle();
                    $("#hiddenField").toggle();
                }
            });
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }
    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function ageText() {
    $("#age_from").attr("disabled", true);
    $("#age_to").attr("disabled", true);
    $("#age_text").attr("disabled", false);
}

function ageDate() {
    $("#age_from").attr("disabled", false);
    $("#age_to").attr("disabled", false);
    $("#age_text").attr("disabled", true);
}

function checkAll(id) {
    var list = document.getElementsByClassName(id);
    var all = document.getElementById(id);

    if (all.checked) {
        for (i = 0; i < list.length; i++) {
            list[i].checked = 1;
        }
    } else {
        all.checked;

        for (i = 0; i < list.length; i++) {
            list[i].checked = 0;
        }
    }
}

function uncheckAll(id) {
    var list = document.getElementById(id).className;
    // var all = document.getElementById(list);

    var item = document.getElementById(id);
    var allitems = document.getElementsByClassName(list);
    var cnt = 0;

    if (item.checked) {
        for (i = 0; i < allitems.length; i++) {
            if (allitems[i].checked) {
                cnt++;
            }
        }

        if (cnt == allitems.length) {
            $("#visible").prop('checked', true);
            // all.checked = 1;
        }
    } else {
        $("#visible").prop('checked', false);
        // all.checked = 0;
    }
}

//--------DOCUMENT READY---------
//---------------
$(document).ready(function() {
    PlayerManagementProcess.initialize();
});


//player management module
var PlayerManagementProcess = {

    initialize: function() {
        // console.log("initialized now!");

        //tooltip
        $('body').tooltip({
            selector: '[data-toggle="tooltip"]'
        });

        $('#from_date').prop("disabled", true);
        $('#to_date').prop("disabled", true);

        //for ranking level add form
        var is_addRankingVisible = true;
        //for add new tag
        var is_playerTagMngmtVisible = false;
        //for add new manual subtract balance tag
        var is_ManualSubtractBalanceTagMngmtVisible = false;
        //for ranking level edit form
        var is_editRankingVisible = false;
        //for edit tag
        var is_editPlayerTagMngmtVisible = false;
        //for edit manual subtract balance tag
        var is_editManualSubtractBalanceTagMngmtVisible = false;

        if (!is_addRankingVisible) {
            $('#addRankingLevelSetting').hide();
        } else {
            $('#addRankingLevelSetting').show();
        }

        if (!is_playerTagMngmtVisible) {
            $('#addPlayerTagMngmt').hide();
        } else {
            $('#addPlayerTagMngmt').show();
        }

        if (!is_ManualSubtractBalanceTagMngmtVisible) {
            $('#addManualSubtractBalanceTagMngmt').hide();
        } else {
            $('#addManualSubtractBalanceTagMngmt').show();
        }

        $("#addPlayerTagMngmtBtn").click(function() {
            // console.log('Log'+is_playerTagMngmtVisible);
            if (!is_playerTagMngmtVisible) {
                is_playerTagMngmtVisible = true;
                $('#addPlayerTagMngmt').show();
                $('#is_playerTagMngmtVisible').hide();
            } else {
                is_playerTagMngmtVisible = false;
                $('#addPlayerTagMngmt').hide();
            }
        });

        $("#addManualSubtractBalanceTagMngmtBtn").click(function () {
            if(!is_ManualSubtractBalanceTagMngmtVisible){
                is_ManualSubtractBalanceTagMngmtVisible = true;
                $('#addManualSubtractBalanceTagMngmt').show();
                // $('#is_playerTagMngmtVisible').hide();
            }else{
                is_ManualSubtractBalanceTagMngmtVisible = false;
                $('#addManualSubtractBalanceTagMngmt').hide();
            }
        });

        $("#editTagMngmt").click(function() {
            is_editPlayerTagMngmtVisible = true;
            $('#addPlayerTagMngmt').show();
        });

        $("#editManualSubtractBalanceTagMngmt").click(function () {
            is_editManualSubtractBalanceTagMngmtVisible = true;
            $('#addManualSubtractBalanceTagMngmt').show();
        });

        //show hide
        $(".addRankingLevelSettingBtn").click(function() {
            // console.log('Log'+is_addRankingVisible);
            if (!is_addRankingVisible) {
                is_addRankingVisible = true;
                $('#addRankingLevelSetting').show();
                $('.addRankingLevelSettingBtn').removeClass('glyphicon glyphicon-plus-sign');
                $('.addRankingLevelSettingBtn').addClass('glyphicon glyphicon-minus-sign');
                $('#editRankingLevelSetting').hide();
            } else {
                is_addRankingVisible = false;
                $('#addRankingLevelSetting').hide();
                $('.addRankingLevelSettingBtn').removeClass('glyphicon glyphicon-minus-sign');
                $('.addRankingLevelSettingBtn').addClass('glyphicon glyphicon-plus-sign');
            }
        });

        if (!is_editRankingVisible) {
            $('#editRankingLevelSetting').hide();
        } else {
            $('#editRankingLevelSetting').show();
        }

        //show hide
        $(".editRankingLevelSettingBtn").click(function() {
            is_editRankingVisible = true;
            $('.addRankingLevelSettingBtn').removeClass('glyphicon glyphicon-minus-sign');
            $('.addRankingLevelSettingBtn').addClass('glyphicon glyphicon-plus-sign');

            $('#editRankingLevelSetting').show();
            $('#addRankingLevelSetting').hide();
        });

        //for add vip group panel
        var is_addPanelVisible = false;

        //for ranking level edit form
        var is_editPanelVisible = false;

        if (!is_addPanelVisible) {
            $('.add_new_tag_sec').hide();
        } else {
            $('.add_new_tag_sec').show();
        }

        if (!is_editPanelVisible) {
            $('.add_new_tag_sec').hide();
        } else {
            $('.add_new_tag_sec').show();
        }

        //show hide add vip group panel
        $("#add_new_tag").click(function() {
            if (!is_addPanelVisible) {
                is_addPanelVisible = true;
                $('#tagName').val('');
                $('#tagDescription').val('');
                $('.add_new_tag_sec').show();
                $('#addVipGroupGlyhicon').removeClass('glyphicon glyphicon-plus-sign');
                $('#addVipGroupGlyhicon').addClass('glyphicon glyphicon-minus-sign');
            } else {
                is_addPanelVisible = false;
                $('.add_new_tag_sec').hide();
                $('#addVipGroupGlyhicon').removeClass('glyphicon glyphicon-minus-sign');
                $('#addVipGroupGlyhicon').addClass('glyphicon glyphicon-plus-sign');
            }
        });

        //show hide edit vip group panel
        $(".editTag").click(function() {
            is_editPanelVisible = true;
            $('.add_vip_group_sec').hide();
            $('.edit_vip_group_sec').show();
        });

        //cancel add vip group
        $(".addvip-cancel-btn").click(function() {
            is_addPanelVisible = false;
            $('.add_vip_group_sec').hide();
            $('#addVipGroupGlyhicon').removeClass('glyphicon glyphicon-minus-sign');
            $('#addVipGroupGlyhicon').addClass('glyphicon glyphicon-plus-sign');
        });
    },

    getTagDetails: function(tag_id) {
        is_playerTagMngmtVisible = true;
        var $evidence = $('#addEvidence');
        var listArr = [];
        var list = [];
        $('#addPlayerTagMngmt').show();
        $.ajax({
            'url': base_url + 'player_management/getTagDetails/' + tag_id,
            'type': 'GET',
            'dataType': "json",
            'success': function(data) {
                $('#tagId').val(data[0].tagId).attr('value', data[0].tagId).trigger('change');
                $('#playerTagName').val(data[0].tagName).attr('value', data[0].tagName).trigger('change');
                $('#tagDescription').val(data[0].tagDescription).text(data[0].tagDescription).trigger('change');
                $('#tagColor').val(data[0].tagColor).attr('value', data[0].tagColor).trigger('change');

                
                $('#wdRemark').val(data[0].wdRemark).attr('value', data[0].wdRemark).trigger('change');


                if (data[0].blockedPlayerTag) {
                    $('#chkPlayerBlockTag').prop('checked', true);
                } else {
                    $('#chkPlayerBlockTag').prop('checked', false);
                }

                if (data[0].noGameAllowedTag) {
                    $('#chkNoGameAllowedTag').prop('checked', true);
                } else {
                    $('#chkNoGameAllowedTag').prop('checked', false);
                }
            }
        }, 'json');
        return false;
    },

    getManualSubtractBalanceTagDetails: function(id) {
        is_ManualSubtractBalanceTagMngmtVisible = true;
        $('#addManualSubtractBalanceTagMngmt').show();
        $.ajax({
            'url' : base_url + 'player_management/getManualSubtractBalanceTagDetails/' + id,
            'type' : 'GET',
            'dataType' : 'json',
            'success' : function(data) {
                $('#id').val(data[0].id);
                $('#adjust_tag_name').val(data[0].adjust_tag_name);
                $('#adjust_tag_description').val(data[0].adjust_tag_description);
            }
        }, 'json');
        return false;
    },

    getRankingList: function(requestId) {
        $.ajax({
            'url': base_url + 'player_management/getRankingList',
            'type': 'GET',
            'dataType': "json",
            'success': function(data) {
                // console.log(data.length);
                for (var i = 0; i < data.length; i++) {
                    // console.log(data[i]);
                    html = '';
                    html += '<option value="' + data[i].levelId + '" >' + data[i].levelGroup + '-' + data[i].levelName + '</option>';
                    $('#addRankingLevel').append(html);
                };
            }
        }, 'json');
        return false;
    },

    getRankingLevelSettingsDetail: function(requestId) {
        $.ajax({
            'url': base_url + 'player_management/getRankingLevelSettingsDetail/' + requestId,
            'type': 'GET',
            'dataType': "json",
            'success': function(data) {
                // console.log(data[0]);
                $('#editRankingLevelGroup').val(data[0].rankingLevelGroup);
                $('#editRankingLevel').val(data[0].rankingLevel);
                $('#editMinRequiredDeposit').val(data[0].minDepositRequirement);
                $('#editCurrency').val(data[0].currency);
                $('#editPointRequirement').val(data[0].pointRequirement);
                $('#editMaxDepositAmount').val(data[0].maxDepositAmount);
                $('#editMinDepositAmount').val(data[0].minDepositAmount);
                $('#editDailyMaxWithdrawal').val(data[0].dailyMaxWithdrawal);
                $('#editRankingLevelId').val(data[0].rankingLevelSettingId);
            }
        }, 'json');
        return false;
    },

    clearPlayerBonus: function(requestId) {
        var playerDepositPromoId = $('#playerDepositPromoId').val();

        // console.log('playerDepositPromoId: '+playerDepositPromoId);
        $.ajax({
            'url': base_url + 'player_management/clearPlayerBonus/' + playerDepositPromoId,
            'type': 'GET',
            'success': function(data) {
                location.reload();
            }
        }, 'json');
        return false;
    }
};

$(document).ready(function() {
    var offset = 200;
    var duration = 500;
    jQuery(window).scroll(function() {
        if (jQuery(this).scrollTop() > offset) {
            jQuery('.custom-scroll-top').fadeIn(duration);
        } else {
            jQuery('.custom-scroll-top').fadeOut(duration);
        }
    });

    $('.custom-scroll-top').on('click', function(event) {
        event.preventDefault();
        $('html, body').animate({ scrollTop: 0 }, 'slow');
    });

    //modal Tags
    $("#tags").change(function() {
        $("#tags option:selected").each(function() {
            if ($(this).attr("value") == "Others") {
                $("#specify").show();
            } else {
                $("#specify").hide();
            }
        });
    }).change();
    //end modal Tags

    //Use Random Password
    $('#randomPassword').click(function() {
        if ($(this).attr("value") == "randomPassword") {
            $("#passwordField").toggle();
            $("#hiddenField").toggle();
        }
    });
    //End of Use Random Password

    //password press
    $("#cpassword").keyup(checkPass);
    $("#password").keyup(checkPass);
    //end password press

    //tool tip
    $(".details").tooltip({
        placement: "top",
        title: "Show Details",
    });

    $(".playerlevel").tooltip({
        placement: "top",
        title: "Adjust Player Level",
    });

    $(".lock").tooltip({
        placement: "top",
        title: "Lock/Unlock Player",
    });

    $(".block").tooltip({
        placement: "top",
        title: "Block/Unblock Player",
    });

    $(".tags").tooltip({
        placement: "top",
        title: "Edit Tag",
    });

    $(".edit").tooltip({
        placement: "top",
        title: "Edit Details",
    });

    $(".edit_tag").tooltip({
        placement: "top",
        title: "Edit this tag",
    });

    $(".delete").tooltip({
        placement: "top",
        title: "Delete this item",
    });

    $(".delete_tag").tooltip({
        placement: "top",
        title: "Delete this tag",
    });


    $("#edit_column").tooltip({
        placement: "left",
        title: "Edit columns",
    });

    $("#show_advance_search").tooltip({
        placement: "top",
        title: "Advance search",
    });


    //end of tool tip

    // view_player_list.php MAIN
    // $("#main_panel_body").slideToggle();
    $("#hide_main").click(function() {
        $("#main_panel_body").slideToggle();
        $("#hide_main_up", this).toggleClass("glyphicon glyphicon-chevron-up glyphicon glyphicon-chevron-down");
    });
    // end of view_player_list.php MAIN

    // view_plist.php MAIN
    $("#hide_search_main").click(function() {
        $("#deposit_fieldset").slideToggle();
        $("#hide_search_main", this).toggleClass("glyphicon glyphicon-chevron-down glyphicon glyphicon-chevron-up");
    });
    // end of view_plist.php MAIN

    // view_player_list.php PERSONAL
    $("#hide_personal").click(function() {
        $("#personal_panel_body").slideToggle();
        $("#hide_personal_up", this).toggleClass("glyphicon glyphicon-chevron-right glyphicon glyphicon-minus-sign");
    });
    // end of view_player_list.php PERSONAL

    // view_player_list.php TRACKING
    $("#hide_tracking").click(function() {
        $("#tracking_panel_body").slideToggle();
        $("#hide_tracking_up", this).toggleClass("glyphicon glyphicon-chevron-right glyphicon glyphicon-minus-sign");
    });
    // end of view_player_list.php TRACKING

    // view_player_list.php PAYMENT
    $("#hide_payment").click(function() {
        $("#payment_panel_body").slideToggle();
        $("#hide_payment_up", this).toggleClass("glyphicon glyphicon-chevron-right glyphicon glyphicon-minus-sign");
    });
    // end of view_player_list.php PAYMENT

    // view_player_list.php OTHER OPTIONS
    $("#hide_other_options").click(function() {
        $("#other_options_panel_body").slideToggle();
        $("#hide_other_options_up", this).toggleClass("glyphicon glyphicon-chevron-right glyphicon glyphicon-minus-sign");
    });
    // end of view_player_list.php OTHER OPTIONS

    // view_player_list.php SECURITY TOKEN
    $("#hide_security_token").click(function() {
        $("#security_token_panel_body").slideToggle();
        $("#hide_security_token_up", this).toggleClass("glyphicon glyphicon-chevron-right glyphicon glyphicon-minus-sign");
    });
    // end of view_player_list.php SECURITY TOKEN

    // view_player_list.php affiliate
    $("#hide_affiliate").click(function() {
        $("#affiliate_panel_body").slideToggle();
        $("#hide_affiliate_up", this).toggleClass("glyphicon glyphicon-chevron-right glyphicon glyphicon-minus-sign");
    });
    // end of view_player_list.php SECURITY TOKEN

    // view_player_list.php SIGNUPINFO
    $("#hide_signupinfo").click(function() {
        $("#signupinfo_panel_body").slideToggle();
        $("#hide_signupinfo_up", this).toggleClass("glyphicon glyphicon-chevron-right glyphicon glyphicon-chevron-down");
    });
    // end of view_player_list.php SIGNUPINFO

    // view_player_list.php SIGNUPINFO
    $("#hide_sign_up").click(function() {
        $("#signupinfo_panel_body").slideToggle();
        $("#hide_si_up", this).toggleClass("glyphicon glyphicon-chevron-right glyphicon glyphicon-chevron-down");
    });
    // end of view_player_list.php SIGNUPINFO

    // view_player_list.php SIGNUPINFO
    $("#hide_personal_info").click(function() {
        $("#personal_panel_body").slideToggle();
        $("#hide_pi_up", this).toggleClass("glyphicon glyphicon-chevron-right glyphicon glyphicon-chevron-down")
        return false; // prevents from jumping view on the bottom
    });
    // end of view_player_list.php SIGNUPINFO

    // view_player_list.php SIGNUPINFO
    $("#hide_contact_info").click(function() {
        $("#contact_panel_body").slideToggle();
        $("#hide_pi_up", this).toggleClass("glyphicon glyphicon-chevron-right glyphicon glyphicon-chevron-down");
    });
    // end of view_player_list.php SIGNUPINFO

    // linked account in player's info
    $("#hide_linked_account_info").click(function() {
        $("#linked_account_info_panel_body").slideToggle();
        $("#hide_la_info_up", this).toggleClass("glyphicon glyphicon-chevron-right glyphicon glyphicon-chevron-down");
    });
    // end of linked account in player's info

    // view_player_list.php SIGNUPINFO
    $("#hide_balance_info").click(function() {
        $("#balance_panel_body").slideToggle();
        $("#hide_bi_up", this).toggleClass("glyphicon glyphicon-chevron-right glyphicon glyphicon-chevron-down");
    });
    // end of view_player_list.php SIGNUPINFO

    // view_player_list.php bank details
    $("#hide_bank_info").click(function() {
        $("#bank_panel_body").slideToggle();
        $("#hide_bank_up", this).toggleClass("glyphicon glyphicon-chevron-right glyphicon glyphicon-chevron-down");
    });
    // end of view_player_list.php SIGNUPINFO

    // view_player_list.php bank details
    $("#hide_game_info").click(function() {
        $("#game_panel_body").slideToggle();
        $("#hide_game_up", this).toggleClass("glyphicon glyphicon-chevron-right glyphicon glyphicon-chevron-down");
    });
    // end of view_player_list.php SIGNUPINFO

    // view_player_list.php bank details
    $("#hide_log_info").click(function() {
        $("#log_panel_body").slideToggle();
        $("#hide_log_up", this).toggleClass("glyphicon glyphicon-chevron-right glyphicon glyphicon-chevron-down");
    });
    // end of view_player_list.php SIGNUPINFO

    // view_player_list.php responsible gaming
    $("#hide_resp_info").click(function() {
        $("#resp_game_panel_body").slideToggle();
        $("#hide_resp_up", this).toggleClass("glyphicon glyphicon-chevron-right glyphicon glyphicon-chevron-down");
    });

    // view_player_list.php responsible gaming
    $("#hide_comm_pref_info").click(function() {
        $("#comm_pref_panel_body").slideToggle();
        $("#hide_comm_pref_up", this).toggleClass("glyphicon glyphicon-chevron-right glyphicon glyphicon-chevron-down");
    });

    // end of view_player_list.php SIGNUPINFO

    $("#hide_search").click(function() {
        $("#search_body").slideToggle();
        $("#hide_search_up", this).toggleClass("glyphicon glyphicon-chevron-up glyphicon glyphicon-chevron-down");
    });

    // view_player_list.php withdrawal info
    $("#hide_withrawal_info").click(function() {
        $("#withrawal_panel_body").slideToggle();
        $("#hide_withrawal_up", this).toggleClass("glyphicon glyphicon-chevron-up glyphicon glyphicon-chevron-down");
    });

    // transfer_condition_details.php transfer info
    $("#hide_transfer_condition_info").click(function() {
        $(".transfer_condition_panel_body").slideToggle();
        $("#hide_transfer_condition_up", this).toggleClass("glyphicon glyphicon-chevron-up glyphicon glyphicon-chevron-down");
    });

    $('#save_changes').on("click", function(e) {
        e.preventDefault();
        $("form#modal_column_form").submit();
    });

    // //scrolling down
    // $('html, body').animate({
    //     scrollTop: $("#toggleView").offset().top
    // }, 1500);

    $("#first_deposit_date").click(function() {
        if ($('#first_deposit_date').is(":checked") || $('#second_deposit_date').is(":checked") || $('#never_deposited').is(":checked")) {
            $('#from_date').prop("disabled", false);
            $('#to_date').prop("disabled", false);
        } else {
            $('#from_date').prop("disabled", true);
            $('#to_date').prop("disabled", true);
        }
    });

    $("#second_deposit_date").click(function() {
        if ($('#first_deposit_date').is(":checked") || $('#second_deposit_date').is(":checked") || $('#never_deposited').is(":checked")) {
            $('#from_date').prop("disabled", false);
            $('#to_date').prop("disabled", false);
        } else {
            $('#from_date').prop("disabled", true);
            $('#to_date').prop("disabled", true);
        }
    });

    $("#never_deposited").click(function() {
        if ($('#first_deposit_date').is(":checked") || $('#second_deposit_date').is(":checked") || $('#never_deposited').is(":checked")) {
            $('#from_date').prop("disabled", false);
            $('#to_date').prop("disabled", false);
        } else {
            $('#from_date').prop("disabled", true);
            $('#to_date').prop("disabled", true);
        }
    });

    $('#less_deposit_amount').focusout(function() {
        var check_greater = $('#less_deposit_amount').val();
        var check_less = $('#greater_deposit_amount').val();
        if (check_less == "" && check_greater == "") {
            $("#start_first_deposit_date").attr('disabled', true);
            $("#end_first_deposit_date").attr('disabled', true);
            $("#start_second_deposit_date").attr('disabled', true);
            $("#end_second_deposit_date").attr('disabled', true);
        } else {
            $("#start_first_deposit_date").attr('disabled', false);
            $("#end_first_deposit_date").attr('disabled', false);
            $("#start_second_deposit_date").attr('disabled', false);
            $("#end_second_deposit_date").attr('disabled', false);
        }
    });

    $('#greater_deposit_amount').focusout(function() {
        var check_greater = $('#greater_deposit_amount').val();
        var check_less = $('#less_deposit_amount').val();
        if (check_less == "" && check_greater == "") {
            $("#start_first_deposit_date").attr('disabled', true);
            $("#end_first_deposit_date").attr('disabled', true);
            $("#start_second_deposit_date").attr('disabled', true);
            $("#end_second_deposit_date").attr('disabled', true);
        } else {
            $("#start_first_deposit_date").attr('disabled', false);
            $("#end_first_deposit_date").attr('disabled', false);
            $("#start_second_deposit_date").attr('disabled', false);
            $("#end_second_deposit_date").attr('disabled', false);
        }
    });

    $(".number_only").keydown(function(e) {
        var code = e.keyCode || e.which;
        // Allow: backspace, delete, tab, escape, enter and .
        if ($.inArray(code, [46, 8, 9, 27, 13, 110]) !== -1 ||
            // Allow: Ctrl+A
            (code == 65 && e.ctrlKey === true) ||
            // Allow: home, end, left, right, down, up
            (code >= 35 && code <= 40)) {
            // let it happen, don't do anything
            return;
        }
        // Ensure that it is a number and stop the keypress
        if ((e.shiftKey || (code < 48 || code > 57)) && (code < 96 || code > 105)) {
            e.preventDefault();
        }
    });

    $('input[name="deposit_amount_type"]').change(function() {
        $('#deposit_amount').prop('disabled', $(this).val() == 3);
    });

    $('body').on('click', '.block.blockAction', function(e){
        var theTarget$El = $(e.target);
        if( $.isEmptyObject(theTarget$El.data('player_id')) ){ // when clicked under icon
            theTarget$El = theTarget$El.closest('[data-player_id]');
        }
        blockPlayer(theTarget$El.data('player_id'));
    });
});

function checkDepositCheckBox() {
    //console.log('test');
    var first_deposited = document.getElementById("first_deposited");
    var second_deposited = document.getElementById("second_deposited");
    var never_deposited = document.getElementById("never_deposited");
    var deposited = document.getElementById("deposited");

    if (first_deposited.checked == true || second_deposited.checked == true || deposited.checked == true) {
        $("#deposit_amount").attr('disabled', $('input[name="deposit_amount_type"]').val() == 3);
        $("#deposit_amount_type1").attr('disabled', false);
        $("#deposit_amount_type2").attr('disabled', false);
        $("#deposit_amount_type3").attr('disabled', false);
        $("#deposit_amount_type3").prop('checked', true);
        $("#deposit_date_from").attr('disabled', false);
        $("#deposit_date_to").attr('disabled', false);
    } else if (never_deposited.checked == true) {
        $("#deposit_amount").attr('disabled', true);
        $("#deposit_amount").val('');
        $("#deposit_amount_type1").attr('disabled', true);
        $("#deposit_amount_type2").attr('disabled', true);
        $("#deposit_amount_type3").attr('disabled', true);
        $("#deposit_amount_type3").attr('checked', false);
        $("#deposit_date_from").attr('disabled', true);
        $("#deposit_date_to").attr('disabled', true);
    }
}

function resetSearching() {
    $("#first_deposited").attr('checked', false);
    $("#second_deposited").attr('checked', false);
    $("#never_deposited").attr('checked', false);

    $("#deposit_amount_type1").attr('checked', false);
    $("#deposit_amount_type1").attr('disabled', true);
    $("#deposit_amount_type2").attr('disabled', true);

    $("#deposit_amount").attr('disabled', true);
    $("#deposit_amount").val('');

    $("#deposit_date_from").attr('required', false);
    $("#deposit_date_to").attr('required', false);
    $("#deposit_date_from").attr('disabled', true);
    $("#deposit_date_to").attr('disabled', true);
    $("#deposit_date_from").val('');
    $("#deposit_date_to").val('');


    $("#wallet_amount_type1").attr('checked', false);
    $("#wallet_amount_type2").attr('checked', false);
    $("#wallet_amount_type1").attr('disabled', true);
    $("#wallet_amount_type2").attr('disabled', true);


    $("#main_wallet").attr('checked', false);
    $("#pt_wallet").attr('checked', false);
    $("#ag_wallet").attr('checked', false);

    $("#wallet_amount").attr('disabled', true);
    $("#wallet_amount").val('');

    // $("#status").val('');
    // $("#tagged").val('');
}

function checkDepositAmount() {
    var deposit_amount_type1 = document.getElementById("deposit_amount_type1");
    var deposit_amount_type2 = document.getElementById("deposit_amount_type2");

    if (deposit_amount_type1.checked == true || deposit_amount_type2.checked == true) {
        $("#deposit_amount").attr('disabled', false);
    }
}

function checkWalletCheckBox() {
    var main_wallet = document.getElementById("main_wallet");
    var pt_wallet = document.getElementById("pt_wallet");
    var ag_wallet = document.getElementById("ag_wallet");

    if (main_wallet.checked == true || pt_wallet.checked == true || ag_wallet.checked == true) {
        $("#wallet_amount").attr('disabled', false);
        $("#wallet_amount").attr('required', true);
        $("#wallet_amount_type1").attr('checked', true);
        $("#wallet_amount_type1").attr('disabled', false);
        $("#wallet_amount_type2").attr('disabled', false);
    }
}

function searchGameHistory(username) {
    var xmlhttp = GetXmlHttpObject();

    var start_date = $('#dateRangeValueStart').val();
    var end_date = $('#dateRangeValueEnd').val();
    url = base_url + "marketing_management/searchGameLog/" + username;
    var div = document.getElementById('changeable_table');
    var posting = $.post(url, { 'start_date': start_date, 'end_date': end_date });

    posting.done(function(data) {
        div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        div.innerHTML = data;

        $('#gameTable').DataTable({
            "responsive": {
                details: {
                    type: 'column'
                }
            },
            "columnDefs": [{
                className: 'control',
                orderable: false,
                targets: [1],
                "visible": false
            }],
            "order": [1, 'desc'],
            "pageLength": 30,
            "dom": '<"top"f>rt<"bottom"ip>', // l = rows, f = search, i = # entries, p = prev and next btn
            "fnDrawCallback": function(oSettings) {
                $('.selected_date').prependTo($('.top'));
            }
        });
    });
}

function checkPlayerInfo(value) {
    var checkbox = document.getElementById(value);
    var all = document.getElementById('checkall');

    var list = document.getElementsByClassName('checkall');
    var cnt = 0;

    if (checkbox.checked) {
        $('#' + value + "_form").show();
    } else {
        $('#' + value + "_form").hide();

        all.checked = 0;
    }

    for (i = 0; i < list.length; i++) {
        if (list[i].checked) {
            cnt++;
        }
    }

    if (cnt == 6) {
        all.checked = 1;
    }
}

function checkSearchPlayerInfo(value) {
    var checkbox = document.getElementById(value);

    if (checkbox.checked) {
        $('#' + value + "_search").show();
    } else {
        $('#' + value + "_search").hide();
    }
}

function checkAllPlayerInfo(value) {
    var checkbox = document.getElementById(value);
    // console.log("here"+value);
    var signup = document.getElementById('signup');
    var personal = document.getElementById('personal');
    var withdraw_condition = document.getElementById('withdraw_condition');
    var contact = document.getElementById('contact');
    var account = document.getElementById('account');
    var game = document.getElementById('game');
    var bank = document.getElementById('bank');
    var players = document.getElementById('players');
    var linked_accounts = document.getElementById('linked_accounts');

    // console.log("test: "+checkbox.checked);

    if (checkbox.checked) {
        $("#signup_form").show();
        $("#personal_form").show();
        $("#contact_form").show();
        $("#account_form").show();
        $("#game_form").show();
        $("#bank_form").show();
        $("#players_form").show();
        $("#withdraw_condition_form").show();
        $("#linked_accounts_form").show();

        signup.checked = 1;
        personal.checked = 1;
        contact.checked = 1;
        account.checked = 1;
        game.checked = 1;
        bank.checked = 1;
        players.checked = 1;
        withdraw_condition.checked = 1;
        linked_accounts.checked = 1;
    } else {
        $("#signup_form").hide();
        $("#personal_form").hide();
        $("#contact_form").hide();
        $("#account_form").hide();
        $("#game_form").hide();
        $("#bank_form").hide();
        $("#players_form").hide();
        $("#withdraw_condition_form").hide();
        $("#linked_accounts_form").hide();

        withdraw_condition.checked = 0;
        signup.checked = 0;
        personal.checked = 0;
        contact.checked = 0;
        account.checked = 0;
        game.checked = 0;
        bank.checked = 0;
        players.checked = 0;
        linked_accounts.checked = 0;
    }
}

function changeOnlineList(type) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "player_management/changeOnlineList/" + type;

    var div = document.getElementById('nav_content');

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
            $('#playersTable').DataTable({
                "responsive": {
                    details: {
                        type: 'column'
                    }
                },
                "columnDefs": [{
                    className: 'control',
                    orderable: false,
                    targets: 0
                }],
                "order": [1, 'asc']
            });
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }

    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}


function blockPlayer(player_id) {
    var dst_url = "/player_management/load_block_player_view/" + player_id;
    var $dfd = open_modal('block_player', dst_url, 'Block Player');
    var oBlockPlayerForm = {};

    $('#block_player').off('hide.bs.modal');
    $dfd.done(function(resp4dst_url, resp4shown){
        oBlockPlayerForm = blockPlayerForm.initialize();
        oBlockPlayerForm.onReady();
    }).fail(function(resp4dst_url, resp4shown) {

    });

    $('#block_player').on('hide.bs.modal', function(e){
        if( ! $.isEmptyObject(oBlockPlayerForm) ){
            oBlockPlayerForm.destruct();
        }
        oBlockPlayerForm = {};
    });
}

function refreshAccountInfo(player_id) {
    var xmlhttp = GetXmlHttpObject();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "player_management/refreshAccountInfo/" + player_id;

    var div = document.getElementById('balance_panel_body');

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }

    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function player_notes(player_id) {
    var dst_url = "/player_management/player_notes/" + player_id;
    open_modal('player_notes', dst_url, 'Player Notes');
}

function add_player_notes(self, player_id) {
    var url = $(self).attr('action');
    var params = $(self).serializeArray();
    $.post(url, params, function(data) {
        if (data.success) {
            refresh_modal('player_notes', "/player_management/player_notes/" + player_id, 'Player Notes', true);
        }
    });
    return false;
}

function edit_player_note(self, note_id, player_id, action = 'edit') {
    var note_list    = $(self).closest('.note_list');
    var old_note     = $(note_list).find('.old_note').val();
    var display_note = $(note_list).find('.display_note');
    var edit_notes_button = $(note_list).find('.edit_notes');
    var save_notes_button = $(note_list).find('.save_notes');
    var cancel_notes_button = $(note_list).find('.cancel_notes');
    var dropdown_notes_button = $(note_list).find('.drop_notes');

    if(action == 'edit'){//Edit
        $(save_notes_button).removeClass("hidden");
        $(cancel_notes_button).removeClass("hidden");
        $(edit_notes_button).addClass("hidden");
        $(dropdown_notes_button).addClass("hidden");
        $(display_note).empty().append('<textarea class="form-control" style="min-width: 100%" name="new_notes" required></textarea>');
        $(display_note).find('textarea').val(old_note.replace(/<br ?\/?>/g, ""));

    }else if (action == 'save'){ //Save
        var url = $(self).attr('action');
        var params = $(self).serializeArray();
        $.post(url, params, function(data) {
            if (data.success) {
                $(save_notes_button).addClass("hidden");
                $(cancel_notes_button).addClass("hidden");
                $(edit_notes_button).removeClass("hidden");
                refresh_modal('player_notes', "/player_management/player_notes/" + player_id, 'Player Notes', true);
            }
        });

    }else{ //Cancel
        $(save_notes_button).addClass("hidden");
        $(cancel_notes_button).addClass("hidden");
        $(edit_notes_button).removeClass("hidden");
        $(dropdown_notes_button).removeClass("hidden");
        $(display_note).empty().append('<textarea class="form-control input-sm" rows="2" style="max-width: 100%; min-width: 100%;" disabled>' + old_note + '</textarea>');
    }

    return false;
}

function remove_player_note(note_id, player_id) {
    var confirm_val = confirm('Are you sure you want to delete this player note?');
    if (confirm_val) {
        var url = '/player_management/remove_player_note/' + note_id + '/' + player_id;
        // console.log(url);
        $.getJSON(url, function(data) {
            if (data.success) {
                refresh_modal('player_notes', "/player_management/player_notes/" + player_id, 'Player Notes', true);
            }
        });
    }
    return false;
}

function open_modal(name, dst_url, title) {
    var main_selector = '#' + name;

    var label_selector = '#label_' + name;
    $(label_selector).html(title);

    var body_selector = main_selector + ' .modal-body';
    var target = $(body_selector);
    var $dfd = $.Deferred();
    var $dfd4dst_url = $.Deferred();
    var $dfd4modal_shown = $.Deferred();
    target.html('<center><img src="' + imgloader + '"></center>').delay(1000).load(dst_url, function( response, status, xhr ) { // complete
        $dfd4dst_url.resolve({});
    });

    $(main_selector).modal('show');
    $(main_selector).on('shown.bs.modal', function(e){
        // console.log("I want this to appear after the modal has opened!");
        $dfd4modal_shown.resolve({});
    });

    $.when( $dfd4dst_url.promise(), $dfd4modal_shown.promise() )
        .done(function(res4dst_url, res4modal_shown) {
            $dfd.resolve({
                dst_url : res4dst_url,
                modal_shown : res4modal_shown
            });
    });

    return $dfd.promise();
}

function refresh_modal(name, dst_url, title, loader = false) {
    var main_selector = '#' + name;
    var body_selector = main_selector + ' .modal-body';
    var target = $(body_selector);

    if(loader){
        target.html('<center><img src="' + imgloader + '"></center>').delay(1000).load(dst_url);
    }else{
        target.load(dst_url);
    }

}

function iframe_promotion() {
    var xmlhttp = GetXmlHttpObject();
    var player_id = $('#player_id').val();

    if (xmlhttp == null) {
        alert("Browser does not support HTTP Request");
        return;
    }

    url = base_url + "iframe_module/iframe_promotion";

    var div = document.getElementById('balance_adjustment_history');

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
            div.innerHTML = xmlhttp.responseText;
            $('#adjustmentTable').DataTable({
                "responsive": {
                    details: {
                        type: 'column'
                    }
                },
                "columnDefs": [{
                    className: 'control',
                    orderable: false,
                    targets: 0
                }],
                "order": [1, 'asc']
            });
        }
        if (xmlhttp.readyState != 4) {
            div.innerHTML = '<table class="table table-hover"><tr><td align="center" valign="middle" style="border:0; height:358px; width: 220px; text-align: center; font-size: 11px"><img src="' + imgloader + '"><br/>Loading. Please wait.</td></tr></table>';
        }
    }

    xmlhttp.open("GET", url, true);
    xmlhttp.send(null);
}

function autogrow(textarea){
    var adjustedHeight = textarea.clientHeight;

    adjustedHeight = Math.max(textarea.scrollHeight,adjustedHeight);
    if (adjustedHeight>textarea.clientHeight){
        textarea.style.height = adjustedHeight + 'px';
    }
}

// ==== PlayerManagement class ====
var PlayerManagement = PlayerManagement || {};
PlayerManagement.uri = {};
PlayerManagement.langs = {};
PlayerManagement.initial = function(options){
    var _this = this;
    _this.dbg = false; // work in querystring has "dbg=1".
    _this.amountFormat = false; /// '0,0.00';
    _this.theUpgradeLevelModalBodySelector = '.ajaxManuallyUpgradeLevelModalBody';
    _this.theDowngradeLevelModalBodySelector = '.ajaxManuallyDowngradeLevelModalBody';
    _this = $.extend(true, {}, _this, options );

    // detect dbg for console.log
    var query = window.location.search.substring(1);
    var qs = _this.parse_query_string(query);
    if ('dbg' in qs
        && typeof (qs.dbg) !== 'undefined'
        && qs.dbg
    ) {
        _this.dbg = true;
    }

    return _this;
}

/**
 * Cloned from promotionDetails.parse_query_string()
 *
 * @param {*} query
 */
PlayerManagement.parse_query_string = function (query) {
    var vars = query.split("&");
    var query_string = {};
    for (var i = 0; i < vars.length; i++) {
        var pair = vars[i].split("=");
        var key = decodeURIComponent(pair[0]);
        var value = decodeURIComponent(pair[1]);
        // If first entry with this name
        if (typeof query_string[key] === "undefined") {
            query_string[key] = decodeURIComponent(value);
            // If second entry with this name
        } else if (typeof query_string[key] === "string") {
            var arr = [query_string[key], decodeURIComponent(value)];
            query_string[key] = arr;
            // If third or later entry with this name
        } else {
            query_string[key].push(decodeURIComponent(value));
        }
    }
    return query_string;
}; // EOF parse_query_string


/**
 * convert the _this.featurelist.vip_level_maintain_settings to boolean type
 */
PlayerManagement.is_vip_level_maintain_settings = function () {
    var _this = this;
    var isVipLevelMaintainSettings = null;
    if (isNaN(parseInt(_this.featurelist.vip_level_maintain_settings))) {
        isVipLevelMaintainSettings = false;
    } else if (parseInt(_this.featurelist.vip_level_maintain_settings) == 0) {
        isVipLevelMaintainSettings = false;
    } else {
        isVipLevelMaintainSettings = true;
    }
    return isVipLevelMaintainSettings;
} // EOF is_vip_level_maintain_settings


/**
 * convert the _this.featurelist.disable_player_multiple_upgrade to boolean type
 */
PlayerManagement.is_disable_player_multiple_upgrade = function () {
    var _this = this;
    var isDisablePlayerMultipleUpgrade = null;
    if (isNaN(parseInt(_this.featurelist.disable_player_multiple_upgrade))) {
        isDisablePlayerMultipleUpgrade = true;
    } else if (parseInt(_this.featurelist.disable_player_multiple_upgrade) == 0) {
        isDisablePlayerMultipleUpgrade = false;
    } else {
        isDisablePlayerMultipleUpgrade = true;
    }
    return isDisablePlayerMultipleUpgrade;
} // EOF is_disable_player_multiple_upgrade


// _this.configlist.enable_separate_accumulation_in_setting
PlayerManagement.is_enable_separate_accumulation_in_setting = function () {
    var _this = this;
    var isEnableSeparateAccumulationInSetting = null;
    if (isNaN(parseInt(_this.configlist.enable_separate_accumulation_in_setting))) {
        isEnableSeparateAccumulationInSetting = false;
    } else if (parseInt(_this.configlist.enable_separate_accumulation_in_setting) == 1) {
        isEnableSeparateAccumulationInSetting = true;
    } else {
        isEnableSeparateAccumulationInSetting = false;
    }
    return isEnableSeparateAccumulationInSetting;
}

// configlist.vip_setting_form_ver
PlayerManagement.number_vip_setting_form_ver = function () {
    var _this = this;
    var numberVipSettingFormVer = null;
    if (isNaN(parseInt(_this.configlist.vip_setting_form_ver))) {
        numberVipSettingFormVer = 1;
    } else if (parseInt(_this.configlist.vip_setting_form_ver) == 2) {
        numberVipSettingFormVer = 2;
    } else {
        numberVipSettingFormVer = 1;
    }
    return numberVipSettingFormVer;
}

PlayerManagement.onReady = function(){
    var _this = this;
    _this._registerEvents();

}

PlayerManagement._registerEvents = function () {
    var _this = this;

    $('#ajaxManuallyDowngradeLevelModal')
        .on('show.bs.modal', function (e) {
            _this.show_ajaxManuallyDowngradeLevelModal(e);
        })
        .on('shown.bs.modal', function (e) {
            _this.shown_ajaxManuallyDowngradeLevelModal(e);
        })
        .on('hide.bs.modal', function (e) {
            _this.hide_ajaxManuallyDowngradeLevelModal(e);
        })
        .on('hidden.bs.modal', function (e) {
            _this.hidden_ajaxManuallyDowngradeLevelModal(e);
        });

    $('#ajaxManuallyUpgradeLevelModal')
        .on('show.bs.modal', function (e) {
            _this.show_ajaxManuallyUpgradeLevelModal(e);
        })
        .on('shown.bs.modal', function (e) {
            _this.shown_ajaxManuallyUpgradeLevelModal(e);
        })
        .on('hide.bs.modal', function (e) {
            _this.hide_ajaxManuallyUpgradeLevelModal(e);
        })
        .on('hidden.bs.modal', function (e) {
            _this.hidden_ajaxManuallyUpgradeLevelModal(e);
        });


    $('body').on('click', '.manuallyDowngradeLevel',function(e){
        _this.clicked_manuallyDowngradeLevel(e);
    });

    $('body').on('click', '.manuallyUpgradeLevel',function(e){
        _this.clicked_manuallyUpgradeLevel(e);
    });

    /// reset player password
    $('body').on( 'submit','form[name="player_reset_password"]', function (e) {
        _this.submited4player_reset_password(e);
        return false; // handle by PlayerManagement.submited4player_reset_password()
    }); // EOF $('body').on( 'submit','form[...
}


PlayerManagement.clicked_manuallyDowngradeLevel = function(e){
    var _this = this;
    var theTarget$El = $(e.target);
    var playerId = theTarget$El.data('player_id');

    $('#ajaxManuallyDowngradeLevelModal').modal('show');
}


PlayerManagement.clicked_manuallyUpgradeLevel = function(e){
    var _this = this;
    var theTarget$El = $(e.target);
    var playerId = theTarget$El.data('player_id');

    $('#ajaxManuallyUpgradeLevelModal').modal('show');
}

PlayerManagement.show_ajaxManuallyUpgradeLevelModal = function(e){
    var _this = this;
    var theUri = _this.uri.manuallyUpgradeLevel; // playerId
    theUri = theUri.replace(/{\$playerId}/gi, _this.playerId);

    var jqXHR = $.ajax({
        type: 'POST', // for add/update a detail
        url: theUri,
        // data: theData,
        beforeSend: function () {
            _this.initialCheckScheduleBodyInUpgrade();
        },
        complete: function () {

        }
    });
    jqXHR.done(function (data, textStatus, jqXHR) {
        _this.handlePLAHInUpgrade(data.PLAH);
        // reload the tab
        changeUserInfoTab(1); // <!-- signup_info -->
    });
    jqXHR.fail(function (jqXHR, textStatus, errorThrown) {
        // console.log('manuallyDowngradeLevel.fail().jqXHR',jqXHR);
        // console.log('manuallyDowngradeLevel.fail().textStatus',textStatus);
        // console.log('manuallyDowngradeLevel.fail().errorThrown',errorThrown)
        // if ( errorThrown == 'Forbidden'
        //     || errorThrown == 'Unauthorized'
        // ) {
        //     $('#somethingWrongModal').modal('show');
        // }
    });
}
PlayerManagement.shown_ajaxManuallyUpgradeLevelModal = function(e){

}
PlayerManagement.hide_ajaxManuallyUpgradeLevelModal = function(e){

}
PlayerManagement.hidden_ajaxManuallyUpgradeLevelModal = function(e){

}

PlayerManagement.show_ajaxManuallyDowngradeLevelModal = function(e){
    var _this = this;
    var theUri = _this.uri.manuallyDowngradeLevel; // playerId
    theUri = theUri.replace(/{\$playerId}/gi, _this.playerId);

    var jqXHR = $.ajax({
        type: 'POST', // for add/update a detail
        url: theUri,
        // data: theData,
        beforeSend: function () {
            _this.initialCheckScheduleBodyInDowngrade();
        },
        complete: function () {

        }
    });
    jqXHR.done(function (data, textStatus, jqXHR) {
        _this.handlePLAHInDowngrade(data.PLAH);
        // reload the tab
        changeUserInfoTab(1); // <!-- signup_info -->
    });
    jqXHR.fail(function (jqXHR, textStatus, errorThrown) {
        // console.log('manuallyDowngradeLevel.fail().jqXHR',jqXHR);
        // console.log('manuallyDowngradeLevel.fail().textStatus',textStatus);
        // console.log('manuallyDowngradeLevel.fail().errorThrown',errorThrown)
        // if ( errorThrown == 'Forbidden'
        //     || errorThrown == 'Unauthorized'
        // ) {
        //     $('#somethingWrongModal').modal('show');
        // }
    });
}
PlayerManagement.shown_ajaxManuallyDowngradeLevelModal = function(e){

}
PlayerManagement.hide_ajaxManuallyDowngradeLevelModal = function(e){

}
PlayerManagement.hidden_ajaxManuallyDowngradeLevelModal = function(e){

}

PlayerManagement.initialCheckScheduleBodyInUpgrade = function(){
    var _this = this;

    $(_this.theUpgradeLevelModalBodySelector).find('.check_schedule_title').text(_this.langs.loading);

    $(_this.theUpgradeLevelModalBodySelector).find('.next_level_name_value').html('');

    $(_this.theUpgradeLevelModalBodySelector).find('.above-content').html('');

    $(_this.theUpgradeLevelModalBodySelector).find('.current_level_value').html('');

    $(_this.theUpgradeLevelModalBodySelector).find('.below-content').html('');

    $(_this.theUpgradeLevelModalBodySelector).find('.cronjob_period').html('');

    $(_this.theUpgradeLevelModalBodySelector).find('.cronjob_period_value').html('');

} // EOF initialCheckScheduleBodyInUpgrade

/**
 * Get element's outter Html like node.outerHTML .
 * @param {string} selectorStr The selector string.
 * @return {string} The html script.
 */
// PlayerManagement.outerHtml = function(selectorStr){
//     return $('<div>').append($(selectorStr).clone()).html();
// };

PlayerManagement.getHtmlAfterAppliedWithTpl = function(theTplSelector, theApplyData){
    // #tpl-col-md-offset1-6-5
    var _this = this;
    var tpl$El = $( '<div>'+ $(theTplSelector).html()+ '</div>' );

    $.each(theApplyData, function(indexString, currVal){
        tpl$El.find(indexString).html(currVal);
    });

    return tpl$El.html(); //_this.outerHtml(tpl$El);
} // getHtmlAfterAppliedWithTpl

/**
 * Handle PLAH In Upgrade
 * @param {*} thePLAH
 */
PlayerManagement.handlePLAHInUpgrade = function(thePLAH){
    var _this = this;
    //_this.handleBetAmountWithResult_formula_detail(thePLAH.result_formula_detail);

    // var totalDepositAmount = 0;
    // var totalLossAmount = 0;
    // var totalWinAmount = 0;
    // if( typeof(thePLAH.result_formula_detail) !== 'undefined'){
    //     if ( 'deposit_amount' in thePLAH.result_formula_detail) {
    //         totalDepositAmount = thePLAH.result_formula_detail.deposit_amount;
    //     }
    //     if ( 'loss_amount' in thePLAH.result_formula_detail) {
    //         totalLossAmount = thePLAH.result_formula_detail.loss_amount;
    //     }
    //     if ( 'win_amount' in thePLAH.result_formula_detail) {
    //         totalWinAmount = thePLAH.result_formula_detail.win_amount;
    //     }
    // }

    // lastNewVipLevelData is empty, maybe the cause by "Downgrade Failed".
    // Get the last changed VipLevelData, lastNewVipLevelData.
    var lastNewVipLevelData = {}; // default
    if( !$.isEmptyObject(thePLAH.playerLevelInfo.newVipLevelDataList) ){
        var counter = thePLAH.playerLevelInfo.newVipLevelDataList.length;
        var _newVipLevelDataList = [];
        if (counter > 0) { // for upgrade to top level.
            _newVipLevelDataList = thePLAH.playerLevelInfo.newVipLevelDataList.filter(function (curVal) {
                return 'isConditionMet' in curVal;
            }, _this);
        }
        var _counter = _newVipLevelDataList.length;
        lastNewVipLevelData = _newVipLevelDataList[_counter - 1];
    }else{
        // not met the condition for downgrade.
        lastNewVipLevelData = thePLAH.playerLevelInfo.playerLevelData;
    }

    var firstFailedOfNewVipLevelData = _this.getFirstFailedOfNewVipLevelDataWithPLAH(thePLAH);
    var lastFailedOfNewVipLevelData = _this.getLastFailedOfNewVipLevelDataWithPLAH(thePLAH);
    var lastUpgradedOfNewVipLevelData = _this.getLastUpgradedOfNewVipLevelDataWithPLAH(thePLAH);
    var _hasUpgraded = _this.getHasUpgradedWithPLAH(thePLAH);
    var hasUpgraded = _hasUpgraded.bool;

    var lastestNewVipLevelData = _this.getLastestNewVipLevelDataWithPLAH(thePLAH);
    var isEnableSeparateAccumulationInSetting = _this.is_enable_separate_accumulation_in_setting();
    var numberVipSettingFormVer = _this.number_vip_setting_form_ver();

    var theMode = '';
    if (isEnableSeparateAccumulationInSetting) {
        // separate accumulation
        theMode += 'SA';
    } else {
        // common accumulation
        theMode += 'CA';
    }
    if (numberVipSettingFormVer == 2) {
        // separate bet amount by game tree
        theMode += 'SB';
    } else {
        // common(total) bet amount
        theMode += 'CB';
    }

    if (theMode == 'CACB') {
        _this.handlePLAHInUpgradeCB_for_CACB.apply(_this, arguments); // will call this.handlePLAHInUpgradeCB_for_CACB().
    } else if (theMode == 'CASB') {
        _this.handlePLAHInUpgradeCB_for_CASB.apply(_this, arguments); // will call this.handlePLAHInUpgradeCB_for_CASB().
    } else if (theMode == 'SACB') {
        _this.handlePLAHInUpgradeCB_for_SACB.apply(_this, arguments); // will call this.handlePLAHInUpgradeCB_for_SACB().
    } else if (theMode == 'SASB') {
        _this.handlePLAHInUpgradeCB_for_SASB.apply(_this, arguments); // will call this.handlePLAHInUpgradeCB_for_SASB().
    }

    // ======= ======= ======= ======= ======= ======= ======= =======
    // @todo 回填上述 函式。
    if (false) {
        var isSeparateBets = false;
        if (typeof (lastestNewVipLevelData.vip_upgrade_setting) !== 'undefined'
            && 'bet_amount_settings' in lastestNewVipLevelData.vip_upgrade_setting
        ) {
            if (lastestNewVipLevelData.vip_upgrade_setting.bet_amount_settings !== null) {
                isSeparateBets = true;
            }
        }

        // @todo 驗證
        // - 升級失敗的等級設定
        // - 若是最高等級，則使用最高等級的設定。
        // - 連續升級、單升級。
        var HUAC_Parsed = {};
        var HUAC_game_id4human_list = [];
        if (hasUpgraded) {
            if (!$.isEmptyObject(lastUpgradedOfNewVipLevelData)) {
                HUAC_Parsed = _this.parseAmountsInfoFromFormula(lastUpgradedOfNewVipLevelData.remark.rule);
                HUAC_game_id4human_list = lastUpgradedOfNewVipLevelData.remark.game_id4human_list;
            }
        }
        if (!$.isEmptyObject(lastFailedOfNewVipLevelData)) {
            HUAC_Parsed = _this.parseAmountsInfoFromFormula(lastFailedOfNewVipLevelData.remark.rule);
            HUAC_game_id4human_list = lastFailedOfNewVipLevelData.remark.game_id4human_list;
        }
        _this.handleUpgradeAboveContent(HUAC_Parsed, HUAC_game_id4human_list);
        // console.log('handleUpgradeAboveContent.HUAC_Parsed:', HUAC_Parsed, 'HUAC_game_id4human_list:', HUAC_game_id4human_list);


        // HUBC = handleUpgradeBelowContent
        // @todo 驗證：
        // - 下方應該尋找 未升級成功的數據。
        // - 若是最高等級，則使用最高等級的數據。
        // - 連續升級、單升級。
        var HUBC_Parsed = _this.parseAmountsInfoFromFormula(lastFailedOfNewVipLevelData.remark.rule);
        var HUBC_game_id4human_list = lastFailedOfNewVipLevelData.remark.game_id4human_list;
        var theSeparateAccumulationCalcResult = lastFailedOfNewVipLevelData.remark.separate_accumulation_calcResult;
        var prefixWhileEmptyPrecon = _this.langs.current_vip4prefix;
        var theSelector = _this.theUpgradeLevelModalBodySelector + ' .below-content';
        // console.log('HUBC_Parsed:', HUBC_Parsed);
        _this.handleUpgradeBelowContent(HUBC_Parsed // #1
            , HUBC_game_id4human_list // #2
            , theSelector // #3
            , _this.HUBC_getAmountNameCB // #4
            , _this.HUBC_getAmountValueCB // #5
            , prefixWhileEmptyPrecon // #6
            , theSeparateAccumulationCalcResult //  #7
        );

        var currentLevelNameValue = thePLAH.playerLevelInfo.playerLevelData.groupLevelName
        if (hasUpgraded) {
            currentLevelNameValue = lastUpgradedOfNewVipLevelData.groupLevelName;
        }
        $(_this.theUpgradeLevelModalBodySelector).find('.current_level_value').html(currentLevelNameValue);


        var nextLevelNameValue = _this.langs.na; // default, for top of level.
        // if( typeof(lastFailedOfNewVipLevelData) !== 'undefined' ){
        //     nextLevelNameValue = lastFailedOfNewVipLevelData.groupLevelName;
        // }

        if (lastestNewVipLevelData.isConditionMet == true) {
            nextLevelNameValue = lastFailedOfNewVipLevelData.groupLevelName;
            // nextLevelNameValue = lastestNewVipLevelData.nextVipLevelData.groupLevelName;
        } else {
            nextLevelNameValue = lastestNewVipLevelData.groupLevelName;
        }

        $(_this.theUpgradeLevelModalBodySelector).find('.next_level_name_value').html(nextLevelNameValue);

        var checkScheduleTitle = null;
        if (hasUpgraded) {
            checkScheduleTitle = _this.langs.upgrade_condition_met;
        } else {
            checkScheduleTitle = _this.langs.upgrade_condition_not_met;
        }

        if (hasUpgraded) {
            checkScheduleTitle = checkScheduleTitle + '<span class="font-size-06em text-danger">' + _this.langs.skipped_level + '</span>';
        }

        $(_this.theUpgradeLevelModalBodySelector).find('.check_schedule_title').html(checkScheduleTitle);

        $(_this.theUpgradeLevelModalBodySelector).find('.cronjob_period').text('');
        $(_this.theUpgradeLevelModalBodySelector).find('.cronjob_period_value').text('');
    }

} // EOF handlePLAHInUpgrade


/**
 * Handle handlePLAHInUpgrade script for Common Accumulation + Common Bet Amount
 * @param {*} thePLAH
 */
PlayerManagement.handlePLAHInUpgradeCB_for_CACB = function (thePLAH) {
    var _this = this;

    var isUpgradeSuccess = _this.detect4isUpgradeSuccessWithPLAH(thePLAH);
    var isNoUpgradeSetting = _this.detect4isNoUpgradeSettingWithPLAH(thePLAH);
    var isDisablePlayerMultipleUpgrade = _this.is_disable_player_multiple_upgrade();
    var _hasUpgraded = _this.getHasUpgradedWithPLAH(thePLAH);
    var hasUpgraded = _hasUpgraded.bool;
    var theUpgradedCounter = _hasUpgraded.upgradedCounter;

    var ignoreHasIsConditionMet = false;
    var firstOfNewVipLevelData = _this.getFirstNewVipLevelDataWithPLAH(thePLAH, ignoreHasIsConditionMet);

    var lastUpgradedOfNewVipLevelData = _this.getLastUpgradedOfNewVipLevelDataWithPLAH(thePLAH);
    var lastFailedOfNewVipLevelData = _this.getLastFailedOfNewVipLevelDataWithPLAH(thePLAH);
    var firstUpgradedOfNewVipLevelData = _this.getFirstUpgradedOfNewVipLevelDataWithPLAH(thePLAH);
    var firstFailedOfNewVipLevelData = _this.getFirstFailedOfNewVipLevelDataWithPLAH(thePLAH);
    var afterPlayerLevelData = _this.getAfterPlayerLevelDataWithPLAH(thePLAH);

    var checkScheduleTitle = null;
    if (isUpgradeSuccess) { // met
        checkScheduleTitle = _this.langs.upgrade_condition_met;
    } else { // Not met
        checkScheduleTitle = _this.langs.upgrade_condition_not_met;
    }
    $(_this.theUpgradeLevelModalBodySelector).find('.check_schedule_title').html(checkScheduleTitle);

    // get next Level Name
    var nextLevelNameValue = _this.langs.na; // default, for top of level.
    if (!$.isEmptyObject(afterPlayerLevelData.nextVipLevelData)
        && 'groupLevelName' in afterPlayerLevelData.nextVipLevelData
    ) {
        nextLevelNameValue = afterPlayerLevelData.nextVipLevelData.groupLevelName;
    }

    // append skipped_level
    if (hasUpgraded) { // met
        if (!isDisablePlayerMultipleUpgrade) {
            if (theUpgradedCounter > 1) {
                nextLevelNameValue += '&nbsp;';
                nextLevelNameValue += '<div class="font-size-06em text-danger">( ' + _this.langs.skipped_level + ' )</div>';
            }
        }

    }
    $(_this.theUpgradeLevelModalBodySelector).find('.next_level_name_value').html(nextLevelNameValue);

    var currentLevelNameValue = afterPlayerLevelData.groupLevelName;

    $(_this.theUpgradeLevelModalBodySelector).find('.current_level_value').html(currentLevelNameValue);

    $(_this.theUpgradeLevelModalBodySelector).find('.cronjob_period').text('');
    $(_this.theUpgradeLevelModalBodySelector).find('.cronjob_period_value').text('');


    // Common / Separate Accumulation
    // lastUpgradedOfNewVipLevelData
    // lastFailedOfNewVipLevelData.vip_upgrade_setting.accumulation = 1
    // lastFailedOfNewVipLevelData.vip_upgrade_setting.separate_accumulation_settings
    // null
    // {"bet_amount": {"accumulation": "1"}, "deposit_amount": {"accumulation": "1"}}
    //

    /// 驗證
    // - 連續升級: 升級失敗的升級等級設定 ，或是 升級成功，下一等級的升級等級設定
    // - 單升級: 檢查升級條件後的等級設定
    // - 若是最高等級，則使用最高等級的設定。
    // - 連續升級、單升級。
    // - 沒有設置升級條件。
    /// Recommand test:
    // VIP8 > VIP 9 > VIP 10 for skipped level
    // VIP9 > VIP 10 > VIP Top, single and multi level upgrade.
    var HUAC_Parsed = {};
    var HUAC_game_id4human_list = [];
    // if (hasUpgraded) {
    //     if ($.isEmptyObject(afterPlayerLevelData.nextVipLevelData.vipsettingcashbackruleId)) {
    //         // Without the next level after upgraded.
    //     } else {
    //         if (!$.isEmptyObject(lastUpgradedOfNewVipLevelData)) {
    //             var the_vip_upgrade_setting = lastUpgradedOfNewVipLevelData.vip_upgrade_setting;
    //             if (_this.dbg) console.log('HUAC_Parsed in 3230');
    //             HUAC_Parsed = _this.parseAmountsInfoFromFormula(lastUpgradedOfNewVipLevelData.remark.rule, the_vip_upgrade_setting);
    //             HUAC_game_id4human_list = lastUpgradedOfNewVipLevelData.remark.game_id4human_list;
    //         }
    //     }
    // }
    // if (!$.isEmptyObject(lastFailedOfNewVipLevelData)) {
    //     var the_vip_upgrade_setting = lastFailedOfNewVipLevelData.vip_upgrade_setting;
    //     if (!$.isEmptyObject(lastFailedOfNewVipLevelData.remark)
    //         && 'rule' in lastFailedOfNewVipLevelData.remark
    //     ) {
    //         if (_this.dbg) console.log('HUAC_Parsed in 3236');
    //         HUAC_Parsed = _this.parseAmountsInfoFromFormula(lastFailedOfNewVipLevelData.remark.rule, the_vip_upgrade_setting);
    //         HUAC_game_id4human_list = lastFailedOfNewVipLevelData.remark.game_id4human_list;
    //     }
    // }
    //
    ///  solution2 HUAC START
    var the_vip_upgrade_setting = {};
    if (isDisablePlayerMultipleUpgrade) {
        if (!$.isEmptyObject(afterPlayerLevelData.remark)
            && 'vip_upgrade_setting' in afterPlayerLevelData.remark
        ) {
            the_vip_upgrade_setting = afterPlayerLevelData.vip_upgrade_setting;
        }
        if (!$.isEmptyObject(afterPlayerLevelData.remark)
            && 'rule' in afterPlayerLevelData.remark
        ) {
            if (_this.dbg) console.log('HUAC_Parsed in 3264');
            HUAC_Parsed = _this.parseAmountsInfoFromFormula(afterPlayerLevelData.remark.rule, the_vip_upgrade_setting);
        }
        if (!$.isEmptyObject(afterPlayerLevelData.remark)
            && 'game_id4human_list' in afterPlayerLevelData.remark
        ) {
            HUAC_game_id4human_list = afterPlayerLevelData.remark.game_id4human_list;
        }
    } else if (afterPlayerLevelData.isConditionMet == false) {
        if (!$.isEmptyObject(afterPlayerLevelData.remark)
            && 'vip_upgrade_setting' in afterPlayerLevelData.remark
        ) {
            the_vip_upgrade_setting = afterPlayerLevelData.vip_upgrade_setting;
        }
        if (!$.isEmptyObject(afterPlayerLevelData.remark)
            && 'rule' in afterPlayerLevelData.remark
        ) {
            if (_this.dbg) console.log('HUAC_Parsed in 3281');
            HUAC_Parsed = _this.parseAmountsInfoFromFormula(afterPlayerLevelData.remark.rule, the_vip_upgrade_setting);
        }
        if (!$.isEmptyObject(afterPlayerLevelData.remark)
            && 'game_id4human_list' in afterPlayerLevelData.remark
        ) {
            HUAC_game_id4human_list = afterPlayerLevelData.remark.game_id4human_list;
        }
    } else if (afterPlayerLevelData.isConditionMet == true) {
        if (!$.isEmptyObject(afterPlayerLevelData.nextVipLevelData.remark)
            && 'vip_upgrade_setting' in afterPlayerLevelData.nextVipLevelData.remark
        ) {
            the_vip_upgrade_setting = afterPlayerLevelData.nextVipLevelData.vip_upgrade_setting;
        }
        if (!$.isEmptyObject(afterPlayerLevelData.nextVipLevelData.remark)
            && 'rule' in afterPlayerLevelData.nextVipLevelData.remark
        ) {
            if (_this.dbg) console.log('HUAC_Parsed in 3298');
            HUAC_Parsed = _this.parseAmountsInfoFromFormula(afterPlayerLevelData.nextVipLevelData.remark.rule, the_vip_upgrade_setting);
        }
        if (!$.isEmptyObject(afterPlayerLevelData.nextVipLevelData.remark)
            && 'game_id4human_list' in afterPlayerLevelData.nextVipLevelData.remark
        ) {
            HUAC_game_id4human_list = afterPlayerLevelData.nextVipLevelData.remark.game_id4human_list;
        }
    }
    ///  solution2 HUAC END

    if (_this.dbg) console.log('3242.HUAC_Parsed', HUAC_Parsed);
    if (_this.dbg) console.log('3243.HUAC_game_id4human_list', HUAC_game_id4human_list);
    _this.handleUpgradeAboveContent(HUAC_Parsed, HUAC_game_id4human_list);


    // 驗證：
    // - 下方應該尋找 未升級成功(單升級：當下等級)的升級設定數據。
    // - 若是最高等級，則使用最高等級的數據。
    // 但 最高等級 升級條件是不設定的，所以應該為空。
    // - 連續升級、單升級。
    // - 沒有設置升級條件。
    var HUBC_Parsed = {};
    var HUBC_game_id4human_list = [];
    var theSeparateAccumulationCalcResult = [];
    // if (!$.isEmptyObject(lastFailedOfNewVipLevelData)) {
    //     // HUBC = handleUpgradeBelowContent
    //     if (!$.isEmptyObject(lastFailedOfNewVipLevelData.remark)
    //         && 'rule' in lastFailedOfNewVipLevelData.remark
    //     ) {
    //         if (_this.dbg) console.log('HUBC_Parsed in 3258');
    //         var the_vip_upgrade_setting = lastFailedOfNewVipLevelData.vip_upgrade_setting;
    //         HUBC_Parsed = _this.parseAmountsInfoFromFormula(lastFailedOfNewVipLevelData.remark.rule, the_vip_upgrade_setting);
    //         HUBC_game_id4human_list = lastFailedOfNewVipLevelData.remark.game_id4human_list;
    //         theSeparateAccumulationCalcResult = lastFailedOfNewVipLevelData.remark.separate_accumulation_calcResult;
    //     }
    // } else {
    //     // should be disable_player_multiple_upgrade=true and upgraded case
    //     if (isDisablePlayerMultipleUpgrade) {
    //         if (hasUpgraded) {
    //             if (!$.isEmptyObject(lastUpgradedOfNewVipLevelData)
    //                 && 'vip_upgrade_setting' in lastUpgradedOfNewVipLevelData // for the upgraded level has setup upgrade settings
    //             ) {
    //                 if (_this.dbg) console.log('HUBC_Parsed in 3266');
    //                 var the_vip_upgrade_setting = lastUpgradedOfNewVipLevelData.vip_upgrade_setting;
    //                 HUBC_Parsed = _this.parseAmountsInfoFromFormula(lastUpgradedOfNewVipLevelData.remark.rule, the_vip_upgrade_setting);
    //                 HUBC_game_id4human_list = lastUpgradedOfNewVipLevelData.remark.game_id4human_list;
    //                 theSeparateAccumulationCalcResult = lastUpgradedOfNewVipLevelData.remark.separate_accumulation_calcResult;
    //             }
    //         }
    //     }
    // }
    //
    ///  solution2 HUBC START
    if (afterPlayerLevelData.isConditionMet == false || true) {
        var the_vip_upgrade_setting = {};
        if (!$.isEmptyObject(afterPlayerLevelData.vip_upgrade_setting)) {
            the_vip_upgrade_setting = afterPlayerLevelData.vip_upgrade_setting;
        }

        if (!$.isEmptyObject(afterPlayerLevelData.remark)
            && 'rule' in afterPlayerLevelData.remark
        ) {
            HUBC_Parsed = _this.parseAmountsInfoFromFormula(afterPlayerLevelData.remark.rule, the_vip_upgrade_setting);
            if (_this.dbg) console.log('HUBC_Parsed in 3360', JSON.stringify(HUBC_Parsed));
        }

        if (!$.isEmptyObject(afterPlayerLevelData.remark)
            && 'game_id4human_list' in afterPlayerLevelData.remark
        ) {
            HUBC_game_id4human_list = afterPlayerLevelData.remark.game_id4human_list;
        }

        if (!$.isEmptyObject(afterPlayerLevelData.remark)
            && 'separate_accumulation_calcResult' in afterPlayerLevelData.remark
        ) {
            if (_this.dbg) console.log('3377.initialAmount', afterPlayerLevelData.remark.initialAmount);
            var addedCalcResult = _this.plusCalcResultAndInitialAmount(afterPlayerLevelData.remark.separate_accumulation_calcResult, afterPlayerLevelData.remark.initialAmount)
            theSeparateAccumulationCalcResult = addedCalcResult;
        }
    }
    ///  solution2 HUBC END

    var prefixWhileEmptyPrecon = _this.langs.current_vip4prefix;
    var theSelector = _this.theUpgradeLevelModalBodySelector + ' .below-content';
    // console.log('HUBC_Parsed:', HUBC_Parsed);
    if (_this.dbg) console.log('3280.HUBC_Parsed', HUBC_Parsed);
    if (_this.dbg) console.log('3281.HUBC_game_id4human_list', HUBC_game_id4human_list);
    if (_this.dbg) console.log('3282.theSeparateAccumulationCalcResult', JSON.stringify(theSeparateAccumulationCalcResult));


    _this.handleUpgradeBelowContent(HUBC_Parsed // #1
        , HUBC_game_id4human_list // #2
        , theSelector // #3
        , _this.HUBC_getAmountNameCB // #4
        , _this.HUBC_getAmountValueCB // #5
        , prefixWhileEmptyPrecon // #6
        , theSeparateAccumulationCalcResult //  #7
    );

}// EOF handlePLAHInUpgradeCB_for_CACB

/**
 * Add the initialAmounts into the theCalcResult
 * @param {*} theCalcResult
 * @param {*} initialAmounts
 */
PlayerManagement.plusCalcResultAndInitialAmount = function (theCalcResult, initialAmounts) {
    var _this = this;

    // initialAmount, SASB:
    //
    // {"total_bet":0,"deposit":"43","total_win":0,"total_loss":0,"separated_bet":{"game_type_id_440":0,"game_platform_id_5654":"41"}}"

    // theCalcResult
    // CACB:
    // {"details":{"total_bet":{"count":0,"from":"2021-03-19 15:10:37","to":"2021-03-19 15:51:53"},"deposit":{"count":0,"from":"2021-03-19 15:10:37","to":"2021-03-19 15:51:53"}},"total_bet":0,"separated_bet":null,"deposit":0}
    // SASB:
    // {"details":{"separated_bet":{"0":{"type":"game_type","value":"48","math_sign":"<=","game_type_id":"440","result_amount":0,"count":0,"game_type_id_440":0},"1":{"type":"game_platform","value":"47","math_sign":"<=","game_platform_id":"5654","precon_logic_flag":"and","result_amount":0,"count":0,"game_platform_id_5654":0},"from":"2021-03-19 15:10:37","to":"2021-03-19 16:16:23"},"total_win":{"count":0,"from":"2021-03-19 15:10:37","to":"2021-03-19 16:16:23"},"total_loss":{"count":0,"from":"2021-03-19 15:10:37","to":"2021-03-19 16:16:23"},"deposit":{"count":0,"from":"2021-03-19 15:10:37","to":"2021-03-19 16:16:23"}},"total_bet":null,"separated_bet":[{"type":"game_type","value":"48","math_sign":"<=","game_type_id":"440","result_amount":0,"count":0,"game_type_id_440":0},{"type":"game_platform","value":"47","math_sign":"<=","game_platform_id":"5654","precon_logic_flag":"and","result_amount":0,"count":0,"game_platform_id_5654":0}],"total_win":0,"total_loss":0,"deposit":43}
    $.each(theCalcResult, function (keyString, currValue) {
        var subTotal = 0;
        switch (keyString) {
            case 'total_win':
                subTotal = parseFloat(theCalcResult[keyString]);
                if (typeof (initialAmounts['total_win']) !== 'undefined') {
                    subTotal += parseFloat(initialAmounts['total_win']);
                }
                theCalcResult[keyString] = subTotal;
                break;
            case 'total_loss':
                subTotal = parseFloat(theCalcResult[keyString]);
                if (typeof (initialAmounts['total_loss']) !== 'undefined') {
                    subTotal += parseFloat(initialAmounts['total_loss']);
                }
                theCalcResult[keyString] = subTotal;
                break;
            case 'deposit':
                subTotal = parseFloat(theCalcResult[keyString]);
                if (typeof (initialAmounts['deposit']) !== 'undefined') {
                    subTotal += parseFloat(initialAmounts['deposit']);
                }
                theCalcResult[keyString] = subTotal;
                break;
            case 'total_bet':
                if (typeof (theCalcResult[keyString]) !== 'undefined'
                    && $.isEmptyObject(theCalcResult['separated_bet'])
                ) {
                    subTotal = parseFloat(theCalcResult[keyString]);
                    if (typeof (initialAmounts['total_bet']) !== 'undefined') {
                        subTotal += parseFloat(initialAmounts['total_bet']);
                    }
                    theCalcResult[keyString] = subTotal;
                }
                break;
            case 'separated_bet':
                if (typeof (theCalcResult[keyString]) !== 'undefined'
                    && !$.isEmptyObject(theCalcResult[keyString])
                ) {
                    var separatedBetOfCalcResult = theCalcResult[keyString];
                    $.each(separatedBetOfCalcResult, function (_indexNumber, _currValue) {
                        var theGameKey = '';
                        if (separatedBetOfCalcResult[_indexNumber]['type'] == 'game_type') {
                            theGameKey = 'game_type_id_' + separatedBetOfCalcResult[_indexNumber]['game_type_id'];
                        } else if (separatedBetOfCalcResult[_indexNumber]['type'] == 'game_platform') {
                            theGameKey = 'game_platform_id_' + separatedBetOfCalcResult[_indexNumber]['game_platform_id'];
                        }
                        if (typeof (initialAmounts['separated_bet']) !== undefined
                            && theGameKey in initialAmounts['separated_bet']
                        ) {
                            subTotal += parseFloat(separatedBetOfCalcResult[_indexNumber][theGameKey]);
                            subTotal += parseFloat(initialAmounts['separated_bet'][theGameKey]);
                            theCalcResult[keyString][_indexNumber]['count'] = subTotal;
                            theCalcResult[keyString][_indexNumber][theGameKey] = subTotal;
                        }

                    });
                }
                break;
        }// EOF switch (keyString) {...
    }); // EOF $.each(theCalcResult, function (keyString, currValue) {...
    return theCalcResult;
} // EOF plusCalcResultAndInitialAmount

PlayerManagement.handlePLAHInUpgradeCB_for_CASB = function (thePLAH) {
    var _this = this;
    return _this.handlePLAHInUpgradeCB_for_CACB.apply(_this, arguments);
}// EOF handlePLAHInUpgradeCB_for_CASB

PlayerManagement.handlePLAHInUpgradeCB_for_SACB = function () {
    var _this = this;
    return _this.handlePLAHInUpgradeCB_for_CACB.apply(_this, arguments);
}// EOF handlePLAHInUpgradeCB_for_SACB

PlayerManagement.handlePLAHInUpgradeCB_for_SASB = function () {
    var _this = this;
    return _this.handlePLAHInUpgradeCB_for_CACB.apply(_this, arguments);
}// EOF handlePLAHInUpgradeCB_for_SASB

/**
 * Get the First Failed from newVipLevelDataList in PLAH.
 * @param {*} thePLAH
 */
PlayerManagement.getFirstFailedOfNewVipLevelDataWithPLAH = function (thePLAH) {
    var _this = this;
    var firstFailedOfNewVipLevelData = {}; // default
    // get the lastest success upgrade data
    var counter = thePLAH.playerLevelInfo.newVipLevelDataList.length;
    var maxIndex = counter - 1;

    for (var i = 0; i <= maxIndex; i++) {
        if (thePLAH.playerLevelInfo.newVipLevelDataList[i]['isConditionMet'] == false) {
            firstFailedOfNewVipLevelData = thePLAH.playerLevelInfo.newVipLevelDataList[i]
            break;
        }
    }
    return firstFailedOfNewVipLevelData;
}// EOF getFirstFailedOfNewVipLevelDataWithPLAH

/**
 * Get the Last Failed from newVipLevelDataList in PLAH.
 * @param {*} thePLAH
 */
PlayerManagement.getLastFailedOfNewVipLevelDataWithPLAH = function (thePLAH) {
    var _this = this;
    var lastFailedOfNewVipLevelData = {}; // default
    // get the lastest success upgrade data
    var counter = thePLAH.playerLevelInfo.newVipLevelDataList.length;
    var maxIndex = counter - 1;

    for (var i = maxIndex; i >= 0; i--) {
        if (thePLAH.playerLevelInfo.newVipLevelDataList[i]['isConditionMet'] == false) {
            lastFailedOfNewVipLevelData = thePLAH.playerLevelInfo.newVipLevelDataList[i]
            break;
        }
    }
    return lastFailedOfNewVipLevelData;
} // EOF getLastFailedOfNewVipLevelDataWithPLAH

/**
 * Get the First Upgraded from newVipLevelDataList in PLAH.
 * @param {*} thePLAH
 */
PlayerManagement.getFirstUpgradedOfNewVipLevelDataWithPLAH = function (thePLAH) {
    var _this = this;
    var firstUpgradedOfNewVipLevelData = {}; // default
    // get the lastest success upgrade data
    var counter = thePLAH.playerLevelInfo.newVipLevelDataList.length;
    var maxIndex = counter - 1;

    for (var i = 0; i <= maxIndex; i++) {
        if (thePLAH.playerLevelInfo.newVipLevelDataList[i]['isConditionMet'] == true) {
            firstUpgradedOfNewVipLevelData = thePLAH.playerLevelInfo.newVipLevelDataList[i]
            break;
        }
    }
    return firstUpgradedOfNewVipLevelData;
}// EOF getFirstUpgradedOfNewVipLevelDataWithPLAH

/**
 *
 * @param {*} thePLAH
 * @param {bool} ignoreHasIsConditionMet If true, its will IGNORE the newVipLevelData Must has the attribute, "isConditionMet".
 */
PlayerManagement.getFirstNewVipLevelDataWithPLAH = function (thePLAH, ignoreHasIsConditionMet) {
    var _this = this;
    var firstOfNewVipLevelData = {}; // default

    if (typeof (ignoreHasIsConditionMet) === 'undefined') {
        ignoreHasIsConditionMet = false;
    }
    // get the lastest success upgrade data
    var counter = thePLAH.playerLevelInfo.newVipLevelDataList.length;
    var maxIndex = counter - 1;

    for (var i = 0; i <= maxIndex; i++) {
        if (ignoreHasIsConditionMet) {
            firstOfNewVipLevelData = thePLAH.playerLevelInfo.newVipLevelDataList[i];
            break;
        } else if ('isConditionMet' in thePLAH.playerLevelInfo.newVipLevelDataList[i]) {
            firstOfNewVipLevelData = thePLAH.playerLevelInfo.newVipLevelDataList[i];
            break;
        }
    }
    return firstOfNewVipLevelData;
} // getFirstNewVipLevelDataWithPLAH

/**
 * Just get the playerLevelData of @.PLAH.playerLevelInfo .
 * The playerLevelData is the PlayerLevel Before Upgrade/Downgrade checking.
 * @param {*} thePLAH
 */
PlayerManagement.getBeforePlayerLevelDataWithPLAH = function (thePLAH) {
    var _this = this;
    var _beforePlayerLevelData = {};
    if (typeof (thePLAH.playerLevelInfo) !== 'undefined'
        && 'playerLevelData' in thePLAH.playerLevelInfo
    ) {
        _beforePlayerLevelData = thePLAH.playerLevelInfo.playerLevelData;
    }
    return _beforePlayerLevelData;
} // EOF getBeforePlayerLevelDataWithPLAH

/**
 * Just get the afterPlayerLevelData of @.PLAH.playerLevelInfo .
 * The afterPlayerLevelData is the PlayerLevel After Upgrade checking.
 * @param {*} thePLAH
 */
PlayerManagement.getAfterPlayerLevelDataWithPLAH = function (thePLAH) {
    var _this = this;
    var _afterPlayerLevelData = {};
    if (typeof (thePLAH.playerLevelInfo) !== 'undefined'
        && 'afterPlayerLevelData' in thePLAH.playerLevelInfo
    ) {
        _afterPlayerLevelData = thePLAH.playerLevelInfo.afterPlayerLevelData
    }
    return _afterPlayerLevelData;
} // EOF getAfterPlayerLevelDataWithPLAH

/**
 * Get the First NewVipLevelData List
 *
 * @param {*} thePLAH
 * @param {integer} theLength
 * @param {bool} ignoreHasIsConditionMet
 * @return {array} firstOfNewVipLevelDataList
 */
PlayerManagement.getFirstNewVipLevelDataListWithPLAH = function (thePLAH, theLength, ignoreHasIsConditionMet) {
    var _this = this;
    var firstOfNewVipLevelDataList = [];
    if (typeof (theLength) === 'undefined') {
        theLength = 1;
    }
    for (var i = 0; i <= maxIndex; i++) {
        // assign into the firstOfNewVipLevelDataList
        if (ignoreHasIsConditionMet) {
            firstOfNewVipLevelDataList.push(thePLAH.playerLevelInfo.newVipLevelDataList[i]);
            // firstOfNewVipLevelData = thePLAH.playerLevelInfo.newVipLevelDataList[i];
            // break;
        } else if ('isConditionMet' in thePLAH.playerLevelInfo.newVipLevelDataList[i]) {
            firstOfNewVipLevelDataList.push(thePLAH.playerLevelInfo.newVipLevelDataList[i]);
            // firstOfNewVipLevelData = thePLAH.playerLevelInfo.newVipLevelDataList[i];
            // break;
        }
        // detect theLength
        if (firstOfNewVipLevelDataList.length >= theLength) {
            break;
        }
    }
    return firstOfNewVipLevelDataList;
} // EOF getFirstNewVipLevelDataListWithPLAH
/**
 * Get the Last Upgraded from newVipLevelDataList in PLAH.
 * @param {*} thePLAH
 */
PlayerManagement.getLastUpgradedOfNewVipLevelDataWithPLAH = function (thePLAH) {
    var _this = this;
    var lastUpgradedOfNewVipLevelData = {}; // default
    // get the lastest success upgrade data
    var counter = thePLAH.playerLevelInfo.newVipLevelDataList.length;
    var maxIndex = counter - 1;
    for (var i = maxIndex; i >= 0; i--) {
        if (thePLAH.playerLevelInfo.newVipLevelDataList[i]['isConditionMet'] == true) {
            lastUpgradedOfNewVipLevelData = thePLAH.playerLevelInfo.newVipLevelDataList[i]
            break;
        }
    }

    return lastUpgradedOfNewVipLevelData;
} // EOF getLastUpgradedOfNewVipLevelDataWithPLAH
/**
 * Get the Had Upgraded from newVipLevelDataList in PLAH.
 * for disable_player_multiple_upgrade=false
 * @param {*} thePLAH
 */
PlayerManagement.getHasUpgradedWithPLAH = function (thePLAH) {
    var _this = this;
    var returnObj = {};
    returnObj.bool = null;
    returnObj.upgradedCounter = null;
    returnObj.failedCounter = null;

    var upgradedCounter = 0;
    var failedCounter = 0;
    var hasUpgraded = null;
    var counter = thePLAH.playerLevelInfo.newVipLevelDataList.length;
    var maxIndex = counter - 1;
    // count Failed, Upgraded counter
    for (var i = maxIndex; i >= 0; i--) {
        if (thePLAH.playerLevelInfo.newVipLevelDataList[i]['isConditionMet'] == true) {
            upgradedCounter++;
        } else if (thePLAH.playerLevelInfo.newVipLevelDataList[i]['isConditionMet'] == false) {
            failedCounter++;
        } else {
            // In the case,isConditionMet = null
        }
    }
    if (upgradedCounter > 0) {
        hasUpgraded = true;
    } else {
        hasUpgraded = false;
    }
    returnObj.bool = hasUpgraded;
    returnObj.upgradedCounter = upgradedCounter;
    returnObj.failedCounter = failedCounter;

    return returnObj;
} // EOF getHasUpgradedWithPLAH
/**
 * Get the Last newVipLevelData from newVipLevelDataList in PLAH.
 * Get the the Last newVipLevelData whatever that is met or not upgrade condition.
 * @param {*} thePLAH
 */
PlayerManagement.getLastestNewVipLevelDataWithPLAH = function (thePLAH) {
    var _this = this;
    var lastestNewVipLevelData = {};
    if (thePLAH.playerLevelInfo.newVipLevelDataList.length > 0) {
        var _newVipLevelDataList = [];// for upgrade to top level.
        _newVipLevelDataList = thePLAH.playerLevelInfo.newVipLevelDataList.filter(function (curVal) {
            return 'isConditionMet' in curVal;
        }, _this);
        var _lastIndex = _newVipLevelDataList.length - 1;
        lastestNewVipLevelData = _newVipLevelDataList[_lastIndex];
    }
    return lastestNewVipLevelData;
} // EOF getLastestNewVipLevelDataWithPLAH

/**
 * https://regex101.com/r/77AZDh/1
 */
PlayerManagement.getDepositAmountFromFormula = function(theFormulaStr){
    var _this = this;
    var regex = /(?<precon>and|or)\s?deposit_amount(?<compare>[ ><=]+)?(?<amount>\d+)/gm;
    // const str = `bet_amount >= 2500000 and deposit_amount >= 250000`;
    var m; // null
    var isNeedGameIdKey = false;
    var depositAmountInfo = _this.getEmptyAmountInfo(isNeedGameIdKey);

    m = regex.exec(theFormulaStr);
    if( m !== null ){
        if( typeof(m[0]) !== 'undefined'){
            depositAmountInfo['full_match'] = m[0];
        }
        if( typeof(m.groups.precon) !== 'undefined'){
            depositAmountInfo['precon'] = m.groups.precon;
        }
        if( typeof(m.groups.compare) !== 'undefined'){
            depositAmountInfo['compare'] = m.groups.compare.trim();
        }
        if( typeof(m.groups.amount) !== 'undefined'){
            depositAmountInfo['amount'] = m.groups.amount;
            /// formated m.groups.amount
            var theAmountNumeral = numeral(m.groups.amount);
            depositAmountInfo['amount_formated'] = _this.numeralApplyAmountFormat(theAmountNumeral);
        }
    }

    return depositAmountInfo;
}// EOF getDepositAmountFromFormula

/**
 * Get a empty theAmountInfo container.
 * @param {boolean} isNeedGameIdKey If true than return an element that's contains game_id_key key.
 */
PlayerManagement.getEmptyAmountInfo  = function(isNeedGameIdKey){
    if( typeof(isNeedGameIdKey) === 'undefined'){
        isNeedGameIdKey = true;
    }
    var theAmountInfo = {};
    theAmountInfo['accumulation'] = null;
    theAmountInfo['precon'] = null;
    theAmountInfo['compare'] = null;
    theAmountInfo['amount'] = null;
    theAmountInfo['amount_formated'] = null;
    if(isNeedGameIdKey){
        theAmountInfo['game_id_key'] = null;
    }
    theAmountInfo['full_match'] = null;
    return theAmountInfo;
} // EOF getEmptyAmountInfo

/**
 * https://regex101.com/r/CJJiAQ/1
 */
PlayerManagement.parseAmountsInfoFromFormula = function (theFormulaStr, theVipUpgradeSetting) {
    var _this = this;

    // lastFailedOfNewVipLevelData.vip_upgrade_setting.accumulation = 1
    // lastFailedOfNewVipLevelData.vip_upgrade_setting.separate_accumulation_settings = null
    // separate_accumulation_settings:{"bet_amount": {"accumulation": "1"}, "deposit_amount": {"accumulation": "1"}}

    var separateAccumulationSettings = {};
    var isCA = false;
    if (!$.isEmptyObject(theVipUpgradeSetting.separate_accumulation_settings)) {
        // Separate Accumulation
        // console.log('parseAmountsInfoFromFormula.separate_accumulation_settings:', theVipUpgradeSetting.separate_accumulation_settings);
        separateAccumulationSettings = JSON.parse(theVipUpgradeSetting.separate_accumulation_settings);
    } else {
        // Common Accumulation
        // theVipUpgradeSetting.accumulation
        // console.log('parseAmountsInfoFromFormula.accumulation:', theVipUpgradeSetting.accumulation);
        // theVipUpgradeSetting.accumulation
        isCA = true;
    }

    /// https://regex101.com/r/CJJiAQ/1
    // var regex = /(?<game_type_precon>and|or)?\s?(?<game_type_id_key>game_type_id_\d+)(?<game_type_compare>[ ><=]+)?(?<game_type_id_amount>\d+)|(?<game_platform_precon>and|or)?\s?(?<game_platform_id_key>game_platform_id_\d+)(?<game_platform_compare>[ ><=]+)?(?<game_platform_id_amount>\d+)|(?<bet_amount_precon>and|or)?\s?bet_amount(?<bet_amount_compare>[ ><=]+)?(?<bet_amount_value>\d+)|(?<deposit_amount_precon>and|or)?\s?deposit_amount(?<deposit_amount_compare>[ ><=]+)?(?<deposit_amount_value>\d+)|(?<loss_amount_precon>and|or)?\s?loss_amount(?<loss_amount_compare>[ ><=]+)?(?<loss_amount_value>\d+)|(?<win_amount_precon>and|or)?\s?win_amount(?<win_amount_compare>[ ><=]+)?(?<win_amount_value>\d+)/gm;
    /// https://regex101.com/r/Y6Q8wQ/1
    var regex =/(?<game_type_full_match>(?<game_type_precon>and|or)?\s?(?<game_type_id_key>game_type_id_\d+)(?<game_type_compare>[ ><=]+)?(?<game_type_id_amount>\d+))|(?<game_platform_full_match>(?<game_platform_precon>and|or)?\s?(?<game_platform_id_key>game_platform_id_\d+)(?<game_platform_compare>[ ><=]+)?(?<game_platform_id_amount>\d+))|(?<bet_full_match>(?<bet_amount_precon>and|or)?\s?bet_amount(?<bet_amount_compare>[ ><=]+)?(?<bet_amount_value>\d+))|(?<deposit_full_match>(?<deposit_amount_precon>and|or)?\s?deposit_amount(?<deposit_amount_compare>[ ><=]+)?(?<deposit_amount_value>\d+))|(?<loss_full_match>(?<loss_amount_precon>and|or)?\s?loss_amount(?<loss_amount_compare>[ ><=]+)?(?<loss_amount_value>\d+))|(?<win_full_match>(?<win_amount_precon>and|or)?\s?win_amount(?<win_amount_compare>[ ><=]+)?(?<win_amount_value>\d+))/gm;
    var m;
    var theAmountInfoList = [];
    while ((m = regex.exec(theFormulaStr)) !== null) {
        if (m.index === regex.lastIndex) {
            regex.lastIndex++;
        }
        // console.log('m:', m);
        var _accumulation = null;
        if (isCA) {
            _accumulation = theVipUpgradeSetting.accumulation;
        }
        var game_id_mode = '';
        if( typeof(m.groups.game_type_id_key) !== 'undefined'){
            game_id_mode = 'game_type';
        }else if( typeof(m.groups.game_platform_id_key) !== 'undefined'){
            game_id_mode = 'game_platform';
        }

        var isNeedGameIdKey = false;
        if(game_id_mode != ''){
            isNeedGameIdKey = true;
        }
        var theAmountInfo = _this.getEmptyAmountInfo(isNeedGameIdKey);

        if( game_id_mode == 'game_type'){
            if (typeof (separateAccumulationSettings.bet_amount) !== 'undefined') {
                // for SA
                _accumulation = separateAccumulationSettings.bet_amount.accumulation;
            }

            var full_matchKey = game_id_mode+'_full_match';
            if( typeof(m.groups[full_matchKey]) !== 'undefined'){
                theAmountInfo['full_match'] = m.groups[full_matchKey];
            }

            if( typeof(m.groups.game_type_precon) !== 'undefined'){
                theAmountInfo['precon'] = m.groups['game_type_precon'];
            }

            if( typeof(m.groups.game_type_id_key) !== 'undefined'){
                theAmountInfo['game_id_key'] = m.groups['game_type_id_key'];
            }

            if( typeof(m.groups.game_type_compare) !== 'undefined'){
                theAmountInfo['compare'] = m.groups['game_type_compare'].trim();
            }

            if( typeof(m.groups.game_type_id_amount) !== 'undefined'){
                theAmountInfo['amount'] = m.groups['game_type_id_amount'];
                var theAmountNumeral = numeral(m.groups['game_type_id_amount']);
                theAmountInfo['amount_formated'] = _this.numeralApplyAmountFormat(theAmountNumeral);
            }
            theAmountInfo['amount_key'] = game_id_mode;
            theAmountInfo['accumulation'] = _accumulation;
            // EOF if( game_id_mode == 'game_type'){...
        }else if( game_id_mode == 'game_platform'){
            if (typeof (separateAccumulationSettings.bet_amount) !== 'undefined') {
                // for SA
                _accumulation = separateAccumulationSettings.bet_amount.accumulation;
            }
            var full_matchKey = game_id_mode+'_full_match';
            if( typeof(m.groups[full_matchKey]) !== 'undefined'){
                theAmountInfo['full_match'] = m.groups[full_matchKey];
            }

            if( typeof(m.groups.game_platform_precon) !== 'undefined'){
                theAmountInfo['precon'] = m.groups['game_platform_precon'];
            }

            if( typeof(m.groups.game_platform_id_key) !== 'undefined'){
                theAmountInfo['game_id_key'] = m.groups['game_platform_id_key'];
            }

            if( typeof(m.groups.game_platform_compare) !== 'undefined'){
                theAmountInfo['compare'] = m.groups['game_platform_compare'].trim();
            }
            if( typeof(m.groups.game_platform_id_amount) !== 'undefined'){
                theAmountInfo['amount'] = m.groups['game_platform_id_amount'];
                var theAmountNumeral = numeral(m.groups['game_platform_id_amount']);
                theAmountInfo['amount_formated'] = _this.numeralApplyAmountFormat(theAmountNumeral);
            }
            theAmountInfo['amount_key'] = game_id_mode;
            theAmountInfo['accumulation'] = _accumulation;
            // EOF if( game_id_mode == 'game_platform'){...
        } else {
            // bet_amount, deposit_amount, loss_amount and win_amount
            if( typeof(m.groups.bet_amount_compare) !== 'undefined'){ // for bet_amount
                if (typeof (separateAccumulationSettings.bet_amount) !== 'undefined') {
                    // for SA
                    _accumulation = separateAccumulationSettings.bet_amount.accumulation;
                }
                theAmountInfo = _this.parseAmountsInfoFromGroups(m.groups, 'bet');
                theAmountInfo['accumulation'] = _accumulation;
            }
            if( typeof(m.groups.deposit_amount_compare) !== 'undefined'){ // for deposit_amount
                if (typeof (separateAccumulationSettings.deposit_amount) !== 'undefined') {
                    // for SA
                    _accumulation = separateAccumulationSettings.deposit_amount.accumulation;
                }
                theAmountInfo = _this.parseAmountsInfoFromGroups(m.groups, 'deposit');
                theAmountInfo['accumulation'] = _accumulation;
            }
            if( typeof(m.groups.win_amount_compare) !== 'undefined'){ // for win_amount
                if (typeof (separateAccumulationSettings.win_amount) !== 'undefined') {
                    // for SA
                    _accumulation = separateAccumulationSettings.win_amount.accumulation;
                }
                theAmountInfo = _this.parseAmountsInfoFromGroups(m.groups, 'win');
                theAmountInfo['accumulation'] = _accumulation;
            }
            if( typeof(m.groups.loss_amount_compare) !== 'undefined'){ // for loss_amount
                if (typeof (separateAccumulationSettings.loss_amount) !== 'undefined') {
                    // for SA
                    _accumulation = separateAccumulationSettings.loss_amount.accumulation;
                }
                theAmountInfo = _this.parseAmountsInfoFromGroups(m.groups, 'loss');
                theAmountInfo['accumulation'] = _accumulation;
            }

        } // EOF else {...

        theAmountInfoList.push(theAmountInfo);
    } // EOF while ((m = regex.exec(theFormulaStr)) !== null) {...
    return theAmountInfoList;
} // EOF parserAmountsInfoFromFormula

PlayerManagement.parseAmountsInfoFromGroups = function(theGroups, theAmountName){
    var _this = this;
    var theAmountInfo = _this.getEmptyAmountInfo(false);
    if( typeof(theAmountName) === 'undefined'){
        theAmountName = 'bet';
    }

// console.log('theGroups:', theGroups);
    var full_matchKey = theAmountName+'_full_match';
    if( typeof(theGroups[full_matchKey]) !== 'undefined'){
        theAmountInfo['full_match'] = theGroups[full_matchKey];
    }

    // theGroups[theAmountName+'_amount_compare']
    var compareKey = theAmountName+'_amount_compare';
    if( typeof(theGroups[compareKey]) !== 'undefined'
        && ! $.isEmptyObject(theGroups[compareKey])
    ){
        theAmountInfo['compare'] = theGroups[compareKey].trim();
    }

    var preconKey = theAmountName+'_amount_precon';
    if( typeof(theGroups[preconKey]) !== 'undefined'
        && ! $.isEmptyObject(theGroups[preconKey])
    ){
        theAmountInfo['precon'] = theGroups[preconKey];
    }

    var amountKey = theAmountName+'_amount_value';
    if( typeof(theGroups[amountKey]) !== 'undefined'
        && ! $.isEmptyObject(theGroups[amountKey])
    ){
        theAmountInfo['amount'] = theGroups[amountKey];

        var theAmountNumeral = numeral(theGroups[amountKey]);
        theAmountInfo['amount_formated'] = _this.numeralApplyAmountFormat(theAmountNumeral);
    }

    var amountKey = theAmountName+'_amount';
    theAmountInfo['amount_key'] = amountKey;

    return theAmountInfo;

} // EOF parseAmountsInfoFromGroups

/**
 * https://regex101.com/r/Wa5EWm/1
 */
PlayerManagement.getBetsAmountWithGameIdKeyFromFormula = function(theFormulaStr){
    var _this = this;
    var regex = /((?<game_type_precon>and|or)?\s?(?<game_type_id_key>game_type_id_\d+)(?<game_type_compare>[ ><=]+)?(?<game_type_id_amount>\d+)|(?<game_platform_precon>and|or)?\s?(?<game_platform_id_key>game_platform_id_\d+)(?<game_platform_compare>[ ><=]+)?(?<game_platform_id_amount>\d+))/gm;
    var m;
    var theAmountInfoList = [];
    while ((m = regex.exec(theFormulaStr)) !== null) {
        if (m.index === regex.lastIndex) {
            regex.lastIndex++;
        }
        // console.log('m:', m);
        var theAmountInfo = _this.getEmptyAmountInfo();

        theAmountInfo['full_match'] = m[0];
        var game_id_mode = '';
        if( typeof(m.groups.game_type_id_key) !== 'undefined'){
            game_id_mode = 'game_type';
        }else if( typeof(m.groups.game_platform_id_key) !== 'undefined'){
            game_id_mode = 'game_platform';
        }

        if( game_id_mode == 'game_type'){
            if( typeof(m.groups.game_type_precon) !== 'undefined'){
                theAmountInfo['precon'] = m.groups['game_type_precon'];
            }

            if( typeof(m.groups.game_type_id_key) !== 'undefined'){
                theAmountInfo['game_id_key'] = m.groups['game_type_id_key'];
            }

            if( typeof(m.groups.game_type_compare) !== 'undefined'){
                theAmountInfo['compare'] = m.groups['game_type_compare'].trim();
            }

            if( typeof(m.groups.game_type_id_amount) !== 'undefined'){
                theAmountInfo['amount'] = m.groups['game_type_id_amount'];
                var theAmountNumeral = numeral(m.groups['game_type_id_amount']);
                theAmountInfo['amount_formated'] = _this.numeralApplyAmountFormat(theAmountNumeral);
            }
        }else if( game_id_mode == 'game_platform'){
            if( typeof(m.groups.game_platform_precon) !== 'undefined'){
                theAmountInfo['precon'] = m.groups['game_platform_precon'];
            }

            if( typeof(m.groups.game_platform_id_key) !== 'undefined'){
                theAmountInfo['game_id_key'] = m.groups['game_platform_id_key'];
            }

            if( typeof(m.groups.game_platform_compare) !== 'undefined'){
                theAmountInfo['compare'] = m.groups['game_platform_compare'].trim();
            }
            if( typeof(m.groups.game_platform_id_amount) !== 'undefined'){
                theAmountInfo['amount'] = m.groups['game_platform_id_amount'];
                var theAmountNumeral = numeral(m.groups['game_platform_id_amount']);
                theAmountInfo['amount_formated'] = _this.numeralApplyAmountFormat(theAmountNumeral);
            }
        }

        theAmountInfoList.push(theAmountInfo);
    } // EOF while
    return theAmountInfoList;
} // EOF getBetsAmountWithGameIdKeyFromFormula


/**
 * get Bet Amount From Formula String
 * @param string theFormulaStr The Formula String,
 * the examples,
 * "bet_amount >= 2500000 and deposit_amount >= 250000"
 * "game_type_id_22 <= 0 or game_platform_id_5674 <= 0 and deposit_amount >= 250000"
 * @return object theAmountInfo The object structure,
 * - theAmountInfo[full_match] string The string match the bet_amount string in the formula.
 * - theAmountInfo[precon] string The logical connection string, ex: "and", "or".
 * - theAmountInfo[compare] string The compare operator.ex: ">", ">=", "<", "<=" and "=" .
 * - theAmountInfo[amount] string The amount.
 * - theAmountInfo[amount_formated] string The formated amount after applied numeral().
 */
PlayerManagement.getBetAmountFromFormula = function(theFormulaStr){
    var _this = this;
    var regex = /(?<precon>and|or)?\s?bet_amount(?<compare>[ ><=]+)?(?<amount>\d+)/gm;
    // const str = `bet_amount >= 2500000 and deposit_amount >= 250000`;
    var m;
    var isNeedGameIdKey = false;
    var theAmountInfo = _this.getEmptyAmountInfo(isNeedGameIdKey);

    m = regex.exec(theFormulaStr);
    if( m !== null ){
        if( typeof(m[0]) !== 'undefined'){
            theAmountInfo['full_match'] = m[0];
        }
        if( typeof(m.groups.precon) !== 'undefined'){
            theAmountInfo['precon'] = m.groups.precon;
        }
        if( typeof(m.groups.compare) !== 'undefined'){
            theAmountInfo['compare'] = m.groups.compare.trim();
        }
        if( typeof(m.groups.amount) !== 'undefined'){
            theAmountInfo['amount'] = m.groups.amount;
            /// formated m.groups.amount
            var theAmountNumeral = numeral(m.groups.amount);
            theAmountInfo['amount_formated'] = _this.numeralApplyAmountFormat(theAmountNumeral);
        }
    }

    return theAmountInfo;
} // EOF getBetAmountFromFormula


/**
 * Get the amount name with callback in handleUpgradeAboveContent().
 * HUAC = handleUpgradeAboveContent
 *
 * @param {string} theAmountKey The amount key string, ex: bet_amount, deposit_amount, game_platform_id_XXX, game_type_id_XXX, ...etc.
 * @param {object} currValue The object is a element of the return from parseAmountsInfoFromFormula().
 * @param {object} theGameId4humanList The GameIdKey mapping to Game Name of the array.
 *  The key be GameIdKey,game_platform_id_XXX, game_type_id_XXX and the value be Game Name.
 * @param {string} prefixWhileEmptyPrecon The pre-connect string, ex: "Current VIP" or "Upgrade Needs".
 * @return {string} The amount name.
 */
PlayerManagement.HUAC_getAmountNameCB = function(theAmountKey, currValue, theGameId4humanList, prefixWhileEmptyPrecon){
    var _this = this;
    // console.error('HUAC_getAmountNameCB.currValue:', currValue, 'theAmountKey:', theAmountKey);

    if( typeof(prefixWhileEmptyPrecon) === 'undefined'){
        prefixWhileEmptyPrecon = _this.langs.upgrade_needs4prefix;
    }

    var amountName = '';

    if (typeof (currValue['accumulation']) !== 'undefined') {
        if (!isNaN(currValue['accumulation'])) {
            if (parseInt(currValue['accumulation']) > 0) {
                amountName += _this.langs.accumulation_prefix;
                amountName += ' ';
            }
        }
    }

    switch( theAmountKey ){
        case 'bet_amount':
            amountName += _this.langs.bet_amount;
        break;
        case 'deposit_amount':
            amountName += _this.langs.deposit_amount;
        break;
        case 'win_amount':
            amountName += _this.langs.win_amount;
        break;
        case 'loss_amount':
            amountName += _this.langs.loss_amount;
        break;
        default: // for game_platfom OR game_type
            if( ! $.isEmptyObject(currValue['amount_key']) ){
                if( ! $.isEmptyObject( theGameId4humanList[currValue['game_id_key']] ) ){
                    amountName = theGameId4humanList[currValue['game_id_key']];
                }
            }
            amountName += ' ' + _this.langs.bet_amount;
        break;
    }

    if( $.isEmptyObject(currValue['precon']) ){ // for frist prefix
        amountName = prefixWhileEmptyPrecon+ ' '+ amountName;
    }
    return amountName;
}// EOF HUAC_getAmountNameCB


PlayerManagement.HUBC_getAmountNameCB = function(theAmountKey, currValue, theGameId4humanList, prefixWhileEmptyPrecon){
    var _this = this;
    var args = Array.prototype.slice.call(arguments); // clone arguments
    return _this.HUAC_getAmountNameCB.apply(_this, args);
} // EOF HUBC_getAmountNameCB

/**
 * Get the amount value with callback in handleUpgradeAboveContent().
 * HUAC = handleUpgradeAboveContent
 * @param {string} theAmountKey The amount key string, ex: bet_amount, deposit_amount, game_platform_id_XXX, game_type_id_XXX, ...etc.
 * @param {object} currValue The object is a element of the return from parseAmountsInfoFromFormula().
 * @return {string} The amount value.
 */
PlayerManagement.HUAC_getAmountValueCB = function(theAmountKey, currValue){
    var _this = this;
    var amountValue = null;
    amountValue = currValue['compare']+ ' '+ currValue['amount_formated'];
    return amountValue;
} // EOF HUAC_getAmountValueCB

// HUBC = handleUpgradeBelowContent
PlayerManagement.HUBC_getAmountValueCB = function(theAmountKey, currValue, theSeparateAccumulationCalcResult){
    var _this = this;
    var amountValue = null;
    if (_this.dbg) console.error('HUBC_getAmountValueCB.currValue:', currValue
        , 'theAmountKey:', theAmountKey
        , 'theSeparateAccumulationCalcResult:', theSeparateAccumulationCalcResult
    );
    switch(theAmountKey){
        case 'game_platform':
        case 'game_type':
            var gameIdKey = currValue['game_id_key']; // ex: game_type_id_360, game_platform_id_5674,...
            if (typeof (theSeparateAccumulationCalcResult) !== 'undefined'
                && 'separated_bet' in theSeparateAccumulationCalcResult
            ) {
                // amountValue = theSeparateAccumulationCalcResult.separated_bet[n][gameIdKey];
                var theSeparatedBet = theSeparateAccumulationCalcResult.separated_bet.filter(function (curVal) {
                    return typeof (curVal[gameIdKey]) !== 'undefined';
                }, _this);
                amountValue = theSeparatedBet[0][gameIdKey]; // just one for the gameIdKey
            } else {
                amountValue = 0;
            }
            // console.log('theSeparatedBet:', theSeparatedBet[0], 'gameIdKey:', gameIdKey, 'amountValue:', amountValue);
            break;

        case 'win_amount':
            amountValue = theSeparateAccumulationCalcResult.total_win;
            break;
        case 'loss_amount':
            amountValue = theSeparateAccumulationCalcResult.total_loss;
            break;
        case 'bet_amount': // @todo to handle theSeparateAccumulationCalcResult.separated_bet Not null
            amountValue = theSeparateAccumulationCalcResult.total_bet;
            break;
        case 'deposit_amount':
            amountValue = theSeparateAccumulationCalcResult.deposit;
            break;
        default:
        break;
    }
    // amountValue = currValue['amount_formated'];
    var theAmountNumeral = numeral(amountValue);
    var amount_formated = _this.numeralApplyAmountFormat(theAmountNumeral);
    return amount_formated;
} // EOF HUBC_getAmountValueCB

/**
 * The amountFormat apply to amount
 * @param numeral theAmountNumeral The numeral value
 * @return string the formated amount
 */
PlayerManagement.numeralApplyAmountFormat = function(theAmountNumeral){
    var _this = this;
    var amount_formated = theAmountNumeral.value();
    if( ! $.isEmptyObject(_this.amountFormat) ){
        amount_formated = theAmountNumeral.format(_this.amountFormat);
    }else{
        // for more options, please ref. to https://www.w3schools.com/jsref/jsref_tolocalestring_number.asp
        amount_formated = amount_formated.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 8});
    }
    return amount_formated;
} // EOF numeralApplyAmountFormat

/**
 * Handle the Parsed append to the Above Content division.
 * @param {object} theParsed The return of parseAmountsInfoFromFormula().
 * @param {object} theGameId4humanList The GameIdKey mapping to Game Name of the array.
 * @param {string} theSelector The divison selector string for append.
 * @param {string function( string theAmountKey, object theGameId4humanList )} theGetAmountNameCB The function for get the amount name.
 * More detail please reference to description of this.HUAC_getAmountNameCB().
 * @param {string function( string theAmountKey, object currValue )} theGetAmountValueCB The function for get the amount value.
 * More detail please reference to doc of this.HUAC_getAmountValueCB().
 *
 */
PlayerManagement.handleUpgradeAboveContent = function( theParsed // #1
                                                    , theGameId4humanList // #2
                                                    , theSelector // #3
                                                    , theGetAmountNameCB // #4
                                                    , theGetAmountValueCB // #5
                                                    , thePrefixWhileEmptyPrecon // #6
                                                    , theSeparateAccumulationCalcResult // #7, for separate_accumulation_calcResult
){
    var _this = this;

    if( typeof(theSelector) === 'undefined'){
        theSelector = '.ajaxManuallyUpgradeLevelModalBody .above-content';
    }
    if( typeof(theGameId4humanList) === 'undefined'){
        theGameId4humanList = [];
    }

    if( typeof(theSeparateAccumulationCalcResult) === 'undefined'){
        theSeparateAccumulationCalcResult = [];
    }

    var theUpgradeAboveContent$El = $(theSelector);

    if (!$.isEmptyObject(theParsed)) {
        if (_this.dbg) console.log('theParsed in 4243', theParsed);
        // console.log('handleUpgradeAboveContent.theParsed:', theParsed);
        $.each(theParsed, function(indexNumber, currValue){
            // var amountName = null;
            // var amountValue = null;
            var theAmountKey = currValue['amount_key'];
            if (_this.dbg) console.log('currValue in 4248', currValue);
            // console.log('handleUpgradeAboveContent.theAmountKey:', theAmountKey);
            if( typeof(theGetAmountNameCB) === 'undefined'){
                theGetAmountNameCB = _this.HUAC_getAmountNameCB;
            }
            /// Alias _this.theGetAmountNameCB(),
            // call theGetAmountNameCB with _this.
            var amountName = theGetAmountNameCB.apply(_this, [theAmountKey, currValue, theGameId4humanList, thePrefixWhileEmptyPrecon]);
            // if( typeof(theGetAmountNameCB) !== 'undefined'){
            //     var amountName = theGetAmountNameCB(theAmountKey, currValue, theGameId4humanList);
            // }else{
            //     var amountName = _this.HUAC_getAmountNameCB(theAmountKey, currValue, theGameId4humanList);
            // }

            if( typeof(theGetAmountValueCB) === 'undefined'){
                theGetAmountValueCB = _this.HUAC_getAmountValueCB;
            }
            /// Alias _this.theGetAmountValueCB(),
            // call theGetAmountValueCB with _this.
            var amountValue = theGetAmountValueCB.apply(_this, [theAmountKey, currValue, theSeparateAccumulationCalcResult]);
            // if( typeof(theGetAmountValueCB) !== 'undefined'){
            //     var amountValue = theGetAmountValueCB(theAmountKey, currValue);
            // }else{
            //     var amountValue = _this.HUAC_getAmountValueCB(theAmountKey, currValue);
            // }

            // amountValue = currValue['compare']+ ' '+ currValue['amount_formated'];
            if( ! $.isEmptyObject(currValue['precon']) ){
                // for contains and/or at prefix.
                var theTplSelector = '#tpl-col-md-2-5-5';
                var theApplyData = {};
                theApplyData['.col-content-1'] = currValue['precon'];
                theApplyData['.col-content-2'] = amountName;
                theApplyData['.col-content-3'] = amountValue;
            }else{
                // for without and/or at prefix.
                var theTplSelector = '#tpl-col-md-offset1-6-5';
                var theApplyData = {};
                // theApplyData['.col-content-1'] = _this.langs.upgrade_needs_bets_amount;
                theApplyData['.col-content-1'] = amountName;
                theApplyData['.col-content-2'] = amountValue;
            }

            var theHtml = _this.getHtmlAfterAppliedWithTpl(theTplSelector, theApplyData);
            theUpgradeAboveContent$El.append(theHtml);
        }); // EOF $.each(theParsed, function(indexNumber, currValue){...
    }


} // EOF handleUpgradeAboveContent

PlayerManagement.handleUpgradeBelowContent = function(theParsed // #1
                                                    , theGameId4humanList // #2
                                                    , theSelector // #3
                                                    , theGetAmountNameCB // #4
                                                    , theGetAmountValueCB // #5
                                                    , thePrefixWhileEmptyPrecon // #6
                                                    , theSeparateAccumulationCalcResult // #7, for separate_accumulation_calcResult
){
    var _this = this;
    var args = Array.prototype.slice.call(arguments); // clone arguments

    return _this.handleUpgradeAboveContent.apply(_this, args);
} // EOF handleUpgradeBelowContent


PlayerManagement.initialCheckScheduleBodyInDowngrade = function () {
    var _this = this;
    $('.check_schedule_body div[class*="col-"][class*="amount"]').text( _this.langs.loading);
    $('.check_schedule_body div[class*="col-"][class*="value"]').text( _this.langs.loading);

    $('.current_bet-row').html('');
    $('.current_deposit-row').html(''); // should be ingored
    $('.cronjob_period_value').html('');

    $('.down_maintain_period_time').html('');
    $('.down_maintain_condition_bet_amount').html('');
    $('.down_maintain_condition_deposit_amount').html('');

    // vip_level_maintain_settings UI reset
    $('.col-vip_level_maintain_settings').addClass('hide');
    $('.col-disable-vip_level_maintain_settings').addClass('hide');
} // EOF initialCheckScheduleBodyInDowngrade

/**
 *
 */
PlayerManagement.handlePLAH4atTheLowestLevel = function(thePLAH){
    var _this = this;
    var isVipLevelMaintainSettings = _this.is_vip_level_maintain_settings();
    var theGroupLevelName = thePLAH.playerLevelInfo.playerLevelData.groupLevelName;
    var thePlayerLevelData = thePLAH.playerLevelInfo.playerLevelData;
    var thePeriodDown = thePlayerLevelData.period_down;
    thePeriodDown = JSON.parse(thePeriodDown);

    // // thePeriod_down convert to period_down
    // var period_down = {};
    // if( !$.isEmptyObject(thePlayerLevelData) ){
    //     period_down = JSON.parse(thePeriodDown);
    // }

    $('.keepgrade_level_value').text(theGroupLevelName);
    $('.current_level_value').text(_this.langs.na);

    // _this.handleBetAmountWithResult_formula_detail(thePLAH.result_formula_detail);



    if (isVipLevelMaintainSettings) {
        _this.handleDown_maintainWithPeriod_down(thePeriodDown);
    }else{
        _this.handleGuaranteed_periodWithLastNewVipLevelData(thePlayerLevelData);
    }

    if (!$.isEmptyObject(thePeriodDown)) {
        _this.handleCronjob_period_valueWithPeriod_down(thePeriodDown);
    }
} // EOF handlePLAH4atTheLowestLevel


/**
 * Handle Amounts after downgrade
 * @param {*} thePLAH
 */
PlayerManagement.handleCurrentAmountsInDowngradeWithPLAH = function (thePLAH) {
    var _this = this;

    var firstFailedOfNewVipLevelData = _this.getFirstFailedOfNewVipLevelDataWithPLAH(thePLAH);
    var lastDowngradedOfNewVipLevelData = _this.getLastUpgradedOfNewVipLevelDataWithPLAH(thePLAH);

    var prefixWhileEmptyPrecon = _this.langs.current_vip4prefix;
    var theSelector = _this.theDowngradeLevelModalBodySelector + ' .current_bet-row';

    if ($.isEmptyObject(firstFailedOfNewVipLevelData)) {
        // downgrade success
        var the_vip_downgrade_setting = lastDowngradedOfNewVipLevelData.nextVipLevelData.vip_downgrade_setting;
        var _parsed = _this.parseAmountsInfoFromFormula(lastDowngradedOfNewVipLevelData.nextVipLevelData.remark.rule, the_vip_downgrade_setting);
        // console.log('handleCurrentAmountsInDowngradeWithPLAH._parsed:', _parsed);
        // [{"accumulation":"1","precon":null,"compare":"<=","amount":"410","amount_formated":"410.00","game_id_key":"game_type_id_440","full_match":"game_type_id_440 <= 410","amount_key":"game_type"},{"accumulation":"1","precon":"and","compare":"<=","amount":"411","amount_formated":"411.00","game_id_key":"game_platform_id_5654","full_match":"and game_platform_id_5654 <= 411","amount_key":"game_platform"},{"accumulation":"1","precon":"and","compare":"<=","amount":"412","amount_formated":"412.00","full_match":"and deposit_amount <= 412","amount_key":"deposit_amount"},{"accumulation":"0","precon":"and","compare":"<=","amount":"1","amount_formated":"1.00","full_match":"and loss_amount <= 1","amount_key":"loss_amount"},{"accumulation":"4","precon":"and","compare":"<=","amount":"2","amount_formated":"2.00","full_match":"and win_amount <= 2","amount_key":"win_amount"}]
        var _game_id4human_list = lastDowngradedOfNewVipLevelData.nextVipLevelData.remark.game_id4human_list;
        var theSeparateAccumulationCalcResult = lastDowngradedOfNewVipLevelData.nextVipLevelData.remark.separate_accumulation_calcResult;
    } else {
        // downgrade failed
        var the_vip_downgrade_setting = firstFailedOfNewVipLevelData.vip_downgrade_setting;
        var _parsed = _this.parseAmountsInfoFromFormula(firstFailedOfNewVipLevelData.remark.rule, the_vip_downgrade_setting);
        // console.log('handleCurrentAmountsInDowngradeWithPLAH._parsed:', _parsed);
        // [{"accumulation":"1","precon":null,"compare":"<=","amount":"410","amount_formated":"410.00","game_id_key":"game_type_id_440","full_match":"game_type_id_440 <= 410","amount_key":"game_type"},{"accumulation":"1","precon":"and","compare":"<=","amount":"411","amount_formated":"411.00","game_id_key":"game_platform_id_5654","full_match":"and game_platform_id_5654 <= 411","amount_key":"game_platform"},{"accumulation":"1","precon":"and","compare":"<=","amount":"412","amount_formated":"412.00","full_match":"and deposit_amount <= 412","amount_key":"deposit_amount"},{"accumulation":"0","precon":"and","compare":"<=","amount":"1","amount_formated":"1.00","full_match":"and loss_amount <= 1","amount_key":"loss_amount"},{"accumulation":"4","precon":"and","compare":"<=","amount":"2","amount_formated":"2.00","full_match":"and win_amount <= 2","amount_key":"win_amount"}]
        var _game_id4human_list = firstFailedOfNewVipLevelData.remark.game_id4human_list;
        var theSeparateAccumulationCalcResult = firstFailedOfNewVipLevelData.remark.separate_accumulation_calcResult;
    }

    _this.handleUpgradeBelowContent(_parsed // #1
        , _game_id4human_list // #2
        , theSelector // #3
        , _this.HUBC_getAmountNameCB // #4
        , _this.HUBC_getAmountValueCB // #5
        , prefixWhileEmptyPrecon // #6
        , theSeparateAccumulationCalcResult //  #7
    );

} // EOF handleCurrentAmountsInDowngradeWithPLAH

/**
 * Detect for isNoUpgradeSetting  (Need debug)
 * If true,  No upgrade setting after upgrade checking.
 */
PlayerManagement.detect4isNoUpgradeSettingWithPLAH = function (thePLAH) {
    var _this = this;
    var isNoUpgradeSetting = null;
    // var isDisablePlayerMultipleUpgrade = _this.is_disable_player_multiple_upgrade();
    // var lastestOfNewVipLevelData = _this.getLastestNewVipLevelDataWithPLAH(thePLAH);
    var lastUpgradedOfNewVipLevelData = _this.getLastUpgradedOfNewVipLevelDataWithPLAH(thePLAH);
    var lastFailedOfNewVipLevelData = _this.getLastFailedOfNewVipLevelDataWithPLAH(thePLAH);
    // var firstUpgradedOfNewVipLevelData = _this.getFirstUpgradedOfNewVipLevelDataWithPLAH(thePLAH);
    // var firstFailedOfNewVipLevelData = _this.getFirstFailedOfNewVipLevelDataWithPLAH(thePLAH);

    if (!$.isEmptyObject(lastUpgradedOfNewVipLevelData)) {
        if ('nextVipLevelData' in lastUpgradedOfNewVipLevelData) {
            if ('vip_upgrade_id' in lastUpgradedOfNewVipLevelData.nextVipLevelData) {
                if (!$.isEmptyObject(lastUpgradedOfNewVipLevelData.nextVipLevelData.vip_upgrade_id)) {
                    if (_this.dbg) console.log('isNoUpgradeSetting in 3167');
                    isNoUpgradeSetting = false;
                } else {
                    if (_this.dbg) console.log('isNoUpgradeSetting in 3169');
                    isNoUpgradeSetting = true;
                }
            } else {
                if (_this.dbg) console.log('isNoUpgradeSetting in 3172');
                isNoUpgradeSetting = true;
            }
        } else {
            if (_this.dbg) console.log('isNoUpgradeSetting in 3175');
            isNoUpgradeSetting = true;
        }
    } else if (!$.isEmptyObject(lastFailedOfNewVipLevelData)) {
        if ('nextVipLevelData' in lastFailedOfNewVipLevelData) {
            if ('vip_upgrade_id' in lastFailedOfNewVipLevelData.nextVipLevelData) {
                if (!$.isEmptyObject(lastFailedOfNewVipLevelData.nextVipLevelData.vip_upgrade_id)) {
                    if (_this.dbg) console.log('isNoUpgradeSetting in 3181');
                    isNoUpgradeSetting = false;
                } else {
                    if (_this.dbg) console.log('isNoUpgradeSetting in 3183');
                    isNoUpgradeSetting = true;
                }
            } else {
                if (_this.dbg) console.log('isNoUpgradeSetting in 3186');
                isNoUpgradeSetting = true;
            }
        } else {
            if (_this.dbg) console.log('isNoUpgradeSetting in 3189');
            isNoUpgradeSetting = true;
        }
    } else {
        if (_this.dbg) console.log('isNoUpgradeSetting in 3239');
        isNoUpgradeSetting = true; // upgrade from top level
    }

    if (_this.dbg) console.log('3192.isNoUpgradeSetting:', isNoUpgradeSetting);
    return isNoUpgradeSetting;
} // EOF detect4isNoUpgradeSettingWithPLAH

/**
 * Detect for isUpgradeSuccess
 * If true, its means had upgraded.
 */
PlayerManagement.detect4isUpgradeSuccessWithPLAH = function (thePLAH) {
    var _this = this;
    var isUpgradeSuccess = null;

    var beforePlayerLevelData = _this.getBeforePlayerLevelDataWithPLAH(thePLAH);
    var afterPlayerLevelData = _this.getAfterPlayerLevelDataWithPLAH(thePLAH);


    var before_vipsettingcashbackruleId = -2;
    if ('vipsettingcashbackruleId' in beforePlayerLevelData) {
        before_vipsettingcashbackruleId = beforePlayerLevelData.vipsettingcashbackruleId;
    }
    var after_vipsettingcashbackruleId = -1;
    if ('vipsettingcashbackruleId' in afterPlayerLevelData) {
        after_vipsettingcashbackruleId = afterPlayerLevelData.vipsettingcashbackruleId;
    }

    if (before_vipsettingcashbackruleId < 0 || after_vipsettingcashbackruleId < 0) {
        if (_this.dbg) console.log('isUpgradeSuccess in 4452');
        isUpgradeSuccess = false;
    } else if (before_vipsettingcashbackruleId != after_vipsettingcashbackruleId) {
        if (_this.dbg) console.log('isUpgradeSuccess in 4453');
        isUpgradeSuccess = true;
    } else {
        if (_this.dbg) console.log('isUpgradeSuccess in 4456');
        isUpgradeSuccess = false;
    }

    if (_this.dbg) console.log('4460.isUpgradeSuccess:', isUpgradeSuccess, before_vipsettingcashbackruleId, after_vipsettingcashbackruleId);
    return isUpgradeSuccess;
} // detect4isUpgradeSuccessWithPLAH

/**
 * Handle the Downgrade Pop-Up after Downgrade Check
 * @param {*} thePLAH
 */
PlayerManagement.handlePLAHInDowngrade = function (thePLAH) {
    var _this = this;

    var isVipLevelMaintainSettings = _this.is_vip_level_maintain_settings();
    var isDowngradeSuccess = null; // Had Downgrade Success?
    var firstFailedOfNewVipLevelData = _this.getFirstFailedOfNewVipLevelDataWithPLAH(thePLAH);
    var lastDowngradedOfNewVipLevelData = _this.getLastUpgradedOfNewVipLevelDataWithPLAH(thePLAH);

    var isNoDowngradeSetting = null;// If true,it's mean No Setup Downgrade Settings.
    // The case,bottom Level downgrade It's also No the Downgrade Settings.
    if (!$.isEmptyObject(firstFailedOfNewVipLevelData)) {
        if ('nextVipLevelData' in firstFailedOfNewVipLevelData) {
            if ('vip_downgrade_id' in firstFailedOfNewVipLevelData.nextVipLevelData) {
                if ($.isEmptyObject(firstFailedOfNewVipLevelData.nextVipLevelData.vip_downgrade_id)) {
                    isNoDowngradeSetting = true;
                    if (_this.dbg) console.log('isNoDowngradeSetting.4223', isNoDowngradeSetting);
                } else {
                    isNoDowngradeSetting = false;
                    if (_this.dbg) console.log('isNoDowngradeSetting.4232', isNoDowngradeSetting);
                }
            } else {
                isNoDowngradeSetting = true;
                if (_this.dbg) console.log('isNoDowngradeSetting.4226', isNoDowngradeSetting);
            }
        } else {
            isNoDowngradeSetting = true;
            if (_this.dbg) console.log('isNoDowngradeSetting.4225', isNoDowngradeSetting);
        }
    } else if (!$.isEmptyObject(lastDowngradedOfNewVipLevelData)) {
        if ('nextVipLevelData' in lastDowngradedOfNewVipLevelData) {
            isNoDowngradeSetting = false;
            if (_this.dbg) console.log('isNoDowngradeSetting.4230', isNoDowngradeSetting);
        } else {
            isNoDowngradeSetting = true;
            if (_this.dbg) console.log('isNoDowngradeSetting.4233', isNoDowngradeSetting);
        }
    }

    if (!$.isEmptyObject(firstFailedOfNewVipLevelData)) {
        isDowngradeSuccess = false;
        if (_this.dbg) console.log('isDowngradeSuccess.4554', isDowngradeSuccess);
    } else if (!$.isEmptyObject(lastDowngradedOfNewVipLevelData)) {
        isDowngradeSuccess = true;
        if (_this.dbg) console.log('isDowngradeSuccess.4557', isDowngradeSuccess);
    }

    if (thePLAH.playerLevelInfo.newVipLevelDataList.length == 0
        && $.isEmptyObject(parseInt(thePLAH.playerLevelInfo.playerLevelData.vip_downgrade_id))
    ) { // for downgrade at bottom level.
        isNoDowngradeSetting = true;
        if (_this.dbg) console.log('isNoDowngradeSetting.4224', isNoDowngradeSetting);
        isDowngradeSuccess = false;
        if (_this.dbg) console.log('isDowngradeSuccess.4243', isDowngradeSuccess);
    }

    if (isNoDowngradeSetting) {
        _this.handlePLAH4atTheLowestLevel(thePLAH);
    } else {
        _this.handleCurrentAmountsInDowngradeWithPLAH(thePLAH);

        if (isDowngradeSuccess) {
            $('.keepgrade_level_value').text(lastDowngradedOfNewVipLevelData.groupLevelName);
            $('.current_level_value').text(lastDowngradedOfNewVipLevelData.nextVipLevelData.groupLevelName);
        } else {
            $('.keepgrade_level_value').text(thePLAH.playerLevelInfo.playerLevelData.groupLevelName);
            $('.current_level_value').text(thePLAH.playerLevelInfo.playerLevelData.groupLevelName);
        }

        var thePeriodDown = null;// period_down = JSON.parse(lastNewVipLevelData.period_down);
        var theVipLevelData = null; // lastNewVipLevelData = thePLAH.playerLevelInfo.newVipLevelDataList[counter-1];
        if (isDowngradeSuccess) {
            if (_this.dbg) console.log('thePeriodDown = lastDowngradedOfNewVipLevelData.nextVipLevelData.period_down:');
            thePeriodDown = lastDowngradedOfNewVipLevelData.nextVipLevelData.period_down;
            theVipLevelData = lastDowngradedOfNewVipLevelData.nextVipLevelData;
        } else {
            if (_this.dbg) console.log('thePeriodDown = thePLAH.playerLevelInfo.playerLevelData.period_downlastDowngradedOfNewVipLevelData.nextVipLevelData.period_down:');
            thePeriodDown = thePLAH.playerLevelInfo.playerLevelData.period_down;
            theVipLevelData = thePLAH.playerLevelInfo.playerLevelData;
        }
        thePeriodDown = JSON.parse(thePeriodDown);
        if (isVipLevelMaintainSettings) {
            // .col-vip_level_maintain_settings
            // 新的保級制度 vip_level_maintain_settings eq. true
            _this.handleDown_maintainWithPeriod_down(thePeriodDown);
        } else {
            // .col-disable-vip_level_maintain_settings
            // 舊的保級制度 vip_level_maintain_settings eq. false
            // console.log('theVipLevelData:', theVipLevelData);
            _this.handleGuaranteed_periodWithLastNewVipLevelData(theVipLevelData);
        }

        if (!$.isEmptyObject(thePeriodDown)) {
            if (_this.dbg) console.log('will handleCronjob_period_valueWithPeriod_down.4602.thePeriodDown:', thePeriodDown);
            _this.handleCronjob_period_valueWithPeriod_down(thePeriodDown);
        }
    } // EOF if (isNoDowngradeSetting) {...
} // EOF handlePLAHInDowngrade


PlayerManagement.handleGuaranteed_periodWithLastNewVipLevelData = function(theLastNewVipLevelData){
    var _this = this;
    $('.guaranteed_period_number').text(theLastNewVipLevelData.guaranteed_downgrade_period_number);
    $('.guaranteed_period_total_deposit').text(theLastNewVipLevelData.guaranteed_downgrade_period_total_deposit);

    $('.col-disable-vip_level_maintain_settings').removeClass('hide'); // display
} // EOF handleGuaranteed_periodWithLastNewVipLevelData

PlayerManagement.handleDown_maintainWithPeriod_down = function(thePeriod_down){
    var _this = this;

    var down_maintain_time_unit = null;
    var switchVal = _this.langs.disabled;
    var down_maintain_period_time = _this.langs.na;
    var down_maintain_condition_deposit_amount = _this.langs.na;
    var down_maintain_condition_bet_amount = _this.langs.na;
    var downMaintainUnit = '';

    if (!$.isEmptyObject(thePeriod_down)) {
        if ('enableDownMaintain' in thePeriod_down
            && thePeriod_down.enableDownMaintain == true
        ) {
            switchVal = _this.langs.enabled;
        }
        downMaintainUnit = thePeriod_down.downMaintainUnit;
        switch (parseInt(thePeriod_down.downMaintainUnit)) {
            default:
            case _this.settings.DOWN_MAINTAIN_TIME_UNIT_DAY:
            down_maintain_time_unit = _this.langs.day;
            break;

            case _this.settings.DOWN_MAINTAIN_TIME_UNIT_WEEK:
            down_maintain_time_unit = _this.langs.week;
            break;

            case _this.settings.DOWN_MAINTAIN_TIME_UNIT_MONTH:
            down_maintain_time_unit = _this.langs.month;
            break;
        }
        var downMaintainTimeLength = 0;
        if (!$.isEmptyObject(thePeriod_down.downMaintainTimeLength)) {
            downMaintainTimeLength = thePeriod_down.downMaintainTimeLength;
        }

        var downMaintainConditionDepositAmount = 0;
        if (!$.isEmptyObject(thePeriod_down.downMaintainConditionDepositAmount)) {
            downMaintainConditionDepositAmount = thePeriod_down.downMaintainConditionDepositAmount;
        }
        down_maintain_condition_deposit_amount = '&gt;= '; // >=
        down_maintain_condition_deposit_amount += downMaintainConditionDepositAmount;

        var downMaintainConditionBetAmount = 0;
        if (!$.isEmptyObject(thePeriod_down.downMaintainConditionBetAmount)) {
            downMaintainConditionBetAmount = thePeriod_down.downMaintainConditionBetAmount;
        }
        down_maintain_condition_bet_amount = '&gt;= '; // >=
        down_maintain_condition_bet_amount += downMaintainConditionBetAmount;
    } // EOF if (!$.isEmptyObject(thePeriod_down)) {...

    down_maintain_period_time = downMaintainTimeLength;
    down_maintain_period_time += ' ';
    down_maintain_period_time += down_maintain_time_unit;

    $('.down_maintain_period_time').text(down_maintain_period_time);

    $('.enable_down_maintain').text(switchVal);
    $('.down_maintain_condition_deposit_amount').html(down_maintain_condition_deposit_amount);
    $('.down_maintain_condition_bet_amount').html(down_maintain_condition_bet_amount);

    $('.col-vip_level_maintain_settings').removeClass('hide'); // display
} // EOF handleDown_maintainWithPeriod_down

PlayerManagement.handleCronjob_period_valueWithPeriod_down = function(thePeriod_down){
    var _this = this;
    var _cronjob_period_value = _this.langs.na;
    if (typeof (thePeriod_down) === 'string') {
        thePeriod_down = JSON.parse(thePeriod_down);
    }
    // console.error('thePeriod_down:', thePeriod_down);
    if( typeof(thePeriod_down.daily) !== 'undefined'){ // daily
        _cronjob_period_value = _this.langs.daily;
    }else if( typeof(thePeriod_down.weekly) !== 'undefined'){ // weekly
        var weekday = _this.dayOfWeekAsString(thePeriod_down.weekly);
        weekday = weekday.toLowerCase();
        _cronjob_period_value = _this.langs.weekly + ' ' + _this.langs[weekday];

    }else if( typeof(thePeriod_down.monthly) !== 'undefined'){ // monthly
        var monthlyDay = numeral(thePeriod_down.monthly);
        _cronjob_period_value = _this.langs.monthly + ' ' + monthlyDay.format('Oo');
    }
    $('.cronjob_period_value').text(_cronjob_period_value);
} // EOF handleCronjob_period_valueWithPeriod_down

/**
 * Get Total beting amount from the result_formula_detail of PLAH object.
 * @param theResultFormulaDetail The result_formula_detail attr. of the PLAH object.
 * @return float|integer totalBetAmount The total betting amounts.
 */
PlayerManagement.getTotalBetAmountFromBetGameIdKeyWithResultFormulaDetail = function(theResultFormulaDetail){

        var totalBetAmount = 0;
        var _resultFormulaDetail = theResultFormulaDetail;
        var _betGameIdKeyValList = {};
        if( typeof(_resultFormulaDetail.betGameIdKeyValList) !== 'undefined'
            && 'betGameIdKeyValList' in _resultFormulaDetail
        ){
            _betGameIdKeyValList = _resultFormulaDetail.betGameIdKeyValList;
        }
        if( !$.isEmptyObject(_betGameIdKeyValList) ){
            $.each(_betGameIdKeyValList, function(keyStr, currVal) {
                totalBetAmount += _betGameIdKeyValList[keyStr];
            });
        }

        return totalBetAmount;
} // EOF getTotalBetAmountFromResultFormulaDetail


/**
* Converts a day number to a string.
* Ref.to https://stackoverflow.com/a/24333274
* @param {Number} dayIndex
* @return {String} Returns day as string
*/
PlayerManagement.dayOfWeekAsString = function(dayIndex) {
    return ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"][dayIndex];
}

PlayerManagement.getExistsCountByNameInArray = function (indexed_array, pName) {
    var iKey = 0;
    Object.keys(indexed_array).forEach(function (key) {
        var _pName = pName.replace('[]', ''); // xxx[] => xxx
        if (key.lastIndexOf(_pName) > -1) {
            iKey++;
        }
    })
    return iKey;
}
PlayerManagement.getFormData = function (unindexed_array) {
    var _this = this;
    var indexed_array = {};

    $.map(unindexed_array, function (n, i) {
        if (n['name'].lastIndexOf('[]') > -1) {
            var iKey = _this.getExistsCountByNameInArray(indexed_array, n['name']);
            indexed_array[n['name'].replace('[]', '[' + iKey + ']')] = n['value'];
        } else {
            indexed_array[n['name']] = n['value'];
        }

    });

    return indexed_array;
};

PlayerManagement.callResetPlayerPassword = function(password, uri, beforeCallback, completeCallback ){
    var _this = this;
    if (_this.dbg) console.log('callResetPlayerPassword.uri:', uri, 'password:', password);
    var _ajax = $.ajax({
        url: uri,
        type: 'POST',
        data: { 'password': password
            , 'doAlertMessage': 1 // Zero or 1
        },
        beforeSend: function (xhr, settings) {
            var cloned_arguments = Array.prototype.slice.call(arguments);
            if(typeof(beforeCallback) !== 'undefined'){
                beforeCallback.apply(_this, cloned_arguments);
            }
        },
        complete: function (xhr, textStatus) {
            var cloned_arguments = Array.prototype.slice.call(arguments);
            if(typeof(completeCallback) !== 'undefined'){
                completeCallback.apply(_this, cloned_arguments);
            }
        }
    });
    return _ajax;
}
PlayerManagement.callKickPlayer = function(beforeCallback, completeCallback){
    var _this = this;
    var _uri =  _this.uri.kickPlayer;
    if (_this.dbg) console.log('callKickPlayer._uri:', _uri);
    var _ajax = $.ajax({
        url: _uri,
        type: 'POST',
        data: {
            'doAlertMessage': 0 // Zero or 1
        },
        beforeSend: function (xhr, settings) {
            var cloned_arguments = Array.prototype.slice.call(arguments);
            if(typeof(beforeCallback) !== 'undefined'){
                beforeCallback.apply(_this, cloned_arguments);
            }
        },
        complete: function (xhr, textStatus) {
            var cloned_arguments = Array.prototype.slice.call(arguments);
            if(typeof(completeCallback) !== 'undefined'){
                completeCallback.apply(_this, cloned_arguments);
            }
        }
    });
    return _ajax;
}
PlayerManagement.progress4player_reset_password = function(show_switch_str){
    var _this = this;
    if( typeof(show_switch_str) == 'undefined' ){
        show_switch_str = 'none';
    }

    $('.progress4changingPassword').addClass('hide');
    $('.progress4kickingOutGames').addClass('hide');

    switch(show_switch_str){
        default:
        case 'none':
            break;
        case 'changingPassword':
            $('.progress4changingPassword').removeClass('hide');
            break;
        case 'kickingOutGames':
            $('.progress4kickingOutGames').removeClass('hide');
            break;
        case 'all':
            $('.progress4changingPassword').removeClass('hide');
            $('.progress4kickingOutGames').removeClass('hide');
            break;
    }
}
PlayerManagement.submited4player_reset_password = function(e){
    var _this = this;

    var form$El = $(e.target);
    var uri = form$El.prop('action');
    var goKickPlayer$dfd = $.Deferred();
    var password = form$El.find('input[name="password"]').val();

    // console.log('jqXHR.done.arguments:', arguments);
    // var cloned_arguments = Array.prototype.slice.call(arguments);
    _this.popup4player_reset_password(function(){ // showCallback
        var cloned_arguments = Array.prototype.slice.call(arguments);
        if (_this.dbg) console.log('4932.showCallback', cloned_arguments);

        // beforeCB,
        form$El.find('input.submit_btn').button('loading');

        // phase 1, reset password in SBE and player site.
        var _ajax = _this.callResetPlayerPassword(password, uri
            , function (){ // beforeCallback
                _this.progress4player_reset_password('changingPassword');
                $('.notify-text.notify4changingPassword').removeClass('hide');
            }, function (){ // completeCallback
                // form$El.find('input.submit_btn').button('reset');
            });
        _ajax.done(function (data, textStatus, jqXHR) {
            if( typeof(data.message) !== 'undefined' ){
                $('.notify-text.notify4changePassOk').html(data.message);
            }
            $('.notify-text.notify4changePassOk').removeClass('hide');
            goKickPlayer$dfd.resolve({}); // will call goKickPlayer$dfd.done()
        }); // EOF _ajax.done(function (data, textStatus, jqXHR) {...

    }, function(){ // shownCallback
    }, function(){ // hideCallback
    }, function(){ // hiddenCallback
        window.location.reload(); // for sync UI
    }, function(){ // loadedCallback
    }); // EOF _this.popup4player_reset_password(...

    goKickPlayer$dfd.done(function() {
        // phase 2, Kick player from games
        var _ajax = _this.callKickPlayer(function (){ // beforeCallback
            _this.progress4player_reset_password('kickingOutGames');
            $('.notify-text.notify4kickingOutGames').removeClass('hide');
        }, function (){ // completeCallback

        });
        _ajax.done(function (data, textStatus, jqXHR) {
            _this.progress4player_reset_password('none');

            if( typeof(data.message) !== 'undefined' ){
                $('.notify-text.notify4kickOutGames').html(data.message);
            }
            $('.notify-text.notify4kickOutGames').removeClass('hide');
        });
        _ajax.done(function (data, textStatus, jqXHR) {
            $('.notify-button').removeClass('hide');
        });
        _ajax.done(function (data, textStatus, jqXHR) {
            // completeCB,
            form$El.find('input.submit_btn').button('reset');
        });
    });



} // EOF PlayerManagement.submited4player_reset_password = function(e){...

PlayerManagement.popup4player_reset_password = function ( showCallback
                                                        , shownCallback
                                                        , hideCallback
                                                        , hiddenCallback
                                                        , loadedCallback
) {
    var _this = this;

    var _notify = _this.getTplHtmlWithOuterHtmlAndReplaceAll('#tpl-notify4changePassword', []);
    // 密码修改成功 popup
    var load = _notify; // html
    var title = ''; // 修改密码
    var modal_id = 'notify4changePassOk';
    var load_source = 'html';
    var backdrop = 'false'; // for "data-backdrop".
    modalV2( load
            , title
            , modal_id
            , load_source
            , backdrop
            , showCallback
            // , function(){ // showCallback
            //     var cloned_arguments = Array.prototype.slice.call(arguments);
            //     console.log('54.showCallback', cloned_arguments);
            // }
            , shownCallback
            // , function(){ // shownCallback
            //     var cloned_arguments = Array.prototype.slice.call(arguments);
            //     console.log('58.shownCallback', cloned_arguments);
            // }
            , hideCallback
            // , function(){ // hideCallback
            //     var cloned_arguments = Array.prototype.slice.call(arguments);
            //     console.log('62.hideCallback', cloned_arguments);
            // }
            , hiddenCallback
            // , function(){ // hiddenCallback
            //     var cloned_arguments = Array.prototype.slice.call(arguments);
            //     console.log('66.hiddenCallback', cloned_arguments);
            // }
            , loadedCallback
            // , function(){ // loadedCallback
            //     var cloned_arguments = Array.prototype.slice.call(arguments);
            //     console.log('70.loadedCallback', cloned_arguments);
            // }
    ); // EOF modalV2( load, title, modal_id, load_source...
}

PlayerManagement.getTplHtmlWithOuterHtmlAndReplaceAll = function (selectorStr, regexList) {
    var _this = this;

    var _outerHtml = '';
    if (typeof (selectorStr) !== 'undefined') {
        _outerHtml = $(selectorStr).html(); // _this.outerHtml(selectorStr);
    }

    if (typeof (regexList) === 'undefined') {
        regexList = [];
    }

    if (regexList.length > 0) {
        regexList.forEach(function (currRegex, indexNumber) {
            // assign playerpromo_id into the tpl
            var regex = currRegex['regex']; // var regex = /\$\{playerpromo_id\}/gi;
            _outerHtml = _outerHtml.replaceAll(regex, currRegex['replaceTo']);// currVal.playerpromo_id);
        });
    }
    return _outerHtml;
} // getTplHtmlWithOuterHtmlAndReplaceAll


