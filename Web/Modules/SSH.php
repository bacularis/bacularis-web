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

use Prado\Prado;

/**
 * SSH command module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class SSH extends WebModule
{
	/**
	 * Base command to run.
	 */
	private const CMD = 'ssh -t';

	/**
	 * Pattern types used to prepare command.
	 */
	public const PTYPE_REG_CMD = 0;
	public const PTYPE_BG_CMD = 1;

	/**
	 * Foreground ssh command.
	 *
	 * Format: %cmd %options %user@%host %remote_cmd
	 */
	private const SSH_COMMAND_PATTERN = "%s %s \"%s\" \"%s\"";


	/**
	 * Background ssh command.
	 *
	 * Format: nohup %cmd %opts %user@%host %remote_cmd >%out_file
	 */
	private const SSH_BG_COMMAND_PATTERN = "%s %s \"%s\" \"%s\" 2>&1";

	public const OUTPUT_FILE_PREFIX = 'output_';

	/**
	 * SSH command timeout in seconds.
	 */
	private const SSH_COMMAND_TIMEOUT = 1200;

	/**
	 * Single expect case timeout in seconds.
	 */
	private const SSH_CASE_TIMEOUT = 20;

	private $config;

	public function init($config)
	{
		$this->loadConfig();
	}

	/**
	 * Load remote command configuration file.
	 */
	public function loadConfig()
	{
		//$this->config = $this->getModule();
	}

	/**
	 * Execute remote command.
	 *
	 * @param string $address destination host address
	 * @param array $creds credentials ('username', 'password', 'sshkey', 'passphrase')
	 * @param array $command command to execute
	 * @param int $ptype command pattern type
	 * @return array command output, exitcode and output file for background commands
	 */
	public function execCommand($address, $creds = [], $command = [], $ptype = self::PTYPE_REG_CMD)
	{
		$cmd = $this->prepareCommand($address, $creds, $command, $ptype);
		$expect = $this->getModule('expect');
		$expect->setCommand($cmd['cmd']);
		$env = [];
		if ($ptype === self::PTYPE_REG_CMD) {
			if (key_exists('passphrase', $creds) && !empty($creds['passphrase'])) {
				$expect->addAction('passphrase', $creds['passphrase'], self::SSH_CASE_TIMEOUT);
			} elseif (key_exists('password', $creds) && !empty($creds['password'])) {
				$expect->addAction('password:$', $creds['password'], self::SSH_CASE_TIMEOUT);
			} else {
				$expect->addAction('', '');
			}
			if ($creds['use_sudo'] && !empty($creds['password_sudo'])) {
				$expect->addAction('.sudo. password for.*:', $creds['password_sudo'], self::SSH_CASE_TIMEOUT);
			}
		} elseif ($ptype === self::PTYPE_BG_CMD) {
			if (key_exists('password', $creds) && !empty($creds['password'])) {
				$env['PASSWORD'] = $creds['password'];
			}
			if (key_exists('passphrase', $creds) && !empty($creds['passphrase'])) {
				$env['PASSPHRASE'] = $creds['passphrase'];
			}
			if ($creds['use_sudo'] && !empty($creds['password_sudo'])) {
				$env['PASSWORD_SUDO'] = $creds['password_sudo'];
			}
		}
		$output = $expect->exec($env);
		$output = explode(PHP_EOL, implode('', $output));
		$exitcode = self::getExitCode($output);
		return [
			'output' => $output,
			'output_id' => $cmd['output_id'],
			'exitcode' => $exitcode
		];
	}


	/**
	 * Prepare SSH command to execution.
	 *
	 * @param string $address destination address
	 * @param array $params SSH parameters (user/SSH config...)
	 * @param array $command command to execute on remote host
	 * @param int $ptype command pattern type
	 * @return string full command string
	 */
	private function prepareCommand($address, array $params, array $command, $ptype)
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

		$remote_cmd = '';
		if (count($command) > 0) {
			$remote_cmd = 'LANG=C ' . implode(' ', $command);
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
			$dest,
			$remote_cmd
		);
		$file = $output_id = '';
		if ($ptype == self::PTYPE_BG_CMD) {
			$file = $this->prepareOutputFile();
			$f = basename($file);
			$output_id = str_replace(self::OUTPUT_FILE_PREFIX, '', $f);
		}
		return [
			'cmd' => $this->prepareExpectCommand($cmd, $file),
			'output_id' => $output_id
		];
	}

	/**
	 * Get command pattern by pattern type.
	 *
	 * @param int $ptype command pattern type
	 * @return string command pattern
	 */
	private function getCmdPattern($ptype)
	{
		$pattern = null;
		switch ($ptype) {
			case self::PTYPE_REG_CMD: $pattern = self::SSH_COMMAND_PATTERN;
				break;
			case self::PTYPE_BG_CMD: $pattern = self::SSH_BG_COMMAND_PATTERN;
				break;
			default: $pattern = self::SSH_COMMAND_PATTERN;
				break;
		}
		return $pattern;
	}

	/**
	 * Prepare output file for remote background commands.
	 * It is for commands that take long time.
	 *
	 * @return string output file name with path
	 */
	private function prepareOutputFile()
	{
		$dir = Prado::getPathOfNamespace('Bacularis.Web.Config');
		$fname = tempnam($dir, self::OUTPUT_FILE_PREFIX);
		return $fname;
	}

	/**
	 * Read output file for remote background commands.
	 *
	 * @param string $out_id output identifier
	 * @param array output from file and exitcode
	 */
	public static function readOutputFile($out_id)
	{
		$output = [];
		$dir = Prado::getPathOfNamespace('Bacularis.Web.Config');

		if (preg_match('/^[a-z0-9]+$/i', $out_id) === 1) {
			$file = $dir . '/' . self::OUTPUT_FILE_PREFIX . $out_id;
			if (file_exists($file)) {
				$output = file($file);
			}
			$output_count = count($output);
			$last = $output_count > 0 ? trim($output[$output_count - 1]) : '';
			if ($last === 'quit') {
				$output = array_map('rtrim', $output);
				// output is complete, so remove the file
				unlink($file);
			} else {
				// show output only on last quit
				$output = [];
			}
		}
		$exitcode = self::getExitCode($output);
		return [
			'output' => $output,
			'exitcode' => $exitcode
		];
	}


	/**
	 * Get remote command exit code basing on command output.
	 * In output there is provided EXITCODE=XX string with real exit code.
	 * If exitcode not found, default exit code is -1.
	 *
	 * @param array command output
	 * @param array $output
	 * @return int command exit code
	 */
	private static function getExitCode(array $output)
	{
		$exitcode = -1; // -1 means that process is pending
		$output_count = count($output);
		if ($output_count > 1 && preg_match('/^EXITCODE=(?P<exitcode>\d+)$/i', $output[$output_count - 2], $match) === 1) {
			$exitcode = (int) $match['exitcode'];
		}
		return $exitcode;
	}

	/**
	 * Prepare command to execution via expect.
	 *
	 * @param string $cmd command to spawn
	 * @param string $file file path to put output (only for background commands)
	 * @return string command to execute
	 */
	private function prepareExpectCommand($cmd, $file)
	{
		$command = '';
		if (!empty($file)) {
			$command = $this->prepareExpectBgCommand($cmd, $file);
		} else {
			$command = $this->prepareExpectFgCommand($cmd);
		}
		return $command;
	}

	/**
	 * Use foreground expect prepare SSH command to spawn.
	 * For getting password from stdin, envronment variables into change:
	 *   expect_user -re "(.*)\n"
	 *   set pwd $expect_out(1,string)
	 *   send "$pwd\r"
	 *
	 * @param string $cmd SSH command
	 * @param string $file file for writing output
	 * @return string expect command ready to run
	 */
	private function prepareExpectFgCommand($cmd)
	{
		return 'expect -c \'spawn ' . $this->quoteExpectCommand($cmd) . '
set timeout ' . self::SSH_COMMAND_TIMEOUT . '
set prompt "(.*)\[#%>:\$\]  $"
expect {
	-re "\[Pp\]assword:" {
		expect_user -re "(.*)\n"
		set pwd $expect_out(1,string)
		send "$pwd\r"
		exp_continue
	}
	-re ".sudo. password for.*:" {
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
puts "\nEXITCODE=$value"
puts "quit"
exit\'';
	}

	/**
	 * Use background expect prepare SSH command to spawn.
	 *
	 * @param string $cmd SSH command
	 * @param string $file file for writing output
	 * @return string expect command ready to run
	 */
	private function prepareExpectBgCommand($cmd, $file)
	{
		return 'expect -c \'spawn ' . $this->quoteExpectCommand($cmd) . '
set timeout ' . self::SSH_COMMAND_TIMEOUT . '
set prompt "(.*)\[#%>:\$\]  $"
expect {
	-re "\[Pp\]assword:" {
		send "$env(PASSWORD)\r"
		exp_continue
	}
	-re ".sudo. password for.*:" {
		send "$env(PASSWORD_SUDO)\r"
		exp_continue
	}
	-re "passphrase for key" {
		send "$env(PASSPHRASE)\r"
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
puts "\nEXITCODE=$value"
puts "quit"
exit\' 1>' . $file . ' 2>&1 &';
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
			['[', ']', '$'],
			['\\[', '\\]', '\\\\\\$'],
			$cmd
		);
	}
}
