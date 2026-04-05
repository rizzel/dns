<?php

/**
 * Class Email
 *
 * Class for sending emails and managing tokens.
 */
class Email
{
    /**
     * @var Page The base page instance
     */
    private Page $page;

    /**
     * @var bool Whether this host has PEAR mail support (detect on instantiation).
     */
    private bool $hasPearMail;

    function __construct(Page $page)
    {
        $this->page = $page;
        $this->cleanUpTokens();

        set_include_path(get_include_path() . PATH_SEPARATOR . "/usr/share/php");

        $this->hasPearMail = @include_once("Mail.php");
    }

    /**
     * Cleanup old tokens.
     */
    public function cleanUpTokens(): void
    {
        $this->page->db->query("DELETE FROM dns_users_update WHERE DATEDIFF(NOW(), requesttime) > 2");
        $this->page->db->query("DELETE FROM dns_login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    }

    /**
     * Send an email to the current user.
     *
     * @param string $subject The subject of the email.
     * @param string $body The body of the email.
     * @return bool Whether successful.
     */
    public function sendToCurrent(string $subject, string $body): bool
    {
        return $this->sendTo($this->page->currentUser->email, $subject, $body);
    }

    /**
     * Send an email.
     *
     * @param string $to The recipient of the email.
     * @param string $subject The subject of the email.
     * @param string $body The body of the email.
     * @return bool Whether successful.
     */
    public function sendTo(string $to, string $subject, string $body): bool
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL))
            return false;

        $mailSettings = $this->page->settings['mail'];
        if (!isset($mailSettings['from']))
            return false;
        $from = $mailSettings['from'];
        $subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";

        if (
            $this->hasPearMail &&
            $mailSettings['usePear'] &&
            $mailSettings['pearBackend']
        ) {
            $e = ini_get('error_reporting');
            ini_set('error_reporting', 0);

            $mail = &Mail::factory($mailSettings['pearBackend'], $mailSettings['pearConfig']);

            $mail->send(
                $to,
                array(
                    'From' => $from,
                    'To' => $to,
                    'Subject' => $subject,
                    'Content-type' => 'text/plain; charset=utf-8',
                    'Content-Transfer-Encoding' => '8bit'
                ),
                $body
            );

            ini_set('error_reporting', $e);
        } else {
            mail($to, $subject, $body, implode("\r\n", array(
                'From' => $from,
                'Content-type' => 'text/plain; charset=utf-8',
                'Content-Transfer-Encoding' => '8bit'
            )));
        }

        return true;
    }

    /**
     * Create an database update request with email confirmation.
     *
     * @param string $subject The subject of the email.
     * @param string $text The text of the email.
     * @param string $key The key to update.
     * @param string $value The value to update the key to.
     * @param string|null $username Username or null for current user.
     * @return bool Whether successful.
     */
    public function createUpdate(string $subject, string $text, string $key, string $value, ?string $username = null): bool
    {
        $withURL = true;
        if ($username != null) {
            $withURL = false;
            $user = $this->page->users->getUserByName($username);
        } else {
            $user = $this->page->currentUser;
        }
        if ($user->username == 'anonymous')
            return false;

        $token = bin2hex(random_bytes(32));

        $url = sprintf("%s://%s/u?u=%s&t=%s",
            isset($_SERVER['HTTPS']) ? 'https' : 'http',
            $_SERVER['SERVER_NAME'],
            $user->username,
            $token
        );

        $this->sendTo(
            $user->email,
            $subject,
            $text . "\n" .
            ($withURL ? "\nURL: $url\nODER" : "") .
            "\nToken: $token" .
            ($withURL ? "\n\n" . _("This token can be inserted on the Settings page.") : "")
        );

        $this->page->db->query("
            INSERT INTO dns_users_update VALUES (?, NOW(), ?, ?, ?)
        ",
            $user->username,
            $token,
            $key,
            $value
        );
        return true;
    }

    /**
     * Verify an update token.
     *
     * @param string $username The user the update belongs to.
     * @param string $token The token to verify.
     * @return array|null The key and value to update, or null on failure.
     */
    public function verifyUpdate(string $username, string $token): ?array
    {
        $get = $this->page->db->query("
            SELECT * FROM dns_users_update
            WHERE username = ? AND token = ?
        ",
            $username, $token
        );

        if ($row = $get->fetch()) {
            $this->page->db->query("
                DELETE FROM dns_users_update
                WHERE username = ? AND token = ?
            ",
                $username, $token
            );

            switch ($row['key']) {
                case 'password':
                    if (!$this->page->currentUser->confirmPasswordUpdate($username, $row['value'], true))
                        return null;
                    break;
                case 'email':
                    if (!$this->page->currentUser->confirmEmailUpdate($username, $row['value']))
                        return null;
                    break;
            }
            return $row;
        }
        return null;
    }
}
