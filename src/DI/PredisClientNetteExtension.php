<?php

declare(strict_types=1);

/**
 * @copyright   Copyright (c) 2017 ublaboo <ublaboo@paveljanda.com>
 * @author      Pavel Janda <me@paveljanda.com>
 * @package     Ublaboo
 */

namespace Ublaboo\PredisClientNetteExtension\DI;

use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\DI\Definitions\ServiceDefinition;
use Predis\Client;
use Predis\Session\Handler;

/**
 * @property-read array $config
 */
final class PredisClientNetteExtension extends CompilerExtension
{

	private const DEFAULTS = [
		/**
		 * Or UNIX socket: 'unix:/path/to/redis.sock' or other options
		 * @see https://github.com/nrk/predis#connecting-to-redis
		 */
		'uri' => 'tcp://127.0.0.1:6379',
		'options' => [],
		'sessions' => false,
		'sessionsTtl' => null,
	];


	/**
	 * @return void
	 */
	public function loadConfiguration()
	{
		$this->validateConfig(self::DEFAULTS);
	}


	/**
	 * @return void
	 */
	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();

		$client = $builder->addDefinition($this->prefix('client'))
			->setClass(Client::class)
			->setArguments([$this->config['uri'], $this->config['options']]);

		if ($this->config['sessions'] === true) {
			if (!class_exists('Nette\Http\Session')) {
				throw new \LogicException('Class Nette\Http\Session does not exist');
			}

			$sessionHandler = $builder->addDefinition($this->prefix('sessionHandler'))
				->setClass(Handler::class)
				->setArguments([$client, ['gc_maxlifetime' => $this->config['sessionsTtl']]])
				->getResultDefinition();

			$sessionName = $builder->getByType('Nette\Http\Session');

			if ($sessionName === null) {
				throw new \UnexpectedValueException;
			}

			$session = $builder->getDefinition($sessionName);

			if ($session instanceof ServiceDefinition) {
				/**
				 * OK
				 */
			} elseif ($session instanceof FactoryDefinition) {
				$session = $session->getResultDefinition();
			} else {
				throw new \UnexpectedValueException;
			}

			$session->addSetup('setHandler', [$sessionHandler]);
		}
	}
}
