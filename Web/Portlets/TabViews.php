<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2024 Marcin Haba
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

/**
 * Data tab views control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class TabViews extends Portlets
{
	private const DATA_VIEW_NAME = 'DataViewName';
	private const DATA_VIEW_DESC = 'DataViewDesc';
	private const VIEW_DATA_FUNCTION = 'ViewDataFunction';
	private const UPDATE_VIEW_FUNCTION = 'UpdateViewFunction';

	public function onPreRender($param): void
	{
		parent::onPreRender($param);
	}

	public function setViewName(string $name): void
	{
		$this->setViewState(self::DATA_VIEW_NAME, $name);
	}

	public function getViewName(): string
	{
		return $this->getViewState(self::DATA_VIEW_NAME, '');
	}

	public function getConfig(): array
	{
		$view_config = $this->getModule('dataview_config');
		$username = $this->User->getUsername();
		$views = $view_config->getDataViewConfig($username);
		$view_name = $this->getViewName();
		$config = [];
		if (key_exists($view_name, $views)) {
			$config = $views[$view_name];
		}
		return $config;
	}

	public function saveConfig($sender, $param): void
	{
		$view = $param->getCallbackParameter();
		$view_config = $this->getModule('dataview_config');
		$username = $this->User->getUsername();
		if (is_object($view)) {
			$view = json_decode(json_encode($view), true);
			$views = $view_config->getDataViewConfig($username);
			$name = key($view);
			$views[$this->ViewName][$name] = json_decode(json_encode($view[$name]), true);
			$view_config->setDataViewConfig($username, $views);
		}
	}

	public function removeConfig($sender, $param): void
	{
		$tab = $param->getCallbackParameter();
		$view_config = $this->getModule('dataview_config');
		$username = $this->User->getUsername();
		if (!empty($tab)) {
			$view_config->removeDataViewConfig($username, $this->ViewName, $tab);
		}
	}

	public function setDescription(array $desc): void
	{
		$this->setViewState(self::DATA_VIEW_DESC, $desc);
	}

	public function getDescription(): array
	{
		return $this->getViewState(self::DATA_VIEW_DESC, []);
	}

	public function setViewDataFunction(string $data): void
	{
		$this->setViewState(self::VIEW_DATA_FUNCTION, $data);
	}

	public function getViewDataFunction(): string
	{
		return $this->getViewState(self::VIEW_DATA_FUNCTION, '');
	}

	public function getUpdateViewFunction(): string
	{
		return $this->getViewState(self::UPDATE_VIEW_FUNCTION, '');
	}

	public function setUpdateViewFunction(string $data): void
	{
		$this->setViewState(self::UPDATE_VIEW_FUNCTION, $data);
	}
}
