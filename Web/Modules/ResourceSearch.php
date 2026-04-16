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

/**
 * Resource search module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class ResourceSearch extends WebModule
{
	/**
	 * Decides if by default resource search is enabled.
	 */
	public const DEF_ENABLED = 1;

	/**
	 * Search for resources in given category.
	 *
	 * @param string $category search category
	 * @param string $keyword keyword to find
	 * @return array search result or empty array
	 */
	public function searchByCategory(string $category, string $keyword): array
	{
		$result = [];
		$search_category = $this->getModule('search_category');
		$engine = $search_category->getCategoryEngine($category);
		if ($engine) {
			$result = $engine->search($keyword);
		}
		return $result;
	}
}
