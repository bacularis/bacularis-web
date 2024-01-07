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

use Prado\Prado;

/**
 * Go to resource drop down list control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class GoToResource extends Portlets
{
	public const RESOURCE_TYPE = 'ResourceType';
	public const DIRECTOR = 'Director';

	/**
	 * Supported resource types.
	 */
	private $supported_resources = [
		'job' => ['page' => 'JobView', 'param' => 'job'],
		'client' => ['page' => 'ClientView', 'param' => 'clientid'],
		'storage' => ['page' => 'StorageView', 'param' => 'storageid'],
		'pool' => ['page' => 'PoolView', 'param' => 'poolid']
	];

	public function onPreRender($param)
	{
		if ($this->getPage()->IsPostBack || $this->getPage()->IsCallback) {
			return;
		}

		$this->loadResourceNames();
	}

	private function loadResourceNames()
	{
		$resource_type = strtolower($this->getResourceType());

		if (!key_exists($resource_type, $this->supported_resources)) {
			return;
		}

		$names = [];
		$api = $this->getModule('api');
		switch ($resource_type) {
			case 'job': {
				$ret = $api->get(['jobs', 'resnames']);
				if ($ret->error == 0) {
					$dir = $this->getDirector();
					if (property_exists($ret->output, $dir)) {
						natcasesort($ret->output->{$dir});
						$names = array_combine($ret->output->{$dir}, $ret->output->{$dir});
					}
				}
				break;
			}
			case 'client': {
				$ret = $api->get(['clients']);
				if ($ret->error == 0) {
					usort($ret->output, function ($a, $b) {
						return strnatcasecmp($a->name, $b->name);
					});
					for ($i = 0; $i < count($ret->output); $i++) {
						$names[$ret->output[$i]->clientid] = $ret->output[$i]->name;
					}
				}
				break;
			}
			case 'storage': {
				$ret = $api->get(['storages']);
				if ($ret->error == 0) {
					usort($ret->output, function ($a, $b) {
						return strnatcasecmp($a->name, $b->name);
					});
					for ($i = 0; $i < count($ret->output); $i++) {
						$names[$ret->output[$i]->storageid] = $ret->output[$i]->name;
					}
				}
				break;
			}
			case 'pool': {
				$ret = $api->get(['pools']);
				if ($ret->error == 0) {
					usort($ret->output, function ($a, $b) {
						return strnatcasecmp($a->name, $b->name);
					});
					for ($i = 0; $i < count($ret->output); $i++) {
						$names[$ret->output[$i]->poolid] = $ret->output[$i]->name;
					}
				}
				break;
			}
		}
		$res = [' ' => Prado::localize('Go to resource')];
		foreach ($names as $param => $name) {
			$url = $this->getService()->constructUrl(
				$this->supported_resources[$resource_type]['page'],
				[$this->supported_resources[$resource_type]['param'] => $param]
			);
			$res[$url] = $name;
		}
		$this->ResourceNames->DataSource = $res;
		$this->ResourceNames->dataBind();
	}

	public function getResourceType()
	{
		return $this->getViewState(self::RESOURCE_TYPE, '');
	}

	public function setResourceType($type)
	{
		$this->setViewState(self::RESOURCE_TYPE, $type);
	}

	public function getDirector()
	{
		return $this->getViewState(self::DIRECTOR, '');
	}

	public function setDirector($director)
	{
		$this->setViewState(self::DIRECTOR, $director);
	}
}
