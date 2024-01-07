<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2024 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * Bacula(R) - The Network Backup Solution
 * Baculum   - Bacula web interface
 *
 * Copyright (C) 2013-2020 Kern Sibbald
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

namespace Bacularis\Web\Modules;

/**
 * Web user roles module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class WebUserRoles extends WebModule
{
	/**
	 * Pre-defined roles.
	 */
	public const ADMIN = 'admin';
	public const NORMAL = 'normal';

	/**
	 * Single role properties.
	 */
	private $role_prop = [
		'role',
		'long_name',
		'description',
		'enabled',
		'resources'
	];

	/**
	 * Get pre-defined roles with available resources for them.
	 *
	 * @return array pre-defined roles
	 */
	public function getPreDefinedRoles()
	{
		$roles = [];

		// Admin user resources
		$res = $this->getModule('page_category')->getCategories(false);
		$admin_res = array_values($res);
		$roles[self::ADMIN] = array_combine($this->role_prop, [
			self::ADMIN,
			'Administrator',
			'Role with full access',
			'1',
			implode(',', $admin_res)
		]);

		// Normal user resources
		$res = [
			PageCategory::DASHBOARD,
			PageCategory::JOB_LIST,
			PageCategory::JOB_VIEW,
			PageCategory::CLIENT_LIST,
			PageCategory::CLIENT_VIEW,
			PageCategory::RESTORE_WIZARD,
			PageCategory::GRAPHS,
			PageCategory::ACCOUNT_SETTINGS
		];
		$roles[self::NORMAL] = array_combine($this->role_prop, [
			self::NORMAL,
			'Normal user',
			'Role with limitted access',
			'1',
			implode(',', $res)
		]);
		return $roles;
	}

	/**
	 * Check if a role is predefined.
	 *
	 * @param string $role nazwa roli
	 * @return bool true if role is predefined, otherwise false
	 */
	public function isRolePreDefined($role)
	{
		$roles = $this->getPreDefinedRoles();
		return key_exists($role, $roles);
	}

	/**
	 * Get all roles pre-defined and defined in config.
	 * Pre-defined are merged together with defined.
	 *
	 * @return array all available roles
	 */
	public function getRoles()
	{
		$roles = $this->getModule('role_config')->getConfig();
		return array_merge($this->getPreDefinedRoles(), $roles);
	}

	/**
	 * Get single role config.
	 *
	 * @param string $role role name
	 * @return array role config or empty array if role doesn't exist
	 */
	public function getRole($role)
	{
		$ret = [];
		$roles = $this->getRoles();
		if (key_exists($role, $roles)) {
			$roles[$role]['role'] = $role;
			$ret = $roles[$role];
		}
		return $ret;
	}

	/**
	 * Get all roles by specific page path.
	 * They are roles that have page defined in allowed resources.
	 *
	 * @param string $page_path page path
	 * @param mixed $with_disabled
	 * @return array roles defined for a page
	 */
	public function getRolesByPagePath($page_path, $with_disabled = true)
	{
		$roles = [];
		$all_roles = $this->getRoles();
		foreach ($all_roles as $role => $prop) {
			$rs = explode(',', $prop['resources']);
			$enabled = (bool) $prop['enabled'];
			if (($enabled || $with_disabled) && in_array($page_path, $rs)) {
				$roles[] = $role;
			}
		}
		return $roles;
	}

	/**
	 * Get all pages for specific role.
	 * @param mixed $role
	 */
	public function getPagesByRole($role)
	{
		$pages = [];
		$role_cfg = $this->getRole($role);
		return explode(',', $role_cfg['resources']);
	}
}
