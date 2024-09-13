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

use Bacularis\Common\Modules\IBacularisNotificationPlugin;
use Bacularis\Web\Modules\BacularisWebPluginBase;
use Prado\Prado;

/**
 * The Mattermost notification plugin module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Plugin
 */
class MattermostNotification extends BacularisWebPluginBase implements IBacularisNotificationPlugin
{
	/**
	 * Maximum characters number in title.
	 */
	private const MAX_TITLE_SIZE = 60;

	/**
	 * Get plugin name displayed in web interface.
	 *
	 * @return string plugin name
	 */
	public static function getName(): string
	{
		return 'Mattermost notification';
	}

	/**
	 * Get plugin version.
	 *
	 * @return string plugin version
	 */
	public static function getVersion(): string
	{
		return '1.0.0';
	}

	/**
	 * Get plugin type.
	 *
	 * @return string plugin type
	 */
	public static function getType(): string
	{
		return 'notification';
	}

	/**
	 * Get plugin configuration parameters.
	 *
	 * return array plugin parameters
	 */
	public static function getParameters(): array
	{
		return [
			['name' => 'webhook_url', 'type' => 'string', 'default' => '', 'label' => 'Webhook URL'],
			['name' => 'channel', 'type' => 'string', 'default' => '', 'label' => 'Channel'],
			['name' => 'username', 'type' => 'string', 'default' => '', 'label' => 'Message post username'],
			['name' => 'icon_url', 'type' => 'string', 'default' => '', 'label' => 'Message post profile picture URL'],
			['name' => 'icon_emoji', 'type' => 'string', 'default' => '', 'label' => 'Message post profile emoji'],
			['name' => 'type', 'type' => 'string', 'default' => '', 'label' => 'Message type'],
			['name' => 'props', 'type' => 'string', 'default' => '', 'label' => 'Message properties'],
			['name' => 'priority', 'type' => 'string', 'default' => '', 'label' => 'Message priority'],
			['name' => 'content', 'type' => 'string_long', 'default' => "Type: %type\nCategory: %category\nUser: %user\nIP address/Hostname: %address\nMessage: %content", 'label' => 'Message content'],
			['name' => 'msg_types', 'type' => 'array_multiple', 'data' => ['INFO', 'WARNING', 'ERROR'], 'default' => ['ERROR'], 'label' => 'Log types'],
			['name' => 'msg_categories', 'type' => 'array_multiple', 'data' => ['Config', 'Action', 'Application', 'Security'], 'default' => ['Security'], 'label' => 'Log categories']
		];
	}

	/**
	 * Main execute command.
	 * It sends e-mail with the notification message.
	 *
	 * @param string $type message type (INFO, WARNING, ERROR)
	 * @param string $category message category (Config, Action, Application, Security)
	 * @param string $msg message content
	 * @return bool true on success, false otherwise
	 */
	public function execute(string $type, string $category, string $msg): bool
	{
		$result = false;
		$config = $this->getConfig();
		if (in_array($type, $config['parameters']['msg_types']) && in_array($category, $config['parameters']['msg_categories'])) {
			$content = $this->getContent($type, $category, $msg);
			$result = $this->sendMessage($content);
		}
		return $result;
	}

	/**
	 * Get formatted message.
	 *
	 * @param string $type message type (INFO, WARNING, ERROR)
	 * @param string $category message category (Config, Action, Application, Security)
	 * @param string $msg message content
	 * @return string formatted message
	 */
	private function getContent(string $type, string $category, string $msg): string
	{
		$config = $this->getConfig();
		$content = $config['parameters']['content'];
		$this->setKeywords($type, $category, $msg, $content);
		return $content;
	}

	private function sendMessage(string $content)
	{
		$config = $this->getConfig();
		$url = $config['parameters']['webhook_url'];
		$headers = ['Content-type: application/json'];
		$params = $this->getMessageParams($content);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		// For debugging purposes, check $result content
		$result = curl_exec($ch);
		$errno = curl_errno($ch);
		curl_close($ch);
		return ($errno == 0);
	}

	private function getMessageParams(string $content): string
	{
		$config = $this->getConfig();
		$params = [];
		if ($config['parameters']['channel']) {
			$params['channel'] = $config['parameters']['channel'];
		}
		if ($config['parameters']['username']) {
			$params['username'] = $config['parameters']['username'];
		}
		if ($config['parameters']['icon_url']) {
			$params['icon_url'] = $config['parameters']['icon_url'];
		}
		if ($config['parameters']['icon_emoji']) {
			$params['icon_emoji'] = $config['parameters']['icon_emoji'];
		}
		if ($config['parameters']['type']) {
			$params['type'] = $config['parameters']['type'];
		}
		if ($config['parameters']['props']) {
			$params['props'] = json_decode($config['parameters']['props'], true);
		}
		if ($config['parameters']['priority']) {
			$params['priority'] = $config['parameters']['priority'];
		}
		$params['text'] = $content;
		return json_encode($params);
	}

	/**
	 * Find and replace keywords into appropriate values.
	 *
	 * @param string $type message type (INFO, WARNING, ERROR)
	 * @param string $category message category (Config, Action, Application, Security)
	 * @param string $content message raw content
	 * @param string $text text with keywords (pattern)
	 */
	private function setKeywords(string $type, string $category, string $content, string &$text): void
	{
		$user = Prado::getApplication()->getUser()->getUsername();
		$address = $_SERVER['REMOTE_ADDR'];
		$date = date('Y-m-d H:i:s');
		$title = substr($content, 0, self::MAX_TITLE_SIZE);
		$text = str_replace('%service', 'Bacularis', $text);
		$text = str_replace('%user', $user, $text);
		$text = str_replace('%type', $type, $text);
		$text = str_replace('%category', $category, $text);
		$text = str_replace('%address', $address, $text);
		$text = str_replace('%date', $date, $text);
		$text = str_replace('%title', $title, $text);
		$text = str_replace('%content', $content, $text);
	}
}
