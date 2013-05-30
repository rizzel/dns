<div id="admin">
	<h3>Administration</h3>
	<h4>Benutzer</h4>
	<input type="button" id="user_hinzu_button" value="Benutzer hinzufügen" />
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
		<label for="user_hinzu_email">Email:</label>
		<input type="text" id="user_hinzu_email" />
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
			<th>Benutzer</th>
			<th>Level</th>
			<th>Email</th>
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

	<h4>Domains</h4>
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
		<input type="button" id="domain_hinzu_submit" value="Hinzufügen" />
	</div>
	<table id="domains" border="1">
		<tr>
			<th>ID</th>
			<th>Name</th>
			<th>Typ</th>
			<th>Last Check</th>
			<th>Spezielle Records</th>
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
	<div id="domainsListRecordPopup" class="popup" style="display: none">
		<label for="domainsListRecordName">Name:</label>
		<input type="text" id="domainsListRecordName" />
		<br />
		<label for="domainsListRecordType">Typ:</label>
		<select id="domainsListRecordType">
			<option>A</option>
			<option>AAAA</option>
			<option value="CERT">CERT (pdns2.9.21)</option>
			<option>CNAME</option>
			<option value="DNSKEY">DNSKEY (pdns2.9.21)</option>
			<option value="DS">DS (pdns 2.9.21)</option>
			<option>HINFO</option>
			<option value="KEY">KEY (pdns 2.9.21)</option>
			<option>LOC</option>
			<option>MX</option>
			<option>NAPTR</option>
			<option>NS</option>
			<option value="NSEC">NSEC (pdns 2.9.21)</option>
			<option>PTR</option>
			<option>RP</option>
			<option value="RRSIG">RRSIG (pdns 2.9.21)</option>
			<option>SOA</option>
			<option>SPF</option>
			<option>SSHFP</option>
			<option>SRV</option>
			<option>TXT</option>
		</select>
		<span>Siehe <a href="http://doc.powerdns.com/html/types.html" target="_blank">hier</a></span>
		<br />
		<label for="domainsListRecordContent">Content:</label>
		<input type="text" id="domainsListRecordContent" size="32" />
		<br />
		<label for="domainsListRecordTTL">TTL:</label>
		<input type="number" min="5" id="domainsListRecordTTL" />
		<br />
		<input type="button" id="domainsListRecordSubmit" value="OK" />
		<input type="button" class="popupAbort" value="Abbruch" />
	</div>
</div>
