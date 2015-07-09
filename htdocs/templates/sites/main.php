<?php

global $page;

?>
<div id="records">
    <h3><?php echo pgettext("TemplateHeading", "Record Management"); ?></h3>
    <?php IF ($page->currentUser->isLoggedIn()): ?>

    <h4><?php echo pgettext("TemplateHeading", "My Records"); ?></h4>
	<input type="button" id="addRecord_button" value="<?php echo pgettext("AddRecord", "Add Record"); ?>" />
    <div id="addRecord" class="hider">
		<div>
			<label for="addRecordType"><?php echo pgettext("AddRecord", "Type"); ?>:</label>
			<select id="addRecordType">
				<option value="A">A (IPv4)</option>
				<option value="AAAA">AAAA (IPv6)</option>
				<option value="CNAME">CNAME (Alias)</option>
			</select>
		</div>
		<div>
			<label for="addRecordDomain"><?php echo pgettext("AddRecord", "Domain"); ?>:</label>
			<select id="addRecordDomain"></select>
		</div>
		<div>
			<label for="addRecordName"><?php echo pgettext("AddRecord", "Name (URI)"); ?>:</label>
			<input type="text" id="addRecordName" />
			<span id="addRecordTest"></span>
		</div>
		<div>
			<label for="addRecordContent"><?php echo pgettext("AddRecord", "IPv4"); ?>:</label>
			<input type="text" id="addRecordContent" />
		</div>
		<div id="addRecord_d_password">
			<label for="addRecordPassword"><?php echo pgettext("AddRecord", "Update-Password"); ?>:</label>
			<input type="text" id="addRecordPassword" size="64" />
		</div>
		<div>
			<label for="addRecordTTL"><?php echo pgettext("AddRecord", "TTL (s)"); ?>:</label>
			<input type="number" id="addRecordTTL" min="5" />
		</div>
        <input type="button" id="addRecordSubmit" value="<?php echo pgettext("AddRecord", "Add"); ?>" />
    </div>
    <table id="recordList" border="1">
        <tr>
			<th><?php echo pgettext("RecordListTableHeader", "ID"); ?></th>
			<th><?php echo pgettext("RecordListTableHeader", "Domain"); ?></th>
			<th><?php echo pgettext("RecordListTableHeader", "Name"); ?></th>
			<th><?php echo pgettext("RecordListTableHeader", "Type"); ?></th>
			<th><?php echo pgettext("RecordListTableHeader", "Content"); ?></th>
            <th><?php echo pgettext("RecordListTableHeader", "TTL"); ?></th>
            <th><?php echo pgettext("RecordListTableHeader", "Password"); ?></th>
			<th><?php echo pgettext("RecordListTableHeader", "Changed"); ?></th>
			<th><?php echo pgettext("RecordListTableHeader", "Operation"); ?></th>
        </tr>
    </table>
	<input type="button" id="recordListReload" value="<?php echo pgettext("RecordList", "Reload List"); ?>" />
	<div id="recordListNamePopup" class="popup" style="display: none">
		<label for="recordListName"><?php echo pgettext("RecordModify", "Name"); ?>:</label>
		<input type="text" id="recordListName" />
		<br />
		<input type="button" id="recordListNameSubmit" value="<?php echo _("OK"); ?>" />
		<input type="button" class="popupAbort" value="<?php echo _("Abort"); ?>" />
	</div>
	<div id="recordListContentPopup" class="popup" style="display: none">
		<label for="recordListContent"><?php echo pgettext("RecordModify", "Content"); ?>:</label>
		<input type="text" id="recordListContent" />
		<br />
		<input type="button" id="recordListContentSubmit" value="<?php echo _("OK"); ?>" />
		<input type="button" class="popupAbort" value="<?php echo _("Abort"); ?>" />
	</div>
	<div id="recordListPasswordPopup" class="popup" style="display: none">
		<label for="recordListPassword"><?php echo pgettext("RecordModify", "Password"); ?>:</label>
		<input type="text" id="recordListPassword" size="64" />
		<br />
		<input type="button" id="recordListPasswordSubmit" value="<?php echo _("OK"); ?>" />
		<input type="button" class="popupAbort" value="<?php echo _("Abort"); ?>" />
	</div>
	<div id="recordListTTLPopup" class="popup" style="display: none">
		<label for="recordListTTL"><?php echo pgettext("RecordModify", "TTL (s)"); ?>:</label>
		<input type="number" id="recordListTTL" min="5" />
		<br />
		<input type="button" id="recordListTTLSubmit" value="<?php echo _("OK"); ?>" />
		<input type="button" class="popupAbort" value="<?php echo _("Abort"); ?>" />
	</div>

    <?php
        if (file_exists(__DIR__ . "/../../locale/{$page->currentUser->locale}/LC_MESSAGES/help.php"))
            require(__DIR__ . "/../../locale/{$page->currentUser->locale}/LC_MESSAGES/help.php");
        else
            require(__DIR__ . "/../../locale/en_US/LC_MESSAGES/help.php");
        ?>
    <?php ELSE: ?>
        <p>
            <?php echo _("Log in, please."); ?>
        </p>
    <?php ENDIF ?>
</div>
