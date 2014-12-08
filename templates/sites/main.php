<?php

global $page;

$loggedIn = $page->user->isLoggedIn();

?>
<div id="records">
	<h3>Record Verwaltung.</h3>
<?php if ($loggedIn) { ?>
	<input type="button" id="addRecord_button" value="Record hinzufügen" />
    <div id="addRecord" class="hider">
		<div>
			<label for="addRecordType">Typ:</label>
			<select id="addRecordType">
				<option value="A">A (IPv4)</option>
				<option value="AAAA">AAAA (IPv6)</option>
				<option value="CNAME">CNAME (Alias)</option>
			</select>
		</div>
		<div>
			<label for="addRecordDomain">Domain:</label>
			<select id="addRecordDomain"></select>
		</div>
		<div>
			<label for="addRecordName">Name (URI):</label>
			<input type="text" id="addRecordName" />
			<span id="addRecordTest"></span>
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

	<h4>Aktualisierung eines Content-Feldes</h4>
	<p>
		Ein Content-Feld kann über eine Reihe von URLs aktualisiert werden.
		Das Content-Feld ist dabei immer optional - wird es weggelassen, wird die IP
		mit welcher die Seite aufgerufen wird als Content-Feld gesetzt.
		<br />
		Allerdings ist der Host ggdns.de derzeit nicht über IPv6 erreichbar!
		<br />
		Das Passwort zu jedem Record wird im Klartext gespeichert und kann
		via Klick auf "Klick" in der Passwort-Spalte angezeigt werden.
	</p>
	<p>
		Möglichkeiten:
		<blockquote>http://ggdns.de/ip?${RECORDID};${PASSWORT};${CONTENT_FELD}</blockquote>
		<blockquote>http://ggdns.de/ip4?${RECORDNAME};${PASSWORT};${CONTENT_FELD}</blockquote>
		<blockquote>http://ggdns.de/ip6?${RECORDNAME};${PASSWORT};${CONTENT_FELD}</blockquote>
		<blockquote>http://ggdns.de/inadyn4?${PASSWORT};${RECORDNAME}</blockquote>
		<blockquote>http://ggdns.de/inadyn6?${PASSWORT};${RECORDNAME}</blockquote>
	</p>
	<p>
		Werden andere URL-Schemata benötigt, so können diese noch hinzugefügt werden.
		<br />
		Eine Beispiel Crontab-Zeile für ein Update alle 5 Minuten sieht so aus:
		<blockquote>*/5 * * * * root wget -O - 'http://ggdns.de/ip4?bsp.ggdns.de;updatepasswort' >/dev/null 2 >&1</blockquote>
	</p>
	<p>
		Eine Beispiel INADYN-Konfiguration sieht so aus:
		<blockquote>
			dyndns_system custom@http_svr_basic_auth<br />
			ip_server_name ggdns.de /myip<br />
			dyndns_server_name ggdns.de<br />
			dyndns_server_url /inadyn4?${PASSWORT};<br />
			alias ${RECORDNAME}
		</blockquote>
		Um inadyn unter Windows nutzen zu können wird noch folgendes benötigt:
		<ul>
			<li><a href="/download/srvany.rar">SRVANY</a> um inadyn als Service laufen zu lassen</li>
			<li><a href="/download/configure_inadyn_service_ggdns.reg">Registry-Eintrag</a> zur Konfiguration des Dienstes. In dieser Datein müssen die Pfade noch angepasst werden.</li>
		</ul>
	</p>
	<p>
		Im DD-WRT Frontend muss man Folgendes unter Setup->DDNS eintragen um
		obige inadyn-Konfiguration zu erreichen:
		<table border="1">
			<tr><th>Feld</th><th>Inhalt</th></tr>
			<tr><td>DDNS Service</td><td>Custom</td></tr>
			<tr><td>DYNDNS Server</td><td>ggdns.de</td></tr>
			<tr><td>User Name</td><td>${EGAL}</td></tr>
			<tr><td>Password</td><td>${EGAL}</td></tr>
			<tr><td>Host Name</td><td>${RECORDNAME}</td></tr>
			<tr><td>URL</td><td>/inadyn4?${PASSWORT};</td></tr>
		</table>
	</p>
	<p>
		Für openwrt kann folgende Konfiguration mit dem Paket <em>luci-app-ddns</em> verwendet werden:
		<table border="1">
			<tr><th>Feld</th><th>Inhalt</th></tr>
			<tr><td>Service</td><td>-- custom --</td></tr>
			<tr><td>Custom update-URL</td><td>http://ggdns.de/ip4?[DOMAIN];[PASSWORD]</td></tr>
			<tr><td>Hostname</td><td>${RECORDNAME}</td></tr>
			<tr><td>Password</td><td>${PASSWORT}</td></tr>
		</table>
		Für <em>Source of IP address</em> kann die URL <a href="http://ggdns.de/myip" target="_blank">http://ggdns.de/myip</a> verwendet werden.
	</p>
	<p>
		Eine Liste von Update-Clients ist
		<a href="http://freedns.afraid.org/scripts/freedns.clients.php" target="_blank">hier</a>
		zu finden. Es funktionieren jedoch nicht alle mit ggdns.
	</p>

	<h4>Client IP</h4>
	<p>
		Die IP des Clients aus Sicht des Webservers ggdns.de kann via
		<blockquote>http://ggdns.de/myip</blockquote>
		abgefragt werden<!-- (nur IPv4)-->.
<!--		Wird IPv6 benoetigt, muss derzeit diese URL genommen werden:
		<blockquote>http://rizzle.ggdns.de/myip.php</blockquote>-->
	</p>

	<h4>Erzeugung von Records</h4>
	<p>
		Es gibt 3 verschiedene Records die gesetzt werden können.
		Jedes dieser Records muss einer Domain zugeordnet werden.
		Derzeit gibt es nur ggdns.de, wenn andere Domains gewünscht (===gesponsert) werden
		- einfach melden.
	</p>
	<p>
		Regeln für Domain-Namen:
		<ul>
			<li>Ein Domain-Name darf nicht mit einem "." beginnen (z.B. "<span class="nicht">.</span>test.ggdns.de")</li>
			<li>Ein Domain-Name darf nicht mit einem "." aufhören (z.B. "test.ggdns.de<span class="nicht">.</span>")</li>
			<li>Ein Domain-Name darf am Anfang einer Subdomain kein "-" haben (z.B. "<span class="nicht">-</span>test.ggdns.de" oder "a.<span class="nicht">-</span>test.ggdns.de")</li>
			<li>Ein Domain-Name darf am Ende einer Subdomain kein "-" haben (z.B. "test<span class="nicht">-</span>.ggdns.de" oder "a.test<span class="nicht">-</span>.ggdns.de")</li>
			<li>Ein Domain-Name darf nur aus folgenden Zeichen bestehen: 0-9, a-z, A-Z, ., - (keine Kommas...).</li>
		</ul>
	</p>
	<p>
		Ein A Record gibt einem Domain-Namen eine IPv4-Adresse. Im Name-Feld wird der gewünschte
		Domain-Name eingegeben. Wird die Domain (z.B. ggdns.de) weggelassen, wird diese automatisch
		angehängt.
		<br />
		Das Content-Feld enthält die IPv4-Adresse auf welche die Domain auflösen soll.
		Per Default steht die IP-Adresse über welche die Homepage aufgerufen wird.
		<br />
		Das Update-Passwort wird zur Aktualisierung des Content-Feldes via Script genutzt.
		Dies wird im Klartext gespeichert und kann jederzeit in der Tabelle angezeigt werden.
		<br />
		Die TTL gibt an, wie lange eine Domain im Cache liegt bis der DNS wieder angefragt
		werden muss.
	</p>
	<p>
		Ein AAAA Record weist einem Domain-Namen eine IPv6-Adresse zu.
		Dieser Record ist analog zum A Record aufgebaut.
	</p>
	<p>
		Ein CNAME Record ist ein Alias für eine Domain.
		Im Name-Feld wird der Alias angegeben, im Content-Feld die Original-Domain auf welche verwiesen wird.
		Dieser Record kann nicht automatisch aktualisiert werden - deswegen auch kein Passwort.
		Dies hat keinen technischen Grund - es gibt nur keinen mir bekannten Anwendungsfall.
		Da dieser Record nicht automatisch aktualisiert wird kann die TTL recht hoch sein.
	</p>

	<h4>Anderes</h4>
	<p>
		Bugs und Feature-Request an <a href="mailto:rizzle@underdog-projects.net">rizzle@underdog-projects.net</a>.
	</p>
	<p>
		Geplante Features:
		<ul>
			<li class="fertig">SSL</li>
			<li class="fertig">Email-Nachfragen für Aktionen (Passwort-Änderung, Record hinzufügen / entfernen)</li>
		</ul>
	</p>
	<h4></h4>
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
