<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2025 Marcin Haba
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

use Bacularis\Web\Modules\WebUserConfig;
use Bacularis\Common\Modules\ConfigFileModule;

/**
 * Manage data view configuration.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Config
 */
class DataViewConfig extends ConfigFileModule
{
	/**
	 * Data view config file path
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.Web.Config.dataview';

	/**
	 * Data view config file format
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * Allowed characters pattern for the view name.
	 */
	public const VIEW_PATTERN = '(?!^\d+$)[\p{L}\p{N}\p{Z}\-\'\\/\\(\\)\\{\\}:.#~_,+!$]{1,100}';

	/**
	 * Stores user data view config.
	 */
	private $config;

	/**
	 * Get data view config.
	 *
	 * @return array data view config
	 */
	public function getConfig(): array
	{
		if (is_null($this->config)) {
			$this->config = $this->readConfig(self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
		}
		return $this->config;
	}

	/**
	 * Set data view config.
	 *
	 * @param array $config data view config
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setConfig(array $config): bool
	{
		$result = $this->writeConfig($config, self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
		if ($result === true) {
			$this->config = null;
		}
		return $result;
	}

	/**
	 * Get user data view config.
	 *
	 * @param string $org_id organization identifier
	 * @param string $user_id user identifier
	 * @return array user data view config
	 */
	public function getDataViewConfig(string $org_id, string $user_id): array
	{
		$view_config = [];
		$config = $this->getConfig();

		$uid = WebUserConfig::getOrgUserID($org_id, $user_id);
		if (key_exists($uid, $config)) {
			$view_config = $config[$uid];
		}
		if (is_array($view_config)) {
			foreach ($view_config as $view => $data) {
				foreach ($data as $name => $value) {
					parse_str($value, $result);
					$view_config[$view][$name] = $result;
				}
			}
		}
		return $view_config;
	}

	/**
	 * Set single user data view config.
	 *
	 * @param string $org_id organization identifier
	 * @param string $user_id user identifier
	 * @param array $view_config user data view configuration
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setDataViewConfig(string $org_id, string $user_id, array $view_config): bool
	{
		$config = $this->getConfig();
		foreach ($view_config as $view => $data) {
			foreach ($data as $name => $value) {
				$vw = http_build_query($value);
				$view_config[$view][$name] = $vw;
			}
		}
		$uid = WebUserConfig::getOrgUserID($org_id, $user_id);
		$config[$uid] = $view_config;
		return $this->setConfig($config);
	}

	/**
	 * Remove single user data view config.
	 *
	 * @param string $org_id organization identifier
	 * @param string $user_id user identifier
	 * @param string $view view name
	 * @param string $item item name (tab name)
	 * @return bool true if config removed successfully, otherwise false
	 */
	public function removeDataViewConfig(string $org_id, string $user_id, string $view, string $item): bool
	{
		$ret = false;
		$config = $this->getConfig();
		$uid = WebUserConfig::getOrgUserID($org_id, $user_id);
		if (isset($config[$uid][$view][$item])) {
			unset($config[$uid][$view][$item]);
			$ret = $this->setConfig($config);
		}
		return $ret;
	}

	/**
	 * Reassign data view config on rename user.
	 *
	 * @param string $prev_org_id previous organization identifier
	 * @param string $new_org_id new organization identifier
	 * @param string $prev_user_id previous user identifier
	 * @param string $new_user_id new user identifier
	 * @return bool true on success, otherwise false
	 */
	public function moveUserDataViewConfig(string $prev_org_id, string $new_org_id, string $prev_user_id, string $new_user_id): bool
	{
		$tag_config = [];
		$config = $this->getConfig();
		$prev_uid = WebUserConfig::getOrgUserID($prev_org_id, $prev_user_id);
		$new_uid = WebUserConfig::getOrgUserID($new_org_id, $new_user_id);
		if (isset($config[$prev_uid])) {
			$tag_config = $config[$prev_uid];
		}
		$ret = false;
		if (count($tag_config) > 0 && !key_exists($new_uid, $config)) {
			$config[$new_uid] = $tag_config;
			unset($config[$prev_uid]);
			$ret = $this->setConfig($config);
		}
		return $ret;
	}
}
