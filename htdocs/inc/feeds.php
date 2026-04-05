<?php

/**
 * Class Feeds
 *
 * The base abstract class for all feeds.
 */
abstract class Feeds {
    /**
     * @var Page The base page instance.
     */
    protected Page $page;

    /**
     * @var string Optional special headers to send.
     */
    protected string $specialHeader;

    /**
     * @var array The result to send to the client.
     */
    protected array $result;

    /**
     * @var bool Whether this is a raw result.
     */
    protected bool $rawResult = false;

    function __construct(Page $page) {
        $this->page = $page;
        $this->result = array('status' => 'error');
    }

    /**
     * Output the result.
     */
    public function printResult(): void {
        if (isset($this->specialHeader)) {
            if (strlen($this->specialHeader) > 0) {
                header($this->specialHeader);
            }
        } else {
            header("Content-Type: application/json; charset=utf-8;");
        }
        if ($this->result) {
            if ($this->rawResult) {
                echo $this->result['data'];
            } else {
                $this->result['user'] = $this->page->currentUser->getPrintableUser();
                echo json_encode($this->result);
            }
        }
    }

    /**
     * Sets a special header.
     *
     * @param string $header The special header.
     */
    public function setSpecialHeader(string $header = ''): void {
        $this->specialHeader = $header;
    }

    /**
     * Sets the data to be sent to the client.
     *
     * @param null|mixed $data The data to send.
     * @param string $status The status to send.
     * @param bool $raw Whether this is a raw result (not JSON).
     */
    public function setResult(mixed $data = null, string $status = 'ok', bool $raw = false): void {
        $this->result = array('data' => $data, 'status' => $status);
        $this->rawResult = $raw;
    }
}
