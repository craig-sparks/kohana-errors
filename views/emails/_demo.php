<html>
	<head>
		<title><?php echo $type.' ['.$code.']' ?></title>
	</head>
	<body>
		<ul>
			<?php foreach ($error as $key => $value): ?>
				<li><strong><?php echo $key ?></strong>: <?php echo $value ?></li>
			<?php endforeach; ?>
		</ul>
	</body>
</html>
