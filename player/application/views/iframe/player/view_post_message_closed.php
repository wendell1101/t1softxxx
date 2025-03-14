<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Post message closed</title>
</head>
<body>
	Post message closed ......
	<script>
		window.top.postMessage(
				{
					error: "<?php echo $error_message; ?>",
					action: {
						type: "close"
					},
				},
				"*");
	</script>
</body>
</html>