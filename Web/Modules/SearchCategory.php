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

use Prado\Prado;

/**
 * Resource search categories module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class SearchCategory extends WebModule
{
	/**
	 * All supported search categories.
	 */
	public const CATEGORY_JOB = 'Job';
	public const CATEGORY_CLIENT = 'Client';
	public const CATEGORY_STORAGE = 'Storage';
	public const CATEGORY_POOL = 'Pool';
	public const CATEGORY_VOLUME = 'Volume';
	public const CATEGORY_DIR_CONFIG = 'DIR config';

	/**
	 * All categories registered to use.
	 * The 'default' property means if category should be enabled by default.
	 */
	private const ALL_CATEGORIES = [
		self::CATEGORY_JOB => ['class' => \Bacularis\Web\Modules\SearchJobs::class, 'default' => true],
		self::CATEGORY_CLIENT => ['class' => \Bacularis\Web\Modules\SearchClients::class, 'default' => true],
		self::CATEGORY_STORAGE => ['class' => \Bacularis\Web\Modules\SearchStorages::class, 'default' => true],
		self::CATEGORY_POOL => ['class' => \Bacularis\Web\Modules\SearchPools::class, 'default' => true],
		self::CATEGORY_VOLUME => ['class' => \Bacularis\Web\Modules\SearchVolumes::class, 'default' => true],
		self::CATEGORY_DIR_CONFIG => ['class' => \Bacularis\Web\Modules\SearchDirConfig::class, 'default' => false]
	];

	/**
	 * Get category engines.
	 *
	 * @param bool $all decides if get return all categories or only enabled/activated.
	 * @return array list with category engine instances
	 */
	public function getCategoryEngines(bool $all = false): array
	{
		$engines = [];
		foreach (self::ALL_CATEGORIES as $category => $props) {
			$engine = $this->getCategoryEngine($category, $all);
			if (!$engines) {
				// category disabled or not existing
				continue;
			}
			$engines[] = $engine;
		}
		return $engines;
	}

	/**
	 * Get category engine instance.
	 *
	 * @param string $category search category to instantiate
	 * @param bool $force if true, returns engine even if category is not enabled in config
	 * @return null|object engine instance or null on error
	 */
	public function getCategoryEngine(string $category, bool $force = false): ?object
	{
		$engine = null;
		if (!key_exists($category, self::ALL_CATEGORIES)) {
			// category does not exist
			return $engine;
		}

		$web_config = $this->getModule('web_config');
		$config = $web_config->getConfig('baculum');
		$enabled = !key_exists('enable_resource_search', $config) || $config['enable_resource_search'] == 1;
		$categories = $config['resource_search_categories'] ?? array_keys(self::ALL_CATEGORIES);
		if (!$enabled || (!in_array($category, $categories) && !$force)) {
			// function disabled, category disabled or category is not default
			return $engine;
		}

		$cls = self::ALL_CATEGORIES[$category]['class'];
		$engine = Prado::createComponent($cls);
		return $engine;
	}

	/**
	 * Get categories which are enabled by default.
	 *
	 * @return array default categories with properties
	 */
	public static function getDefaultCategories(): array
	{
		return array_filter(
			self::ALL_CATEGORIES,
			fn ($key) => self::ALL_CATEGORIES[$key]['default'],
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * Get all category names.
	 *
	 * @return array all category list
	 */
	public static function getAllCategories(): array
	{
		return array_keys(self::ALL_CATEGORIES);
	}

	/**
	 * Get search categories currently enabled in search settings.
	 *
	 * @return array category list
	 */
	public function getCategories(): array
	{
		$web_config = $this->getModule('web_config');
		$config = $web_config->getConfig('baculum');
		$rcats = $config['resource_search_categories'] ?? array_keys(self::ALL_CATEGORIES);
		$categories = [];
		for ($i = 0; $i < count($rcats); $i++) {
			if (!key_exists($rcats[$i], self::ALL_CATEGORIES)) {
				continue;
			}
			$props = self::ALL_CATEGORIES[$rcats[$i]];
			$props['name'] = $rcats[$i];
			$obj = Prado::createComponent($props['class']);
			$props['icon_css'] = $obj->getIconCSS();
			$categories[$rcats[$i]] = $props;
		}
		return $categories;
	}
}
