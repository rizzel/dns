<?php

global $page;

$loggedIn = $page->user->isLoggedIn();

?>
<div id="records">
	<h4>Record Verwaltung.</h4>
<?php if ($loggedIn) { ?>
	<input type="button" id="addRecord_button" value="Record hinzufügen" />
    <div id="addRecord" class="hider">
		<div>
			<label for="addRecordType">Typ:</label>
			<select id="addRecordType">
				<option value="A">A</option>
				<option value="AAAA">AAAA</option>
				<option value="CNAME">CNAME</option>
			</select>
		</div>
		<div>
			<label for="addRecordDomain">Domain:</label>
			<select id="addRecordDomain"></select>
		</div>
		<div>
			<label for="addRecordName">Name (URI):</label>
			<input type="text" id="addRecordName" />
		</div>
		<div>
			<label for="addRecordContent">IPv4:</label>
			<input type="text" id="addRecordContent" />
		</div>
		<div id="addRecord_d_password">
			<label for="addRecordPassword">Update-Password:</label>
			<input type="text" id="addRecordPassword" size="64" />
		</div>
		<div>
			<label>TTL (s):</label>
			<input type="number" id="addRecordTTL" min="5" />
		</div>
        <input type="button" id="addRecordSubmit" value="Hinzufügen" />
    </div>
    <div>Meine Records:</div>
    <table id="recordList" border="1">
        <tr>
			<td>ID</td>
			<th>Domain</th>
			<th>Name</th>
			<th>Typ</th>
			<th>Content</th>
            <th>TTL</th>
            <th>Passwort</th>
			<th>Geändert</th>
			<th>Operation</th>
        </tr>
    </table>
	<input type="button" id="recordListReload" value="Neu laden" />
	<div id="recordListNamePopup" class="popup" style="display: none">
		<label for="recordListName">Name:</label>
		<input type="text" id="recordListName" />
		<br />
		<input type="button" id="recordListNameSubmit" value="OK" />
		<input type="button" class="popupAbort" value="Abbruch" />
	</div>
	<div id="recordListContentPopup" class="popup" style="display: none">
		<label for="recordListContent">Content:</label>
		<input type="text" id="recordListContent" />
		<br />
		<input type="button" id="recordListContentSubmit" value="OK" />
		<input type="button" class="popupAbort" value="Abbruch" />
	</div>
	<div id="recordListPasswordPopup" class="popup" style="display: none">
		<label for="recordListPassword">Passwort:</label>
		<input type="text" id="recordListPassword" size="64" />
		<br />
		<input type="button" id="recordListPasswordSubmit" value="OK" />
		<input type="button" class="popupAbort" value="Abbruch" />
	</div>
	<div id="recordListTTLPopup" class="popup" style="display: none">
		<label for="recordListTTL">TTL (s):</label>
		<input type="number" id="recordListTTL" min="5" />
		<br />
		<input type="button" id="recordListTTLSubmit" value="OK" />
		<input type="button" class="popupAbort" value="Abbruch" />
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
