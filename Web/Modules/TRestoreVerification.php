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

use Bacularis\Common\Modules\RestoreVerification;
use Prado\Prado;

/**
 * Restore verification module providing common interface for verification portlets.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
trait TRestoreVerification
{
	/**
	 * Update backup job configuration.
	 * This adds/updates in RunScript a command to start restore test
	 *
	 * @param array $rt_config restore test configuration
	 * @param string $action restore test action: create|update|remove
	 * @return bool true on success, otherwise false
	 */
	protected function updateBackupJob(array $rt_config, string $action): bool
	{
		// Get backup job config
		$job = $this->getJobConfig($rt_config['source_backup_job']);
		if (!$job) {
			return false;
		}

		// Remove old restore script in RunScript (if any)
		$this->removeOldRestoreTest($job);

		if ($action == 'create' || $action == 'update') {
			// Prepare run restore test script in RunScript
			$restore_script = Prado::getPathOfNamespace('Bacularis.Common.Bin.restore');
			$web_protocol = $rt_config['web_protocol'] ?? '';
			$web_address = $rt_config['web_address'] ?? '';
			$web_port = $rt_config['web_port'] ?? '';

			$sparams = [
				"--token=\"{$rt_config['web_run_token']}\"",
				'--level="%l"'
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
				'RunsOnClient' => false,
				'RunsWhen' => 'After',
				'Command' => "{$restore_script} verification/run $params"
			];

			if (key_exists('Runscript', $job)) {
				// Job uses RunScripts, add run admin job runscript
				$job['Runscript'][] = $runscript;
			} else {
				// Job does not use RunScript, create runscript
				$job['Runscript'] = [$runscript];
			}
		}

		$result = BaculaConfigAction::updateResource(
			'dir',
			'Job',
			$job['Name'],
			$job
		);
		return ($result->error == 0);
	}

	/**
	 * Get backup job configuration.
	 *
	 * @param string $job_name backup job name
	 */
	protected function getJobConfig(string $job_name): array
	{
		$result = BaculaConfigAction::readResource(
			'dir',
			'Job',
			$job_name
		);
		$job = [];
		if ($result->error == 0) {
			$misc = $this->getModule('misc');
			$job = $misc->objectToArray($result->output);
		}
		return $job;
	}

	/**
	 * Remove old restore test runscript from job configuration.
	 *
	 * @param array $job Bacula job configuration
	 * @param string $runs_when runscript execution time
	 */
	private function removeOldRestoreTest(array &$job, string $runs_when = 'After')
	{
		$script = Prado::getPathOfNamespace('Bacularis.Common.Bin.restore');
		$sparam = 'verification/run';
		if (key_exists('Runscript', $job) && is_array($job['Runscript'])) {
			for ($i = 0; $i < count($job['Runscript']); $i++) {
				if (!isset($job['Runscript'][$i]['RunsWhen']) || $job['Runscript'][$i]['RunsWhen'] != $runs_when) {
					continue;
				}
				if (!isset($job['Runscript'][$i]['RunsOnClient']) || $job['Runscript'][$i]['RunsOnClient'] !== false) {
					continue;
				}
				if (!isset($job['Runscript'][$i]['Command'])) {
					continue;
				}
				if (strpos($job['Runscript'][$i]['Command'], "$script $sparam") === 0) {
					// old runscript found, remove it and finish
					array_splice($job['Runscript'], $i, 1);
					break;
				}
			}
		}
	}

	/**
	 * Create run token.
	 * This is token to run admin job by backup job.
	 *
	 * @param array $rt_config restore test configuration
	 * @param array $rp_config restore policy configuration
	 * @return null|string token value or null on error
	 */
	protected function createRunWebAccessToken(array $rt_config, array $rp_config): ?string
	{
		$action_params = [];
		if ($rp_config['run_method'] == RestorePolicyConfig::RUN_METHOD_AFTER_BACKUP) {
			$action_params['allowed_levels'] = implode(',', $rp_config['job_levels']);
		}
		[
			'state' => $state,
			'token' => $token
		] = $this->createWebAccess(
			$rt_config['name'],
			WebAccessBaculaResource::ACTION_RUN_NAME,
			$action_params,
			$rt_config['web_allowed_ips']
		);
		if (!$state) {
			return null;
		}
		return $token;
	}

	/**
	 * Create web access token.
	 *
	 * @param string $job job name
	 * @param string $action action name
	 * @param array $action_params action parameters
	 * @param string $allowed_ips allowed IP addresses separated by commas
	 * @return array web access creation result
	 */
	protected function createWebAccess(string $job, string $action, array $action_params = [], string $allowed_ips = ''): array
	{
		$sess = $this->getPage()->getSession();
		$allowed_ips_list = $this->getAllowedIPs($allowed_ips);

		// General config
		$config = [
			'access_type' => WebAccessConfig::WEB_ACCESS_TYPE_RESOURCE,
			'api_hosts' => [$this->User->getDefaultAPIHost()],
			'component_type' => 'dir',
			'component_name' => $sess->itemAt('director'),
			'resource_type' => 'Job',
			'resource_name' => $job,
			'action' => $action,
			'action_params' => $action_params
		];

		// Time access methods
		$config['time_method'] = WebAccessConfig::WEB_ACCESS_TIME_METHOD_UNLIMITED;
		$config['time_from'] = $config['time_to'] = -1;

		// Usage access methods
		$config['usage_method'] = WebAccessConfig::WEB_ACCESS_USAGE_METHOD_UNLIMITED;
		$config['usage_max'] = $config['usage_left'] = -1;

		// Source access methods
		$source_access = WebAccessConfig::WEB_ACCESS_SOURCE_METHOD_NO_RESTRICTION;
		if ($allowed_ips_list) {
			$source_access = WebAccessConfig::WEB_ACCESS_SOURCE_METHOD_IP_RESTRICTION;
		}
		$config['source_access'] = $source_access;
		$config['source_ips_allowed'] = $allowed_ips_list;

		// Access time 0 - no access yet
		$config['access_time'] = 0;

		// Create time - current
		$config['create_time'] = time();

		$result = WebAccessAction::create($config);
		return $result;
	}

	/**
	 * Get allowed IP addresses from user input.
	 *
	 * @param string $allowed_ips allowed IP addresses separated by commas
	 * @return array allowed IP addresses
	 */
	protected function getAllowedIPs(string $allowed_ips): array
	{
		$ips = explode(',', $allowed_ips);
		$ips = array_map('trim', $ips);
		$ips = array_filter($ips, 'strlen');
		return array_values($ips);
	}

	/**
	 * Get restore test admin job configuration.
	 *
	 * @param array $rt_config restore test configuration
	 * @param array $resource admin job resource directives
	 * @return array admin job configuration
	 */
	private function getAdminJobConfig(array $rt_config, array $resource): array
	{
		// Prepare restore test script in RunScript
		$restore_script = Prado::getPathOfNamespace('Bacularis.Common.Bin.restore');
		$web_protocol = $rt_config['web_protocol'] ?? '';
		$web_address = $rt_config['web_address'] ?? '';
		$web_port = $rt_config['web_port'] ?? '';

		$sparams = ["--token=\"{$rt_config['web_admin_token']}\""];
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
			'RunsOnClient' => false,
			'RunsWhen' => 'Before',
			'Command' => "{$restore_script} verification/run $params"
		];

		// Prepare Admin job with RunScript
		$job = [
			'Name' => $rt_config['name'],
			'Type' => 'Admin',
			'Description' => 'Restore verification test job',
			'Client' => $resource['Client'],
			'Fileset' => $resource['Fileset'],
			'Storage' => $resource['Storage'],
			'Pool' => $resource['Pool'],
			'Messages' => $resource['Messages'],
			'Runscript' => [$runscript]
		];
		$rpolicy_config = $this->getModule('restore_policy_config');
		$rp_config = $rpolicy_config->getRestorePolicyConfig($rt_config['restore_policy']);
		if ($rp_config['enabled'] == 1 && $rp_config['run_method'] == RestorePolicyConfig::RUN_METHOD_SCHEDULE) {
			$job['Schedule'] = $rp_config['schedule'];
		}
		return $job;
	}
}
