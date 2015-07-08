<?php

global $page;

?>
<div id="records">
	<h3><?php echo pgettext("TemplateHeader", "User Management"); ?></h3>
<?php IF ($page->currentUser->isLoggedIn()): ?>
	<div>
		<label for="password1"><?php echo pgettext("UpdateUser", "New Password"); ?>:</label>
		<input type="password" id="password1" />
		<input type="password" id="password2" />
		<input type="button" id="password_submit" value="<?php echo pgettext("UpdateUser", "Update"); ?>" />
		<span id="user_add_nomatch" style="display: none"><?php echo pgettext("UpdateUserPassword", "passwords differ"); ?></span>
	</div>
	<div>
		<label for="email"><?php echo pgettext("UpdateUser", "New Email"); ?>:</label>
		<input type="text" id="email" value="<?php echo $page->currentUser->getEmail() ?>" size="64" />
		<input type="button" id="email_submit" value="<?php echo pgettext("UpdateUser", "Update"); ?>" />
	</div>
	<div>
		<label for="token"><?php echo _("Token to verify"); ?>:</label>
		<input type="text" id="token" size="64" />
		<input type="button" id="token_submit" value="<?php echo pgettext("VerifyToken", "Verify Token"); ?>" />
	</div>
	<h4>
		<?php echo pgettext("DescriptionTokensHeader", "Tokens"); ?>
	</h4>
	<p>
		<?php echo pgettext("DescriptionTokens",
            "Changing the password or the email address requires a confirmation via a token
            sent to your current email address."); ?>
		<br/>
		<?php echo pgettext("DescriptionTokens",
            sprintf("You can either click the link in the email to verify your request or
            copy the containing token in the field \"%s\" on this page.", _("Token to verify"))); ?>
		<br />
		<?php echo pgettext("DescriptionTokens", "The token is valid for at least one day."); ?>
	</p>
<?php ELSE: ?>
	<p>
		<?php echo _("Log in, please."); ?>
	</p>
<?php ENDIF ?>
</div>
