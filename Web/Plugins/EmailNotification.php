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
 * The e-mail notification plugin module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Plugin
 */
class EmailNotification extends BacularisWebPluginBase implements IBacularisNotificationPlugin
{
	/**
	 * Common mail headers.
	 */
	private const MAIL_HEADERS = "Content-Type: text/plain; charset=UTF-8\r\nFrom: %s\r\n";

	/**
	 * Maximum characters number in title (without [] tags).
	 */
	private const MAX_TITLE_SIZE = 60;

	/**
	 * Get plugin name displayed in web interface.
	 *
	 * @return string plugin name
	 */
	public static function getName(): string
	{
		return 'System e-mail notification';
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
			['name' => 'sender', 'type' => 'string', 'default' => '', 'label' => 'Sender e-mail'],
			['name' => 'recipients', 'type' => 'string', 'default' => '', 'label' => 'Recipeints e-mails'],
			['name' => 'subject', 'type' => 'string', 'default' => '[%service][%type][%category] %title', 'label' => 'Subject'],
			['name' => 'content', 'type' => 'string_long', 'default' => "Date: %date\nUser: %user\nIP address/Hostname: %address\nMessage: %content", 'label' => 'Message content'],
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
			$title = $this->getSubject($type, $category, $msg);
			$content = $this->getContent($type, $category, $msg);
			$result = $this->sendEmail($title, $content);
		}
		return $result;
	}

	/**
	 * Get all custom e-mail headers value.
	 *
	 * @return string e-mail headers
	 */
	private function getEmailHeaders(): string
	{
		$config = $this->getConfig();
		$sender_email = $config['parameters']['sender'];
		$headers = sprintf(self::MAIL_HEADERS, $sender_email);
		return $headers;
	}

	/**
	 * Get formatted e-mail subject.
	 *
	 * @param string $type message type (INFO, WARNING, ERROR)
	 * @param string $category message category (Config, Action, Application, Security)
	 * @param string $msg message content
	 * @return string formatted e-mail subject
	 */
	private function getSubject(string $type, string $category, string $msg): string
	{
		$config = $this->getConfig();
		$subject = $config['parameters']['subject'];
		$this->setKeywords($type, $category, $msg, $subject);
		return $subject;
	}

	/**
	 * Get formatted e-mail content.
	 *
	 * @param string $type message type (INFO, WARNING, ERROR)
	 * @param string $category message category (Config, Action, Application, Security)
	 * @param string $msg message content
	 * @return string formatted e-mail content
	 */
	private function getContent(string $type, string $category, string $msg): string
	{
		$config = $this->getConfig();
		$content = $config['parameters']['content'];
		$this->setKeywords($type, $category, $msg, $content);
		return $content;
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

	/**
	 * Send e-mail notification.
	 *
	 * @param string $subject e-mail subject
	 * @param string $content e-mail content
	 * @param bool true if e-mail was successfully accepted to delivery, false otherwise
	 */
	private function sendEmail(string $subject, string $content): bool
	{
		$config = $this->getConfig();
		$recipients_email = $config['parameters']['recipients'];
		$headers = $this->getEmailHeaders();
		$result = mail($recipients_email, $subject, $content, $headers);
		return $result;
	}
}
