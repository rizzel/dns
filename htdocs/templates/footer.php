<?php

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
        Zertifikate für diese Domain: <?php echo implode(", ", $certs); ?>
    </div>
</div>
</body>
<script type="text/javascript">
    $(function () {
        if (typeof(initPage) == 'function')
            initPage();
    });
</script>
</html>
