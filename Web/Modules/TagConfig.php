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

namespace Bacularis\Web\Modules;

use Bacularis\Common\Modules\ConfigFileModule;
use Bacularis\Web\Modules\WebUserConfig;

/**
 * Manage tags configuration.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Config
 */
class TagConfig extends ConfigFileModule
{
	/**
	 * Tags config file path
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.Web.Config.tags';

	/**
	 * Tags config file format
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * Allowed characters pattern for tag name.
	 */
	public const TAG_PATTERN = '[a-zA-Z0-9:.\-_ ]+';

	/**
	 * Global tag section (available for all users).
	 */
	public const GLOBAL_SECTION = 'GLOBAL TAGS';

	/**
	 * Tag accessibility.
	 *  - local - tag available only for you
	 *  - global - tag available for everybody
	 */
	public const ACCESSIBILITY_LOCAL = 'local';
	public const ACCESSIBILITY_GLOBAL = 'global';

	/**
	 * Tag severity with the following importance:
	 *  - 5 - most important
	 *  ...
	 *  - 1 - less important
	 */
	public const TAG_SEVERITY = [
		'5' => ['name' => 'critical', 'value' => 5],
		'4' => ['name' => 'major', 'value' => 4],
		'3' => ['name' => 'moderate', 'value' => 3],
		'2' => ['name' => 'minor', 'value' => 2],
		'1' => ['name' => 'trivial', 'value' => 1]
	];

	/**
	 * Tag view name.
	 */
	public const TAG_VIEW_NAME = 'tag';

	/**
	 * Defines default value for enabling global tags.
	 * true - enabled, false - disabled
	 */
	public const DEF_GLOBAL_TAG_ENABLED = true;

	/**
	 * Tag colors.
	 * - name - color name
	 * - bg - main color (background)
	 * - fg - text color (foreground)
	 */
	public const TAG_COLORS = [
		'red' => ['name' => 'red', 'bg' => '#f44336', 'fg' => 'white'],                 // red
		'pink' => ['name' => 'pink', 'bg' => '#e91e63', 'fg' => 'white'],               // pink
		'purple' => ['name' => 'purple', 'bg' => '#9c27b0', 'fg' => 'white'],           // purple
		'deep-purple' => ['name' => 'deep-purple', 'bg' => '#673ab7', 'fg' => 'white'], // deep-purple
		'indigo' => ['name' => 'indigo', 'bg' => '#3f51b5', 'fg' => 'white'],           // indigo
		'blue' => ['name' => 'blue', 'bg' => '#2196f3', 'fg' => 'white'],               // blue
		'light-blue' => ['name' => 'light-blue', 'bg' => '#87ceeb', 'fg' => 'black'],   // light-blue
		'cyan' => ['name' => 'cyan', 'bg' => '#00bcd4', 'fg' => 'black'],               // cyan
		'aqua' => ['name' => 'aqua', 'bg' => '#00ffff', 'fg' => 'black'],               // aqua
		'teal' => ['name' => 'teal', 'bg' => '#009688', 'fg' => 'white'],               // teal
		'green' => ['name' => 'green', 'bg' => '#4CAF50', 'fg' => 'white'],             // green
		'light-green' => ['name' => 'light-green', 'bg' => '#8bc34a', 'fg' => 'black'], // light-green
		'lime' => ['name' => 'lime', 'bg' => '#cddc39', 'fg' => 'black'],               // lime
		'sand' => ['name' => 'sand', 'bg' => '#fdf5e6', 'fg' => 'black'],               // sand
		'khaki' => ['name' => 'khaki', 'bg' => '#f0e68c', 'fg' => 'black'],             // khaki
		'yellow' => ['name' => 'yellow', 'bg' => '#ffeb3b', 'fg' => 'black'],           // yellow
		'amber' => ['name' => 'amber', 'bg' => '#ffc107', 'fg' => 'black'],             // amber
		'orange' => ['name' => 'orange', 'bg' => '#ff9800', 'fg' => 'black'],           // orange
		'deep-orange' => ['name' => 'deep-orange', 'bg' => '#ff5722', 'fg' => 'white'], // deep-orange
		'blue-gray' => ['name' => 'blue-gray', 'bg' => '#607d8b', 'fg' => 'white'],     // blue-gray
		'brown' => ['name' => 'brown', 'bg' => '#795548', 'fg' => 'white'],             // brown
		'light-gray' => ['name' => 'light-gray', 'bg' => '#f1f1f1', 'fg' => 'black'],   // light-gray
		'gray' => ['name' => 'gray', 'bg' => '#9e9e9e', 'fg' => 'black'],               // gray
		'dark-gray' => ['name' => 'dark-gray', 'bg' => '#616161', 'fg' => 'white'],     // dark-gray
		'pale-red' => ['name' => 'pale-red', 'bg' => '#ffdddd', 'fg' => 'black'],       // pale-red
		'pale-yellow' => ['name' => 'pale-yellow', 'bg' => '#ffffcc', 'fg' => 'black'], // pale-yellow
		'pale-green' => ['name' => 'pale-green', 'bg' => '#ddffdd', 'fg' => 'black'],   // pale-green
		'pale-blue' => ['name' => 'pale-blue', 'bg' => '#ddffff', 'fg' => 'black']      // pale-blue
	];

	/**
	 * Stores tags config.
	 */
	private $config;

	/**
	 * Get tags config.
	 *
	 * @return array tags config
	 */
	public function getConfig(): array
	{
		if (is_null($this->config)) {
			$this->config = $this->readConfig(self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
		}
		return $this->config;
	}

	/**
	 * Set tags config.
	 *
	 * @param array $config tags config
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
	 * Get user tags config.
	 *
	 * @param string $org_id organization identifier
	 * @param string $user_id user identifier
	 * @param string $tag tag name
	 * @param bool $add_global if true, to results are added also global tags
	 * @return array user tags config
	 */
	public function getTagConfig(string $org_id, string $user_id, string $tag = '', bool $add_global = false): array
	{
		$tag_config = [];
		$config = $this->getConfig();
		$uid = WebUserConfig::getOrgUserID($org_id, $user_id);
		if (isset($config[$uid][self::TAG_VIEW_NAME])) {
			$tag_config = $config[$uid][self::TAG_VIEW_NAME];
		}
		if (is_array($tag_config)) {
			$access = ($uid == self::GLOBAL_SECTION) ? self::ACCESSIBILITY_GLOBAL : self::ACCESSIBILITY_LOCAL;
			foreach ($tag_config as $tag_name => $value) {
				parse_str($value, $result);
				$result['access'] = $access;
				$tag_config[$tag_name] = $result;
			}
		}
		if ($add_global) {
			$global_tags = $this->getGlobalTagConfig();
			$tag_config = array_merge($global_tags, $tag_config);
		}
		$result = [];
		if (!empty($tag)) {
			if (key_exists($tag, $tag_config)) {
				$result = $tag_config[$tag];
				$result['tag'] = $tag;
			}
		} else {
			$result = $tag_config;
		}
		return $result;
	}

	/**
	 * Get global tags config.
	 *
	 * @return array global tags config
	 */
	public function getGlobalTagConfig(): array
	{
		return $this->getTagConfig('', self::GLOBAL_SECTION);
	}

	/**
	 * Set single user tags config.
	 *
	 * @param string $org_id organization identifier
	 * @param string $user_id user identifier
	 * @param array $tag_config user tags configuration
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setTagConfig(string $org_id, string $user_id, array $tag_config): bool
	{
		$config = $this->getConfig();
		foreach ($tag_config as $tag => $value) {
			$vw = http_build_query($value);
			$tag_config[$tag] = $vw;
		}
		$uid = WebUserConfig::getOrgUserID($org_id, $user_id);
		if (!isset($config[$uid][self::TAG_VIEW_NAME])) {
			$config[$uid] = [
				self::TAG_VIEW_NAME => []
			];
		}
		$config[$uid][self::TAG_VIEW_NAME] = array_merge(
			$config[$uid][self::TAG_VIEW_NAME],
			$tag_config
		);
		return $this->setConfig($config);
	}

	/**
	 * Set global tags config.
	 *
	 * @param array $tag_config user tags configuration
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setGlobalTagConfig(array $tag_config): bool
	{
		return $this->setTagConfig(
			'',
			self::GLOBAL_SECTION,
			$tag_config
		);
	}

	/**
	 * Remove single user tags config.
	 *
	 * @param string $org_id organization identifier
	 * @param string $user_id user identifier
	 * @param string $tag tag name
	 * @return bool true if config removed successfully, otherwise false
	 */
	public function removeTagConfig(string $org_id, string $user_id, string $tag): bool
	{
		$ret = false;
		$config = $this->getConfig();
		$uid = WebUserConfig::getOrgUserID($org_id, $user_id);
		if (isset($config[$uid]['tag'][$tag])) {
			unset($config[$uid]['tag'][$tag]);
			$ret = $this->setConfig($config);
		}
		return $ret;
	}

	/**
	 * Remove global tags config.
	 *
	 * @param string $view view name
	 * @param string $tag tag name
	 * @return bool true if config removed successfully, otherwise false
	 */
	public function removeGlobalTagConfig(string $view, string $tag): bool
	{
		return $this->removeTagConfig(
			'',
			self::GLOBAL_SECTION,
			$view,
			$tag
		);
	}

	/**
	 * Reassign tag config on rename user.
	 *
	 * @param string $prev_org_id previous organization identifier
	 * @param string $new_org_id new organization identifier
	 * @param string $prev_user_id previous user identifier
	 * @param string $new_user_id new user identifier
	 * @return bool true on success, otherwise false
	 */
	public function moveUserTagConfig(string $prev_org_id, string $new_org_id, string $prev_user_id, string $new_user_id): bool
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
