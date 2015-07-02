<?php
	$d = opendir($_SERVER['DOCUMENT_ROOT']);
	$certs = array();
	$hasCrt = $hasDer = FALSE;
	while ($f = readdir($d)) {
		$path = $_SERVER['DOCUMENT_ROOT'] . "/$f";
		if (!$hasDer && is_file($path) && preg_match('/\.der$/i', $f)) {
			$hasDer = TRUE;
			$certs[] = sprintf('<a href="/%s">DER</a>', $f);
		} elseif (!$hasCrt && is_file($path) && preg_match('/\.crt$/i', $f)) {
			$hasCrt = TRUE;
			$certs[] = sprintf('<a href="/%s">CRT</a>', $f);
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
		$(function() {
			if (typeof(initPage) == 'function')
				initPage();
		});
	</script>
</html>
