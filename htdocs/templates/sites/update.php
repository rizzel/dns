<?php

global $page;

$update = false;
if (array_key_exists('u', $_GET) && array_key_exists('t', $_GET))
    $update = $page->email->verifyUpdate($_GET['u'], $_GET['t']);

?>
<div>
    <h3><?php echo pgettext("TokenUpdate", "Update verification"); ?></h3>

    <?php IF ($update): ?>
        <p>
            <?php echo pgettext("TokenUpdate", "Update successful."); ?>
        </p>
    <?php ELSE: ?>
        <p>
            <?php echo pgettext("TokenUpdate", "Update no successful."); ?>
        </p>
    <?php ENDIF ?>
    <a href="/"><?php echo pgettext("TokenUpdate", "Back to Home"); ?></a>
</div>
