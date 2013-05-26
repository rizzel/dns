<div id="admin">
	<h4>Administration</h4>
	<h5>Users</h5>
	<input type="button" id="user_hinzu_button" value="User hinzufügen" />
	<div id="user_hinzu" class="hider">
		<label for="user_hinzu_username">Name:</label>
		<input type="text" id="user_hinzu_username" />
		<br />
		<label for="user_hinzu_password1">Password:</label>
		<input type="password" id="user_hinzu_password1" />
		<input type="password" id="user_hinzu_password2" />
		<span id="user_hinzu_default">
			Default: "<span id="user_hinzu_password_default"></span>"
		</span>
		<span id="user_hinzu_nomatch" style="display: none">Verschieden</span>
		<br />
		<label for="user_hinzu_level">Level:</label>
		<select id="user_hinzu_level">
			<option value="nobody">nobody</option>
			<option value="user">user</option>
			<option value="admin">admin</option>
		</select>
		<br />
		<input type="button" id="user_hinzu_submit" value="Hinzufügen" />
	</div>
	<table id="users" border="1">
		<tr>
			<th>Username</th>
			<th>Level</th>
			<th>Records</th>
			<th>Operation</th>
		</tr>
	</table>
	<input type="button" id="userListReload" value="Neu laden" />
	<div id="userListLevelPopup" class="popup" style="display: none">
		<label for="userListLevel">Level:</label>
		<select id="userListLevel">
			<option>nobody</option>
			<option>user</option>
			<option>admin</option>
		</select>
		<br />
		<input type="button" id="userListLevelSubmit" value="OK" />
		<input type="button" class="popupAbort" value="Abbruch" />
	</div>

	<h5>Domains</h5>
	<input type="button" id="domain_hinzu_button" value="Domain hinzufügen" />
	<div id="domain_hinzu" class="hider">
		<label for="domain_hinzu_name">Name:</label>
		<input type="text" id="domain_hinzu_name" />
		<br />
		<label for="domain_hinzu_type">Typ:</label>
		<select id="domain_hinzu_type">
			<option>NATIVE</option>
		</select>
		<br />
		<label for="domain_hinzu_soa">Soa Zeile bestehend aus '$MasterDNS $Email':</label>
		<input type="text" id="domain_hinzu_soa" size="64" />
		<br />
		<label for="domain_hinzu_mx">MX Domain:</label>
		<input type="text" id="domain_hinzu_mx" size="32" />
		<br />
		<input type="button" id="domain_hinzu_submit" value="Hinzufügen" />
	</div>
	<table id="domains" border="1">
		<tr>
			<th>ID</th>
			<th>Name</th>
			<th>Type</th>
			<th>Last Check</th>
			<th>SOA</th>
			<th>MX</th>
			<th>Operation</th>
		</tr>
	</table>
	<input type="button" id="domainListReload" value="Neu Laden" />
	<div id="domainsListNamePopup" class="popup" style="display: none">
		<label for="domainsListName">Name:</label>
		<input type="text" id="domainsListName" />
		<br />
		<input type="button" id="domainsListNameSubmit" value="OK" />
		<input type="button" class="popupAbort" value="Abbruch" />
	</div>
	<div id="domainsListSOAPopup" class="popup" style="display: none">
		<label for="domainsListSOA">SOA-Eintrag:</label>
		<input type="text" id="domainsListSOA" />
		<br />
		<input type="button" id="domainsListSOASubmit" value="OK" />
		<input type="button" class="popupAbort" value="Abbruch" />
	</div>
	<div id="domainsListMXPopup" class="popup" style="display: none">
		<label for="domainsListMX">Name:</label>
		<input type="text" id="domainsListMX" />
		<br />
		<input type="button" id="domainsListMXSubmit" value="OK" />
		<input type="button" class="popupAbort" value="Abbruch" />
	</div>
</div>
