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

use Prado\Prado;
use Bacularis\Common\Modules\AuditLog;

/**
 * Plugin list control.
 * It enables to manage Bacularis plugins.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class PluginList extends Portlets
{
	public function onLoad($param)
	{
		parent::onLoad($param);
		if ($this->getPage()->IsPostBack || $this->getPage()->IsCallBack) {
			return;
		}
		$this->setPluginNames();
	}

	/**
	 * Plugin settings list loader.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter callback parameter object
	 */
	public function loadPluginSettingsList($sender, $param)
	{
		$plugin_config = $this->getModule('plugin_config');
		$plugins = $plugin_config->getConfig();
		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction(
			'oPlugins.load_plugin_settings_list_cb',
			[$plugins]
		);
	}

	/**
	 * Plugin list loader.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter callback parameter object
	 */
	public function loadPluginPluginsList($sender, $param)
	{
		$plugin_config = $this->getModule('plugin_config');
		$plugins = $plugin_config->getPlugins();
		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction(
			'oPlugins.load_plugin_plugins_list_cb',
			[$plugins]
		);
	}

	/**
	 * Set plugin names in the plugin combobox.
	 */
	public function setPluginNames()
	{
		$data = ['none' => Prado::localize('Select plugin')];
		$plugins = $this->getPlugins();
		foreach ($plugins as $cls => $prop) {
			$data[$cls] = $prop['name'];
		}
		$this->PluginSettingsPluginName->DataSource = $data;
		$this->PluginSettingsPluginName->dataBind();
	}

	/**
	 * Get plugin list with all properties.
	 *
	 * @return array plugin list.
	 */
	public function getPlugins()
	{
		$plugin_config = $this->getModule('plugin_config');
		$plugins = $plugin_config->getPlugins();
		return $plugins;
	}

	/**
	 * Save plugin settings.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter callback parameter object
	 */
	public function savePluginSettingsForm($sender, $param)
	{
		$fields = $param->getCallbackParameter();
		if (!is_object($fields)) {
			return false;
		}
		$fields = (array) $fields;
		$name = $this->PluginSettingsName->Text;
		$enabled = $this->PluginSettingsEnabled->Checked ? '1' : '0';
		$plugin_name = $this->PluginSettingsPluginName->Text;
		$settings = [
			'plugin' => $plugin_name,
			'enabled' => $enabled,
			'parameters' => $fields
		];
		$cb = $this->getPage()->getCallbackClient();
		$plugin_config = $this->getModule('plugin_config');
		$win_mode = $this->PluginSettingsWindowMode->Value;
		if ($win_mode == 'add' && $plugin_config->isPluginSettings($name)) {
			$cb->show('plugin_list_plugin_settings_exists');
			return false;
		}

		$result = $plugin_config->setPluginSettings($name, $settings);
		if ($result === true) {
			$cb->callClientFunction(
				'oPlugins.show_plugin_settings_window',
				[false]
			);
			$this->loadPluginPluginsList($sender, $param);
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				"Save plugin settings. Plugin: {$plugin_name}, Settings: {$name}"
			);
		} else {
			$cb->update(
				'plugin_list_plugin_settings_error',
				'Error while saving plugin form'
			);
			$cb->show('plugin_list_plugin_settings_error');
			$this->getModule('audit')->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_APPLICATION,
				"Error while saving plugin settings. Plugin: {$plugin_name}, Settings: {$name}"
			);
		}
	}

	/**
	 * Remove plugin settings.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter callback parameter object
	 */
	public function removePluginSettings($sender, $param)
	{
		$settings = explode('|', $param->getCallbackParameter());
		$plugin_config = $this->getModule('plugin_config');
		for ($i = 0; $i < count($settings); $i++) {
			$result = $plugin_config->removePluginSettings($settings[$i]);
			if ($result) {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Remove plugin settings. Settings: {$settings[$i]}"
				);
			} else {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_ERROR,
					AuditLog::CATEGORY_APPLICATION,
					"Error while removing plugin settings. Settings: {$settings[$i]}"
				);
				break;
			}
		}

		// Refresh plugin list
		$this->loadPluginSettingsList($sender, $param);
	}
}
