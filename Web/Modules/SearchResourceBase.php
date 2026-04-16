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
 * Search resource base module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
abstract class SearchResourceBase extends WebModule
{
	/**
	 * Check if user is allowed to search results.
	 *
	 * @param bool true if user is allowed, false otherwise
	 */
	protected function isUserAllowed(): bool
	{
		$page = $this->getResultPage();
		$users = $this->getModule('users');
		$is_page_allowed = $users->isPageAllowed($this->User, $page);
		return $is_page_allowed;
	}

	/**
	 * Prepare results to display in search box.
	 *
	 * @param array $search_result search results by keyword
	 * @return array search data ready to use in search box
	 */
	protected function prepareResult(array $search_result): array
	{
		$results = [];
		$properties = $this->getProperties();
		$resname_prop = $this->getResourceNameProp();
		$service = $this->getService();
		$page_name = $this->getResultPage();
		$page_params = [];
		for ($i = 0; $i < count($search_result); $i++) {
			$name = '';
			$key = '';
			if ($resname_prop == '*') {
				// Nested results with variable key name
				if (is_object($search_result[$i])) {
					$cfg = (array) $search_result[$i];
					$key = key($cfg);
					if (count($cfg) == 1 && is_object($cfg[$key]) && isset($cfg[$key]->{'Name'})) {
						// Bacula configuration resource
						$name = $cfg[$key]->{'Name'};
					}
				} elseif (is_array($search_result[$i])) {
					$key = key($search_result[$i]);
					$name = $search_result[$i]->{$key}->{'Name'};
				}
				if ($key) {
					$properties = $this->getProperties($key);
					$page_params = $this->getResultPageParam($key, $name);
				}
			} elseif ($resname_prop) {
				// key => value results
				if (is_object($search_result[$i])) {
					$name = $search_result[$i]->{$resname_prop};
				} elseif (is_array($search_result[$i])) {
					$name = $search_result[$i][$resname_prop];
				}
				if ($name) {
					$page_params = $this->getResultPageParam($name);
				}
			} else {
				// value only results
				$name = $search_result[$i];
				$page_params = $this->getResultPageParam($name);
			}
			$result = [
				'name' => $name,
				'icon_css' => $this->getIconCSS(),
				'page' => $service->constructUrl($page_name, $page_params),
				'values' => []
			];
			foreach ($properties as $id => $pname) {
				$value = '';
				if ($resname_prop == '*') {
					// Nested results with variable key name
					$cfg = [];
					if (is_object($search_result[$i])) {
						$cfg = (array) $search_result[$i];
					} elseif (is_array($search_result[$i])) {
						$cfg = $search_result[$i];
					}
					if ($id == '*') {
						$value = $key;
					} else {
						if (is_object($cfg[$key])) {
							$value = $cfg[$key]->{$id} ?? null;
						} elseif (is_array($cfg[$key])) {
							$value = $cfg[$key][$id] ?? null;
						}
					}
					if (is_null($value)) {
						// empty properties are not displayed
						continue;
					}
				} elseif ($resname_prop) {
					// key => value results
					if (is_object($search_result[$i])) {
						$value = $search_result[$i]->{$id};
					} elseif (is_array($search_result[$i])) {
						$value = $search_result[$i][$id];
					}
				}
				$result['values'][] = ['name' => $pname, 'value' => $value];
			}
			$results[] = $result;
		}
		return $results;
	}

	/**
	 * Get result page where user can see the result.
	 *
	 * @return string result page
	 */
	abstract public function getResultPage(): string;

	/**
	 * Get result page parameter where the item is passed.
	 *
	 * @param array $params page parameter values
	 * @return array result page parameter
	 */
	abstract public function getResultPageParam(...$params): array;
}
