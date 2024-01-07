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
 * Copyright (C) 2013-2019 Kern Sibbald
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

namespace Bacularis\Web\Portlets;

/**
 * FileSet option renderer.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class FileSetOptionRenderer extends DirectiveRenderer
{
	private static $index = 0;
	private $item_count;

	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->item_count = $this->getParent()->getItems()->getCount();
	}

	public function render($writer)
	{
		if (self::$index % 30 === 0) {
			$writer->write('<h3 class="options">Options #' . ((self::$index / 30) + 1) . '</h3><hr style="margin: 7px 0;"/>');
		}
		self::$index++;

		if (self::$index === $this->item_count) {
			$this->resetIndex();
		}
		parent::render($writer);
	}

	public static function resetIndex()
	{
		self::$index = 0;
	}
}
