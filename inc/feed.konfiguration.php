<?php

class DNSFeedsKonfiguration extends DNSFeeds {
	public function konfiguration_getPublicParameters() {
		$c = $this->page->configuration->getPublicConfig();
		if (isset($c) && $c !== FALSE) $this->setResult($c);
	}

	public function konfiguration_getParameters() {
		if ($this->page->user->getCurrentUser()->level < 3) return;
		$c = $this->page->configuration->getAllConfig();
		if (isset($c) && $c !== FALSE) $this->setResult($c);
	}

	public function konfiguration_parameterAdd($name, $value) {
		if ($this->page->user->getCurrentUser()->level < 3) return;
		if (!isset($name) || strlen($name) < 1 || !isset($value)) return;
		$this->page->configuration->setConfig($name, $value);
		$c = $this->page->configuration->getAllConfig();
		if (isset($c) && $c !== FALSE) $this->setResult($c);
	}

	public function konfiguration_parameterUpdate($id, $name, $value) {
		if ($this->page->user->getCurrentUser()->level < 3) return;
		if (!isset($id) || !isset($name) || strlen($name) < 1 || !isset($value)) return;
		$this->page->configuration->updateConfig($id, $name, $value);
		$c = $this->page->configuration->getAllConfig();
		if (isset($c) && $c !== FALSE) $this->setResult($c);
	}
}
