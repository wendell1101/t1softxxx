<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0"/>
</head>
<body onload="redirect()">
</body>
<script>
function redirect(){
    
    var url = '<?= $redirectNotVerifiedContactUrl ?>';
    console.log(url);
    var isInIframe = (window.location != window.parent.location) ? true : false; 
    if(url){
        if(isInIframe == true){ //redirect if iframe
            window.top.location.href = url;
        }else{
            window.location.href = url;
        }
    }
}
</script>
</html>
