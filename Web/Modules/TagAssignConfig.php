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
	 * @param string $username user name
	 * @param string $view view name
	 * @param boolean $add_global if true, to results are added also global tags
	 * @return array tag assignment config
	 */
	public function getTagAssignConfig(string $username, string $view, bool $add_global = false): array
	{
		$tag_config = [];
		$config = $this->getConfig();
		if (key_exists($username, $config)) {
			$tag_config = $config[$username];
		}
		if (is_array($tag_config)) {
			foreach($tag_config as $view => $data) {
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
			self::GLOBAL_SECTION,
			$view
		);
	}


	/**
	 * Set single tag assignment config.
	 *
	 * @param string $username user name
	 * @param string $view view name
	 * @param string $id data identifier value
	 * @param string $tag tag name
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setTagAssignConfig(string $username, string $view, string $id, string $tag): bool
	{
		$config = $this->getConfig();
		if (!key_exists($username, $config)) {
			// user does not have any tag yet at all
			$config[$username] = [
				$view => [
					$id => [
						'tag' => []
					]
				]
			];
		} elseif (!key_exists($view, $config[$username])) {
			// user does not have any tag for given view
			$config[$username][$view] = [
				$id => [
					'tag' => []
				]
			];
		} elseif (!key_exists($id, $config[$username][$view])) {
			// user does not have this tag defined for given view
			$config[$username][$view][$id] = [
				'tag' => []
			];
		} else {
			// user have tag defined for given view
			parse_str($config[$username][$view][$id], $result);
			$config[$username][$view][$id] = $result;
		}

		if (!key_exists('tag', $config[$username][$view][$id])) {
			// id not exists yet, initialize it
			$config[$username][$view][$id]['tag'] = [];
		}

		$ret = true;
		if (!in_array($tag, $config[$username][$view][$id]['tag'])) {
			// tag is not added yet to this id, assign it
			$config[$username][$view][$id]['tag'][] = $tag;
			$config[$username][$view][$id] = http_build_query(
				$config[$username][$view][$id]
			);
			$ret = $this->setConfig($config);
		}
		return $ret;
	}

	/**
	 * Remove single tag assignment.
	 *
	 * @param string $username user name
	 * @param string $view view name
	 * @param string $id identifier value
	 * @param string $tag tag name
	 * @return bool true if config removed successfully, otherwise false
	 */
	public function removeTagAssignConfig(string $username, string $view, string $id, string $tag): bool
	{
		$ret = false;
		$config = $this->getConfig();
		if (isset($config[$username][$view][$id])) {
			parse_str($config[$username][$view][$id], $result);
			$config[$username][$view][$id] = $result;
			if (key_exists('tag', $config[$username][$view][$id])) {
				$idx = array_search(
					$tag,
					$config[$username][$view][$id]['tag']
				);
				if ($idx !== false) {
					array_splice(
						$config[$username][$view][$id]['tag'],
						$idx,
						1
					);
				}
			}
			if (empty($config[$username][$view][$id]['tag'])) {
				// no more tags for element, remove view element
				unset($config[$username][$view][$id]);
			} else {
				// update element tag list
				$config[$username][$view][$id] = http_build_query(
					$config[$username][$view][$id]
				);
			}
			$ret = $this->setConfig($config);
		}
		return $ret;
	}

	/**
	 * Remove all tag assignments.
	 *
	 * @param string $username user name
	 * @param string $tag tag name
	 * @return bool true if config removed successfully, otherwise false
	 */
	public function removeAllTagAssignsConfig(string $username, string $tag): bool
	{
		$ret = false;
		$config = $this->getConfig();
		if (!isset($config[$username])) {
			// error: user does not have any tag
			return $ret;
		}
		foreach ($config[$username] as $view => $props) {
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
					unset($config[$username][$view][$id]);
				} else {
					// tag(s) exist, write it
					$config[$username][$view][$id] = http_build_query($result);
				}
			}
		}
		$ret = $this->setConfig($config);
		return $ret;
	}
}
