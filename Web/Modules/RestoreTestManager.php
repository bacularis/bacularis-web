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

use Bacularis\Common\Modules\AuditLog;
use Bacularis\Common\Modules\RestoreDestinationCapability;
use Bacularis\Common\Modules\RestoreVerification;
use Bacularis\Web\Modules\VerificationRuleConfig;
use Prado\Prado;

/**
 * Manage restore tests.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Config
 */
class RestoreTestManager extends WebModule
{
	/**
	 * Prefix for restore test identifier.
	 * Each restore test id is in form: rt-XXXXXXXXXXX
	 */
	private const RESTORE_TEST_PREFIX = 'rt-';

	/**
	 * Restore script path.
	 * This script runs restore tests.
	 */
	private const RESTORE_TEST_SCRIPT = 'Bacularis.Common.Bin.restore';

	/**
	 * Main method to perform restore and to do tests on it.
	 *
	 * @param string $api_host API host name used to start tests
	 * @param string $restore_test restore test name to run
	 * @return bool true on success, otherwise false
	 */
	public function prepareTest(string $api_host, string $restore_test): bool
	{
		// Prepare a new test identifier
		$test_id = $this->generateTestId();

		// Get restore test configuration
		$rtest_mod = $this->getModule('restore_test_config');
		$rt_config = $rtest_mod->getRestoreTestConfig($restore_test);

		// Get restore destination configuration
		$rdest_config = $this->getModule('restore_destination_config');
		$rd_config = $rdest_config->getRestoreDestinationConfig(
			$rt_config['restore_destination']
		);

		if (!$this->isRestoreTestSupported($rt_config)) {
			return false;
		}

		if (!$this->isRestoreDestinationSupported($rd_config)) {
			return false;
		}

		if (!$this->isVerificationMethodSupported($rt_config)) {
			return false;
		}

		// Get elementary jobids to restore
		[
			'jobids' => $jobids,
			'base_job' => $base_job
		] = $this->getRestoreJobIDs($api_host, $rt_config);

		if (!$this->areJobsForRestoreValid($base_job, $jobids, $rt_config)) {
			return false;
		}

		if ($rd_config['enabled'] != 1) {
			$audit = $this->getModule('audit');
			$audit->audit(
				AuditLog::TYPE_WARNING,
				AuditLog::CATEGORY_ACTION,
				sprintf(
					'Attempt to use disabled restore destination: "%s" for restore test.',
					$rt_config['restore_destination']
				)
			);
			return false;
		}

		// This is destination path for restore
		$restore_dest_path = $this->getRestoreWherePath(
			$test_id,
			$rd_config,
			$rt_config,
			$base_job
		);

		if ($rt_config['verification_method'] == RestoreTestConfig::RESTORE_VERIFICATION_METHOD_RULES) {
			// Prepare destination test environment to perform tests
			$result = $this->sendRestoreTestPlan(
				$test_id,
				$rt_config,
				$rd_config,
				$jobids,
				$restore_dest_path
			);
			if (!$result) {
				return false;
			}
		}

		// Initialize restore test session
		$result = $this->startRestoreSession(
			$test_id,
			$jobids,
			$base_job,
			$rt_config
		);
		if (!$result['status']) {
			return false;
		}
		$sid = $result['sid'];

		// We have everything already, run restore and do tests
		$result = $this->finishRestoreSession(
			$test_id,
			$rt_config,
			$rd_config,
			$restore_dest_path,
			$sid,
			$base_job
		);
		return $result;
	}

	/**
	 * Check if base job and jobids selected for restore are valid
	 * to perform restore and verification.
	 *
	 * @param null|object $base_job base job object
	 * @param array $jobids job identifiers
	 * @param string $rt_config restore test configuration
	 * @return bool true if job is valid, otherwise false
	 */
	private function areJobsForRestoreValid(?object $base_job, array $jobids, array $rt_config): bool
	{
		$valid = true;
		$emsg = '';
		if (!$base_job) {
			$valid = false;
			$emsg = 'There is no backup base jobid to restore tests and verification.';
		} elseif (!$jobids) {
			$valid = false;
			$emsg = 'There is no elementary backup jobids to restore tests and verification.';
		} elseif ($base_job->purgedfiles == 1) {
			$valid = false;
			$emsg = sprintf(
				'Selected job %s (JobId %d) for restore and verification does not have file records in the Bacula Catalog. They are pruned (purgedfiles=1). There is nothing to restore and verify.',
				$base_job->name,
				$base_job->jobid
			);
		} elseif ($rt_config['verification_method'] == RestoreTestConfig::RESTORE_VERIFICATION_METHOD_VERIFY_JOB) {
			if ($base_job->jobfiles == 0) {
				$valid = false;
				$emsg = sprintf(
					'Selected job %s (JobId %d) contains zero files backed up. There is nothing to restore and verify.',
					$base_job->name,
					$base_job->jobid
				);
			}
		}

		if ($emsg) {
			$audit = $this->getModule('audit');
			$audit->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_ACTION,
				$emsg
			);
		}
		return $valid;
	}

	/**
	 * Check if restore test is possible to run.
	 *
	 * @param string $rt_config restore test configuration
	 */
	private function isRestoreTestSupported(array $rt_config): bool
	{
		$emsg = '';
		$result = ($rt_config['enabled'] == 1);
		if (!$result) {
			$emsg = sprintf(
				'Attempt to run disabled restore test: "%s"',
				$rt_config['name']
			);
		}
		if ($emsg) {
			$audit = $this->getModule('audit');
			$audit->audit(
				AuditLog::TYPE_WARNING,
				AuditLog::CATEGORY_ACTION,
				$emsg
			);
		}
		return $result;
	}

	/**
	 * Check if restore destination is possible to use.
	 *
	 * @param string $rd_config restore destination configuration
	 */
	private function isRestoreDestinationSupported(array $rd_config): bool
	{
		$emsg = '';
		$result = ($rd_config['enabled'] == 1);
		if (!$result) {
			$emsg = sprintf(
				'Attempt to run disabled restore destination: "%s"',
				$rd_config['name']
			);
		}
		if ($emsg) {
			$audit = $this->getModule('audit');
			$audit->audit(
				AuditLog::TYPE_WARNING,
				AuditLog::CATEGORY_ACTION,
				$emsg
			);
		}
		return $result;
	}

	/**
	 * Check if verification method is suported and possible to run.
	 *
	 * @param string $rt_config restore test configuration
	 */
	private function isVerificationMethodSupported(array $rt_config): bool
	{
		$rdest_config = $this->getModule('restore_destination_config');
		$rd_config = $rdest_config->getRestoreDestinationConfig(
			$rt_config['restore_destination']
		);
		$emsg = '';
		$is_supported = false;
		if ($rt_config['verification_method'] == RestoreTestConfig::RESTORE_VERIFICATION_METHOD_VERIFY_JOB) {
			// Native Bacula verification method
			if (isset($rd_config['capabilities'])) {
				$is_supported = in_array(
					RestoreDestinationCapability::NATIVE_VERIFY,
					$rd_config['capabilities']
				);
			}
			if (!$is_supported) {
				$emsg = sprintf(
					'The "%s" destination does not support the Bacula Native verification method. Please check the restore destination capabilities.',
					$rt_config['restore_destination']
				);
			}
		} else {
			// Other verification methods
			$is_supported = true;
		}
		if ($emsg) {
			$audit = $this->getModule('audit');
			$audit->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_ACTION,
				$emsg
			);
		}
		return $is_supported;
	}

	/**
	 * Get elementary jobids to do restore.
	 * The jobids are selected using specific criteria defiend in restoretest configuration.
	 *
	 * @param string $api_host API host name used to initialize test
	 * @param string $rt_config restore test configuration
	 * @return array elementary jobids and base job object selected to restore or empty array on problems
	 */
	private function getRestoreJobIDs(string $api_host, array $rt_config): array
	{
		$jobids = [];
		$base_job = null;
		if ($rt_config['source_type'] == RestoreTestConfig::SOURCE_TYPE_BACKUP_JOB) {
			// Latest job selection
			$job_name = $rt_config['source_backup_job'];
			if ($rt_config['source_selection'] == RestoreTestConfig::SOURCE_SELECTION_LATEST_SUCCESSFUL) {
				// Latest successful job
				['jobids' => $jobids, 'base_job' => $base_job] = $this->getJobIDsByName(
					$api_host,
					$job_name
				);
			} elseif ($rt_config['source_selection'] == RestoreTestConfig::SOURCE_SELECTION_LATEST_SUCCESSFUL_TIME_RANGE) {
				// Latest successful job in time range
				['jobids' => $jobids, 'base_job' => $base_job] = $this->getJobIDsByTimeRange(
					$api_host,
					$job_name,
					(int) $rt_config['latest_successful_date_from'],
					(int) $rt_config['latest_successful_date_to']
				);
			} elseif ($rt_config['source_selection'] == RestoreTestConfig::SOURCE_SELECTION_JOB_LEVELS) {
				// Latest successful job given job levels
				['jobids' => $jobids, 'base_job' => $base_job] = $this->getJobIDsByLevels(
					$api_host,
					$job_name,
					$rt_config['job_levels']
				);
			}
		} elseif ($rt_config['source_type'] == RestoreTestConfig::SOURCE_TYPE_BACKUP_JOBID) {
			// Single jobid selection
			$jobids = [(int) $rt_config['source_backup_jobid']];
			$base_job = $this->getBackupJob($api_host, $jobids[0]);
		}
		if ($rt_config['verification_method'] == RestoreTestConfig::RESTORE_VERIFICATION_METHOD_VERIFY_JOB) {
			$jobids = is_object($base_job) ? [$base_job->jobid] : [];
		}
		return ['jobids' => $jobids, 'base_job' => $base_job];
	}

	/**
	 * Get jobids to restore by job name.
	 *
	 * @param string $api_host API host name used to initialize test
	 * @param string $job_name job name to restore (to find latest jobids)
	 * @return array elementary jobids and base job object selected to restore or empty array on problems
	 */
	private function getJobIDsByName(string $api_host, string $job_name): array
	{
		return $this->getElementaryJobIDs(
			$api_host,
			['jobname' => $job_name]
		);
	}

	/**
	 * Get jobids to restore by time range.
	 *
	 * @param string $api_host API host name used to initialize test
	 * @param string $job_name job name to restore (to find latest jobids)
	 * @param int $date_from_ts UNIX timestamp that represents date from
	 * @param int $date_to_ts UNIX timestamp that represents date to
	 * @return array elementary jobids and base job object selected to restore or empty array on problems
	 */
	private function getJobIDsByTimeRange(string $api_host, string $job_name, int $date_from_ts, int $date_to_ts): array
	{
		$date_from = date('Y-m-d H:i:s', $date_from_ts);
		$date_to = date('Y-m-d H:i:s', $date_to_ts);
		return $this->getElementaryJobIDs(
			$api_host,
			[
				'jobname' => $job_name,
				'date_from' => $date_from,
				'date_to' => $date_to,
				'order_by' => 'JobTDate',
				'order_type' => 'desc'
			]
		);
	}

	/**
	 * Get jobids to restore with given job level jobid.
	 *
	 * @param string $api_host API host name used to initialize test
	 * @param string $job_name job name to restore (to find latest jobids)
	 * @param array $levels job level letters (F, I, D) to find jobids
	 * @return array elementary jobids and base job object selected to restore or empty array on problems
	 */
	private function getJobIDsByLevels(string $api_host, string $job_name, array $levels): array
	{
		return $this->getElementaryJobIDs(
			$api_host,
			[
				'jobname' => $job_name,
				'level' => implode('', $levels),
				'order_by' => 'JobTDate',
				'order_type' => 'desc'
			]
		);
	}

	/**
	 * Get elementary jobids to restore.
	 *
	 * @param string $api_host API host name used to initialize test
	 * @param array $props job properties used as job criteria to find base jobid
	 * @return array elementary jobids and base job object selected to restore or empty array on problems
	 */
	private function getElementaryJobIDs(string $api_host, array $props): array
	{
		$jobids = [];
		$jobs = $this->getBackupJobs($api_host, $props);
		$job = null;
		if ($jobs) {
			$job = array_shift($jobs);
			$jobids = $this->getJobIDs($api_host, $job->jobid);
		}
		return ['jobids' => $jobids, 'base_job' => $job];
	}

	/**
	 * Find jobs for given criterias.
	 *
	 * @param string $api_host API host name used to initialize test
	 * @param array $props job properties used as job criteria
	 * @return array  list of job objects that fullfils criterias
	 */
	private function getBackupJobs(string $api_host, array $props): array
	{
		$query = [];
		if (key_exists('jobname', $props)) {
			$query['name'] = $props['jobname'];
		}
		if (key_exists('type', $props)) {
			$query['type'] = $props['type'];
		} else {
			$query['type'] = 'B';
		}
		if (key_exists('level', $props)) {
			$query['level'] = $props['level'];
		} else {
			$query['level'] = 'FID';
		}
		if (key_exists('jobstatus', $props)) {
			$query['jobstatus'] = $props['jobstatus'];
		} else {
			$query['jobstatus'] = 'TW';
		}
		if (key_exists('date_from', $props)) {
			$query['starttime_from'] = $props['date_from'];
		}
		if (key_exists('date_to', $props)) {
			$query['starttime_to'] = $props['date_to'];
		}
		if (key_exists('order_by', $props)) {
			$query['order_by'] = $props['order_by'];
		}
		if (key_exists('order_type', $props)) {
			$query['order_type'] = $props['order_type'];
		}

		$api = $this->getModule('api');
		$qp = '?' . http_build_query($query);
		$result = $api->get(['jobs', $qp], $api_host);
		$jobs = [];
		if ($result->error == 0) {
			$jobs = $result->output;
		}
		return $jobs;
	}

	/**
	 * Get single job details.
	 *
	 * @param string $api_host API host name used to initialize test
	 * @param int $jobid jobid to get details
	 * @return null|object job object or null on error
	 */
	private function getBackupJob(string $api_host, int $jobid): ?object
	{
		$api = $this->getModule('api');
		$result = $api->get(['jobs', $jobid], $api_host);
		$job = null;
		if ($result->error == 0) {
			$job = $result->output;
		}
		return $job;
	}

	/**
	 * Get elementary jobids for given jobid.
	 * This finds all jobids needed to consistent restore given jobid.
	 *
	 * @param string $api_host API host name used to initialize test
	 * @param int $jobid backup job identifier
	 * @return array jobids to restore or empty list on error;
	 */
	private function getJobIDs(string $api_host, int $jobid): array
	{
		$api = $this->getModule('api');
		$query = [
			'jobid' => $jobid,
			'output' => 'json'
		];
		$qp = '?' . http_build_query($query);
		$jobids = [];
		$result = $api->get(
			['bvfs', 'getjobids', $qp],
			$api_host
		);
		if ($result->error == 0) {
			$jobids = $result->output;
		}
		return $jobids;
	}

	/**
	 * Generate a new restore test identifier.
	 *
	 * @return string restore test identifier
	 */
	private function generateTestId(): string
	{
		$crypto = $this->getModule('crypto');
		$test_id = self::RESTORE_TEST_PREFIX . $crypto->getRandomString(32);
		return $test_id;
	}

	/**
	 * Start restore test session.
	 *
	 * @param string $test_id restore test identifier
	 * @param array $jobids elementary job identifiers to restore
	 * @param object $base_job job selected to test restore
	 * @param array $rt_config restore test configuration
	 * @return array restore start state (bool), session identifier and command identifier
	 */
	private function startRestoreSession(string $test_id, array $jobids, object $base_job, array $rt_config): array
	{
		$params = [
			'jobid' => implode(',', $jobids) ?? 0,
			'clientid' => $base_job->clientid,
			'filesetid' => $base_job->filesetid,
			'restorejob' => $rt_config['restore_job'],
			'comment' => "Test Job: {$base_job->name} JobId: {$base_job->jobid} Test Name: {$rt_config['name']}"
		];
		$api = $this->getModule('api');
		$result = $api->create(
			['jobs', 'restore', 'start'],
			$params
		);
		$success = ($result->error == 0);
		$sid = $cid = '';
		if ($success && isset($result->output->{'session-id'})) {
			// Restore session started
			$sid = $result->output->{'session-id'};
			$cid = $result->output->{'command-id'};
		} else {
			// Error while starting restore session
			$audit = $this->getModule('audit');
			$emsg = sprintf(
				'Error while starting restore session for backup JobId: %d, TestId: %s, Error: %d, Output: %s',
				$base_job->jobid,
				$test_id,
				$result->error,
				$result->output
			);
			$audit->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_ACTION,
				$emsg
			);
		}
		return [
			'status' => $success,
			'sid' => $sid,
			'cid' => $cid
		];
	}

	/**
	 * Run restore and perform tests.
	 *
	 * @param string $test_id restore test identifier
	 * @param array $rt_config restore test configuration
	 * @param array $rd_config restore destination configuration
	 * @param string $restore_dest_path restore destination path
	 * @param string $sid restore session identifier
	 * @param object $base_job selected backup job object
	 * @return bool true on success, otherwise false
	 */
	private function finishRestoreSession(
		string $test_id,
		array $rt_config,
		array $rd_config,
		string $restore_dest_path,
		string $sid,
		object $base_job
	): bool
	{
		$lock = $this->createLock();
		if (!$lock) {
			$this->endRestoreSession($sid);
			return false;
		}

		// Get restore job configuration
		$api = $this->getModule('api');
		$result = BaculaConfigAction::readResource(
			'dir',
			'Job',
			$rt_config['restore_job']
		);
		if ($result->error != 0) {
			$this->endRestoreSession($sid);
			$this->removeLock($lock);
			return false;
		}
		$audit = $this->getModule('audit');

		$runscript = '';
		$script = Prado::getPathOfNamespace(self::RESTORE_TEST_SCRIPT);
		if ($rt_config['verification_method'] == RestoreTestConfig::RESTORE_VERIFICATION_METHOD_RULES) {
			// Add runscript to restore job runscript
			$runscript = [
				'RunsWhen' => 'After',
				'RunsOnClient' => true,
				'Command' => "$script verification/verify --test-id=\"$test_id\""
			];
		} elseif ($rt_config['verification_method'] == RestoreTestConfig::RESTORE_VERIFICATION_METHOD_VERIFY_JOB) {
			$config = [
				'action_params' => [
					'jobid' => $base_job->jobid,
					'verifyjob' => $base_job->name,
					'fileset' => $base_job->fileset,
					'comment' => "Test Job: {$base_job->name} JobId: {$base_job->jobid} Test Name: {$rt_config['name']}"
				]
			];
			$ret = WebAccessAction::update($rt_config['web_verify_token'], $config);
			if (!$ret['state']) {
				$this->endRestoreSession($sid);
				$this->removeLock($lock);
				return false;
			}
			$web_protocol = $rt_config['web_protocol'] ?? '';
			$web_address = $rt_config['web_address'] ?? '';
			$web_port = $rt_config['web_port'] ?? '';

			$sparams = [
				"--token=\"{$rt_config['web_verify_token']}\""
			];
			if ($web_protocol != RestoreVerification::DEFAULT_WEB_ACCESS_PROTOCOL) {
				$sparams[] = "--web-protocol=\"{$web_protocol}\"";
			}
			if ($web_address != RestoreVerification::DEFAULT_WEB_ACCESS_ADDRESS) {
				$sparams[] = "--web-address=\"{$web_address}\"";
			}
			if ($web_port != RestoreVerification::DEFAULT_WEB_ACCESS_PORT) {
				$sparams[] = "--web-port=\"{$web_port}\"";
			}
			$params = implode(' ', $sparams);
			$runscript = [
				'RunsWhen' => 'After',
				'RunsOnClient' => false,
				'Command' => "$script verification/run $params"
			];
		}

		$misc = $this->getModule('misc');
		$config = $misc->objectToArray($result->output);
		$config['Runscript'] = [$runscript];
		$result = BaculaConfigAction::updateResource(
			'dir',
			'Job',
			$rt_config['restore_job'],
			$config
		);
		if ($result->error != 0) {
			$this->endRestoreSession($sid);
			$this->removeLock($lock);
			return false;
		}

		// Run restore
		$paths = $this->getPathsToRestore($rt_config);
		$restore_props = [
			'restoreclient' => $rd_config['restore_client'],
			'directory' => $paths['dirs'],
			'file' => $paths['files'],
			'filesetid' => $base_job->filesetid,
			'where' => $restore_dest_path,
			'session-id' => $sid
		];
		$ret = $api->create(
			['jobs', 'restore', 'finish'],
			$restore_props,
			null,
			false
		);
		$misc = $this->getModule('misc');
		if ($ret->error == 0) {
			$jobid = $misc->findJobIdStartedJob($ret->output);
			$success = is_numeric($jobid);
		} else {
			$success = false;
		}

		// Report restore status
		if ($success) {
			$audit->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_ACTION,
				"Run verification restore. Job: {$rt_config['restore_job']}, JobId: $jobid"
			);
		} else {
			$params = [
				'session-id' => $sid,
				'command' => 'quit',
				'async' => 1
			];
			$result = $api->create(
				['jobs', 'restore', 'command'],
				$params,
				null,
				false
			);
			$audit->audit(
				AuditLog::TYPE_WARNING,
				AuditLog::CATEGORY_ACTION,
				"Run verification restore failed. Job: {$rt_config['restore_job']}, Error: {$ret->error}, Output: '{$ret->output}'",
			);
		}
		$this->endRestoreSession($sid);
		$this->removeLock($lock);

		return $success;
	}

	/**
	 * Create lock for starting restore process.
	 */
	private function createLock()
	{
		$audit = $this->getModule('audit');
		$lock_file = Prado::getPathOfNamespace('Bacularis.Common.Working.restore-verification', '.lock');
		$fp = fopen($lock_file, 'c');
		if ($fp === false) {
			$audit->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_ACTION,
				'Unable to open lock file for restore verification process.',
			);
			return false;
		}
		if (!flock($fp, LOCK_EX)) {
			$audit->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_ACTION,
				'Unable to acquire lock for restore verification process.',
			);
			fclose($fp);
			return false;
		}
		return $fp;
	}

	/**
	 * Remove lock in restore process.
	 */
	private function removeLock($fp): void
	{
		flock($fp, LOCK_UN);
		fclose($fp);
	}

	/**
	 * Finalize restore session.
	 *
	 * @param string $sid restore session identifier
	 * @return bool true on success, otherwise false
	 */
	private function endRestoreSession(string $sid): bool
	{
		$params = [
			'session-id' => $sid,
			'command' => 'done',
			'async' => 0
		];
		$api = $this->getModule('api');
		$result = $api->create(
			['jobs', 'restore', 'command'],
			$params,
			null,
			false
		);
		$params = [
			'session-id' => $sid,
			'command' => 'quit',
			'async' => 1
		];
		$api = $this->getModule('api');
		$result = $api->create(
			['jobs', 'restore', 'command'],
			$params,
			null,
			false
		);
		return ($result->error == 0);
	}

	/**
	 * Get dir/file paths to restore.
	 *
	 * @param array $rt_config restore test configuration
	 * @return array list of paths to restore from backup or empty list on problems
	 */
	private function getPathsToRestore(array $rt_config): array
	{
		static $paths = [];
		if ($paths) {
			return $paths;
		}
		switch ($rt_config['restore_scope']) {
			case RestoreTestConfig::RESTORE_SCOPE_ENTIRE_BACKUP: {
				$paths = $this->getPathsToRestoreEntireBackup();
				break;
			}
			case RestoreTestConfig::RESTORE_SCOPE_SINGLE_BACKUP: {
				$paths = $this->getPathsToRestoreEntireBackup();
				break;
			}
			case RestoreTestConfig::RESTORE_SCOPE_VERIFICATION_RULES_PATHS: {
				$paths = $this->getPathsToRestoreFromRuleSets($rt_config);
				break;
			}
			case RestoreTestConfig::RESTORE_SCOPE_RANDOM_SAMPLE: {
				$paths = $this->getPathsToRestoreRandomSample($rt_config);
				break;
			}
		}
		return $paths;
	}

	/**
	 * Get dir/file paths to do full file restore.
	 *
	 * @return array paths to do full file restore
	 */
	private function getPathsToRestoreEntireBackup(): array
	{
		return ['dirs' => ['/'], 'files' => []];
	}

	/**
	 * Get paths to restore from used rule sets in restore test.
	 *
	 * @param array $rt_config restore test configuration
	 * @return array paths from rule sets to restore (directories and files)
	 */
	private function getPathsToRestoreFromRuleSets(array $rt_config): array
	{
		$paths = ['dirs' => [], 'files' => []];
		$rule_sets = $this->getRuleSetsPaths($rt_config);
		foreach ($rule_sets as $name => $ps) {
			foreach ($ps as $path => $checkers) {
				if (preg_match('/\/$/', $path) === 1) {
					$paths['dirs'][$path] = 1;
				} else {
					$paths['files'][$path] = 1;
				}
			}
		}
		return [
			'dirs' => array_keys($paths['dirs']),
			'files' => array_keys($paths['files'])
		];
	}

	/**
	 * Get paths from rule sets in given restore test.
	 *
	 * @param array $rt_config restore test configuration
	 * @param object $file_meta jobids file metadata from the catalog
	 * @return array paths from rule sets
	 */
	private function getRuleSetsPaths(array $rt_config, ?object $file_meta = null): array
	{
		$vrule_config = $this->getModule('verification_rule_config');
		$rule_sets = $rt_config['verification_rule_sets'] ?? [];
		$rulesets = [];
		for ($i = 0; $i < count($rule_sets); $i++) {
			$rule = $vrule_config->getVerificationRuleConfig($rule_sets[$i]);
			if (!$rule) {
				// Rule set does not exists or is empty
				continue;
			}
			if (!$this->isRuleSetSupported($rule, $rt_config)) {
				// Rule set is disabled
				continue;
			}
			foreach ($rule['rules'] as $rpath => &$rvalue) {
				for ($j = 0; $j < count($rvalue); $j++) {
					if ($rvalue[$j]['operator'] == VerificationRuleConfig::EQUAL_CATALOG_VALUE && isset($file_meta->{$rpath})) {
						$rvalue[$j]['value'] = $file_meta->{$rpath};
					}
				}
			}
			$rulesets[$rule_sets[$i]] = $rule['rules'];
		}
		return $rulesets;
	}


	/**
	 * Check if rule set is possible to use.
	 *
	 * @param string $rs_config single rule set configuration
	 */
	private function isRuleSetSupported(array $rs_config, array $rt_config): bool
	{
		$emsg = '';
		$result = ($rs_config['enabled'] == 1);
		if (!$result) {
			$emsg = sprintf(
				'Attempt to use disabled rule set: "%s" in restore test "%s".',
				$rs_config['name'],
				$rt_config['name']
			);
		}
		if ($emsg) {
			$audit = $this->getModule('audit');
			$audit->audit(
				AuditLog::TYPE_WARNING,
				AuditLog::CATEGORY_ACTION,
				$emsg
			);
		}
		return $result;
	}

	/**
	 * Get random sample paths to restore.
	 * NOTE: Selected are random paths from rule sets used in restore test.
	 *
	 * @param array $rt_config restore test configuration
	 * @return array paths from rule sets to restore (directories and files)
	 */
	private function getPathsToRestoreRandomSample(array $rt_config): array
	{
		$paths = ['dirs' => [], 'files' => []];
		$rs_paths = $this->getPathsToRestoreFromRuleSets($rt_config);
		$all_paths = array_merge($rs_paths['dirs'], $rs_paths['files']);
		$all_paths_len = count($all_paths);
		$rkeys = [];
		if ($all_paths_len > 0) {
			$num = rand(1, $all_paths_len);
			$rkeys = array_rand($all_paths, $num);
		}
		if (!is_array($rkeys)) {
			$rkeys = [$rkeys];
		}
		for ($i = 0; $i < count($rkeys); $i++) {
			if (preg_match('/\/$/', $all_paths[$rkeys[$i]]) === 1) {
				$paths['dirs'][$all_paths[$rkeys[$i]]] = 1;
			} else {
				$paths['files'][$all_paths[$rkeys[$i]]] = 1;
			}
		}
		return [
			'dirs' => array_keys($paths['dirs']),
			'files' => array_keys($paths['files'])
		];
	}

	/**
	 * Get restore path to do restore.
	 * NOTE: On the path are applied keywords (if used) like {date}, {job_name} and others.
	 *
	 * @param string $test_id restore test identifier
	 * @param array $rd_config restore destination configuration
	 * @param array $rt_config restore test configuration
	 * @param object $base_job base job selected to restore
	 * @return string path to do restore or empty string on error
	 */
	private function getRestoreWherePath(string $test_id, array $rd_config, array $rt_config, object $base_job): string
	{
		$path = '';
		switch ($rd_config['restore_mode']) {
			case RestoreDestinationConfig::RESTORE_MODE_PREFIXED_PATH: {
				$path = $rd_config['restore_path_prefixed'];
				break;
			}
			case RestoreDestinationConfig::RESTORE_MODE_ORIGINAL_PATH: {
				$path = $rd_config['restore_path_original'];
				break;
			}
		}
		$this->applyKeywordVals($test_id, $rt_config, $base_job, $path);
		return $path;
	}

	/**
	 * Replace restore test keywords in path to values.
	 *
	 * @param string $test_id restore test identifier
	 * @param array $rt_config restore test configuration
	 * @param object $base_job base job selected to restore
	 * @param string $path path to apply keywords
	 */
	private function applyKeywordVals(string $test_id, array $rt_config, object $base_job, string &$path): void
	{
		$from = [
			'{date}',
			'{test_id}',
			'{job_name}',
			'{restore_test}',
			'{restore_job}',
			'{restore_policy}',
			'{restore_destination}'
		];
		$to = [
			date('Y-m-d_H:i:s'),
			$test_id,
			($base_job->name ?? 'job'),
			($rt_config['name'] ?? 'restore_test'),
			($rt_config['restore_job'] ?? 'restore_job'),
			($rt_config['restore_policy'] ?? 'policy'),
			($rt_config['restore_destination'] ?? 'destination')
		];
		$path = str_replace($from, $to, $path);
	}

	/**
	 * Send restore test plan to destination host.
	 *
	 * @param string $test_id restore test identifier
	 * @param array $rt_config restore test configuration
	 * @param array $rd_config restore destination configuration
	 * @param array $jobids elementary job identifiers for restore
	 * @param string $restore_dest_path restore destination path
	 * @return bool true on success, otherwise false
	 */
	private function sendRestoreTestPlan(string $test_id, array $rt_config, array $rd_config, array $jobids, string $restore_dest_path): bool
	{
		// Prepare plan structure
		$plan = $this->prepareRestoreTestPlan($test_id, $rt_config, $jobids, $restore_dest_path);
		if (!$plan) {
			return false;
		}

		// Get restore client configuration
		$audit = $this->getModule('audit');
		$api = $this->getModule('api');
		$result = BaculaConfigAction::readResource(
			'dir',
			'Client',
			$rd_config['restore_client']
		);
		if ($result->error != 0) {
			return false;
		}

		// Find valid API host
		$api_host = '';
		$host_config = $this->getModule('host_config');
		$api_hosts = $host_config->getConfig();
		$client_address = $result->output->Address;
		foreach ($api_hosts as $name => $attrs) {
			if ($attrs['address'] === $client_address) {
				$api_host = $name;
				break;
			}
		}

		$success = false;
		$emsg = '';
		if ($api_host) {
			// Send restore test plan
			$params = [
				'test_id' => $test_id,
				'plan' => json_encode($plan)
			];
			$result = $api->create(
				['jobs', 'restore', 'verify', 'plan'],
				$params,
				$api_host,
				false
			);
			$success = ($result->error == 0);
			if (!$success) {
				// Something went wrong with sending plan
				$emsg = sprintf(
					'Start restore test failed. Error while preparing restore test "%s" for Client "%s" (%s). Response: %s.',
					$test_id,
					$rd_config['restore_client'],
					$client_address,
					$result->output
				);
			}
		} else {
			// API host not found
			$emsg = sprintf(
				'Start restore test failed. No API host found to use for Client "%s" (%s).',
				$rd_config['restore_client'],
				$client_address
			);
		}
		if ($emsg) {
			$audit->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_APPLICATION,
				$emsg
			);
		}
		return $success;
	}

	/**
	 * Get catalog file metadata for jobids.
	 *
	 * @param array $jobids elementary backup job identifiers
	 * @return null|object catalog file metadata or null on error
	 */
	private function getCatalogJobIdsFiles(array $jobids): ?object
	{
		$audit = $this->getModule('audit');
		$api = $this->getModule('api');
		$jids = implode(',', $jobids);
		$params = [
			'jobids' => $jids
		];
		$query = '?' . http_build_query($params);
		$result = $api->get(
			['jobs', 'files', 'tree', $query]
		);
		$files = null;
		if ($result->error == 0) {
			$files = $result->output;
		} else {
			// Failed getting file list from the catalog
			$emsg = sprintf(
				'There was an error while getting file list for jobids "%s".',
				$jids
			);
			$audit->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_ACTION,
				$emsg
			);
		}
		return $files;
	}

	/**
	 * Prepare restore test plan structure to send.
	 *
	 * @param string $test_id restore test identifier
	 * @param array $rt_config restore test configuration
	 * @param array $jobids elementary backup job identifiers
	 * @param string $restore_dest_path restore destination path
	 * @return array plan ready to send or empty array on error
	 */
	private function prepareRestoreTestPlan(string $test_id, array $rt_config, array $jobids, string $restore_dest_path): array
	{
		$rdest_config = $this->getModule('restore_destination_config');
		$rd_config = $rdest_config->getRestoreDestinationConfig(
			$rt_config['restore_destination']
		);
		if ($rd_config['enabled'] != 1) {
			$audit = $this->getModule('audit');
			$audit->audit(
				AuditLog::TYPE_WARNING,
				AuditLog::CATEGORY_ACTION,
				sprintf(
					'Attempt to use disabled restore destination: "%s" for restore test.',
					$rt_config['restore_destination']
				)
			);
			return [];
		}

		$file_meta = $this->getCatalogJobIdsFiles($jobids);

		$target_path = rtrim($restore_dest_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		$paths = $this->getRuleSetsPaths($rt_config, $file_meta);
		$this->filterPaths($paths, $rt_config);
		$plan = [
			'test_id' => $test_id,
			'restore' => [
				'destination_name' => $rt_config['restore_destination'],
				'destination_capabilities' => $rd_config['capabilities'],
				'target_client' => $rd_config['restore_client'],
				'target_path' => $target_path,
				'original_root' => '/',
				'path_mapping' => [
					'/' => $target_path
				]
			],
			'paths' => $paths
		];
		return $plan;
	}

	/**
	 * Filter not needed paths.
	 * Used mainly for random sample paths.
	 *
	 * @param array $paths paths to test in restore plan
	 * @param array $rt_config restore test configuration
	 */
	private function filterPaths(array &$paths, array $rt_config): void
	{
		if ($rt_config['restore_scope'] == RestoreTestConfig::RESTORE_SCOPE_RANDOM_SAMPLE) {
			// Get paths that will be restored
			$fpaths = $this->getPathsToRestore($rt_config);
			$all_paths = array_merge($fpaths['dirs'], $fpaths['files']);
			$npaths = [];
			foreach ($paths as $rule_set => $value) {
				foreach ($value as $path => $props) {
					if (!in_array($path , $all_paths)) {
						// Path not in restored paths, filtering - skip it
						continue;
					}
					$npaths[$rule_set][$path] = $props;
				}
			}
			$paths = $npaths;
		}
	}
}
