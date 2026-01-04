<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2026 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * Bacula(R) - The Network Backup Solution
 * Baculum   - Bacula web interface
 *
 * Copyright (C) 2013-2019 Kern Sibbald
 *
 * The main author of Baculum is Marcin Haba.
 * The original author of Bacula is Kern Sibbald, with contributions
 * from many others, a complete list can be found in the file AUTHORS.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 *
 * This notice must be preserved when any source code is
 * conveyed and/or propagated.
 *
 * Bacula(R) is a registered trademark of Kern Sibbald.
 */

namespace Bacularis\Web\Portlets;

use Bacularis\Common\Modules\BacularisCommonPluginBase;
use Bacularis\Common\Modules\PluginConfigBase;
use Bacularis\Common\Modules\AuditLog;
use Prado\Prado;

/**
 * New FileSet file options menu.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class NewFileSetFileOptMenu extends DirectiveListTemplate
{
	public const ITEM_INDEX = 'ItemIndex';
	public const FS_BROWSER_ID = 'FsBrowserId';

	public $plugins = [];

	public function loadConfig()
	{
		$plugin_config = $this->getModule('plugin_config');
		$this->plugins = $plugin_config->getPlugins(PluginConfigBase::PLUGIN_TYPE_BACKUP);

		$config_none = ['none' => ' '];
		$configs = $plugin_config->getConfig();
		$config_list = array_filter($configs, fn ($item) => key_exists($item['plugin'], $this->plugins));
		$config_list = array_keys($config_list);
		sort($config_list, SORT_NATURAL | SORT_FLAG_CASE);
		$config_vals = array_combine($config_list, $config_list);
		$this->PluginSettingList->DataSource = array_merge($config_none, $config_vals);
		$this->PluginSettingList->dataBind();

		$plugin_list = array_keys($this->plugins);
		sort($plugin_list, SORT_NATURAL | SORT_FLAG_CASE);
		array_unshift($plugin_list, '');
		$this->PluginPluginList->DataSource = array_combine($plugin_list, $plugin_list);
		$this->PluginPluginList->dataBind();
	}

	public function setItemIndex($index)
	{
		$this->setViewState(self::ITEM_INDEX, $index);
	}

	public function getItemIndex()
	{
		return $this->getViewState(self::ITEM_INDEX);
	}

	public function setFileSetBrowserId($id)
	{
		$this->setViewState(self::FS_BROWSER_ID, $id);
	}

	public function getFileSetBrowserId()
	{
		return $this->getViewState(self::FS_BROWSER_ID);
	}

	public function usePluginSettings($sender, $param)
	{
		$settings_name = is_string($param) ? $param : $this->PluginSettingList->getSelectedValue();
		$plugin_config = $this->getModule('plugin_config');
		$config = $plugin_config->getConfig($settings_name, true);
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
		$this->getParent()->getSourceTemplateControl()->newIncludePlugin($this, $plugin_val);
	}

	public function savePluginSettings($sender, $param)
	{
		$fields = $param->getCallbackParameter();
		if (!is_object($fields)) {
			return false;
		}
		$fields = (array) $fields;
		$name = $this->PluginSettingsName->Text;
		$enabled = '1';
		$plugin_name = $this->PluginPluginList->Text;
		$settings = [
			'plugin' => $plugin_name,
			'enabled' => $enabled,
			'parameters' => $fields
		];
		$cb = $this->getPage()->getCallbackClient();
		$plugin_config = $this->getModule('plugin_config');
		if ($plugin_config->isPluginSettings($name)) {
			$cb->show('plugin_list_plugin_settings_exists');
			return false;
		}

		$result = $plugin_config->setPluginSettings($name, $settings);
		$audit = $this->getModule('audit');
		if ($result === true) {
			$cb->callClientFunction(
				'oPlugins.show_plugin_settings_window',
				[false]
			);
			if (is_object($audit)) {
				$audit->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Save plugin settings. Plugin: {$plugin_name}, Settings: {$name}"
				);
			}
			$this->usePluginSettings(null, $name);
		} else {
			$cb->update(
				'plugin_list_plugin_settings_error',
				'Error while saving plugin form'
			);
			$cb->show('plugin_list_plugin_settings_error');
			if (is_object($audit)) {
				$audit->audit(
					AuditLog::TYPE_ERROR,
					AuditLog::CATEGORY_APPLICATION,
					"Error while saving plugin settings. Plugin: {$plugin_name}, Settings: {$name}"
				);
			}
		}
	}
}
