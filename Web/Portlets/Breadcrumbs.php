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

use Prado\Prado;

/**
 * Extended breadcrumbs navigation.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class Breadcrumbs extends Portlets
{
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$page = $this->getPage();
		if ($page->IsPostBack || $page->IsCallBack) {
			return;
		}
		$data_nav = $this->getNavData();
		$this->CrumbsNav->DataSource = $this->getNavData();
		$this->CrumbsNav->dataBind();
		if (count($data_nav) == 1) {
			$this->CrumbsNav->Visible = false;
		}
	}

	/**
	 * Main method to collect and provide breadcrumbs data.
	 *
	 * @return array data to create breadcrumbs
	 */
	private function getNavData()
	{
		$nav_data = [];
		$page = $this->getPage();
		if (method_exists($page, 'getNavData')) {
			$nav_data = $page->getNavData();
		}
		$this->prepareNavData($nav_data);
		return $nav_data;
	}

	/**
	 * Prepare data to use in breadcrumbs navigation.
	 * Before creating breadcrumbs, data must be prepared.
	 *
	 * @param array $nav_data reference to breadcrumbs data structure
	 */
	private function prepareNavData(&$nav_data)
	{
		for ($i = 0; $i < count($nav_data); $i++) {
			if (!key_exists('page', $nav_data[$i])) {
				continue;
			}
			if (count($nav_data[$i]) == 1) {
				// page is the only field - this is parent page
				$page_path = 'Bacularis.Web.Pages.' . $nav_data[$i]['page'];
				$fpage = Prado::getPathOfNamespace($page_path, Prado::CLASS_FILE_EXT);
				if (!file_exists($fpage)) {
					continue;
				}
				require_once($fpage);
				$page = new $nav_data[$i]['page']();
				if (method_exists($page, 'getNavData')) {
					$ppage = Prado::createComponent($nav_data[$i]['page']);
					$data = $ppage->getNavData();
					$pdata = array_pop($data);
					$nav_data[$i] = array_merge($nav_data[$i], $pdata);
				}
			}
			$params = [];
			if (key_exists('params', $nav_data[$i])) {
				$params = $nav_data[$i]['params'];
			}
			if (key_exists('actions', $nav_data[$i])) {
				foreach ($nav_data[$i]['actions'] as &$action) {
					if (!key_exists('label', $action)) {
						continue;
					}
					// Translate labels
					$action['label'] = Prado::localize($action['label']);
				}
			}
			$service = $this->getApplication()->getService();
			$nav_data[$i]['label'] = Prado::localize($nav_data[$i]['label']);
			$nav_data[$i]['page_url'] = $service->constructUrl($nav_data[$i]['page'], $params);
		}
	}
}
