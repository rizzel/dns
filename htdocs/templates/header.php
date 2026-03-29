<?php global $page; ?>
<html lang="en">
<head>
    <title><?php echo $page->header['title']; ?></title>
    <meta name="robots" content="noindex,nofollow"/>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>"/>
    <?php FOREACH ($page->header['metadata'] as $meta => $data): ?>
        <meta name="<?php echo $meta; ?>" content="<?php echo $data; ?>"/>
    <?php ENDFOREACH ?>

    <?php FOREACH ($page->header['styles'] as $style): ?>
        <link rel="stylesheet" type="text/css" href="<?php echo $style; ?>"/>
    <?php ENDFOREACH ?>

    <?php FOREACH ($page->header['scripts'] as $script): ?>
        <script type="text/javascript" src="<?php echo $script; ?>"></script>
    <?php ENDFOREACH ?>
</head>
<body>
<div id="loadProgresses"></div>

<?php IF (!isset($_SERVER['HTTPS'])): ?>
    <div id="unverschluesselt"><?php echo _("This page is currently not encrypted!"); ?> -> <a
            href="https://<?php echo htmlspecialchars($_SERVER['SERVER_NAME']); ?><?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">SSL</a></div>
<?php ENDIF ?>

<div id="user"><?php include 'login.php'; ?></div>
