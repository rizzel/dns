<?php

global $page;

$d = opendir(__DIR__ . '/..');
$certs = array();
$hasCrt = $hasDer = false;

while ($f = readdir($d)) {
    $path = __DIR__ . "/../$f";
    if (!$hasDer && is_file($path) && preg_match('/\.der$/i', $f)) {
        $hasDer = true;
        $certs[] = sprintf('<a href="http://%s/%s">DER</a>', htmlspecialchars($_SERVER['SERVER_NAME']), htmlspecialchars($f));
    } elseif (!$hasCrt && is_file($path) && preg_match('/\.crt$/i', $f)) {
        $hasCrt = true;
        $certs[] = sprintf('<a href="http://%s/%s">CRT</a>', htmlspecialchars($_SERVER['SERVER_NAME']), htmlspecialchars($f));
    }
}

?>
<div id="footer">
    <div>
        <?php echo _("Certificates for this Domain"); ?>: <?php echo implode(", ", $certs); ?> <?php echo "LOCALE: " . User::getCurrentLocale() ?>
    </div>
</div>
</body>
<script type="text/javascript">
    <?php
    $path = "{$page->currentUser->textDomainFolder}/js.json";
    if (!file_exists($path))
        $path = "/locale/en_US/LC_MESSAGES/js.json";
    ?>
    fetch("<?php echo $path; ?>")
        .then(function (r) { return r.json(); })
        .then(function (translation) {
            window.i18n = new Jed(translation);
            i18n.textdomain('js');
            if (typeof(initPage) == 'function')
                initPage();
        });
</script>
</html>
