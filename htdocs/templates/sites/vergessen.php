<div id="vergessen">
	<h3>Zurücksetzen des Passwortes</h3>
	<p>
		Zum zurücksetzen des Passwortes werden folgende Angaben benötigt:
	</p>
	<div>
		<label for="vergessen_name">Benutzer-Name:</label>
		<input type="text" id="vergessen_name" />
		<br />
		<label for="vergessen_email">Email:</label>
		<input type="text" id="vergessen_email" />
		<br />
		<input type="button" id="vergessen_submit" value="Zurücksetzen" />
	</div>
</div>
<div id="vergessen2" style="display: none">
	<h3>Zurücksetzen des Passwortes - Schritt 2</h3>
	<p>
		Sie haben eine Email erhalten welche ein Token enthält um Ihr Passwort
		neu zu setzen.
		<br />
		Bitte schliessen Sie diese Seite nicht bis das Zurücksetzen abgeschlossen
		wurde.
		<br />
		Geben Sie in folgendem Formular bitte das Token sowie Ihr neues Passwort ein:
	</p>
	<div>
		<label for="vergessen2_token">Token:</label>
		<input type="text" id="vergessen2_token" size="64" />
		<br />
		<label for="vergessen2_password1">Password:</label>
		<input type="password" id="vergessen2_password1" size="32" />
		<input type="password" id="vergessen2_password2" size="32" />
		<span id="user_hinzu_nomatch" style="display: none">Verschieden</span>
		<br />
		<input type="button" id="vergessen2_submit" value="Password setzen" />
	</div>
</div>
