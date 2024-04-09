<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2024 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 */

namespace Bacularis\Web\Portlets;

use Bacularis\Common\Modules\Logging;

/**
 * Network test control.
 * Test newtork between file daemon and storage daemon.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class NetworkTest extends Portlets
{
	private const CLIENT = 'Client';

	/**
	 * Default bytes to send in test.
	 */
	public const DEFAULT_BYTES = '50000000';

	/**
	 * Output parser patterns.
	 */
	private const FD_TO_SD_PATTERN = '/^(?P<error_code>\d+)\s+(?P<status>\w+)(?:\s+FD\s+wrote)?\s+bytes=(?P<bytes>\d+)(?:\s+to\s+SD)?\s+duration=(?P<duration>[\d\w]+)\s+write_speed=(?P<write_speed>[\d\w\.\/\s]+)$/i';
	private const SD_TO_FD_PATTERN = '/^(?P<error_code>\d+)\s+(?P<status>\w+)(?:\s+FD\s+read)?\s+bytes=(?P<bytes>\d+)(?:\s+from\s+SD)?\s+duration=(?P<duration>[\d\w]+)\s+read_speed=(?P<read_speed>[\d\w\.\/\s]+)$/i';
	private const STAT_PATTERN = '/^(?P<error_code>\d+)\s+(?P<status>\w+)\s+packets=(?P<packets>\d+)\s+duration=(?P<duration>[\d\w]+)\s+rtt=(?P<rtt>[\d\w\.]+)\s+min=(?P<min>[\d\w\.]+)\s+max=(?P<max>[\d\w\.]+)$/';

	public function onPreRender($param): void
	{
		parent::onPreRender($param);
		if ($this->Page->IsPostBack || $this->Page->IsCallback) {
			return;
		}
		$this->setClientList();
		$this->setStorageList();
	}

	/**
	 * Set client list control.
	 */
	private function setClientList(): void
	{
		$client_list = [];
		$clients = $this->getModule('api')->get(['clients']);
		if ($clients->error === 0) {
			$client_list = array_map(fn ($client) => $client->name, $clients->output);
			natcasesort($client_list);
		}
		$this->NetworkTestClient->DataSource = array_combine($client_list, $client_list);
		if (!empty($this->Client)) {
			$this->NetworkTestClient->SelectedValue = $this->Client;
		}
		$this->NetworkTestClient->dataBind();
	}

	/**
	 * Set storage list control.
	 */
	private function setStorageList(): void
	{
		$storage_list = [];
		$storages = $this->getModule('api')->get(['storages']);
		if ($storages->error === 0) {
			$storage_list = array_map(fn ($storage) => $storage->name, $storages->output);
			natcasesort($storage_list);
		}
		$this->NetworkTestStorage->DataSource = array_combine($storage_list, $storage_list);
		$this->NetworkTestStorage->dataBind();
	}

	/**
	 * Run network test.
	 *
	 * @param TActiveLinkButton $sender sender object
	 * @param TCallbackEventParameter $param event parameter
	 */
	public function startNetworkTest($sender, $param): void
	{
		$client = $this->NetworkTestClient->getSelectedValue();
		$storage = $this->NetworkTestStorage->getSelectedValue();
		$bytes = $this->NetworkTestBytes->getValue();
		if (empty($client) || empty($storage)) {
			return;
		}
		if (empty($bytes)) {
			$bytes = self::DEFAULT_BYTES;
		}
		$params = [
			'status',
			'network',
			'client="' . $client . '"',
			'storage="' . $storage . '"',
			'bytes="' . $bytes . '"'
		];
		$result = $this->getModule('api')->set(
			['console'],
			$params
		);
		if ($result->error === 0) {
			array_shift($result->output);
			$pres = $this->parseResults($result->output);
			$this->getPage()->getCallbackClient()->callClientFunction(
				'oNetworkTest' . $this->ClientID . '.set_result',
				[$pres]
			);
		} else {
			$emsg = sprintf('Error %d: %s', $result->error, $result->output);
			$this->getPage()->getCallbackClient()->callClientFunction(
				'oNetworkTest' . $this->ClientID . '.set_error',
				[$emsg]
			);
			Logging::log(
				Logging::CATEGORY_EXTERNAL,
				$emsg
			);
		}
	}

	/**
	 * Parse network test results.
	 *
	 * Raw output:
	 * Connecting to Storage CloudDev at 192.168.1.2:9113
	 * Connecting to Client darkstar-fd at ganiwork:9112
	 * Running network test between Client=darkstar-fd and Storage=CloudDev with 104.8 MB ...
	 * 2000 OK FD wrote bytes=104857600 to SD duration=277ms write_speed=379.0 MB/s
	 * 2000 OK FD read bytes=104857600 from SD duration=251ms read_speed=416.7 MB/s
	 * 2000 OK packets=10 duration=1ms rtt=0.07ms min=0.05ms max=0.21ms
	 *
	 * @param array $output raw test output
	 * @return array parsed output results
	 */
	private function parseResults(array $output): array
	{
		$result = [
			'raw' => $output
		];
		for ($i = 0; $i < count($output); $i++) {
			if (preg_match(self::FD_TO_SD_PATTERN, $output[$i], $match) === 1) {
				$result['write'] = $match;
			} elseif (preg_match(self::SD_TO_FD_PATTERN, $output[$i], $match) === 1) {
				$result['read'] = $match;
			} elseif (preg_match(self::STAT_PATTERN, $output[$i], $match) === 1) {
				$result['stat'] = $match;
			}
		}
		return $result;
	}

	public function setClient($client): void
	{
		$this->setViewState(self::CLIENT, $client);
	}

	public function getClient(): string
	{
		return $this->getViewState(self::CLIENT, '');
	}
}
