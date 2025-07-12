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

use Bacularis\Common\Modules\ConfigFileModule;
use Bacularis\Web\Modules\WebUserConfig;

/**
 * Manage tag assignments.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Config
 */
class TagAssignConfig extends ConfigFileModule
{
	/**
	 * Tag assignments config file path
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.Web.Config.tag_assign';

	/**
	 * Tag assignments config file format
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * Global tag assign section (available for all users).
	 */
	public const GLOBAL_SECTION = 'GLOBAL TAGS';

	/**
	 * Stores tags config.
	 */
	private $config;

	/**
	 * Get tag assignments config.
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
	 * Set tag assignments config.
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
	 * Get tag assignment config.
	 *
	 * @param string $org_id organization identifier
	 * @param string $user_id user identifier
	 * @param string $view view name
	 * @param bool $add_global if true, to results are added also global tags
	 * @return array tag assignment config
	 */
	public function getTagAssignConfig(string $org_id, string $user_id, string $view, bool $add_global = false): array
	{
		$tag_config = [];
		$config = $this->getConfig();

		$uid = WebUserConfig::getOrgUserID($org_id, $user_id);
		if (key_exists($uid, $config)) {
			$tag_config = $config[$uid];
		}
		if (is_array($tag_config)) {
			foreach ($tag_config as $view => $data) {
				foreach ($data as $id => $value) {
					parse_str($value, $result);
					$tag_config[$view][$id] = $result;
				}
			}
		}
		if ($add_global) {
			$global_tag_config = $this->getGlobalTagAssignConfig(
				$view
			);
			$tag_config = array_merge_recursive(
				$tag_config,
				$global_tag_config
			);
		}
		return $tag_config;
	}

	/**
	 * Get gloabl tag assignment config.
	 *
	 * @param string $view view name
	 * @return array tag assignment config
	 */
	public function getGlobalTagAssignConfig(string $view = ''): array
	{
		return $this->getTagAssignConfig(
			'',
			self::GLOBAL_SECTION,
			$view
		);
	}


	/**
	 * Set single tag assignment config.
	 *
	 * @param string $org_id organization identifier
	 * @param string $user_id user identifier
	 * @param string $view view name
	 * @param string $id data identifier value
	 * @param string $tag tag name
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setTagAssignConfig(string $org_id, string $user_id, string $view, string $id, string $tag): bool
	{
		$config = $this->getConfig();
		$uid = WebUserConfig::getOrgUserID($org_id, $user_id);
		if (!key_exists($uid, $config)) {
			// user does not have any tag yet at all
			$config[$uid] = [
				$view => [
					$id => [
						'tag' => []
					]
				]
			];
		} elseif (!key_exists($view, $config[$uid])) {
			// user does not have any tag for given view
			$config[$uid][$view] = [
				$id => [
					'tag' => []
				]
			];
		} elseif (!key_exists($id, $config[$uid][$view])) {
			// user does not have this tag defined for given view
			$config[$uid][$view][$id] = [
				'tag' => []
			];
		} else {
			// user have tag defined for given view
			parse_str($config[$uid][$view][$id], $result);
			$config[$uid][$view][$id] = $result;
		}

		if (!key_exists('tag', $config[$uid][$view][$id])) {
			// id not exists yet, initialize it
			$config[$uid][$view][$id]['tag'] = [];
		}

		$ret = true;
		if (!in_array($tag, $config[$uid][$view][$id]['tag'])) {
			// tag is not added yet to this id, assign it
			$config[$uid][$view][$id]['tag'][] = $tag;
			$config[$uid][$view][$id] = http_build_query(
				$config[$uid][$view][$id]
			);
			$ret = $this->setConfig($config);
		}
		return $ret;
	}

	/**
	 * Remove single tag assignment.
	 *
	 * @param string $org_id organization identifier
	 * @param string $user_id user identifier
	 * @param string $view view name
	 * @param string $id identifier value
	 * @param string $tag tag name
	 * @return bool true if config removed successfully, otherwise false
	 */
	public function removeTagAssignConfig(string $org_id, string $user_id, string $view, string $id, string $tag): bool
	{
		$ret = false;
		$config = $this->getConfig();
		$uid = WebUserConfig::getOrgUserID($org_id, $user_id);
		if (isset($config[$uid][$view][$id])) {
			parse_str($config[$uid][$view][$id], $result);
			$config[$uid][$view][$id] = $result;
			if (key_exists('tag', $config[$uid][$view][$id])) {
				$idx = array_search(
					$tag,
					$config[$uid][$view][$id]['tag']
				);
				if ($idx !== false) {
					array_splice(
						$config[$uid][$view][$id]['tag'],
						$idx,
						1
					);
				}
			}
			if (empty($config[$uid][$view][$id]['tag'])) {
				// no more tags for element, remove view element
				unset($config[$uid][$view][$id]);
			} else {
				// update element tag list
				$config[$uid][$view][$id] = http_build_query(
					$config[$uid][$view][$id]
				);
			}
			$ret = $this->setConfig($config);
		}
		return $ret;
	}

	/**
	 * Remove all tag assignments.
	 *
	 * @param string $org_id organization identifier
	 * @param string $user_id user identifier
	 * @param string $tag tag name
	 * @return bool true if config removed successfully, otherwise false
	 */
	public function removeAllTagAssignsConfig(string $org_id, string $user_id, string $tag): bool
	{
		$ret = false;
		$config = $this->getConfig();
		$uid = WebUserConfig::getOrgUserID($org_id, $user_id);
		if (!isset($config[$uid])) {
			// error: user does not have any tag
			return $ret;
		}
		foreach ($config[$uid] as $view => $props) {
			foreach ($props as $id => $values) {
				parse_str($values, $result);
				if (!isset($result['tag'])) {
					// this should not happen
					continue;
				}
				$idx = array_search($tag, $result['tag']);
				if ($idx === false) {
					// tag is not assigned in this view
					continue;
				}
				array_splice($result['tag'], $idx, 1);
				if (count($result['tag']) == 0) {
					// no more tags for element, remove view element
					unset($config[$uid][$view][$id]);
				} else {
					// tag(s) exist, write it
					$config[$uid][$view][$id] = http_build_query($result);
				}
			}
		}
		$ret = $this->setConfig($config);
		return $ret;
	}

	/**
	 * Reassign tag assign config on rename user.
	 *
	 * @param string $prev_org_id previous organization identifier
	 * @param string $new_org_id new organization identifier
	 * @param string $prev_user_id previous user identifier
	 * @param string $new_user_id new user identifier
	 * @return boolean true on success, otherwise false
	 */
	public function moveUserTagAssignsConfig(string $prev_org_id, string $new_org_id, string $prev_user_id, string $new_user_id): bool
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
