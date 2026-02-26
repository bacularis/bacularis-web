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
 * Page header control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class PageHeader extends Portlets
{
	private const ICON = 'Icon';
	private const TITLE = 'Title';
	private const ITEM_NAME = 'ItemName';
	private const SUB_ITEM_NAME = 'SubItemName';
	private const CSS_CLASS = 'CssClass';

	public function setIcon($icon)
	{
		$this->setViewState(self::ICON, $icon);
	}

	public function getIcon()
	{
		return $this->getViewState(self::ICON, '');
	}

	public function setTitle($title)
	{
		$this->setViewState(self::TITLE, $title);
	}

	public function getTitle()
	{
		return $this->getViewState(self::TITLE, '');
	}

	public function setItemName($item_name)
	{
		$this->setViewState(self::ITEM_NAME, $item_name);
	}

	public function getItemName()
	{
		return $this->getViewState(self::ITEM_NAME, '');
	}

	public function setSubItemName($sub_item_name)
	{
		$this->setViewState(self::SUB_ITEM_NAME, $sub_item_name);
	}

	public function getSubItemName()
	{
		return $this->getViewState(self::SUB_ITEM_NAME, '');
	}

	public function setCssClass($css_class)
	{
		$this->setViewState(self::CSS_CLASS, $css_class);
	}

	public function getCssClass()
	{
		return $this->getViewState(self::CSS_CLASS, '');
	}

}
