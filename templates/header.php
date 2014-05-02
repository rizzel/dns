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

<?php
if (!isset($_SERVER['HTTPS']))
{
?>
		<div id="unverschluesselt">Diese Seite ist derzeit unverschlüsselt -> <a href="https://<?php echo $_SERVER['HTTP_HOST']; ?><?php echo $_SERVER['REQUEST_URI']; ?>">SSL</a></div>
<?php
}
?>

		<div id="user"><?php include 'login.php'; ?></div>
