<script>
        $(document).ready(function () {   

        $(".xlmenu").click(function () {

        var xlmenu = ".mmenu";

        var xlmovie = "mmenu_movie";

        $(xlmenu).addClass(xlmovie);

        $(".mm_exit").click(function () {

            $(xlmenu).removeClass(xlmovie);

        });



        $(".mm_main li").click(function () {

            $(".mm_main li i").removeClass("border");

            $(this).find("i").addClass("border");

            $(xlmenu).removeClass(xlmovie);

            var title = $(this).text();

            $("#bankicon").text(title);

        });



    }); 
 
    $(".login_btn").click(function () {

               var amt = $.trim($("#ol_b_amt").val());
               if (amt.length == 0) {
                   $("#zxtext1").html("<i></i>������ת�˽��");
                   $("#zxtext1").show();
                   return false;
               }
               else {
                   $("#zxtext1").hide();
               }

               if (isNaN(amt)) {
                   $("#zxtext1").html("<i></i>������Ч����");
                   $("#zxtext1").show();
                   return false;
               }
               else {
                   $("#zfbtext1").hide();
               }

               var amount = parseFloat(amt, 10);

               if (amount < 100 || amount > 3000) {
                   $("#zxtext1").html("<i></i>ת�˽���С�� 100Ԫ ����� 3000Ԫ");
                   $("#zxtext1").show();
                   return false;
               }
               else {
                   $("#zxtext1").hide();
               }

                var bic = $("#bankicon").text();
                if (bic.indexOf("��ѡ������") != -1) {
                    $("#zxtext2").show();
                    return false;
                }
                else {
                    $("#zxtext2").hide();
                }
        
               var pid = $("#pid").val(); 
               if (confirm("��ȷ���ύ��")) {
                   return true;
               }

               return false;
            });
     
});

function payol(url, minamt, maxamt, pr, username, pk) {
    var $payam = $("#ol_b_amt");
    var payamtval = $.trim($payam.val());

    if (payamtval.length == 0) {
        $("#zxtext1 li").html("<i></i>����������");
        $("#zxtext1").show();
        return false;
    }
    else {
        $("#zxtext1").hide();
    }

    payamtval = parseFloat(payamtval);
    if (payamtval < minamt) {
        $("#zxtext1 li").html("<i></i>���ʳ�ֵ����С��" + minamt + "Ԫ");
        $("#zxtext1").show();
        return false;
    }
    else {
        $("#zxtext1").hide();
    }

    if (payamtval > maxamt) {
        $("#zxtext1 li").html("<i></i>���ʳ�ֵ���ܴ���" + maxamt + "Ԫ");
        $("#zxtext1").show();
        return false;
    } else {
        $("#zxtext1").hide();
    }

    $("#linkpay").attr("target", "_blank");
    $("#linkpay").attr("href", url + "cashier.php?amt=" + payamtval + "&pb=" + pbval + "&pr=" + pr + "&u=" + username + "&gr=1");
    $payam.val("");
    return true;
}
    </script>