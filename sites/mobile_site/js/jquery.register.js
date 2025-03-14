$(document).ready(function () {
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
    var tap = "click"; //"touchstart";//	

    //��ҳЧ��
    $("#register").click(function () {
        var retext1 = $("#reg1").val();
        var retext2 = $("#reg2").val();
        var retext3 = $("#reg3").val();
        var retext4 = $("#reg4").val();
        var retext5 = $("#reg5").val();

        var filter = /^\s*[.A-Za-z0-9_-]{4,15}\s*$/;
        if (retext1.length == 0 || !filter.test(retext1)) {
            $("#retext1").html("<i></i>�ʻ�����4-10���ַ����");
            $("#retext1").show()
            return false;
        }
        else {
            $("#retext1").hide()
        }

        if (retext2.length == 0 || !verifyEmail(retext2)) {
            $("#retext2").html("<i></i>�����ʽ����ȷ������������");
            $("#retext2").show()
            return false;
        } else {
            $("#retext2").hide()
        }


        if (retext3.length < 6 || retext3.length > 20) {
            $("#retext3").html("<i></i>����Ϊ 6-20����ĸ�����ֻ������ɣ����ִ�Сд");
            $("#retext3").show()
            return false;
        } else {
            $("#retext3").hide()
        }

        if (retext4 != retext3) {
            $("#retext4").show()
            return false;
        } else {
            $("#retext4").hide()
        }

        if (retext5.length != 4) {
            $("#retext5").html("<i></i>��������λ��֤��");
            $("#retext5").show()
            return false;
        } else {
            $("#retext5").hide()

        }
        $("#loading").show();

        $.ajax({
            type: "post",
            url: "Ajaxweb/AjaxRegister.aspx",
            data: { act: "create", run: retext1, rpn: retext1, rpwd: retext3, rem: retext2, rcd: retext5, m: Math.random() },
            timeout: 30000,
            success: function (data) {
                if (data == "success") {
                    $.ajax({
                        type: "get",
                        url: "account.aspx",
                        beforeSend: function () {
                            $("#loading").show();
                        },
                        success: function (data) {
                            $("#loading").hide();
                            $("#ht").text("�ʽ����");
                            pagetwo.find(".page").html(data);
                        },
                    });
                }
                else {
                    alert(data);
                }
            },
            complete: function (XMLHttpRequest, status) {
                $("#loading").hide();
                if (status == 'timeout') {
                    alert("��ʱ");
                }
            }
        });

      
    });

    $(".threepage").click(function () {

        var url_two = $(this).attr("url");
        var hdtext = $(this).find("a").text();

        setTimeout("$('#pagetwo .page').addClass('hide')", 300);

        pagetwo.addClass(leftmove);
        pagethree.addClass("move");

        $("#ht1").text(hdtext);

        if (url_two) {

            $.ajax({
                type: "get",
                url: url_two,
                beforeSend: function () {
                    $("#loading").show();
                },
                success: function (data) {
                    $("#loading").hide();
                    pagethree.find(".page").html(data);
                },
            });
        };
        $(".bb_three").click(function () {

            setTimeout("$('#pagethree .page').html('')", 300);
            pagetwo.find(".page").removeClass('hide');
            pagetwo.removeClass(leftmove);
            pagethree.removeClass("move");

        });
    });

    $("img[id$='vCode']").css({ "cursor": "pointer" }).click(function () { $(this).attr('src', 'GetCode.aspx?t=' + Math.random()); });
});
