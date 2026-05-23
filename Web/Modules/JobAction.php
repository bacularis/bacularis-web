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
use Bacularis\Common\Modules\PluginConfigBase;
use Prado\Prado;

/**
 * Various job actions.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class JobAction
{
	/**
	 * Default priority for job if not provided.
	 */
	public const DEFAULT_JOB_PRIORITY = 10;

	/**
	 * Run single job by name.
	 *
	 * @param string $name job name to run
	 * @param array $run_params additional run job action parameters
	 * @return StdClass run job result object
	 */
	public static function runJobByName(string $name, array $run_params = []): object
	{
		$app = Prado::getApplication();
		$audit = $app->getModule('audit');

		// Pre-run job actions
		$plugin_manager = $app->getModule('plugin_manager');
		$plugin_manager->callPluginActionByType(
			PluginConfigBase::PLUGIN_TYPE_RUN_ACTION,
			'run',
			'pre-run-manually',
			'Job',
			$name
		);

		// Run job
		$params = ['name' => $name];
		if ($run_params) {
			$params = array_merge($params, $run_params);
		}
		$api = $app->getModule('api');
		$result = $api->create(
			['jobs', 'run'],
			$params
		);
		if ($result->error === 0) {
			// Post-run job actions
			$plugin_manager->callPluginActionByType(
				PluginConfigBase::PLUGIN_TYPE_RUN_ACTION,
				'run',
				'post-run-manually',
				'Job',
				$name
			);

			$misc = $app->getModule('misc');
			$started_jobid = $misc->findJobIdStartedJob($result->output);
			if (is_numeric($started_jobid)) {
				$audit->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_ACTION,
					"Run job. Job: $name, JobId: $started_jobid"
				);
			} else {
				$errmsg = implode(PHP_EOL, $result->output);
				$result->output = $errmsg;
				$audit->audit(
					AuditLog::TYPE_WARNING,
					AuditLog::CATEGORY_ACTION,
					"Run job failed. Job: $name"
				);
			}
		} else {
			$audit->audit(
				AuditLog::TYPE_WARNING,
				AuditLog::CATEGORY_ACTION,
				"Run job command failed. Job: $name"
			);
		}
		return $result;
	}

	/**
	 * Rerun job by jobid.
	 *
	 * @param int $jobid job identifier
	 * @return object response with output and error
	 */
	public static function runJobById(int $jobid)
	{
		$app = Prado::getApplication();
		$audit = $app->getModule('audit');
		$api = $app->getModule('api');

		// Get job details from catalog
		$result = $api->get(
			['jobs', $jobid]
		);

		if ($result->error != 0) {
			return $result;
		}

		$job_data = $result->output;
		$params = [];
		$params['id'] = $jobid;
		$level = trim($job_data->level);
		$params['level'] = !empty($level) ? $level : 'F'; // Admin job has empty level

		// Get job details from Director configuration
		$result = $api->get(
			['jobs', $jobid, 'show']
		);
		if ($result->error != 0) {
			return $result;
		}

		// Prepare main job parameters
		$job_show = $result->output;
		$jinfo = $app->getModule('job_info');
		$job_info = $jinfo->parseResourceDirectives($job_show);
		$job_info_keys = array_keys($job_info);

		// Prepare storage parameter
		$storage_idx = array_search('storage', $job_info_keys) ?: -1;
		$autochanger_idx = array_search('autochanger', $job_info_keys) ?: -1;
		$storage = key_exists('storage', $job_info) ? $job_info['storage']['name'] : null;
		$autochanger = key_exists('autochanger', $job_info) ? $job_info['autochanger']['name'] : null;
		$params['storage'] = ($autochanger_idx > -1 && ($storage_idx == -1 || $autochanger_idx < $storage_idx)) ? $autochanger : $storage;

		// Prepare fileset parameter
		if ($job_data->filesetid > 0) {
			$params['filesetid'] = $job_data->filesetid;
		} else {
			$params['fileset'] = key_exists('fileset', $job_info) ? $job_info['fileset']['name'] : '';
		}

		// Prepare client parameter
		$params['clientid'] = $job_data->clientid;

		/**
		 * For 'c' type (Copy Job) and 'g' type (Migration Job) the in job table in poolid property is written
		 * write pool, not read pool. Here in 'pool' property is set read pool and from this reason for 'c'
		 * and 'g' types the pool cannot be taken from job table.
		 */
		if ($job_data->poolid > 0 && $job_data->type != 'c' && $job_data->type != 'g') {
			$params['poolid'] = $job_data->poolid;
		} else {
			$params['pool'] = key_exists('pool', $job_info) ? $job_info['pool']['name'] : '';
		}

		// Other job options
		$params['priority'] = key_exists('job', $job_info) ? $job_info['job']['priority'] : self::DEFAULT_JOB_PRIORITY;
		$accurate = key_exists('job', $job_info) && key_exists('accurate', $job_info['job']) ? $job_info['job']['accurate'] : 0;
		$params['accurate'] = ($accurate == 1);

		// Pre-run job actions
		$plugin_manager = $app->getModule('plugin_manager');
		$plugin_manager->callPluginActionByType(
			PluginConfigBase::PLUGIN_TYPE_RUN_ACTION,
			'run',
			'pre-run-manually',
			'Job',
			$job_info['job']['name']
		);

		// Run job
		$api = $app->getModule('api');
		$result = $api->create(
			['jobs', 'run'],
			$params
		);
		if ($result->error === 0) {
			// Post-run job actions
			$plugin_manager->callPluginActionByType(
				PluginConfigBase::PLUGIN_TYPE_RUN_ACTION,
				'run',
				'post-run-manually',
				'Job',
				$job_info['job']['name']
			);

			$misc = $app->getModule('misc');
			$started_jobid = $misc->findJobIdStartedJob($result->output);
			$audit = $app->getModule('audit');
			if (is_numeric($started_jobid)) {
				$audit->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_ACTION,
					"Run job. Job: {$job_info['job']['name']}, JobId: $started_jobid"
				);
			} else {
				$errmsg = implode(PHP_EOL, $result->output);
				$result->output = $errmsg;
				$audit->audit(
					AuditLog::TYPE_WARNING,
					AuditLog::CATEGORY_ACTION,
					"Run job failed. Job: {$job_info['job']['name']}"
				);
			}
		} else {
			$audit->audit(
				AuditLog::TYPE_WARNING,
				AuditLog::CATEGORY_ACTION,
				"Run job command failed. JobId: $jobid"
			);
		}
		return $result;
	}
}
