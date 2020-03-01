<?php 

declare(strict_types=1);

namespace phpClub\Util;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class ConsoleUtil
{
	/**
	 * Adds a handler that prints log messages to stderr. 
	 *
	 * @param int $minLevel Dump messages with this or higher level
	 */
	public static function copyLogToConsole(Logger $monolog, int $minLevel = Logger::WARNING)
	{
		$monolog->pushHandler(new StreamHandler('php://stderr', $minLevel));
	}
}