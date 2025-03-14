<script type="text/javascript">
  BootstrapDialog.show({
    title: <?= json_encode($title); ?>,
    message: <?= json_encode($message); ?>,
    closable: false,
    buttons: [{
      icon: 'glyphicon glyphicon-send',
      label: 'Proceed',
      cssClass: 'btn-primary',
      autospin: true,
      action: function(dialogRef){
        dialogRef.enableButtons(false);
        dialogRef.setClosable(false);
        var params = {
            platforms : <?= json_encode($platforms); ?>,
        };
        $.ajax({
            url : '/game_api/set_game_api_for_maintenance' +  '?' + $.param(params),
            type : 'GET',
            dataType : "json"
        }).done(function (obj) {
          dialogRef.close();
          BootstrapDialog.show({
            size: BootstrapDialog.SIZE_SMALL,
            message: obj.message
          });
          setTimeout(function(){
            window.location = '/game_api/viewGameApi';
          }, 2000);
        }).fail(function (jqXHR, textStatus) {
            if(jqXHR.status<300 || jqXHR.status>500){
                alert(textStatus);
            }
        });
      }
    }, {
      label: 'Close',
      action: function(dialogRef){
        dialogRef.close();
        window.location = '/game_api/viewGameApi';  
      }
    }]
  });
</script>