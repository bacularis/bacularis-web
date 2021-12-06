<?php
/*
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

Prado::using('System.Web.UI.ActiveControls.TActiveLabel');
Prado::using('System.Web.UI.ActiveControls.TActiveLinkButton');
Prado::using('System.Web.UI.ActiveControls.TCallback');
Prado::using('System.Web.UI.JuiControls.TJuiProgressbar');
Prado::using('Application.Web.Class.BaculumWebPage'); 

/**
 * Job history view page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 * @package Baculum Web
 */
class JobHistoryView extends BaculumWebPage {

	const IS_RUNNING = 'IsRunning';
	const JOBID = 'JobId';
	const JOB_NAME = 'JobName';
	const JOB_UNAME = 'JobUname';
	const JOB_LEVEL = 'JobLevel';
	const JOB_TYPE = 'JobType';
	const CLIENTID = 'ClientId';
	const JOB_INFO = 'JobInfo';
	const STORAGE_INFO = 'StorageInfo';

	const USE_CACHE = true;

	const SORT_ASC = 0;
	const SORT_DESC = 1;

	public $is_running = false;
	public $allow_graph_mode = false;
	public $allow_list_files_mode = false;
	private $no_graph_mode_types = array('M', 'D', 'C', 'c', 'g');
	private $no_graph_mode_verify_levels = array('O');
	private $list_files_types = array('B', 'C', 'V');
	private $list_files_mode_verify_levels = array('V');
	private $show_restore_types = array('B', 'C');

	public function onPreInit($param) {
		parent::onPreInit($param);
		$jobid = 0;
		if ($this->Request->contains('jobid')) {
			$jobid = intval($this->Request['jobid']);
		}
		$jobdata = $this->getModule('api')->get(
			array('jobs', $jobid), null, true, self::USE_CACHE
		);
		$jobdata = $jobdata->error === 0 ? $jobdata->output : null;
		$this->setJobId($jobdata->jobid);
		$this->setJobName($jobdata->name);
		$this->setJobUname($jobdata->job);
		$this->setJobType($jobdata->type);
		$this->setJobLevel($jobdata->level);
		$this->setClientId($jobdata->clientid);
		$this->is_running = $this->getModule('misc')->isJobRunning($jobdata->jobstatus);
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
	}

	public function onInit($param) {
		parent::onInit($param);
		$job_name = $this->getJobName();
		$this->RunJobModal->setJobId($this->getJobId());
		$this->RunJobModal->setJobName($job_name);
		$this->FileList->setJobId($this->getJobId());
		if ($this->IsCallBack || $this->IsPostBack) {
			return;
		}
		$this->setJobInfo($job_name);
	}

	public function onLoad($param) {
		parent::onLoad($param);
		if ($this->IsCallBack || $this->IsPostBack) {
			return;
		}
		$this->refreshJobLog(null, null);
	}

	public function runningJobStatus($sender, $param) {
		$running_job_status = $this->getRunningJobStatus($this->getClientId());
		$tabs = array(
			'joblog_subtab_text' => true,
			'status_running_job_subtab_graphical' => false,
			'jobfiles_subtab_text' => false
		);
		if ($this->allow_graph_mode) {
			$tabs['status_running_job_subtab_graphical'] = true;
		} elseif ($this->allow_list_files_mode) {
			$tabs['jobfiles_subtab_text'] = true;
		}
		$this->getCallbackClient()->callClientFunction('init_graphical_running_job_status', array($running_job_status, $tabs));
	}

	public function getRunningJobStatus($clientid) {
		$running_job_status = array(
			'header' => array(),
			'job' => array(),
			'is_running' => $this->is_running
		);
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
						if ($prop === 'jobid' && intval($val) == $jobid) {
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
	 * @return none
	 */
	public function setJobId($jobid) {
		$jobid = intval($jobid);
		$this->setViewState(self::JOBID, $jobid, 0);
	}

	/**
	 * Get jobid to run job again.
	 *
	 * @return integer jobid
	 */
	public function getJobId() {
		return $this->getViewState(self::JOBID, 0);
	}

	/**
	 * Set job clientid
	 *
	 * @return none
	 */
	public function setClientId($clientid) {
		$clientid = intval($clientid);
		$this->setViewState(self::CLIENTID, $clientid, 0);
	}

	/**
	 * Get client jobid.
	 *
	 * @return integer clientid
	 */
	public function getClientId() {
		return $this->getViewState(self::CLIENTID, 0);
	}

	/**
	 * Set job name to run job again.
	 *
	 * @return none;
	 */
	public function setJobName($job_name) {
		$this->setViewState(self::JOB_NAME, $job_name);
	}

	/**
	 * Get job name to run job again.
	 *
	 * @return string job name
	 */
	public function getJobName() {
		return $this->getViewState(self::JOB_NAME);
	}

	/**
	 * Set job uname.
	 *
	 * @return none;
	 */
	public function setJobUname($job_uname) {
		$this->setViewState(self::JOB_UNAME, $job_uname);
	}

	/**
	 * Get job uname.
	 *
	 * @return string job uname
	 */
	public function getJobUname() {
		return $this->getViewState(self::JOB_UNAME);
	}

	/**
	 * Set job type.
	 *
	 * @return none;
	 */
	public function setJobType($job_type) {
		$this->setViewState(self::JOB_TYPE, $job_type);
	}

	/**
	 * Get job type.
	 *
	 * @return string job type
	 */
	public function getJobType() {
		return $this->getViewState(self::JOB_TYPE);
	}

	/**
	 * Set job level.
	 *
	 * @return none;
	 */
	public function setJobLevel($job_level) {
		$this->setViewState(self::JOB_LEVEL, $job_level);
	}

	/**
	 * Get job level.
	 *
	 * @return string job level
	 */
	public function getJobLevel() {
		return $this->getViewState(self::JOB_LEVEL);
	}

	/**
	 * Set job information from show job output.
	 *
	 * @return none
	 */
	public function setJobInfo($job_name) {
		$job_show = $this->getModule('api')->get(
			array('jobs', 'show', '?name='. rawurlencode($job_name)),
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
	public function getJobInfo() {
		return $this->getViewState(self::JOB_INFO, []);
	}

	/**
	 * Set all storage information.
	 *
	 * @return none
	 */
	public function setStorageInfo() {
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
	public function getStorageInfo() {
		return $this->getViewState(self::STORAGE_INFO, []);
	}

	/**
	 * Reload job information.
	 *
	 * @param BaculaConfigDirectives $sender sender object
	 * @param mixed $param save event parameter
	 * @return none
	 */
	public function reloadJobInfo($sender, $param) {
		$job_name = $this->getJobName();
		$this->setJobInfo($job_name);
	}

	/**
	 * Refresh job log page and load latest logs.
	 *
	 * @param $sender TActiveLabel sender object
	 * @param $param TCallbackParameter parameter object
	 */
	public function refreshJobLog($sender, $param) {
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
			$this->RestoreBtn->Display =  $this->isShowRestoreBtn() ? 'Dynamic' : 'None';
		}
		if ($this->getJobLogOrder() === self::SORT_DESC) {
			$joblog = array_reverse($joblog);
		}
		$joblog = $this->getModule('log_parser')->parse($joblog);

		$this->JobLog->Text = implode(PHP_EOL, $joblog);
	}

	private function findLogMediaRequest($joblog) {
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

	private function isShowRestoreBtn() {
		$type = $this->getJobType();
		return in_array($type, $this->show_restore_types);
	}

	public function loadRunJobModal($sender, $param) {
		$this->RunJobModal->loadData();
	}

	public function loadJobConfig($sender, $param) {
		if (!empty($_SESSION['dir'])) {
			$this->JobConfig->setComponentName($_SESSION['dir']);
			$this->JobConfig->setResourceName($this->getJobName());
			$this->JobConfig->setLoadValues(true);
			$this->JobConfig->raiseEvent('OnDirectiveListLoad', $this, null);
			$this->FileSetConfig->unloadDirectives();
			$this->ScheduleConfig->unloadDirectives();
		}
	}

	public function loadFileSetConfig($sender, $param) {
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

	public function loadScheduleConfig($sender, $param) {
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
			}
		}
	}

	public function cancel($sender, $param) {
		$this->getModule('api')->set(array('jobs', $this->getJobId(), 'cancel'), array('a' => 'b'));
		$this->refreshJobLog(null, null);
	}

	public function delete($sender, $param) {
		$this->getModule('api')->remove(array('jobs', $this->getJobId()));
	}

	public function setJobLogOrder($order) {
		$order = TPropertyValue::ensureInteger($order);
		setcookie('log_order', $order, time()+60*60*24*365, '/'); // set cookie for one year
		$_COOKIE['log_order'] = $order;
	}

	public function getJobLogOrder() {
		return (key_exists('log_order', $_COOKIE) ? intval($_COOKIE['log_order']) : self::SORT_DESC);
	}

	public function changeJobLogOrder($sender, $param) {
		$order = $this->getJobLogOrder();
		if ($order === self::SORT_DESC) {
			$this->setJobLogOrder(self::SORT_ASC);
		} else {
			$this->setJobLogOrder(self::SORT_DESC);
		}
		$this->refreshJobLog(null, null);
	}
}
?>
