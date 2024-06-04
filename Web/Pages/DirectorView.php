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
 * Copyright (C) 2013-2021 Kern Sibbald
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

use Prado\Web\UI\TCommandEventParameter;
use Bacularis\Common\Modules\Params;
use Bacularis\Web\Modules\BaculumWebPage;

/**
 * Director view page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class DirectorView extends BaculumWebPage
{
	public const DIRECTOR_NAME = 'DirectorName';

	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->IsCallBack || $this->IsPostBack) {
			return;
		}
		$director = $this->Request->contains('director') ? $this->Request['director'] : null;
		$this->setDirectorName($director);
	}

	public function loadDirectorConfig($sender, $param)
	{
		$component_name = $this->getDirectorName();
		if (!is_null($component_name)) {
			$this->DirDirectorConfig->setComponentName($component_name);
			$this->DirDirectorConfig->setResourceName($component_name);
			$this->DirDirectorConfig->setLoadValues(true);
			$this->DirDirectorConfig->IsDirectiveCreated = false;
			$this->DirDirectorConfig->raiseEvent('OnDirectiveListLoad', $this, null);
			$host = $this->User->getDefaultAPIHost();
			$this->BulkApplyPatternsDirector->setHost($host);
		}
	}

	public function loadDirectorResourcesConfig($sender, $param)
	{
		$resource_type = $param->getCallbackParameter();
		$this->DirDirectorConfig->unloadDirectives();
		$component_name = $this->getDirectorName();
		if (!is_null($component_name) && !empty($resource_type)) {
			$this->DirectorResourcesConfig->setResourceType($resource_type);
			$this->DirectorResourcesConfig->setComponentName($component_name);
			$this->DirectorResourcesConfig->loadResourceListTable();
			$host = $this->User->getDefaultAPIHost();
			$this->BulkApplyPatternsDirector->setHost($host);
		} else {
			$this->DirectorResourcesConfig->showError(true);
		}
	}

	/**
	 * Set director name.
	 *
	 * @param mixed $client_name
	 */
	public function setDirectorName($client_name)
	{
		$this->setViewState(self::DIRECTOR_NAME, $client_name);
	}

	/**
	 * Get director name.
	 *
	 * @return string director name
	 */
	public function getDirectorName()
	{
		return $this->getViewState(self::DIRECTOR_NAME);
	}

	public function status($sender, $param)
	{
		$raw_status = $this->getModule('api')->get(
			[
				'directors',
				$this->getDirectorName(),
				'status'
			]
		)->output;
		$this->DirectorLog->Text = implode(\PHP_EOL, $raw_status);

		$query_str = '?output=json';
		$graph_status = $this->getModule('api')->get(
			[
				'directors',
				$this->getDirectorName(),
				'status',
				$query_str
			]
		);
		$client_status = [
			'header' => [],
			'scheduled' => [],
			'running' => [],
			'terminated' => [],
			'version' => Params::getComponentVersion($raw_status)
		];
		if ($graph_status->error === 0) {
			$client_status['header'] = count($graph_status->output->header) == 1 ? $graph_status->output->header[0] : [];
			$client_status['scheduled'] = $graph_status->output->scheduled;
			$client_status['running'] = $graph_status->output->running;
			$client_status['terminated'] = $graph_status->output->terminated;
		}
		$show = $this->getModule('api')->get(
			[
				'directors',
				$this->getDirectorName(),
				'show',
				$query_str
			]
		);
		if ($show->error === 0) {
			$client_status['show'] = $show->output;
		}

		$this->getCallbackClient()->callClientFunction('init_graphical_director_status', [$client_status]);
	}

	public function renameResource($sender, $param)
	{
		if ($param instanceof TCommandEventParameter) {
			$res = $param->getCommandParameter();
			if ($res['resource_type'] == 'Pool') {
				$this->getCallbackClient()->show('director_view_rename_resource');
			}
		}
	}
}
