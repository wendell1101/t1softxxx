<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Post message</title>
</head>
<body>
	Post Message Go to lobby ......
	<script type="text/javascript">
		window.top.postMessage(
				{
					url:"pm-exit",
					type:"gotoLobby",
				},
				"*");

		const queryString = window.location.search;
		// console.log("queryString", queryString);
		const urlParams = new URLSearchParams(queryString);
		const return_previous_url = urlParams.get('return_previous_url');
		// console.log("return_previous_url", return_previous_url);
		if(return_previous_url === "true"){
			window.history.back();
		}
	</script>
</body>
</html>