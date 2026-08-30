<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2026 Marcin Haba
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
 *
 * Network Diagram Page - Community Contribution
 * Copyright (C) 2026 podheitor
 * Renders a graphical view of all Bacula components with status indicators
 */

use Bacularis\Web\Modules\BaculumWebPage;

/**
 * Network Diagram page.
 *
 * @author podheitor
 * @category Page
 */
class NetworkDiagram extends BaculumWebPage
{
	public $diagram_data = [];

	/**
	 * Page load handler.
	 *
	 * @param mixed $param event parameter
	 */
	public function onLoad($param)
	{
		parent::onLoad($param);
		$this->gatherDiagramData();
	}

	/**
	 * Gather all diagram data from Bacula components.
	 */
	private function gatherDiagramData()
	{
		$this->diagram_data = [
			'director' => $this->getDirectorInfo(),
			'clients'  => $this->getClients(),
			'storages' => $this->getStorages(),
			'pools'    => $this->getPools(),
		];
	}

	/**
	 * Get Director information via bconsole.
	 *
	 * @return array director info with name, version, status, jobs_running
	 */
	private function getDirectorInfo()
	{
		$output = $this->execBconsole('status dir');
		$info = [
			'name'         => 'bacula-dir',
			'version'      => '',
			'status'       => 'gray',
			'jobs_running' => 0,
		];

		if (preg_match('/Version:\s+([^\s(]+)/', $output, $m)) {
			$info['version'] = $m[1];
			$info['status'] = 'green';
		}
		if (preg_match('/running=(\d+)/', $output, $m)) {
			$info['jobs_running'] = (int) $m[1];
		}
		return $info;
	}

	/**
	 * Get list of connected clients via bconsole.
	 *
	 * @return array list of clients with name, addr, status
	 */
	private function getClients()
	{
		$output = $this->execBconsole('status client');
		$clients = [];
		$blocks = preg_split('/(?=Client\s*Resources:)/', $output);

		foreach ($blocks as $block) {
			if (!preg_match('/Client:\s+"([^"]+)"/', $block, $m)) {
				continue;
			}
			$name = $m[1];
			$addr = '127.0.0.1';
			if (preg_match('/connected at\s+([^\s:]+):(\d+)/', $block, $am)) {
				$addr = $am[1];
			}
			$clients[] = ['name' => $name, 'addr' => $addr, 'status' => 'green'];
		}
		return $clients;
	}

	/**
	 * Get list of storage daemons via bconsole.
	 *
	 * @return array list of storages with name, addr, status
	 */
	private function getStorages()
	{
		$output = $this->execBconsole('status storage');
		$storages = [];
		$blocks = preg_split('/(?=Device\s*Resources:)/', $output);

		foreach ($blocks as $block) {
			if (!preg_match('/Storage:\s+"([^"]+)"/', $block, $m)) {
				continue;
			}
			$name = $m[1];
			$addr = '127.0.0.1';
			if (preg_match('/connected at\s+([^\s:]+):(\d+)/', $block, $am)) {
				$addr = $am[1];
			}
			$storages[] = ['name' => $name, 'addr' => $addr, 'status' => 'green'];
		}
		return $storages;
	}

	/**
	 * Get list of pools via bconsole.
	 *
	 * @return array list of pools with name
	 */
	private function getPools()
	{
		$output = $this->execBconsole('list pools');
		$pools = [];
		$lines = explode("\n", trim($output));
		foreach ($lines as $line) {
			$parts = array_map('trim', explode('|', $line));
			if (count($parts) >= 2 && is_numeric($parts[0]) && !empty($parts[1])) {
				$pools[] = ['name' => $parts[1]];
			}
		}
		return $pools;
	}

	/**
	 * Execute a bconsole command and return output.
	 *
	 * @param string $command bconsole command to execute
	 * @return string command output
	 */
	private function execBconsole($command)
	{
		$bin = '/opt/bacula/bin/bconsole';
		$cfg = '/opt/bacula/etc/bconsole.conf';

		$tmpfile = tempnam(sys_get_temp_dir(), 'bcon_');
		file_put_contents($tmpfile, $command . "\n.\n");

		$cmd = sprintf(
			'%s -c %s < %s 2>/dev/null',
			$bin,
			escapeshellarg($cfg),
			escapeshellarg($tmpfile)
		);
		$output = shell_exec($cmd);

		@unlink($tmpfile);
		return $output ?: '';
	}

	/**
	 * Get navigation data for sidebar menu.
	 *
	 * @return array navigation data
	 */
	public function getNavData()
	{
		return [
			[
				'page'    => 'NetworkDiagram',
				'label'   => 'Network Diagram',
				'icon'    => 'fa-solid fa-network-wired fa-fw',
				'actions' => [],
			],
		];
	}
}
