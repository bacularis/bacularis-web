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

use Prado\Web\UI\ActiveControls\TCallbackEventParameter;

/**
 * Resource search field control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class ResourceSearchField extends Portlets
{
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$page = $this->getPage();
		if ($page->IsPostBack || $page->IsCallBack) {
			return;
		}
		$this->loadCategoryList();
	}

	/**
	 * Load list of categories in search box.
	 */
	public function loadCategoryList(): void
	{
		$search_category = $this->getModule('search_category');
		$categories = $search_category->getCategories();
		$this->SearchCategories->DataSource = array_values($categories);
		$this->SearchCategories->dataBind();
	}

	/**
	 * Search for keyword in given category.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param event parameter
	 */
	public function searchResource($sender, $param): void
	{
		$data = $param->getCallbackParameter();
		if (!is_array($data)) {
			return;
		}
		[$category, $keyword] = $data;

		$resource_search = $this->getModule('resource_search');
		$result = $resource_search->searchByCategory($category, $keyword);
		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction(
			'oBSearchField.search_cb',
			[$category, $result]
		);
	}
}
