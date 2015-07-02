<?php

global $page;

$loggedIn = $page->user->isLoggedIn();

?>
<div id="records">
	<h3>Benutzer Verwaltung.</h3>
<?php if ($loggedIn) { ?>
	<div>
		<label for="password1">Neues Passwort:</label>
		<input type="password" id="password1" />
		<input type="password" id="password2" />
		<input type="button" id="password_submit" value="Aktualisieren" />
		<span id="user_hinzu_nomatch" style="display: none">Verschieden</span>
	</div>
	<div>
		<label for="email">Neue Email:</label>
		<input type="text" id="email" value="<?php echo $page->user->getCurrentUser()->email ?>" size="64" />
		<input type="button" id="email_submit" value="Aktualisieren" />
	</div>
	<div>
		<label for="token">Zu verifizierendes Token:</label>
		<input type="text" id="token" size="64" />
		<input type="button" id="token_submit" value="Verifizieren" />
	</div>
	<h4>
		Tokens
	</h4>
	<p>
		Die Änderung des Passwortes bzw. der Email-Adresse erfordert die Bestätigung dieser
		Änderung mittels eines Tokens, welches an die existierende Email-Adresse gesandt
		wird.
		<br/>
		In der Email kann entweder auf den Link geklickt werden oder das enthaltene Token
		direkt in das Feld <label for="token">"Zu verifizierendes Token"</label>
		auf dieser Seite eingefügt werden.
		<br />
		Das Token ist mindestens einen Tag gültig.
	</p>
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
