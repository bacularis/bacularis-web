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
 * Copyright (C) 2013-2021 Kern Sibbald
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
 * Page category module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class PageCategory extends WebModule
{
	/**
	 * Pages allowed for user with native role 'normal'
	 */
	public const DASHBOARD = 'Dashboard';
	public const JOB_LIST = 'JobList';
	public const JOB_VIEW = 'JobView';
	public const CLIENT_LIST = 'ClientList';
	public const CLIENT_VIEW = 'ClientView';
	public const RESTORE_WIZARD = 'RestoreWizard';
	public const GRAPHS = 'Graphs';
	public const ACCOUNT_SETTINGS = 'AccountSettings';

	/**
	 * System pages - always allowed for authenticated users
	 */
	public const MONITOR = 'Monitor';
	public const BACULARIS_ERROR = 'BacularisError';
	public const SELECT_API_HOST = 'SelectAPIHost';

	/**
	 * Public pages - always allowed
	 */
	public const LOGIN_PAGE = 'LoginPage';
	public const OAUTH2_REDIRECT = 'OAuth2Redirect';

	/**
	 * Pages available under conditions.
	 */
	public const WEB_CONFIG_WIZARD = 'WebConfigWizard';

	/**
	 * Get page categories.
	 *
	 * @param mixed $with_sys_pub_pages
	 * @return array page categories
	 */
	public function getCategories($with_sys_pub_pages = true)
	{
		$pages = $this->getModule('url_manager')->getPages();
		//some pages are double because they are defined for access by name or id, so do unique()
		$pages = array_unique($pages);
		if (!$with_sys_pub_pages) {
			$system_pages = $this->getSystemCategories();
			$public_pages = $this->getPublicCategories();
			$pages = array_diff($pages, $system_pages, $public_pages);
		}
		return $pages;
	}

	public function isCategorySystem($category)
	{
		$system_cats = $this->getSystemCategories();
		return in_array($category, $system_cats);
	}

	private function getSystemCategories()
	{
		return [
			self::MONITOR,
			self::BACULARIS_ERROR,
			self::SELECT_API_HOST
		];
	}

	public function isCategoryPublic($category)
	{
		$public_cats = $this->getPublicCategories();
		return in_array($category, $public_cats);
	}

	private function getPublicCategories()
	{
		return [
			self::OAUTH2_REDIRECT,
			self::LOGIN_PAGE
		];
	}

	public function isCategoryConditional($category)
	{
		$cond_cats = $this->getConditionalCategories();
		return in_array($category, $cond_cats);
	}

	private function getConditionalCategories()
	{
		$cond_cats = [];
		$host_config = $this->getModule('host_config')->getConfig();
		$first_run = (count($host_config) == 0 || !key_exists(HostConfig::MAIN_CATALOG_HOST, $host_config));
		if ($first_run) {
			$cond_cats[] = self::WEB_CONFIG_WIZARD;
		}
		return $cond_cats;
	}
}
