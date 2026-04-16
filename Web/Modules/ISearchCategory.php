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
 * Search category interface.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Interface
 */
interface ISearchCategory
{
	/**
	 * Search for given keyword in clients.
	 *
	 * @param string $keyword keyword to find
	 * @return array search result or empty array
	 */
	public function search(string $keyword): array;

	/**
	 * Get CSS classes to display category icon.
	 *
	 * @result string icon CSS class(es)
	 */
	public function getIconCSS(): string;

	/**
	 * Get resource name property.
	 * For simple lists without properties, the result is empty string.
	 * For associative lists, the result is data key to get resource name.
	 *
	 * @return string property used to get resource name
	 */
	public function getResourceNameProp(): string;

	/**
	 * Get properties used for creating category properties.
	 * Category properties are displayed in category results.
	 * Property key is optional for simple lists.
	 *
	 * @param null|string $key current property key
	 * @return array category properties
	 */
	public function getProperties(?string $key = null): array;
}
