$(document).ready(function () {
    $("#tran-amt").OnlyInputNumber();
    var btn = $(".user_btn,.menu_btn,.menu_btn_two");
    var pageone = $("#pageone");
    var pagetwo = $("#pagetwo");
    var pagethree = $("#pagethree");
    var pagefour = $("#pagefour");
    var pagefive = $("#pagefive");
    var menu = $(".menu_right,.menu_left");
    var leftmove = "left_move";
    var mmove = "menu_move";
    var leftm = $(".menu_left");
    var rightm = $(".menu_right");


    $(".xlmenu").click(function () {

        var xlmenu = ".mmenu";
        var xlmovie = "mmenu_movie";

        $(xlmenu).addClass(xlmovie);

        var mmenu = $(this).attr("id");
        if (mmenu == "zh1")/*ѡ�����в˵�*/ {
            choicemenu();

            var eput = $(this);
            var plat;

            plat = $("#zxzh").text();
            indexval = eput.text();

        }
        if (mmenu == "zh2")/*ѡ�����в˵�*/ {
            choicemenu();

            var eput = $(this);
            var plat;

            plat = $("#zxzh").text();
            indexval = eput.text();

        }

        $(".mm_exit").click(function () {
            $(xlmenu).removeClass(xlmovie);
        });
        function choicemenu() {
            $(".mm_main li").unbind("click");
            $(".mm_main li").click(function () {
                $(".mm_main li i").removeClass("border");
                $(this).find("i").addClass("border");
                $(xlmenu).removeClass(xlmovie);
                var title = $(this).text();
                if (mmenu == "zh1" || "zh2") {
                    eput.text($(this).text());
                    if (eput.attr("id") == "zh1" && $(this).text() != plat) {
                        $("#zh2").text(plat);
                    } else if (eput.attr("id") == "zh2" && $(this).text() != plat) {
                        $("#zh1").text(plat);
                    } else if ($("#zh1").text() == plat && $("#zh2").text() == plat) {
                        $("#zh1").text("��ѡ��ת���˻�");
                        $("#zh2").text("��ѡ��ת���˻�");
                        eput.text($(this).text());
                    }
                }
            });
        };
    });

    $("#accqd").click(function () {
        var zha = $("#zh1").text();
        var zhb = $("#zh2").text();
        var zxzh = "�����˻�";

        if (zha == "��ѡ��ת���˻�") {
            $("#zztext1").show();
            return false;
        } else {
            $("#zztext1").hide()
        }

        if (zhb == "��ѡ��ת���˻�") {
            $("#zztext2").show();
            return false;
        } else {
            $("#zztext2").hide()
        }

        if (zha == zxzh && zhb != zxzh) {
            TranToGame();
        }
        else if (zha != zxzh && zhb == zxzh) {
            TranToPlat();
        }

    });
});
var IsSubmit = false;
function TranToGame() {
    if (IsSubmit) {
        alert("����ת����");
        return false;
    }
    var zha = $("#zh1").text();
    if (zha == "��ѡ��ת���˻�") {
        $("#zztext1").show();
        return false;
    } else {
        $("#zztext1").hide()
    }
    var zhb = $("#zh2").text();
    if (zhb == "��ѡ��ת���˻�") {
        $("#zztext2").show();
        return false;
    } else {
        $("#zztext2").hide()
    }

    var tga = $("#tran-amt");
    var amt = $.trim(tga.val());
    if (amt.length == 0) {
        $("#zztext3 li").html("<i></i>������ת�˽��")
        $("#zztext3").show();
        return false;
    } else {
        $("#zztext3").hide()
    }
    if (isNaN(amt)) {
        $("#zztext3 li").html("<i></i>����Ľ��Ϸ�")
        $("#zztext3").show();
        return false;
    } else {
        $("#zztext3").hide()
    }
    amt = parseFloat(amt);
    if (amt < 1) {
        $("#zztext3 li").html("<i></i>ת�˽���С��1Ԫ")
        $("#zztext3").show();
        return false;
    } else {
        $("#zztext3").hide()
    }

    var game = zhb;
    var tip = game;
    if (game == "�����˻�") {
        alert("����ת��");
        return false;
    }
    if (game == "EAƽ̨") {
        game = "EA";
    }
    else if (game == "BBINƽ̨") {
        game = "BBIN";
    }
    else if (game == "KGƽ̨") {
        game = "KG";
    }
    else if (game == "LBƽ̨") {
        game = "LB";
    }
    else if (game == "����ƽ̨") {
        game = "Sports";
    }
    else if (game == "AMPƽ̨") {
        game = "AMP";
    }
    else if (game == "AGƽ̨") {
        game = "AG";
    }
    else if (game == "����ƽ̨") {
        game = "CH";
    }
    else if (game == "EBETƽ̨") {
        game = "EBET";
    }
    else if (game == "PTƽ̨") {
        game = "PT";
    }
    if (confirm("��ȷ��ת��" + tip + "��")) {
        IsSubmit = true;
        $("#loading").show();

        $.ajax({
            type: "post",
            url: "Ajaxweb/AjaxMemberFunds_Cs.aspx",
            data: { act: "tran", c: "IN", game: game, amt: amt, t: Math.random() },
            timeout: 30000,
            success: function (data) {
                alert(data);
                if (data.indexOf('�ɹ�') != -1) {
                    tga.val("");
                }

                IsSubmit = false;
            },
            complete: function (XMLHttpRequest, status) {
                $("#loading").hide();
                if (status == 'timeout') {
                    alert("��ʱ");
                }
            }
        });



    }
    return false;
}

function TranToPlat() {
    if (IsSubmit) {
        alert("����ת����");
        return false;
    }
    var zha = $("#zh1").text();
    if (zha == "��ѡ��ת���˻�") {
        $("#zztext1").show();
        return false;
    } else {
        $("#zztext1").hide()
    }
    var zhb = $("#zh2").text();
    if (zhb == "��ѡ��ת���˻�") {
        $("#zztext2").show();
        return false;
    } else {
        $("#zztext2").hide()
    }

    var tga = $("#tran-amt");
    var amt = $.trim(tga.val());
    if (amt.length == 0) {
        $("#zztext3 li").html("<i></i>������ת�˽��")
        $("#zztext3").show();
        return false;
    } else {
        $("#zztext3").hide()
    }
    if (isNaN(amt)) {
        $("#zztext3 li").html("<i></i>����Ľ��Ϸ�")
        $("#zztext3").show();
        return false;
    } else {
        $("#zztext3").hide()
    }
    amt = parseFloat(amt);
    if (amt < 1) {
        $("#zztext3 li").html("<i></i>ת�˽���С��1Ԫ")
        $("#zztext3").show();
        return false;
    } else {
        $("#zztext3").hide()
    }

    var game = zha;
    var tip = game;
    if (game == "�����˻�") {
        alert("����ת��");
        return false;
    }
    if (game == "EAƽ̨") {
        game = "EA";
    }
    else if (game == "BBINƽ̨") {
        game = "BBIN";
    }
    else if (game == "KGƽ̨") {
        game = "KG";
    }
    else if (game == "LBƽ̨") {
        game = "LB";
    }
    else if (game == "����ƽ̨") {
        game = "Sports";
    }
    else if (game == "AMPƽ̨") {
        game = "AMP";
    }
    else if (game == "AGƽ̨") {
        game = "AG";
    }
    else if (game == "����ƽ̨") {
        game = "CH";
    }
    else if (game == "EBETƽ̨") {
        game = "EBET";
    }
    else if (game == "PTƽ̨") {
        game = "PT";
    }
    if (confirm("��ȷ��ת��" + tip + "��")) {
        IsSubmit = true;
        $("#loading").show();
        $.post("Ajaxweb/AjaxMemberFunds_Cs.aspx", { act: "tran", c: "OUT", game: game, amt: amt, t: Math.random() },
      function (data) {
          $("#loading").hide();
          alert(data);
          if (data.indexOf('�ɹ�') != -1) {
              tga.val("");
          }

          IsSubmit = false;
      });
    }
    return false;
}