<div id="forgotten">
	<h3><?php echo pgettext("TemplateHeader", "Resetting the password"); ?></h3>
	<p>
		<?php echo pgettext("PasswordResetInfo", "To reset the password the following is required:"); ?>
	</p>
	<div>
		<label for="forgotten_name"><?php echo pgettext("PasswordReset", "User-Name"); ?>:</label>
		<input type="text" id="forgotten_name" />
		<br />
		<label for="forgotten_email"><?php echo pgettext("PasswordReset", "Email"); ?>:</label>
		<input type="text" id="forgotten_email" />
		<br />
		<input type="button" id="forgotten_submit" value="<?php echo pgettext("PasswordResetConfirm", "Reset password"); ?>" />
	</div>
</div>
<div id="forgotten2" style="display: none">
	<h3><?php echo pgettext("PasswordResetInfo", "Resetting the password - Step 2"); ?></h3>
	<p>
		<?php echo pgettext("PasswordResetInfo", "You have received an email with a token to reset your password."); ?>
		<br />
		<?php echo pgettext("PasswordResetInfo", "Please keep this page open until you finished resetting your password."); ?>
		<br />
		<?php echo pgettext("PasswordResetInfo", "Please insert the token from the email and your new password:"); ?>
	</p>
	<div>
		<label for="forgotten2_token"><?php echo pgettext("PasswordReset", "Token"); ?>:</label>
		<input type="text" id="forgotten2_token" size="64" />
		<br />
		<label for="forgotten2_password1"><?php echo pgettext("PasswordReset", "Password"); ?>:</label>
		<input type="password" id="forgotten2_password1" size="32" />
		<input type="password" id="forgotten2_password2" size="32" />
		<span id="user_add_nomatch" style="display: none"><?php echo pgettext("PasswordDiffer", "Passwords differ"); ?></span>
		<br />
		<input type="button" id="forgotten2_submit" value="<?php echo pgettext("PasswordResetConfirm", "Set password"); ?>" />
	</div>
</div>
