<?php

/**
 * Class LoginAttempts
 *
 * Per-IP failure tracking shared by login, dynamic-IP updates, and the
 * ACME endpoints. Blocks an IP after 10 failures within 15 minutes.
 */
class LoginAttempts
{
    private const WINDOW_MINUTES = 15;
    private const MAX_FAILURES = 10;
    private const PURGE_AFTER_HOURS = 1;

    private Page $page;

    function __construct(Page $page)
    {
        $this->page = $page;
    }

    /**
     * If the IP has hit the failure threshold, sends HTTP 403 and exits.
     * Otherwise returns normally so the caller can proceed.
     *
     * @param string $ip The client IP.
     */
    public function enforce(string $ip): void
    {
        $check = $this->page->db->query(
            "SELECT COUNT(*) AS c FROM dns_login_attempts WHERE ip = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL " . self::WINDOW_MINUTES . " MINUTE)",
            $ip
        );
        $row = $check->fetch();
        if ($row && $row['c'] >= self::MAX_FAILURES) {
            header("HTTP/1.0 403 Forbidden");
            exit(0);
        }
    }

    /**
     * @param string $ip The client IP.
     * @param string $username The login or record name attempted.
     */
    public function recordFailure(string $ip, string $username): void
    {
        $this->page->db->query(
            "INSERT INTO dns_login_attempts (ip, username, attempt_time) VALUES (?, ?, NOW())",
            $ip, $username
        );
    }

    /**
     * Clears all recorded failures for an IP after a successful auth.
     *
     * @param string $ip The client IP.
     */
    public function clear(string $ip): void
    {
        $this->page->db->query("DELETE FROM dns_login_attempts WHERE ip = ?", $ip);
    }

    /**
     * Drops attempt rows older than the purge window. Called from periodic cleanup.
     */
    public function purgeOld(): void
    {
        $this->page->db->query("DELETE FROM dns_login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL " . self::PURGE_AFTER_HOURS . " HOUR)");
    }
}
