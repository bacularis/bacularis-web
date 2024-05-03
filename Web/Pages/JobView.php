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
 * Copyright (C) 2013-2020 Kern Sibbald
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

use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Web\UI\ActiveControls\TActiveLabel;
use Bacularis\Common\Modules\Errors\ConnectionError;
use Bacularis\Web\Modules\BaculumWebPage;

/**
 * Job view page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class JobView extends BaculumWebPage
{
	public const IS_RUNNING = 'IsRunning';
	public const JOBID = 'JobId';
	public const JOB_NAME = 'JobName';
	public const JOB_UNAME = 'JobUname';
	public const JOB_LEVEL = 'JobLevel';
	public const JOB_TYPE = 'JobType';
	public const PREV_JOBID = 'PrevJobId';
	public const NEXT_JOBID = 'NextJobId';
	public const CLIENTID = 'ClientId';
	public const JOB_INFO = 'JobInfo';
	public const STORAGE_INFO = 'StorageInfo';

	public const USE_CACHE = true;

	public const SORT_ASC = 0;
	public const SORT_DESC = 1;

	public $jobdata = [];
	public $is_running = false;
	public $allow_report_summary = false;
	public $allow_graph_mode = false;
	public $allow_list_files_mode = false;
	private $no_graph_mode_types = ['M', 'D', 'C', 'c', 'g'];
	private $no_graph_mode_verify_levels = ['O'];
	private $list_files_types = ['B', 'C', 'V'];
	private $list_files_mode_verify_levels = ['V'];
	private $show_restore_types = ['B', 'C'];

	public function onPreInit($param)
	{
		parent::onPreInit($param);
		$job_name = '';
		$jobdata = [];
		if ($this->Request->contains('jobid')) {
			$jobid = (int) ($this->Request['jobid']);
			$jobdata = $this->getModule('api')->get(
				['jobs', $jobid],
				null,
				true,
				self::USE_CACHE
			);
			$jobdata = $jobdata->error === 0 ? $jobdata->output : null;
			$this->setJobId($jobdata->jobid);
			$this->setJobUname($jobdata->job);
			$this->setJobType($jobdata->type);
			if (property_exists($jobdata, 'prev_jobid')) {
				$this->setPrevJobId($jobdata->prev_jobid);
			}
			if (property_exists($jobdata, 'next_jobid')) {
				$this->setNextJobId($jobdata->next_jobid);
			}
			$this->setJobLevel($jobdata->level);
			$this->setClientId($jobdata->clientid);
			$this->is_running = $this->getModule('misc')->isJobRunning($jobdata->jobstatus);
			$this->allow_report_summary = !$this->is_running;
			$this->allow_graph_mode = ($this->is_running && !in_array($jobdata->type, $this->no_graph_mode_types));
			$this->allow_list_files_mode = (!$this->is_running && in_array($jobdata->type, $this->list_files_types));
			if ($jobdata->type === 'V') {
				// Verify job requires special treating here
				if (in_array($jobdata->level, $this->no_graph_mode_verify_levels)) {
					$this->allow_graph_mode = false;
				}
				if (!in_array($jobdata->level, $this->list_files_mode_verify_levels)) {
					$this->allow_list_files_mode = false;
				}
			}
			$job_name = $jobdata->name;
		}
		if (empty($job_name) && $this->Request->contains('job')) {
			$job_name = $this->Request['job'];
		}
		$this->setJobName($job_name);
		$this->jobdata = $jobdata;
		if ($this->IsCallBack) {
			$this->getCallbackClient()->callClientFunction(
				'oJobReportSummary.update',
				[$jobdata]
			);
		}
	}

	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->IsCallBack || $this->IsPostBack) {
			return;
		}
		$job_name = $this->getJobName();
		$jobid = $this->getJobId();
		if ($jobid > 0) {
			$this->FileList->setJobId($jobid);
		}
		$this->RunJobModal->setJobName($job_name);
		$this->Schedules->setJob($job_name);
		$this->Schedules->setDays(90);
		$this->setJobInfo($job_name);
	}

	public function onLoad($param)
	{
		parent::onLoad($param);
		if ($this->IsCallBack || $this->IsPostBack) {
			return;
		}
		$this->refreshJobLog(null, null);
	}

	public function loadSchedules($sender, $param)
	{
		$this->Schedules->loadSchedules();
	}

	public function runningJobStatus($sender, $param)
	{
		$running_job_status = $this->getRunningJobStatus($this->getClientId());
		$tabs = [
			'joblog_subtab_text' => true,
			'status_running_job_subtab_graphical' => false,
			'jobfiles_subtab_text' => false,
			'job_report_summary_subtab_text' => false
		];
		if ($this->allow_graph_mode) {
			$tabs['status_running_job_subtab_graphical'] = true;
		} elseif ($this->allow_list_files_mode) {
			$tabs['jobfiles_subtab_text'] = true;
		}
		if ($this->allow_report_summary) {
			$tabs['job_report_summary_subtab_text'] = true;
		}
		$this->getCallbackClient()->callClientFunction(
			'init_graphical_running_job_status',
			[$running_job_status, $tabs]
		);
	}

	public function getRunningJobStatus($clientid)
	{
		$running_job_status = [
			'header' => [],
			'job' => [],
			'is_running' => $this->is_running
		];
		if ($this->is_running) {
			$query_str = '?output=json&type=header';
			$graph_status = $this->getModule('api')->get(
				['clients', $clientid, 'status', $query_str]
			);

			if ($graph_status->error === 0) {
				$running_job_status['header'] = $graph_status->output;
			}

			$query_str = '?output=json&type=running';
			$graph_status = $this->getModule('api')->get(
				['clients', $clientid, 'status', $query_str]
			);
			if ($graph_status->error === 0) {
				$jobid = $this->getJobId();
				for ($i = 0; $i < count($graph_status->output); $i++) {
					foreach ($graph_status->output[$i] as $key => $val) {
						$prop = strtolower($key);
						if ($prop === 'jobid' && (int) $val == $jobid) {
							$running_job_status['job'] = $graph_status->output[$i];
							break 2;
						}
					}
				}
			}
		}
		return $running_job_status;
	}

	/**
	 * Set jobid to run job again.
	 *
	 * @param mixed $jobid
	 */
	public function setJobId($jobid)
	{
		$jobid = (int) $jobid;
		$this->setViewState(self::JOBID, $jobid, 0);
	}

	/**
	 * Get jobid to run job again.
	 *
	 * @return int jobid
	 */
	public function getJobId()
	{
		return $this->getViewState(self::JOBID, 0);
	}

	/**
	 * Set previous jobid.
	 *
	 * @param mixed $jobid
	 */
	public function setPrevJobId($jobid)
	{
		$jobid = (int) $jobid;
		$this->setViewState(self::PREV_JOBID, $jobid, 0);
	}

	/**
	 * Get previous jobid.
	 *
	 * @return int jobid
	 */
	public function getPrevJobId()
	{
		return $this->getViewState(self::PREV_JOBID, 0);
	}

	/**
	 * Set next jobid.
	 *
	 * @param mixed $jobid
	 */
	public function setNextJobId($jobid)
	{
		$jobid = (int) $jobid;
		$this->setViewState(self::NEXT_JOBID, $jobid, 0);
	}

	/**
	 * Get next jobid.
	 *
	 * @return int jobid
	 */
	public function getNextJobId()
	{
		return $this->getViewState(self::NEXT_JOBID, 0);
	}

	/**
	 * Set job clientid
	 *
	 * @param mixed $clientid
	 */
	public function setClientId($clientid)
	{
		$clientid = (int) $clientid;
		$this->setViewState(self::CLIENTID, $clientid, 0);
	}

	/**
	 * Get client jobid.
	 *
	 * @return int clientid
	 */
	public function getClientId()
	{
		return $this->getViewState(self::CLIENTID, 0);
	}

	/**
	 * Set job name to run job again.
	 *
	 * @param mixed $job_name
	 */
	public function setJobName($job_name)
	{
		$this->setViewState(self::JOB_NAME, $job_name);
	}

	/**
	 * Get job name to run job again.
	 *
	 * @return string job name
	 */
	public function getJobName()
	{
		return $this->getViewState(self::JOB_NAME);
	}

	/**
	 * Set job uname.
	 *
	 * @param mixed $job_uname
	 */
	public function setJobUname($job_uname)
	{
		$this->setViewState(self::JOB_UNAME, $job_uname);
	}

	/**
	 * Get job uname.
	 *
	 * @return string job uname
	 */
	public function getJobUname()
	{
		return $this->getViewState(self::JOB_UNAME);
	}

	/**
	 * Set job type.
	 *
	 * @param mixed $job_type
	 */
	public function setJobType($job_type)
	{
		$this->setViewState(self::JOB_TYPE, $job_type);
	}

	/**
	 * Get job type.
	 *
	 * @return string job type
	 */
	public function getJobType()
	{
		return $this->getViewState(self::JOB_TYPE);
	}

	/**
	 * Set job level.
	 *
	 * @param mixed $job_level
	 */
	public function setJobLevel($job_level)
	{
		$this->setViewState(self::JOB_LEVEL, $job_level);
	}

	/**
	 * Get job level.
	 *
	 * @return string job level
	 */
	public function getJobLevel()
	{
		return $this->getViewState(self::JOB_LEVEL);
	}

	/**
	 * Set job information from show job output.
	 *
	 * @param mixed $job_name
	 */
	public function setJobInfo($job_name)
	{
		$job_show = $this->getModule('api')->get(
			['jobs', 'show', '?name=' . rawurlencode($job_name)],
			null,
			true,
			false
		);
		if ($job_show->error == 0) {
			$job_info = $this->getModule('job_info')->parseResourceDirectives($job_show->output);
			$this->setViewState(self::JOB_INFO, $job_info);
		}
	}

	/**
	 * Get job information.
	 *
	 * @return array job information
	 */
	public function getJobInfo()
	{
		return $this->getViewState(self::JOB_INFO, []);
	}

	/**
	 * Set all storage information.
	 *
	 */
	public function setStorageInfo()
	{
		$storages_show = $this->getModule('api')->get(
			['storages', 'show', '?output=json'],
			null,
			true,
			false
		);
		if ($storages_show->error == 0) {
			$this->setViewState(self::STORAGE_INFO, $storages_show->output);
		}
	}

	/**
	 * Get storage information.
	 *
	 * @return array storage information
	 */
	public function getStorageInfo()
	{
		return $this->getViewState(self::STORAGE_INFO, []);
	}

	/**
	 * Reload job information.
	 *
	 * @param BaculaConfigDirectives $sender sender object
	 * @param mixed $param save event parameter
	 */
	public function reloadJobInfo($sender, $param)
	{
		$job_name = $this->getJobName();
		$this->setJobInfo($job_name);
	}

	/**
	 * Refresh job log page and load latest logs.
	 *
	 * @param $sender TActiveLabel sender object
	 * @param $param TCallbackParameter parameter object
	 */
	public function refreshJobLog($sender, $param)
	{
		if ($this->getJobId() == 0) {
			return;
		}
		$params = ['joblog', $this->getJobId()];

		// add time to log if defiend in configuration
		if (key_exists('time_in_job_log', $this->web_config['baculum'])) {
			$query_params = [
				'show_time' => $this->web_config['baculum']['time_in_job_log']
			];
			$params[] = '?' . http_build_query($query_params);
		}
		$log = $this->getModule('api')->get($params);

		$joblog = [];
		if (!is_array($log->output) || count($log->output) == 0) {
			$msg = Prado::localize("Output for selected job is not available yet or you do not have enabled logging job logs to the catalog database.\n\nTo watch job log you need to add to the job Messages resource the following directive:\n\nCatalog = all, !debug, !skipped, !saved");
			$joblog = [$msg];
		} else {
			$joblog = $log->output;

			if ($this->is_running) {
				// search for media requests to display warning
				$this->findLogMediaRequest($joblog);
			} else {
				$this->getCallbackClient()->callClientFunction(
					'oRunningJobStatus.show_warning',
					[false]
				);
			}
		}
		if ($this->is_running) {
			$this->RunningIcon->Display = 'Dynamic';
			$this->FinishedIcon->Display = 'None';
			$this->CancelBtn->Display = 'Dynamic';
			$this->DeleteBtn->Display = 'None';
			$this->RestoreBtn->Display = 'None';
		} else {
			$this->FinishedIcon->Display = 'Dynamic';
			$this->RunningIcon->Display = 'None';
			$this->CancelBtn->Display = 'None';
			$this->DeleteBtn->Display = 'Dynamic';
			$this->RestoreBtn->Display = $this->isShowRestoreBtn() ? 'Dynamic' : 'None';
		}
		if ($this->getJobLogOrder() === self::SORT_DESC) {
			$joblog = array_reverse($joblog);
		}
		$joblog = $this->getModule('log_parser')->parse($joblog);

		$this->JobLog->Text = implode(PHP_EOL, $joblog);
	}

	private function findLogMediaRequest($joblog)
	{
		$waiting = false;
		$needed = [
			'storage' => '',
			'volume' => '',
			'pool' => '',
			'mediatype' => '',
			'write' => true,
			'waiting' => $waiting
		];
		$joblog = array_reverse($joblog);
		for ($i = 0; $i < count($joblog); $i++) {
			if (preg_match('/Cannot find any appendable volumes/i', $joblog[$i]) === 1) {
				$waiting = true;
			} elseif (preg_match('/Please mount read Volume "(?P<volume>[a-zA-Z0-9:.\-_]+)" for:/i', $joblog[$i], $match) === 1) {
				$needed['volume'] = $match['volume'];
				$needed['write'] = false;
				$waiting = true;
			} elseif (preg_match('/Please mount append Volume "(?P<volume>[a-zA-Z0-9:.\-_]+)" or label a new one for:/i', $joblog[$i], $match) === 1) {
				$needed['volume'] = $match['volume'];
				$waiting = true;
			} elseif (preg_match('/(New volume "[a-zA-Z0-9:.\-_]+" mounted on device|Ready to append to end of Volume|Ready to read from volume|Recycled volume.+all previous data lost|Wrote label to prelabeled Volume)/i', $joblog[$i]) === 1) {
				// stop checking, new volume provided
				break;
			}
			if ($waiting) {
				$log = explode(PHP_EOL, $joblog[$i]);
				for ($j = 0; $j < count($log); $j++) {
					if (preg_match('/\s+(?P<key>Storage|Pool|Media type):\s*(?P<value>.+)/i', $log[$j], $match) === 1) {
						$key = strtolower($match['key']);
						$key = str_replace(' ', '', $key);
						$needed[$key] = $match['value'];
					}
				}
				if ($needed['storage'] && $needed['pool'] && $needed['mediatype']) {
					// everything what needed, so stop
					break;
				}
			}
		}
		if ($waiting && $needed['write']) {
			if (!empty($needed['pool'])) {
				// Set pool for labeling
				$this->LabelMedia->setPool($needed['pool']);
			}
			if (!empty($needed['storage'])) {
				// Set storage for labeling
				if (count($this->StorageInfo) == 0) {
					$this->setStorageInfo();
				}
				$storage = '';
				if (preg_match('/^"(?P<storage>[a-zA-Z0-9:.\-_ ]+)"/', $needed['storage'], $match) === 1) {
					$storage = $match['storage'];
				}
				for ($i = 0; $i < count($this->StorageInfo); $i++) {
					if ($this->StorageInfo[$i]->devicename == $storage) {
						// Set storage for labeling
						$this->LabelMedia->setStorage($this->StorageInfo[$i]->name);
						break;
					}
				}
			}
		}
		$needed['waiting'] = $waiting;

		$this->getCallbackClient()->callClientFunction(
			'oRunningJobStatus.set_media_request_msg',
			[$needed]
		);
	}

	private function isShowRestoreBtn()
	{
		$type = $this->getJobType();
		return in_array($type, $this->show_restore_types);
	}

	public function loadRunJobModal($sender, $param)
	{
		$this->RunJobModal->loadData();
	}

	public function loadJobConfig($sender, $param)
	{
		if (!empty($_SESSION['dir'])) {
			$this->JobConfig->setComponentName($_SESSION['dir']);
			$this->JobConfig->setResourceName($this->getJobName());
			$this->JobConfig->setLoadValues(true);
			$this->JobConfig->raiseEvent('OnDirectiveListLoad', $this, null);
			$this->FileSetConfig->unloadDirectives();
			$this->ScheduleConfig->unloadDirectives();
		}
	}

	public function loadFileSetConfig($sender, $param)
	{
		if (!empty($_SESSION['dir'])) {
			$job_info = $this->getJobInfo();
			if (key_exists('fileset', $job_info)) {
				$this->FileSetConfig->setComponentName($_SESSION['dir']);
				$this->FileSetConfig->setResourceName($job_info['fileset']['name']);
				$this->FileSetConfig->setLoadValues(true);
				$this->FileSetConfig->raiseEvent('OnDirectiveListLoad', $this, null);
				$this->JobConfig->unloadDirectives();
				$this->ScheduleConfig->unloadDirectives();
			}
		}
	}

	public function loadScheduleConfig($sender, $param)
	{
		if (!empty($_SESSION['dir'])) {
			$job_info = $this->getJobInfo();
			if (key_exists('schedule', $job_info)) {
				$this->ScheduleConfig->setComponentName($_SESSION['dir']);
				$this->ScheduleConfig->setResourceName($job_info['schedule']['name']);
				$this->ScheduleConfig->setLoadValues(true);
				$this->ScheduleConfig->raiseEvent('OnDirectiveListLoad', $this, null);
				$this->JobConfig->unloadDirectives();
				$this->FileSetConfig->unloadDirectives();
			} else {
				$this->ScheduleConfig->unloadDirectives();
				$this->ScheduleConfig->showLoader(false);
			}
		}
	}

	public function cancel($sender, $param)
	{
		$this->getModule('api')->set(['jobs', $this->getJobId(), 'cancel'], ['a' => 'b']);
		$this->refreshJobLog(null, null);
	}

	public function delete($sender, $param)
	{
		$this->getModule('api')->remove(['jobs', $this->getJobId()]);
	}

	public function setJobLogOrder($order)
	{
		$order = TPropertyValue::ensureInteger($order);
		setcookie('log_order', $order, time() + 60 * 60 * 24 * 365, '/'); // set cookie for one year
		$_COOKIE['log_order'] = $order;
	}

	public function getJobLogOrder()
	{
		return (key_exists('log_order', $_COOKIE) ? (int) ($_COOKIE['log_order']) : self::SORT_DESC);
	}

	public function changeJobLogOrder($sender, $param)
	{
		$order = $this->getJobLogOrder();
		if ($order === self::SORT_DESC) {
			$this->setJobLogOrder(self::SORT_ASC);
		} else {
			$this->setJobLogOrder(self::SORT_DESC);
		}
		$this->refreshJobLog(null, null);
	}

	/**
	 * Run job file difference.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackParameter $param event parameter
	 */
	public function jobFileDiff($sender, $param)
	{
		$data = $param->getCallbackParameter();
		if (!is_object($data) || !property_exists($data, 'a') || !property_exists($data, 'b')) {
			return;
		}
		$a = (int) $data->a;
		$b = (int) $data->b;
		$api = $this->getModule('api');
		$result = $api->get(
			['jobs', $data->name, $a, $b, 'diff', '?method=' . $data->method]
		);
		if ($result->error === 0) {
			$data = $this->prepareJobFileData(
				(array) $result->output,
				$data->method
			);
			$this->getCallbackClient()->callClientFunction(
				'oJobFileDiff.update_table',
				[$data]
			);
		} else {
			$emsg = $result->output;
			if ($result->error === ConnectionError::ERROR_CONNECTION_TO_HOST_PROBLEM) {
				$emsg = Prado::localize('Error. Please check: 1) if Bacula Director version is 11.0 or greater. This function is supported by Directors >= 11.0. 2) if you have enough memory for PHP to display many records (see memory_limit option in php.ini file) 3) if there are too many files in jobs to display (e.g. 1 million files or more).');
			}
			$this->getCallbackClient()->callClientFunction(
				'oJobFileDiff.set_error',
				[$emsg]
			);
		}
	}

	/**
	 * Prepare job file difference data to use in template.
	 *
	 * @param array $data data to prepare
	 * @param string $method file difference method
	 * @return array data ready to use
	 */
	private function prepareJobFileData(array $data, string $method): array
	{
		$methods_allow_multi_jobids = [
			'a_until_b',
			'b_until_a'
		];
		$is_mjobs = in_array($method, $methods_allow_multi_jobids);
		$res = [];
		foreach ($data as $file => $prop) {
			$pr = [];
			for ($i = 0; $i < count($prop); $i++) {
				$prop[$i]->file = $file;
				$pr[$prop[$i]->type] = $prop[$i];
				if ($is_mjobs) {
					$res[] = $pr;
				}
			}
			if (!$is_mjobs) {
				$res[] = $pr;
			}
		}
		return $res;
	}
}
