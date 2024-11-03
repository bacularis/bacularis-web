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

use Bacularis\Common\Modules\Logging;
use Bacularis\Web\Modules\WebModule;
use Prado\Prado;

/**
 * Manage API host deployment.
 * Set of useful tools for deploying API hosts.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class DeployAPIHost extends WebModule
{
	/**
	 * RPM package repository include directory.
	 */
	public const RPM_REPOSITORY_DIR = '/etc/yum.repos.d';

	/**
	 * DEB package repository include directory.
	 */
	public const DEB_REPOSITORY_DIR = '/etc/apt/sources.list.d';

	/**
	 * DEB package repository authentication include directory.
	 */
	public const DEB_REPOSITORY_AUTH_DIR = '/etc/apt/auth.conf.d';

	/**
	 * Temporary directory to store files copied by SCP command before
	 * they are copied to destination places.
	 */
	public const TMP_DIR = '/tmp/bacularis-deploy';

	/**
	 * Get command to test connection to destination host.
	 * It is useful to do connection check before start deployment.
	 */
	public function getCheckHostConnectionCommand(): array
	{
		return ['ls', '/'];
	}

	/**
	 * Get command to create temporary directory for deployment process.
	 * To this directory are copied files before they are distributed
	 * to destination paths.
	 *
	 * @return array create temp directory command
	 */
	public function getCreateTmpDirCommand()
	{
		return ['mkdir', '-p', '-m', '700', self::TMP_DIR];
	}

	/**
	 * Get command to raemove temporary directory for deployment process.
	 * It is removed only if it is empty.
	 *
	 * @return array remove temp directory command
	 */
	public function getRemoveTmpDirCommand()
	{
		return ['rmdir', self::TMP_DIR];
	}

	/**
	 * Prepare file with repository definition.
	 * This file is transfered to remote host.
	 *
	 * @param string $type package repository type (possible values: deb or rpm)
	 * @param string $name repository file name
	 * @param string $entry repository entry to put in repository file
	 * @param string $key repository key to put in repository file
	 * @param string $repo_auth repo auth name
	 * @return array source and destination file parameters
	 */
	public function prepareRepositoryFile($type, $name, $entry, $key, $repo_auth = '')
	{
		$repo = '';
		$rfile = '';
		switch ($type) {
			case 'rpm': {
				$repo = $this->getRPMRepositoryEntry($name, $entry, $key, $repo_auth);
				$rfile = implode('/', [self::RPM_REPOSITORY_DIR, $name . '.repo']);
				break;
			}
			case 'deb': {
				$repo = $this->getDEBRepositoryEntry($name, $entry, $key);
				$rfile = implode('/', [self::DEB_REPOSITORY_DIR, $name . '.list']);
				break;
			}
		}

		$src_file = '';
		$dst_file = '';
		if (!empty($repo) && !empty($rfile)) {
			$dir = Prado::getPathOfNamespace('Bacularis.Web.Config');
			$src_file = implode(DIRECTORY_SEPARATOR, [$dir, basename($rfile)]);
			if (file_put_contents($src_file, $repo) === false) {
				Logging::log(
					Logging::CATEGORY_APPLICATION,
					'Cannot create repository file ' . $src_file
				);
			}
			$dst_file = $rfile;
		}
		return [
			'src_file' => $src_file,
			'dst_file' => $dst_file,
			'perm' => '640',
			'user' => 'root',
			'group' => 'root'
		];
	}

	/**
	 * Prepare and get the RPM repository file content.
	 *
	 * @param string $name repository file name
	 * @param string $entry repository entry to put in repository file
	 * @param string $key repository key to put in repository file
	 * @param string $repo_auth repo auth name
	 * @return string string ready to use repository file content
	 */
	private function getRPMRepositoryEntry($name, $entry, $key, $repo_auth)
	{
		$auth = '';
		$repoauth_config = $this->getModule('repoauth_config');
		if (!empty($repo_auth)) {
			$auth = $repoauth_config->getRepoAuthConfig($repo_auth);
		} else {
			$auth = $repoauth_config->getDefaultRepoAuthConfig();
		}
		$user_pass = '';
		if (!empty($auth)) {
			$user_pass = sprintf(
				'username=%s
password=%s
',
				$auth['username'],
				$auth['password']
			);
		}
		return "[$name]
name=$name repository
baseurl=$entry
gpgcheck=1
gpgkey=$key
enabled=1
$user_pass
";
	}

	/**
	 * Prepare and get the DEB repository file content.
	 *
	 * @param string $name repository file name
	 * @param string $entry repository entry to put in repository file
	 * @param string $key repository key to put in repository file
	 * @return string ready to use repository file content
	 */
	private function getDEBRepositoryEntry($name, $entry, $key)
	{
		return "deb [signed-by=$key] $entry
";
	}

	public function prepareDEBRepositoryAuthFile(string $repo_url, array $repo_auth): array
	{
		$dir = Prado::getPathOfNamespace('Bacularis.Web.Config');
		$dst_file = implode('/', [self::DEB_REPOSITORY_AUTH_DIR, 'bacularis-app.conf']);
		$src_file = implode(DIRECTORY_SEPARATOR, [$dir, basename($dst_file)]);
		$repo = "machine $repo_url login {$repo_auth['username']} password {$repo_auth['password']}";
		if (file_put_contents($src_file, $repo) === false) {
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				'Cannot create DEB repository auth file ' . $src_file
			);
		}
		return [
			'src_file' => $src_file,
			'dst_file' => $dst_file,
			'perm' => '600',
			'user' => 'root',
			'group' => 'root'
		];
	}

	/**
	 * Prepare GPG verification key to use for DEB type repository.
	 *
	 * @param string $key_path key local path
	 * @param string $dest_file key remote destination path
	 * @return array source and destination file parameters
	 */
	public function prepareGPGKey($key_path, $dest_file)
	{
		$key = file_get_contents($key_path);
		if ($key === false) {
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				'Cannot get GPG key file ' . $key_path
			);
			return false;
		}
		$dir = Prado::getPathOfNamespace('Bacularis.Web.Config');
		$file = implode(DIRECTORY_SEPARATOR, [$dir, 'bacularis.pub']);
		if (!file_put_contents($file, $key)) {
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				'Cannot write armored GPG key file ' . $file
			);
			return false;
		}
		if (file_exists("{$file}.gpg")) {
			// unlink previous key if any
			unlink("{$file}.gpg");
		}
		$key_dearmor = $this->getModule('gpg')->execCommand($file, ['dearmor' => true]);
		if ($key_dearmor['exitcode'] !== 0) {
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				'Error while dearmoring GPG key ' . $file
			);
			return false;
		}

		$dst_file = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, ['usr', 'share', 'keyrings', $dest_file]);
		$src_file = "{$file}.gpg";
		$file = [
			'src_file' => $src_file,
			'dst_file' => $dst_file,
			'perm' => '644',
			'user' => 'root',
			'group' => 'root'
		];
		return $file;
	}

	/**
	 * Prepare SUDO file with package/bconsole/json/action commands access.
	 *
	 * @param array $osprofile current OS profile configuration
	 * @return array source and destination file parameters
	 */
	public function prepareSUDOFile(array $osprofile)
	{
		$sudo_cmd = [];

		foreach ($osprofile as $key => $value) {
			$cmd = '';
			if ($osprofile['packages_use_sudo'] === '1' && preg_match('/^packages_(cat|dir|sd|fd|bcons)(_(pre|post))?_(install|upgrade|remove|info|enable)(_cmd)?$/', $key) === 1) {
				$cmd = $value;
			} elseif ($osprofile['bconsole_use_sudo'] === '1' && $key == 'bconsole_bin_path') {
				$cmd = $value;
			} elseif ($osprofile['jsontools_use_sudo'] === '1' && preg_match('/^jsontools_b(dir|sd|fd|bcons)json_path$/', $key) === 1) {
				$cmd = $value;
			} elseif ($osprofile['actions_use_sudo'] === '1' && preg_match('/^actions_(cat|dir|sd|fd)_(start|stop|restart)$/', $key) === 1) {
				$cmd = $value;
			}
			if (!empty($cmd)) {
				$sudo_cmd[] = "{$osprofile['packages_sudo_user']} ALL = (root) NOPASSWD: $cmd";
			}
		}

		$src_file = '';
		$dst_file = '';
		if (count($sudo_cmd) > 0) {
			array_unshift($sudo_cmd, "Defaults:{$osprofile['packages_sudo_user']} !requiretty");
			$sudo_cmd[] = ''; // last line in sudo must be empty
			$fbody = implode(PHP_EOL, $sudo_cmd);
			$dst_file = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, ['etc', 'sudoers.d', 'bacularis-api']);
			$dir = Prado::getPathOfNamespace('Bacularis.Web.Config');
			$src_file = implode(DIRECTORY_SEPARATOR, [$dir, basename($dst_file)]);
			if (file_put_contents($src_file, $fbody) === false) {
				Logging::log(
					Logging::CATEGORY_APPLICATION,
					'Cannot create sudo file ' . $src_file
				);
			}
		}
		return [
			'src_file' => $src_file,
			'dst_file' => $dst_file,
			'perm' => '644',
			'user' => 'root',
			'group' => 'root'
		];
	}

	/**
	 * Prepare main API configuration file basing on information from OS profile.
	 *
	 * @param array $osprofile current OS profile configuration
	 * @return array source and destination file parameters
	 */
	public function prepareConfigureFile(array $osprofile)
	{
		$packages = $db = $bconsole = $jsontools = $actions = [];
		foreach ($osprofile as $key => $value) {
			if (preg_match('/^packages_(?P<key>(cat|dir|sd|fd|bcons|use)(_(pre|post))?_(install|upgrade|remove|info|enable|sudo)(_cmd)?)$/', $key, $match) === 1) {
				$packages[$match['key']] = $value;
			} elseif (preg_match('/^db_(?P<key>\w+)$/', $key, $match) === 1) {
				$db[$match['key']] = $value;
			} elseif (preg_match('/^bconsole_(?P<key>\w+)$/', $key, $match) === 1) {
				$bconsole[$match['key']] = $value;
			} elseif (preg_match('/^jsontools_(?P<key>\w+)$/', $key, $match) === 1) {
				$jsontools[$match['key']] = $value;
			} elseif (preg_match('/^actions_(?P<key>\w+)$/', $key, $match) === 1) {
				$actions[$match['key']] = $value;
			}
		}
		$api = [
			'auth_type' => 'basic',
			'debug' => '0',
			'lang' => 'en'
		];
		if (!empty($db['type']) && !empty($db['name'])) {
			$db['enabled'] = 1;
		} else {
			$db['enabled'] = 0;
		}

		if (!empty($bconsole['bin_path']) && !empty($bconsole['cfg_path'])) {
			$bconsole['enabled'] = 1;
		} else {
			$bconsole['enabled'] = 0;
		}

		if (!empty($jsontools['bdirjson_path']) || !empty($jsontools['bsdjson_path']) || !empty($jsontools['bfdjson_path']) || !empty($jsontools['bbconsjson_path'])) {
			$jsontools['enabled'] = 1;
		} else {
			$jsontools['enabled'] = 0;
		}

		if (!empty($actions['cat_start']) || !empty($actions['dir_start']) || !empty($actions['fd_start']) || !empty($actions['sd_start'])) {
			$actions['enabled'] = 1;
		} else {
			$actions['enabled'] = 0;
		}

		if (count($packages) > 0) {
			$packages['enabled'] = 1;
		}
		$config = [
			'api' => $api,
			'db' => $db,
			'bconsole' => $bconsole,
			'jsontools' => $jsontools,
			'actions' => $actions,
			'software_management' => $packages
		];
		$fbody = $this->getModule('config_ini')->prepareConfig($config);
		$dst_file = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, ['etc', 'bacularis', 'API', 'api.conf']);
		$dir = Prado::getPathOfNamespace('Bacularis.Web.Config');
		$src_file = implode(DIRECTORY_SEPARATOR, [$dir, basename($dst_file)]);
		if (file_put_contents($src_file, $fbody) === false) {
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				'Cannot create Bacularis configuration file ' . $src_file
			);
		}
		return [
			'src_file' => $src_file,
			'dst_file' => $dst_file,
			'perm' => '600',
			'user' => $osprofile['packages_sudo_user']
		];
	}

	/**
	 * Prepare command to create user file on remote host.
	 *
	 * @param array $osprofile current OS profile configuration
	 * @return array source and destination file parameters
	 */
	public function prepareCreateUserFile(array $osprofile)
	{
		$fbody = "[{$osprofile['bacularis_admin_user']}]
bconfig_cfg_path = \"\"
";
		$dst_file = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, ['etc', 'bacularis', 'API', 'basic.conf']);
		$dir = Prado::getPathOfNamespace('Bacularis.Web.Config');
		$src_file = implode(DIRECTORY_SEPARATOR, [$dir, basename($dst_file)]);
		if (file_put_contents($src_file, $fbody) === false) {
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				'Cannot create Bacularis user file ' . $src_file
			);
		}
		return [
			'src_file' => $src_file,
			'dst_file' => $dst_file,
			'perm' => '600',
			'user' => $osprofile['packages_sudo_user']
		];
	}

	/**
	 * Prepare command to create user file on remote host.
	 *
	 * @param array $osprofile current OS profile configuration
	 * @param mixed $type
	 * @return array source and destination file parameters
	 */
	public function prepareUserPwdFile(array $osprofile, $type)
	{
		$pwd = $this->getModule('crypto')->getHashedPassword($osprofile['bacularis_admin_pwd']);
		$creds = [$osprofile['bacularis_admin_user'], $pwd];
		$fbody = implode(':', $creds) . PHP_EOL;
		$dst_file = [];
		if ($type == 'API') {
			$dst_file = $this->getUserAPIPwdFile();
		} elseif ($type == 'Web') {
			$dst_file = $this->getUserWebPwdFile();
		}
		$dir = Prado::getPathOfNamespace('Bacularis.Web.Config');
		$src_file = implode(DIRECTORY_SEPARATOR, [$dir, basename($dst_file) . '_new']);
		if (file_put_contents($src_file, $fbody) === false) {
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				'Cannot create Bacularis password file ' . $src_file
			);
		}
		return [
			'src_file' => $src_file,
			'dst_file' => $dst_file,
			'perm' => '600',
			'user' => $osprofile['packages_sudo_user']
		];
	}

	/**
	 * Get API user password file path.
	 *
	 * @return string user password file path
	 */
	private function getUserAPIPwdFile()
	{
		$path = ['etc', 'bacularis', 'API', 'bacularis.users'];
		return DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $path);
	}

	/**
	 * Get Web user password file path.
	 *
	 * @return string user password file path
	 */
	private function getUserWebPwdFile()
	{
		$path = ['etc', 'bacularis', 'Web', 'bacularis.users'];
		return DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $path);
	}

	/**
	 * Get command to set file ownership.
	 *
	 * @param array $file file parameters
	 * @param array $params deployment options
	 * @return array command to set ownership
	 */
	public function getSetOwnershipCommand($file, $params)
	{
		$ret = [
			'chown',
			$file['user'] . (isset($file['group']) ? ':' . $file['group'] : ''),
			$file['dst_file']
		];
		if ($params['use_sudo']) {
			array_unshift($ret, 'sudo');
		}
		return $ret;
	}

	/**
	 * Get command to set file permissions.
	 *
	 * @param array $file file parameters
	 * @param array $params deployment options
	 * @return array command to set permissions
	 */
	public function getSetPermissionsCommand($file, $params)
	{
		$ret = [
			'chmod',
			$file['perm'],
			$file['dst_file']
		];
		if ($params['use_sudo']) {
			array_unshift($ret, 'sudo');
		}
		return $ret;
	}

	/**
	 * Get command to prepare SSL certificate for web server HTTPS connections.
	 * This command uses the OpenSSL binary.
	 * NOTE: Destination ceritifcate path is hardcorded.
	 *
	 * @param string $address remote host address
	 * @param array $params deployment options
	 * @return array command to prepare certificate
	 */
	public function getPrepareHTTPSCertCommand($address, $params)
	{
		$cert_dir = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, [
			'etc',
			'bacularis'
		]);
		$ret = [
			'openssl',
			'req',
			'-x509',
			'-nodes',
			'-days 3650',
			'-newkey',
			'rsa:2048',
			'-keyout',
			$cert_dir . '/bacularis.key',
			'-out',
			$cert_dir . '/bacularis.crt',
			'<<EOF
ZZ
Bacularis
Bacularis
Bacularis web interface
Bacularis
' . $address . '
non-existing-email@localhost
EOF'];
		if ($params['use_sudo']) {
			array_unshift($ret, 'sudo');
		}
		return $ret;
	}

	/**
	 * Get command to prepare certificate PEM file for HTTPS connection.
	 * NOTE: Destination ceritifcate path is hardcorded.
	 *
	 * @param array $params deployment options
	 * @return array command to prepare certificate PEM file
	 */
	public function getPrepareHTTPSPemCommand($params)
	{
		$cert_dir = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, [
			'etc',
			'bacularis'
		]);
		$ret = [
			'{',
			'cat',
			$cert_dir . '/bacularis.key',
			$cert_dir . '/bacularis.crt',
			'>',
			$cert_dir . '/bacularis.pem',
			';',
			'chmod 600 ' . $cert_dir . '/bacularis.pem',
			';',
			'}'
		];
		if ($params['use_sudo']) {
			array_unshift($ret, 'sudo');
		}
		return $ret;
	}

	/**
	 * Get command to enable HTTPS connection in the Nginx web server configuration file.
	 * NOTE: Web server configuration file paths are hardcorded.
	 *
	 * @param string $package_type package repository type (possible values: deb or rpm)
	 * @param array $params deployment options
	 * @return array command to enable HTTPS in Nginx config
	 */
	public function getEnableHTTPSNginxCommand($package_type, $params)
	{
		$cfg_path = '';
		if ($package_type == 'rpm') {
			$cfg_path = '/etc/nginx/conf.d/bacularis.conf';
		} elseif ($package_type == 'deb') {
			$cfg_path = '/etc/nginx/sites-available/bacularis.conf';
		}

		$ret = [
			'sed',
			'-i',
			'-e',
			'\"/ssl_certificate/d\"',
			'-e',
			'\"s/listen 9097;$/listen 9097 ssl;/\"',
			'-e',
			'\"/index index.php;/a ssl_certificate_key /etc/bacularis/bacularis.key;\"',
			'-e',
			'\"/index index.php;/a ssl_certificate /etc/bacularis/bacularis.crt;\"',
			$cfg_path
		];
		if ($params['use_sudo']) {
			array_unshift($ret, 'sudo');
		}
		return $ret;
	}

	/**
	 * Get command to enable HTTPS connection in the Apache web server configuration file.
	 * NOTE: Web server configuration file paths are hardcorded.
	 *
	 * @param string $package_type package repository type (possible values: deb or rpm)
	 * @param array $params deployment options
	 * @return array command to enable HTTPS in Apache config
	 */
	public function getEnableHTTPSApacheCommand($package_type, $params)
	{
		$cfg_path = '';
		if ($package_type == 'rpm') {
			$cfg_path = '/etc/httpd/conf.d/bacularis.conf';
		} elseif ($package_type == 'deb') {
			$cfg_path = '/etc/apache2/sites-available/bacularis.conf';
		}
		$ret = [
			'sed',
			'-i',
			'-e',
			'\"/SSLEngine on/d\"',
			'-e',
			'\"/SSLCertificate/d\"',
			'-e',
			'\"/ServerName/a SSLEngine on\"',
			'-e',
			'\"/ServerName/a SSLCertificateKeyFile /etc/bacularis/bacularis.key\"',
			'-e',
			'\"/ServerName/a SSLCertificateFile /etc/bacularis/bacularis.crt\"',
			$cfg_path
		];
		if ($params['use_sudo']) {
			array_unshift($ret, 'sudo');
		}
		return $ret;
	}

	/**
	 * Get command to enable HTTPS connection in the Lighttpd web server configuration file.
	 * NOTE: Web server configuration file path is hardcorded.
	 *
	 * @param array $params deployment options
	 * @return array command to enable HTTPS in Nginx config
	 */
	public function getEnableHTTPSLighttpdCommand($params)
	{
		$cfg_path = '/etc/bacularis/bacularis-lighttpd.conf';
		$ret = [
			'{',
			'sed',
			'-i',
			'-e',
			'\"/ssl./d\"',
			'-e',
			'\"/mod_openssl/d\"',
			$cfg_path,
			';',
			'echo',
			'\"server.modules += ( \\\\\"mod_openssl\\\\\" )
ssl.engine = \\\\\"enable\\\\\"
ssl.pemfile = \\\\\"/etc/bacularis/bacularis.pem\\\\\"
\"',
			'>>',
			$cfg_path,
			';',
			'}'
		];
		if ($params['use_sudo']) {
			array_unshift($ret, 'sudo');
		}
		return $ret;
	}
}
