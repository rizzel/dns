<?php global $page; ?>
<html>
	<head>
		<title><?php echo $page->header['title']; ?></title>
		<meta name="robots" content="noindex,nofollow" />
		<?php foreach ($page->header['metadata'] as $meta => $data) { ?>
		<meta name="<?php echo $meta; ?>" content="<?php echo $data; ?>" />
		<?php } ?>

		<?php foreach ($page->header['styles'] as $style) { ?>
		<link rel="stylesheet" type="text/css" href="<?php echo $style; ?>" />
        <?php } ?>

		<?php foreach ($page->header['scripts'] as $script) { ?>
		<script type="text/javascript" src="<?php echo $script; ?>"></script>
        <?php } ?>
	</head>
	<body>
		<div id="loadProgresses"></div>

		<div id="user"><?php include 'login.php'; ?></div>
