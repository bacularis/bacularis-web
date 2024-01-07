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

namespace Bacularis\Web\Modules;

/**
 * GPG command module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class GPG extends WebModule
{
	/**
	 * Base command to run.
	 */
	private const CMD = 'gpg';

	/**
	 * Pattern types used to prepare command.
	 */
	public const PTYPE_REG_CMD = 0;

	/**
	 * Foreground gpg command.
	 *
	 * Format: %cmd %options %src_file %user@%host:%dst_file
	 */
	private const GPG_COMMAND_PATTERN = "%s %s \"%s\"";

	/**
	 * Execute remote command.
	 *
	 * @param string $file file (usually a key)
	 * @param string $options command options
	 * @param int $ptype command pattern type
	 * @return array command output
	 */
	public function execCommand($file, $options = [], $ptype = self::PTYPE_REG_CMD)
	{
		$cmd = $this->prepareCommand(
			$file,
			$options,
			$ptype
		);
		exec($cmd, $output, $error);
		return [
			'output' => $output,
			'exitcode' => $error
		];
	}


	/**
	 * Prepare GPG command to execution.
	 *
	 * @param string $file file (usually a key)
	 * @param string $options command options
	 * @param int $ptype command pattern type
	 * @return string full command string
	 */
	private function prepareCommand($file, array $options, $ptype)
	{
		$opts = [];
		if (key_exists('dearmor', $options)) {
			$opts[] = '--dearmor';
		}

		$options = implode(' ', $opts);

		$gpg_cmd = $this->getCmdPattern($ptype);
		$cmd = sprintf(
			$gpg_cmd,
			self::CMD,
			$options,
			$file
		);
		return $cmd;
	}

	/**
	 * Get command pattern by pattern type.
	 * So far support is only foreground command regular pattern.
	 *
	 * @param int $ptype command pattern type
	 * @return string command pattern
	 */
	private function getCmdPattern($ptype)
	{
		$pattern = null;
		switch ($ptype) {
			case self::PTYPE_REG_CMD: $pattern = self::GPG_COMMAND_PATTERN;
				break;
			default: $pattern = self::GPG_COMMAND_PATTERN;
				break;
		}
		return $pattern;
	}
}
