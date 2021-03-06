<?php

global $page;

$d = opendir(__DIR__ . '/..');
$certs = array();
$hasCrt = $hasDer = FALSE;

while ($f = readdir($d)) {
    $path = __DIR__ . "/../$f";
    if (!$hasDer && is_file($path) && preg_match('/\.der$/i', $f)) {
        $hasDer = TRUE;
        $certs[] = sprintf('<a href="http://%s/%s">DER</a>', $_SERVER['HTTP_HOST'], $f);
    } elseif (!$hasCrt && is_file($path) && preg_match('/\.crt$/i', $f)) {
        $hasCrt = TRUE;
        $certs[] = sprintf('<a href="http://%s/%s">CRT</a>', $_SERVER['HTTP_HOST'], $f);
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
    $(function () {
        <?php
        $path = "{$page->currentUser->textDomainFolder}/js.json";
        if (!file_exists($f))
            $path =  "/locale/en_US/LC_MESSAGES/js.json";
        ?>
        $.getJSON("<?php echo $path; ?>", function (translation) {
            window.i18n = new Jed(translation);
            i18n.textdomain('js');
            if (typeof(initPage) == 'function')
                initPage();
        });
    });
</script>
</html>
