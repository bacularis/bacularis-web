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
 * SCP command module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class SCP extends WebModule
{
	/**
	 * Base command to run.
	 */
	private const CMD = 'scp';

	/**
	 * Pattern types used to prepare command.
	 */
	public const PTYPE_REG_CMD = 0;

	/**
	 * Foreground ssh command.
	 *
	 * Format: %cmd %options %src_file %user@%host:%dst_file
	 */
	private const SCP_COMMAND_PATTERN = "%s %s \"%s\" \"%s:%s\"";

	/**
	 * SCP command timeout in seconds.
	 */
	private const SCP_COMMAND_TIMEOUT = 20;

	/**
	 * Execute remote command.
	 *
	 * @param string $src_file file path to copy to destination host
	 * @param string $dest_path destination path on remote host
	 * @param string $address destination host address
	 * @param array $creds credentials ('username', 'password', 'sshkey', 'passphrase')
	 * @param int $ptype command pattern type
	 * @return array command output
	 */
	public function execCommand($src_file, $dest_path, $address, $creds = [], $ptype = self::PTYPE_REG_CMD)
	{
		$cmd = $this->prepareCommand(
			$src_file,
			$dest_path,
			$address,
			$creds,
			$ptype
		);
		$expect = $this->getModule('expect');
		$expect->setCommand($cmd);
		if ($ptype === self::PTYPE_REG_CMD) {
			if (key_exists('passphrase', $creds) && !empty($creds['passphrase'])) {
				$expect->addAction('passphrase', $creds['passphrase'], self::SCP_COMMAND_TIMEOUT);
			} elseif (key_exists('password', $creds) && !empty($creds['password'])) {
				$expect->addAction('password:$', $creds['password'], self::SCP_COMMAND_TIMEOUT);
			} else {
				$expect->addAction('', '');
			}
		}
		$output = $expect->exec();
		return [
			'output' => $output,
			'exitcode' => $this->getExitCode($output)
		];
	}


	/**
	 * Prepare SSH command to execution.
	 *
	 * @param string $src_file file path to copy to destination host
	 * @param string $dest_path destination path on remote host
	 * @param string $address destination address
	 * @param array $params SSH parameters (user/SSH config...)
	 * @param int $ptype command pattern type
	 * @return string full command string
	 */
	private function prepareCommand($src_file, $dest_path, $address, array $params, $ptype)
	{
		$opts = [];
		if (key_exists('ssh_config', $params)) {
			$ssh_config = $this->getModule('ssh_config')->getConfigPath();
			if (!empty($ssh_config)) {
				$opts[] = '-F "' . $ssh_config . '"';
			}
		}
		if (key_exists('key', $params) && !empty($params['key'])) {
			$opts[] = '-i "' . $params['key'] . '"';
		}
		// Default options
		$opts[] = '-o StrictHostKeyChecking=no';
		$opts[] = '-o UserKnownHostsFile=/dev/null';

		$options = implode(' ', $opts);

		$username = '';
		if (key_exists('username', $params)) {
			$username = $params['username'];
		}

		$dest = $address;
		if (!empty($username)) {
			$dest = implode('@', [$username, $address]);
		}

		$ssh_cmd = $this->getCmdPattern($ptype);
		$cmd = sprintf(
			$ssh_cmd,
			self::CMD,
			$options,
			$src_file,
			$dest,
			$dest_path
		);
		return $this->prepareExpectCommand($cmd);
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
			case self::PTYPE_REG_CMD: $pattern = self::SCP_COMMAND_PATTERN;
				break;
			default: $pattern = self::SCP_COMMAND_PATTERN;
				break;
		}
		return $pattern;
	}

	/**
	 * Get remote command exit code basing on command output.
	 * In output there is provided EXITCODE=XX string with real exit code.
	 * If exitcode not found, default exit code is 100.
	 *
	 * @param array command output
	 * @param mixed $output
	 * @return int command exit code
	 */
	public static function getExitCode($output)
	{
		$exitcode = 100;
		$out = implode('', $output);
		if (preg_match('/EXITCODE=(?P<exitcode>\d+)/i', $out, $match) === 1) {
			$exitcode = (int) $match['exitcode'];
		}
		return $exitcode;
	}

	/**
	 * Use expect prepare SCP command to spawn.
	 *
	 * @param string $cmd SCP command
	 * @param string $file file for writing output
	 * @return string expect command ready to run
	 */
	private function prepareExpectCommand($cmd)
	{
		return 'expect -c \'spawn ' . $this->quoteExpectCommand($cmd) . '
set timeout ' . self::SCP_COMMAND_TIMEOUT . '
set prompt "(.*)\[#%>:\$\]  $"
expect {
	-re "\[Pp\]assword:" {
		expect_user -re "(.*)\n"
		set pwd $expect_out(1,string)
		send "$pwd\r"
		exp_continue
	}
	-re "passphrase" {
		expect_user -re "(.*)\n"
		set pwd $expect_out(1,string)
		send "$pwd\r"
		exp_continue
	}
	-re "$prompt" {
		puts "Prompt -> exit"
	}
	timeout {
		puts "Timeout occurred -> exit"
	}
	eof {
		puts ""
	}
}
lassign [wait] pid spawnid os_error_flag value
puts "EXITCODE=$value"
puts "quit"
exit\' 2>&1';
	}

	/**
	 * Quote special characters in expect spawn command.
	 *
	 * @param string spawn expect command
	 * @param mixed $cmd
	 * @return string spawn expect command with escaped special characters
	 */
	private function quoteExpectCommand($cmd)
	{
		return str_replace(
			['[', ']'],
			['\\[', '\\]'],
			$cmd
		);
	}
}
