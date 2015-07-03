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
    protected $page;

    /**
     * @var string Optional special headers to send.
     */
    protected $specialHeader;

    /**
     * @var array The result to send to the client.
     */
    protected $result;

    /**
     * @var bool Whether this is a raw result.
     */
    protected $rawResult;

    function __construct($page) {
        $this->page = $page;
        $this->result = array('status' => 'error');
    }

    /**
     * Output the result.
     */
    public function printResult() {
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
                $this->result['user'] = $this->page->user->getCurrentUser();
                echo json_encode($this->result);
            }
        }
    }

    /**
     * Sets a special header.
     *
     * @param string $header The special header.
     */
    public function setSpecialHeader($header = '') {
        $this->specialHeader = $header;
    }

    /**
     * Sets the data to be sent to the client.
     *
     * @param null $data The data to send.
     * @param string $status The status to send.
     * @param bool $raw Whether this is a raw result (not JSON).
     */
    public function setResult($data = NULL, $status = 'ok', $raw = FALSE) {
        $this->result = array('data' => $data, 'status' => $status);
        $this->rawResult = $raw;
    }
}
