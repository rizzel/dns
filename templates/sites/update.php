<?php

global $page;

$update = false;
if (array_key_exists('u', $_GET) && array_key_exists('t', $_GET))
	$update = $page->email->verifyUpdate($_GET['u'], $_GET['t']);

?>
<div>
	<h3>Update Verifikation</h3>

<?php
if ($update)
{ ?>
		<p>
			Update erfolgreich.
		</p>
<?php
}
else
{
?>
		<p>
			Update nicht erfolgreich.
		</p>
<?php
}
?>
	<a href="/index.php">Zurück zur Hauptseite</a>
</div>
