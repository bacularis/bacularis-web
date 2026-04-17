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
 * Search engine for searching in Bacula Director configuration resource.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class SearchDirConfig extends SearchResourceBase implements ISearchCategory
{
	/**
	 * Search for given keyword.
	 *
	 * @param string $keyword keyword to find
	 * @return array search result or empty array
	 */
	public function search(string $keyword): array
	{
		$search_result = [];
		if (!$this->isUserAllowed()) {
			// User is not allowed to view this resource
			return $search_result;
		}
		$params = [
			'search' => $keyword
		];
		$query = '?' . http_build_query($params);
		$api = $this->getModule('api');
		$host = $this->User->getDefaultAPIHost();
		$result = $api->get(
			['config', 'dir', $query],
			$host
		);
		if ($result->error == 0 && is_array($result->output)) {
			$search_result = $result->output;
		}
		$res = $this->prepareResult($search_result);
		return $res;
	}

	/**
	 * Get CSS classes to display category icon.
	 *
	 * @result string icon CSS class(es)
	 */
	public function getIconCSS(): string
	{
		return 'fa-solid fa-wrench fa-fw';
	}

	/**
	 * Get resource name property.
	 * For simple lists without properties, the result is empty string.
	 * For associative lists, the result is data key to get resource name.
	 *
	 * @return string property used to get resource name
	 */
	public function getResourceNameProp(): string
	{
		return '*';
	}

	/**
	 * Get properties used for creating category properties.
	 * Category properties are displayed in category results.
	 * Property key is optional for simple lists.
	 *
	 * @param null|string $key current property key
	 * @return array category properties
	 */
	public function getProperties(?string $key = null): array
	{
		$props = [];
		switch ($key) {
			case 'Director': $props = ['DirPort' => 'DirPort'];
				break;
			case 'Client': $props = ['Address' => 'Address'];
				break;
			case 'Job': $props = ['Type' => 'Job Type'];
				break;
			case 'Storage': $props = ['Address' => 'Address', 'MediaType' => 'Media Type'];
				break;
			case 'Pool': $props = ['AutoPrune' => 'AutoPrune', 'Recycle' => 'Recycle'];
				break;
			case 'Schedule':
			case 'Fileset':
			case 'Messages':
			case 'JobDefs':
			case 'Catalog':
			case 'Console':
			case 'Statistics': $props = [];
				break;
		}
		// Description is common for all resources.
		$props = array_merge(['*' => 'Resource'], $props);
		$props['Description'] = 'Description';
		return $props;
	}

	/**
	 * Get result page where user can see the result.
	 *
	 * @return string result page
	 */
	public function getResultPage(): string
	{
		return 'DirectorView';
	}

	/**
	 * Get result page parameter where the item is passed.
	 *
	 * @param array $params page parameter values
	 * @return array result page parameter
	 */
	public function getResultPageParam(...$params): array
	{
		$sess = $this->getApplication()->getSession();
		$director = $sess->itemAt('director');
		$pparams = [
			'director' => $director
		];
		array_unshift($params, 'Director'); // component_type
		$dest = array_combine(
			['component_type', 'resource_type', 'resource_name'],
			$params
		);
		return array_merge($pparams, $dest);
	}
}
