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
 * Manage SSH keys.
 * Module is responsible for get/set SSH keys data.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Config
 */
class SSHKey extends WebModule
{
	/**
	 * Allowed characters pattern for SSH key name.
	 */
	public const SSH_KEY_NAME_PATTERN = '[a-zA-Z0-9\.\-_]+';

	/**
	 * SSH keys file path
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.Web.Config';

	/**
	 * SSH key pattern
	 */
	public const SSH_KEY_PATTERN = '/ssh_*.pem';

	/**
	 * Get SSH keys.
	 * It returns key names with key figerprint.
	 *
	 * @return array SSH keys
	 */
	public function getKeys()
	{
		$keys = [];
		$path = Prado::getPathOfNamespace(self::CONFIG_FILE_PATH);
		$pattern = $path . self::SSH_KEY_PATTERN;
		$iterator = new \GlobIterator($pattern);
		while ($iterator->valid()) {
			$key = $iterator->current()->getFilename();
			$kpath = $iterator->current()->getPathname();
			$keys[] = [
				'key' => $this->getNameByKey($key),
				'fingerprint' => $this->getSSHKeyFingerprint($kpath)
			];
			$iterator->next();
		}
		return $keys;
	}

	/**
	 * Set single SSH key.
	 *
	 * @param string $name SSH key name
	 * @param string $key SSH key body
	 * @return bool true on success, otherwise false
	 */
	public function setKey($name, $key)
	{
		$path = Prado::getPathOfNamespace(self::CONFIG_FILE_PATH);
		$kname = $this->getKeyByName($name);
		$kpath = implode(DIRECTORY_SEPARATOR, [$path, $kname]);
		$ret = false;
		if (!file_exists($kpath)) {
			$key = trim($key);
			$key = preg_replace("/\r\n/", "\n", $key) . "\n";
			$orig_umask = umask(0);
			umask(0077);
			$ret = (file_put_contents($kpath, $key) !== false);
			umask($orig_umask);
		}
		return $ret;
	}

	/**
	 * Remove single SSH key.
	 *
	 * @param string $key key name
	 * @param mixed $name
	 * @return bool true on success, otherwise false
	 */
	public function removeKey($name)
	{
		$path = Prado::getPathOfNamespace(self::CONFIG_FILE_PATH);
		$kname = $this->getKeyByName($name);
		$kpath = implode(DIRECTORY_SEPARATOR, [$path, $kname]);
		$ret = false;
		if (file_exists($kpath)) {
			$ret = unlink($kpath);
		}
		return $ret;
	}

	/**
	 * Get full SSH key name by short name.
	 *
	 * @param string short key name
	 * @param mixed $name
	 * @return string full key name
	 */
	private function getKeyByName($name)
	{
		return sprintf('ssh_%s.pem', $name);
	}

	/**
	 * Get full SSH key path by short name.
	 *
	 * @param string short key name
	 * @param mixed $name
	 * @return string full key path
	 */
	public function getPathByName($name)
	{
		$path = Prado::getPathOfNamespace(self::CONFIG_FILE_PATH);
		$key = $this->getKeyByName($name);
		$kpath = implode(DIRECTORY_SEPARATOR, [$path, $key]);
		return $kpath;
	}

	/**
	 * Get key name from full SSH key path.
	 *
	 * @param string short key name
	 * @param mixed $path
	 * @return string full key path
	 */
	public function getNameByKey($path)
	{
		$key = basename($path);
		$key = preg_replace('/^ssh_|\.pem$/', '', $key);
		return $key;
	}

	/**
	 * Compute SSH key fingerprint.
	 * NOTE: This method computes wrong fingerprint and needs more work.
	 * From this reason fingerprint is not used yet on the interface.
	 *
	 * @param string $kpath SSH key path
	 * @param string $algo fingerprint algorithm (supported: sha1 and md5)
	 * @return string SSH key fingerprint or empty string of failure
	 */
	private function getSSHKeyFingerprint($kpath, $algo = 'md5')
	{
		$ret = '';
		if (!file_exists($kpath)) {
			return $ret;
		}
		$key_pem = file_get_contents($kpath);
		$result = preg_match(
			'/^-----BEGIN (?:[A-Z]+ )?PRIVATE KEY-----\n(?P<key>[\S\s]+)\n-----END (?:[A-Z]+ )?PRIVATE KEY-----$/',
			$key_pem,
			$match
		);
		$key = ($result === 1) ? $match['key'] : $key_pem;
		$key = preg_replace('/\s+/', '', $key);
		$key = base64_decode($key);
		$hkey = '';
		if ($algo == 'sha1') {
			$hkey = sha1($key);
		} elseif ($algo == 'md5') {
			$hkey = md5($key);
		}
		$ret = implode(':', str_split($hkey, 2));
		return $ret;
	}
}
