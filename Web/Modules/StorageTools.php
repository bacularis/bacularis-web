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

namespace Bacularis\Web\Modules;

/**
 * Module responsible for providing various storage tools.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class StorageTools extends WebModule
{
	/**
	 * Default storage daemon port.
	 */
	private const DEFAULT_SD_PORT = 9103;

	/**
	 * Get the storage daemon password from the SD config.
	 *
	 * @param string $api_host API host
	 * @param string $director director name
	 * return string SD password or empty string is password was not possible to get
	 */
	public function getSdPassword(string $api_host, string $director): string
	{
		$api = $this->getModule('api');
		$result = $api->get(
			['config', 'sd', 'Director', $director],
			$api_host,
			false
		);
		$sd_password = '';
		if ($result->error == 0) {
			$sd_password = $result->output->Password ?? '';
		}
		return $sd_password;
	}

	/**
	 * Get the storage daemon port from the SD config.
	 *
	 * @param string $api_host API host
	 * @return int storage daemon port
	 */
	public function getSdPort(string $api_host): int
	{
		$api = $this->getModule('api');
		$result = $api->get(
			['config', 'sd', 'Storage'],
			$api_host,
			false
		);
		$sd_port = 0;
		if ($result->error == 0) {
			$sd_port = (int) ($result->output[0]->Storage->SdPort ?? self::DEFAULT_SD_PORT);
		}
		return $sd_port;
	}

	/**
	 * Create storage device.
	 *
	 * @param string $api_host API host
	 * @param array $directives directives
	 * @param array $def_directives default directives
	 * @return object with output and error code
	 */
	public function createDevice(string $api_host, array $directives, array $def_directives = []): object
	{
		$api = $this->getModule('api');
		$params = [
			'config',
			'sd',
			'Device',
			$directives['Name']
		];
		$config = $directives;
		if (count($def_directives) > 0) {
			$config = array_merge($def_directives, $config);
		}
		$result = $api->create(
			$params,
			['config' => json_encode($config)],
			$api_host,
			false
		);
		return $result;
	}

	/**
	 * Create storage autochanger.
	 *
	 * @param string $api_host API host
	 * @param array $directives directives
	 * @param array $def_directives default directives
	 * @return object with output and error code
	 */
	public function createAutochanger(string $api_host, array $directives, array $def_directives = []): object
	{
		$api = $this->getModule('api');
		$params = [
			'config',
			'sd',
			'Autochanger',
			$directives['Name']
		];
		$config = $directives;
		if (count($def_directives) > 0) {
			$config = array_merge($def_directives, $config);
		}
		$result = $api->create(
			$params,
			['config' => json_encode($config)],
			$api_host,
			false
		);
		return $result;
	}

	/**
	 * Create storage resource in current API host.
	 *
	 * @param array $directives directives
	 * @param array $def_directives default directives
	 * @return object with output and error code
	 */
	public function createStorage(array $directives, array $def_directives = []): object
	{
		$api = $this->getModule('api');
		$params = [
			'config',
			'dir',
			'Storage',
			$directives['Name']
		];
		$config = $directives;
		if (count($def_directives) > 0) {
			$config = array_merge($def_directives, $config);
		}
		$result = $api->create(
			$params,
			['config' => json_encode($config)],
			null,
			false
		);
		return $result;
	}

	/**
	 * Create storage cloud resource.
	 *
	 * @param string $api_host API host
	 * @param array $directives directives
	 * @return object with output and error code
	 */
	public function createCloud(string $api_host, array $directives): object
	{
		$api = $this->getModule('api');
		$params = [
			'config',
			'sd',
			'Cloud',
			$directives['Name']
		];
		$config = $directives;
		$result = $api->create(
			$params,
			['config' => json_encode($config)],
			$api_host,
			false
		);
		return $result;
	}

	/**
	 * Add device to Bacularis config.
	 *
	 * @param string $api_host API host
	 * @param array $props device properties
	 *
	 * @return object with output and error code
	 */
	public function addBacularisDevice(string $api_host, array $props): object
	{
		$api = $this->getModule('api');
		$params = [
			'devices',
			$props['name']
		];
		$config = [
			'type' => $props['type'],
			'device' => $props['device']
		];

		if (key_exists('index', $props)) {
			$config['index'] = $props['index'];
		}
		if (key_exists('command', $props)) {
			$config['command'] = $props['command'];
		}
		if (key_exists('use_sudo', $props)) {
			$config['use_sudo'] = $props['use_sudo'];
		}
		if (key_exists('sudo_user', $props)) {
			$config['sudo_user'] = $props['sudo_user'];
		}
		if (key_exists('sudo_group', $props)) {
			$config['sudo_group'] = $props['sudo_group'];
		}
		if (key_exists('drives', $props)) {
			$config['drives'] = $props['drives'];
		}
		$result = $api->create(
			$params,
			$config,
			$api_host,
			false
		);
		return $result;
	}

	/**
	 * Get storage catalog identifier by storage address.
	 *
	 * @param string $sd_address storage daemon address
	 * @return int storage identifier or 0 if the intentifier could not be found
	 */
	public function getStorageIdByAddress(string $sd_address): int
	{
		$api = $this->getModule('api');
		$params = [
			'config',
			'dir',
			'Storage'
		];
		$storage_name = '';
		$result = $api->get($params);
		if ($result->error == 0) {
			for ($i = 0; $i < count($result->output); $i++) {
				$addr = $result->output[$i]->Storage->Address ?? '';
				if ($addr === $sd_address) {
					$storage_name = $result->output[$i]->Storage->Name;
					break;
				}
			}
		}
		$storage_id = 0;
		if ($storage_name) {
			$query_params = [
				'name' => $storage_name
			];
			$query = '?' . http_build_query($query_params);
			$params = [
				'storages',
				$query
			];
			$result = $api->get($params);
			if ($result->error == 0) {
				$storage_id = $result->output[0]->storageid ?? 0;
			}
		}
		return $storage_id;
	}

	/**
	 * Get running job number on the given storage.
	 *
	 * @param int $storage_id storage identifier
	 * @return int running job number
	 */
	public function getRunningJobNumber(int $storage_id): int
	{
		$api = $this->getModule('api');
		$query_params = [
			'output' => 'json',
			'type' => 'header'
		];
		$query = '?' . http_build_query($query_params);
		$params = [
			'storages',
			$storage_id,
			'status',
			$query
		];
		$running_job_nb = 0;
		$result = $api->get($params);
		if ($result->error == 0) {
			$running_job_nb = $result->output->jobs_running ?? 0;
		}
		return $running_job_nb;
	}

	/**
	 * Get autochanger configuration.
	 *
	 * @param string $api_host SD API host
	 * @param string $ach_name autochanger name
	 * @return array autochanger config or empty array on error
	 */
	public function getAutochangerConfig(string $api_host, string $ach_name): array
	{
		$config = [];
		$api = $this->getModule('api');
		$params = [
			'config',
			'sd',
			'Autochanger',
			$ach_name
		];
		$result = $api->get(
			$params,
			$api_host,
			true,
			true
		);
		if ($result->error === 0) {
			$config = (array) $result->output;
		}
		return $config;
	}

	/**
	 * Get device configuration.
	 *
	 * @param string $api_host SD API host
	 * @param string $dev_name device name
	 * @return array device config or empty array on error
	 */
	public function getDeviceConfig(string $api_host, string $dev_name): array
	{
		$config = [];
		$api = $this->getModule('api');
		$params = [
			'config',
			'sd',
			'Device',
			$dev_name
		];
		$result = $api->get(
			$params,
			$api_host,
			true,
			true
		);
		if ($result->error === 0) {
			$config = (array) $result->output;
		}
		return $config;
	}
}
