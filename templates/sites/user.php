<?php

global $page;

$loggedIn = $page->user->isLoggedIn();

?>
<div id="records">
	<h3>User Verwaltung.</h3>
<?php if ($loggedIn) { ?>
	<div>
		<div>
			<label for="password1">Neues Password:</label>
			<input type="password" id="password1" />
			<input type="password" id="password2" />
			<span id="user_hinzu_nomatch" style="display: none">Verschieden</span>
		</div>
		<div>
			<input type="button" id="password_submit" value="Aktualisieren" />
		</div>
	</div>
<?php
}
else
{
?>
	<p>
		Bitte Einloggen.
	</p>
<?php
}
?>
</div>
