<?php

use Doctrine\DBAL\DriverManager,
	Doctrine\DBAL\Types\Type,
	Doctrine\DBAL\Event\Listeners\SQLSessionInit,
	Doctrine\ORM\EntityManager,
	Doctrine\ORM\Mapping\Driver\AttributeDriver,
	Doctrine\Persistence\Mapping\Driver\MappingDriverChain,
	Gedmo\DoctrineExtensions,
	Symfony\Component\Cache\Adapter\ArrayAdapter,
	Symfony\Component\Cache\Adapter\ApcuAdapter,
	Symfony\Component\Cache\Adapter\FilesystemAdapter,
	Symfony\Component\Validator\Validation;

class DoctrineException extends \FuelException {}

/**
 * Wraps Doctrine configuration with FuelPHP features. Formerly extended
 * aspendigital/fuel-doctrine2's \Fuel\Doctrine, which has been folded in here.
 *
 * <code>
 * $em = \D::manager();
 * $em->createQuery(...);
 * </code>
 */
class D
{
	/** @var array */
	protected static $_managers;

	/** @var array */
	protected static $settings;

	/**
	 * Map cache types to PSR-6 cache adapters
	 */
	protected static $cache_drivers = array(
		'array'=>ArrayAdapter::class,
		'apc'=>ApcuAdapter::class,
		'apcu'=>ApcuAdapter::class,
		'file'=>FilesystemAdapter::class,
		'filesystem'=>FilesystemAdapter::class
	);

	/** @var \Symfony\Component\Validator\Validator\ValidatorInterface */
	protected static $_validator;
	
	/** @var \Doctrine\DBAL\Logging\SQLLogger */
	protected static $_logger;

	public static $clear_cache = false;

	/**
	 * Read configuration
	 */
	public static function _init()
	{
		static::$settings = \Config::load('db', true);
	}
	
	/**
	 * Sets up the entity manager for a connection
	 */
	public static function _init_manager($connection)
	{
		$settings = static::connection_settings($connection);

		$config = new \Doctrine\ORM\Configuration();

		$cache = static::_init_cache($settings);
		if ($cache)
		{
			$config->setMetadataCache($cache);
			if (!\CMF::$lang_enabled) {
				$config->setQueryCache($cache);
			}
			$config->setResultCache($cache);
		}

		// PHP 8.4 native lazy objects replace generated proxy classes
		$config->enableNativeLazyObjects(true);
		$config->setMetadataDriverImpl(static::_init_metadata($config, $settings));
		$config->setNamingStrategy(new \CMF\ORM\ModuleNamingStrategy());

		$EventManager = new \Doctrine\Common\EventManager();

		// Matches the connection collation used before the PHP 8 upgrade
		$EventManager->addEventSubscriber(new SQLSessionInit("SET NAMES utf8 COLLATE utf8_unicode_ci"));

		$conn = DriverManager::getConnection($settings['connection'], $config, $EventManager);
		static::$_managers[$connection] = new EntityManager($conn, $config, $EventManager);

		if (!empty($settings['profiling']))
			$conn->getConfiguration()->setSQLLogger(new \CMF\Doctrine\QueryLogger());

		// Connection init callback
		if (!empty($settings['init_callback']))
		{
			// If array merge combined this numeric array, grab last two array elements as the real callback
			if (is_array($settings['init_callback']) && count($settings['init_callback']) > 2)
				$settings['init_callback'] = array_slice($settings['init_callback'], -2);

			call_user_func($settings['init_callback'], static::$_managers[$connection], $connection);
		}
		
		if (isset(static::$_logger)) {
			$current_logger = $conn->getConfiguration()->getSQLLogger();
			if (!is_null($current_logger) && method_exists(static::$_logger, 'setLogger')) {
				static::$_logger->setLogger($current_logger);
			}
			$conn->getConfiguration()->setSQLLogger(static::$_logger);
		}
		
		if (empty($connection) || is_null($connection))
			$connection = static::$settings['active'];
		
		// First, add custom types from our config
		$types = \Arr::get(static::$settings, 'doctrine2.types', array());
		$em = static::$_managers[$connection];
		$platform = $em->getConnection()->getDatabasePlatform();
		
		foreach ($types as $type => $info)
		{
			if (!Type::hasType($type))
			{
				try {
					Type::addType($type, $info['class']);
					if (isset($info['dbtype'])) $platform->registerDoctrineTypeMapping($info['dbtype'], $type);
				} catch (\Exception $e) { }
			}
		}
		
		// Now initialise any extensions from our config
		$extensions = \Arr::get(static::$settings, 'doctrine2.extensions', array());

		// Add custom DQL functions
		$em->getConfiguration()->addCustomStringFunction('TYPE', 'CMF\\Doctrine\\Functions\\TypeFunction');

		//Add Event Listener For exporting language canonicals
		$language =  \Config::get('language');
		try {
			if (!empty(\CMF\Model\DevSettings::instance()->parent_site) && !empty($language) && \Config::get('update_language_canonical',false)) {
				$em->getEventManager()->addEventSubscriber(new \CMF\Doctrine\Extensions\Canonicallistener());
			}
		} catch (\Exception $e) {}
		
		foreach ($extensions as $extension_class) {
			if (!is_subclass_of($extension_class, 'CMF\\Doctrine\\Extensions\\Extension'))
				throw new \Exception($extension_class.' is not a subclass of CMF\\Doctrine\\Extensions\\Extension');
			
			$extension_class::init($em, null);
		}
	}
	
	/**
	 * Builds the attribute metadata driver chain, including Gedmo's mapped superclasses
	 */
	protected static function _init_metadata($config, $connection_settings)
	{
		$driverChain = new MappingDriverChain();

		// Initialise Gedmo with the driver chain (uses attributes when no annotation reader is passed)
		DoctrineExtensions::registerAbstractMappingIntoDriverChainORM($driverChain);

		$attributeDriver = new AttributeDriver(\Arr::get($connection_settings, 'metadata_path', array()));

		// Add the driver for the configured namespaces
		$namespaces = array_unique(\Arr::get($connection_settings, 'entity_namespaces', array()));
		foreach ($namespaces as $namespace) {
			$driverChain->addDriver($attributeDriver, $namespace);
		}

		// And set it as the default driver for good measure
		$driverChain->setDefaultDriver($attributeDriver);

		return $driverChain;
	}
	
	/**
	 * Creates the PSR-6 cache pool used for metadata, queries and results.
	 * The cache namespace is set in the 'db.doctrine2.cache_namespace' config setting.
	 */
	protected static function _init_cache($connection_settings)
	{
		$type = \Fuel::$is_cli ? 'array' : \Arr::get($connection_settings, 'cache_driver', 'array');
		if ($type) {
			if (!array_key_exists($type, static::$cache_drivers))
				throw new DoctrineException('Invalid Doctrine2 cache driver: ' . $type);

			$class = static::$cache_drivers[$type];
			$namespace = \Arr::get($connection_settings, 'cache_namespace', basename(realpath(APPPATH.'..'.DS.'../')));
			$namespace = preg_replace('/[^-+.A-Za-z0-9_]/', '_', $namespace);

			switch ($class) {
				case FilesystemAdapter::class:
					$cache = new FilesystemAdapter($namespace, 0, APPPATH.'cache/doctrine2');
					break;

				case ApcuAdapter::class:
					$cache = new ApcuAdapter($namespace);
					break;

				default:
					$cache = new ArrayAdapter();
					break;
			}

			if (\Input::param('clearcache', false) !== false || static::$clear_cache === true) {
				try {
					$cache->clear();
				} catch (\Exception $e) {
					// Nothing
				}
			}
			
			return $cache;
		}
		
		return false;
	}

	/**
	 * Translates the Fuel DB config for a connection into Doctrine settings
	 */
	public static function connection_settings($connection)
	{
		if (!isset(static::$settings['doctrine2']))
			throw new DoctrineException('Missing "doctrine2" key in DB config');
		
		if (!isset(static::$settings[$connection]) or !isset(static::$settings[$connection]['connection']))
			throw new DoctrineException("No connection configuration for '$connection'");

		$connection_settings = static::$settings[$connection];
		$settings = static::$settings['doctrine2'];
		if (isset($connection_settings['doctrine2']))
		{
			$settings = array_replace($settings, $connection_settings['doctrine2']);
			unset($connection_settings['doctrine2']);
		}

		// Required settings
		foreach (array('metadata_path') as $key)
		{
			if (!isset($settings[$key]))
				throw new DoctrineException("'$key' not configured for connection '$connection'");
		}

		// Translate DB connection config to terms Doctrine understands
		$options = array();
		if (isset($connection_settings['type']))
		{
			$options['user'] = \Arr::get($connection_settings['connection'], 'username', null);
			switch ($connection_settings['type'])
			{
				case 'mysql':
				case 'mysqli':
					$options['host'] = \Arr::get($connection_settings['connection'], 'hostname', null);
					$options['dbname'] = \Arr::get($connection_settings['connection'], 'database', null);
					$options['driver'] = 'pdo_mysql';
					break;
				case 'pdo':
					$parts = explode(':', \Arr::get($connection_settings['connection'], 'dsn', ':'), 2);
					if (!in_array($parts[0], array('mysql', 'sqlite', 'pgsql', 'oci', 'sqlsrv')))
						throw new DoctrineException('Unsupported driver '.$parts[0]);
					$options['driver'] = 'pdo_'.$parts[0];
					$conf = explode(';', $parts[1]);
					foreach ($conf as $opt)
					{
						$v = explode('=', $opt, 2);
						if (count($v) == 2) $options[$v[0]] = $v[1];
					}
					break;
				default:
					throw new DoctrineException('Unsupported connection type '.$connection_settings['type']);
			}
		}

		if (isset($connection_settings['charset']))
			$options['charset'] = $connection_settings['charset'];
			
		$options = array_filter($options);
		$settings['connection'] = array_merge($options, $connection_settings['connection']);
		$settings['profiling'] = \Arr::get($connection_settings, 'profiling', false);
		$settings['init_callback'] = \Arr::get($settings, 'init_callback');

		return $settings;
	}

	/**
	 * @return \Doctrine\ORM\EntityManager
	 */
	public static function manager($connection = null)
	{
		if (empty($connection))
			$connection = static::$settings['active'];

		if (!isset(static::$_managers[$connection]))
			static::_init_manager($connection);

		return static::$_managers[$connection];
	}
	
	/**
	 * Flushes changes to the given entities only, plus any pending inserts and
	 * removals: what ORM 2's flush($entity) did. ORM 3's flush() checks every
	 * managed entity for changes, which gets slow when many are loaded (eg. imports).
	 *
	 * @param object|object[] $entities
	 */
	public static function flushOnly($entities, $connection = null)
	{
		$em = static::manager($connection);
		$switched = array();

		// With explicit change tracking, flush() only checks entities passed to persist()
		foreach ($em->getMetadataFactory()->getLoadedMetadata() as $metadata) {
			if ($metadata->isChangeTrackingDeferredImplicit()) {
				$metadata->setChangeTrackingPolicy(\Doctrine\ORM\Mapping\ClassMetadata::CHANGETRACKING_DEFERRED_EXPLICIT);
				$switched[] = $metadata;
			}
		}

		try {
			foreach ((is_array($entities) ? $entities : array($entities)) as $entity) {
				if ($entity !== null) $em->persist($entity);
			}
			$em->flush();
		} finally {
			foreach ($switched as $metadata) {
				$metadata->setChangeTrackingPolicy(\Doctrine\ORM\Mapping\ClassMetadata::CHANGETRACKING_DEFERRED_IMPLICIT);
			}
		}
	}

	/**
	 * @return \Symfony\Component\Validator\Validator\ValidatorInterface
	 */
	public static function validator($connection = null)
	{
		if (!isset(static::$_validator))
			static::_init_validator(static::manager($connection));

		return static::$_validator;
	}
	
	/**
	 * Initialises up the Symfony Validator, reading constraints from attributes
	 * 
	 * @return void
	 */
	protected static function _init_validator($em)
	{
		static::$_validator = Validation::createValidatorBuilder()
			->enableAttributeMapping()
			->getValidator();
	}
	
	/**
	 * Set the logger to be used
	 * @param \Doctrine\DBAL\Logging\SQLLogger $logger
	 */
	public static function setLogger($logger, $connection = 'default')
	{
		if (isset(static::$_managers[$connection])) {
			
			// Set the logger into the manager
			$connection = static::$_managers[$connection]->getConfiguration();
			$connection->setSQLLogger($logger);
			
		} else {
			
			// Prepare the logger for addition into the manager when it's created
			static::$_logger = $logger;
			
		}
	}
	
}


D::_init();
