<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Error: <?php echo $message; ?></title>
	<style type="text/css">
		* { margin: 0; padding: 0; }
		body { background-color: #EEE; font-family: sans-serif; font-size: 16px; line-height: 20px; margin: 40px; }
		#wrapper { padding: 30px; background: #fff; color: #333; margin: 0 auto; width: 800px; }
		h1 { color: #000; font-size: 40px; padding: 0 0 25px; line-height: 1em; }
		.intro { font-size: 22px; line-height: 30px; font-family: georgia, serif; color: #555; padding: 29px 0 0; border-top: 1px solid #CCC; }
		p { margin: 0; line-height: 22px;}
	</style>
</head>
<body>
	<div id="wrapper">
		<h1><?php echo $status; ?></h1>
		<p class="intro"><?php echo $message; ?></p>
	</div>
</body>
</html>