<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2026 Marcin Haba
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

use Bacularis\Common\Modules\AuditLog;
use Bacularis\Common\Modules\Cloud\Amazon\EBS\EBS;
use Bacularis\Common\Modules\Cloud\Amazon\Region as AmazonRegion;
use Prado\Prado;

/**
 * Amazon module providing common methods for Amazon-type backup job portlets.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
abstract class AmazonBackupWindowBase extends AmazonBase
{
	private static $resources = [];

	private const SESS_AWS_ACCOUNT = 'aws_account';

	private const JOBDEFS = 'JobDefs';

	protected function loadResource($resource_type, $control)
	{
		$name_list = [];
		if (key_exists($resource_type, self::$resources)) {
			$name_list = self::$resources[$resource_type];
		} else {
			$api = $this->getModule('api');
			$res = strtolower($resource_type);
			$result = $api->get(['config', 'dir', $res]);
			if ($result->error != 0) {
				return;
			}
			$resources = $result->output;
			for ($i = 0; $i < count($resources); $i++) {
				$name_list[] = $resources[$i]->{$resource_type}->Name;
			}
			sort($name_list, SORT_NATURAL | SORT_FLAG_CASE);
			self::$resources[$resource_type] = $name_list;
		}
		$control->setData($name_list);
		$control->createDirective();
	}

	protected function loadAmazonAccounts($control)
	{
		$api = $this->getModule('api');
		$host = $this->getFDAPIHost();
		$result = $api->get(
			['cloud', 'amazon', 'accounts'],
			$host,
			false
		);
		if ($result->error != 0) {
			return;
		}
		$hosts = array_filter($result->output, fn ($item) => $item->enabled == 1);
		$hosts = array_map(fn ($item) => $item->name, $hosts);
		$values = array_combine($hosts, $hosts);
		asort($values, SORT_NATURAL | SORT_FLAG_CASE);
		$sess = $this->getSession();
		$selected = $sess->itemAt(self::SESS_AWS_ACCOUNT);
		$values = array_merge([' ' => Prado::localize('Please select account')], $values);
		$control->DataSource = $values;
		if ($selected) {
			$control->SelectedValue = $selected;
		} elseif (count($values) == 2) {
			$keys = array_keys($values);
			$control->SelectedValue = $values[$keys[1]];
		}
		$control->dataBind();
		return $values;
	}

	protected function loadEndpoints($control)
	{
		$eps = EBS::getEndpoints();
		$keys = array_keys($eps);
		$vals = array_map(fn ($item) => $item['name'], $eps);
		$endpoints = array_combine($keys, $vals);
		$control->DataSource = $endpoints;
		$control->dataBind();
	}

	protected function loadLevels($control)
	{
		$level_list = [
			'Full', 'Incremental'
		];
		$control->setData($level_list);
		$control->createDirective();
	}

	protected function loadRegions($control)
	{
		$sess = $this->getSession();
		$account_name = $sess->itemAt(self::SESS_AWS_ACCOUNT);
		$api = $this->getModule('api');
		$host = $this->getFDAPIHost();
		$result = $api->get(
			['cloud', 'amazon', 'accounts', $account_name],
			$host,
			false
		);
		$account = [];
		if ($result->error == 0) {
			$account = (array) $result->output;
		}
		$regions = AmazonRegion::getRegions();
		$control->DataSource = array_merge(['' => ''], $regions);
		if (key_exists('region', $account) && !empty($account['region'])) {
			$control->setSelectedValue($account['region']);
		}
		$control->dataBind();
	}

	protected function isInJobDefs($directive_name, $directive_value)
	{
		$jobdefs = $this->getJobDefs();
		$ret = false;
		if ($directive_name === 'Storage') {
			if (key_exists($directive_name, $jobdefs)) {
				$diff = array_diff($jobdefs[$directive_name], $directive_value);
				$ret = (count($diff) == 0);
			}
		} else {
			$ret = (key_exists($directive_name, $jobdefs) && $jobdefs[$directive_name] === $directive_value);
		}
		return $ret;
	}

	/**
	 * Create new plugin setting.
	 *
	 * @param array $opts plugin options
	 * @return bool true on setting created successfully, false otherwise
	 */
	protected function setPluginSettings(array $opts): bool
	{
		$settings = [
			'plugin' => 'AmazonEC2Backup',
			'enabled' => 1,
			'parameters' => $opts['parameters']
		];

		$plugin_config = $this->getModule('plugin_config');
		if ($plugin_config->isPluginSettings($opts['name'])) {
			$emsg = sprintf("Plugin setting '%s' already exists.", $opts['name']);
			$this->showError($emsg);
			return '';
		}
		$result = $plugin_config->setPluginSettings($opts['name'], $settings);
		if ($result) {
			$audit = $this->getModule('audit');
			$audit->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				"Save plugin settings. Plugin: {$settings['plugin']}, Settings: {$opts['name']}"
			);
		} else {
			$emsg = sprintf("Error while saving plugin setting '%s'.", $opts['name']);
			$this->showError($emsg);
			return '';
		}
		return $result;
	}

	/**
	 * Get single plugin line.
	 * This line can be used directly in FileSet Plugin directive.
	 *
	 * @param string $settings_name plugin settings name
	 * @return string plugin line or empty string on error
	 */
	protected function getPluginDirective(string $settings_name): string
	{
		$plugin_config = $this->getModule('plugin_config');
		$config = $plugin_config->getConfig($settings_name, true);
		if (!$config) {
			return '';
		}
		$plugins = $this->getModule('plugins');
		$plugin = $plugins->getPluginByName($config['plugin']);
		$pparams_req = [
			'plugin-name' => $config['plugin'],
			'plugin-config' => $config['name'],
			'job-id' => '%i',
			'job-name' => '%n',
			'job-level' => '%l'
		];
		$pparams = array_merge($pparams_req, $config['parameters']);
		$cparams = $plugin->getPluginCommand('command/list', $pparams);
		$plugin_val = '\|' . implode(' ', $cparams);
		return $plugin_val;
	}

	protected function getBackupClient()
	{
		$fd_api_host = $this->getFDAPIHost();
		$host_config = $this->getModule('host_config');
		$hconf = $host_config->getHostConfig($fd_api_host);
		$api = $this->getModule('api');
		$result = $api->get(['config', 'dir', 'Client']);
		if ($result->error != 0) {
			return;
		}
		$fd_name = '-';
		for ($i = 0; $i < count($result->output); $i++) {
			if ($result->output[$i]->Client->Address == $hconf['address']) {
				$fd_name = $result->output[$i]->Client->Name;
			}
		}
		return $fd_name;
	}

	/**
	 * Set JobDefs directives.
	 *
	 * @param array $data jobdefs directives.
	 */
	protected function setJobDefs(array $data): void
	{
		$this->setViewState(self::JOBDEFS, $data);
	}

	/**
	 * Get JobDefs directives.
	 *
	 * @return array JobDefs directives
	 */
	protected function getJobDefs(): array
	{
		return $this->getViewState(self::JOBDEFS, []);
	}

	/**
	 * Hide error message.
	 */
	abstract protected function hideError(): void;

	/**
	 * Show error message in window.
	 *
	 * @param string $emsg error message
	 */
	abstract protected function showError(string $emsg): void;
}
