<?php
/*
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

Prado::using('Application.:Web.Class.HostConfig');
Prado::using('Application.:Web.Class.WebModule');

/**
 * Page category module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 * @package Baculum Web
 */
class PageCategory extends WebModule {

	/**
	 * Pages allowed for user with native role 'normal'
	 */
	const DASHBOARD = 'Dashboard';
	const JOB_HISTORY_LIST = 'JobHistoryList';
	const JOB_HISTORY_VIEW = 'JobHistoryView';
	const JOB_LIST = 'JobList';
	const JOB_VIEW = 'JobView';
	const CLIENT_LIST = 'ClientList';
	const CLIENT_VIEW = 'ClientView';
	const RESTORE_WIZARD = 'RestoreWizard';
	const GRAPHS = 'Graphs';

	/**
	 * System pages - always allowed for authenticated users
	 */
	const MONITOR = 'Monitor';
	const BACULUM_ERROR = 'BaculumError';
	const SELECT_API_HOST = 'SelectAPIHost';

	/**
	 * Public pages - always allowed
	 */
	const LOGIN_PAGE = 'LoginPage';
	const OAUTH2_REDIRECT = 'OAuth2Redirect';

	/**
	 * Pages available under conditions.
	 */
	const WEB_CONFIG_WIZARD = 'WebConfigWizard';

	/**
	 * Get page categories.
	 *
	 * @return array page categories
	 */
	public function getCategories($with_sys_pub_pages = true) {
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

	public function isCategorySystem($category) {
		$system_cats = $this->getSystemCategories();
		return in_array($category, $system_cats);
	}

	private function getSystemCategories() {
		return [
			self::MONITOR,
			self::BACULUM_ERROR,
			self::SELECT_API_HOST
		];
	}

	public function isCategoryPublic($category) {
		$public_cats = $this->getPublicCategories();
		return in_array($category, $public_cats);
	}

	private function getPublicCategories() {
		return [
			self::OAUTH2_REDIRECT,
			self::LOGIN_PAGE
		];
	}

	public function isCategoryConditional($category) {
		$cond_cats = $this->getConditionalCategories();
		return in_array($category, $cond_cats);
	}

	private function getConditionalCategories() {
		$cond_cats = [];
		$host_config = $this->getModule('host_config')->getConfig();
		$first_run = (count($host_config) == 0 || !key_exists(HostConfig::MAIN_CATALOG_HOST, $host_config));
		if ($first_run) {
			$cond_cats[] = self::WEB_CONFIG_WIZARD;
		}
		return $cond_cats;
	}
}
?>
