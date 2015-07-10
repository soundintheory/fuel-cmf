<?php

use Doctrine\DBAL\Types\Type,
	Doctrine\Common\Annotations\AnnotationRegistry,
	Doctrine\Common\Annotations\AnnotationReader,
	Doctrine\Common\Annotations\CachedReader,
	Doctrine\ORM\Mapping\Driver\AnnotationDriver,
	Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain,
	Gedmo\DoctrineExtensions,
	Symfony\Component\Validator\Validator,
	Symfony\Component\Validator\DefaultTranslator,
	Symfony\Component\Validator\Mapping\ClassMetadataFactory as ValidatorMetadataFactory,
    Symfony\Component\Validator\Mapping\Loader\AnnotationLoader as ValidatorAnnotationLoader,
    Symfony\Component\Validator\ConstraintValidatorFactory,
    Doctrine\DBAL\Event\Listeners\MysqlSessionInit;

class DoctrineException extends \FuelException {}

/**
 * @inheritdoc
 */
class D extends \Fuel\Doctrine
{
	protected static $cache_drivers = array(
		'array'=>'ArrayCache',
		'apc'=>'ApcCache',
		'xcache'=>'XcacheCache',
		'wincache'=>'WinCache',
		'zend'=>'ZendDataCache',
		'file'=>'FilesystemCache',
		'filesystem'=>'FilesystemCache'
	);

	/** @var \Symfony\Component\Validator\Validator */
	protected static $_validator;
	
	/** @var \Doctrine\DBAL\Logging\SQLLogger */
	protected static $_logger;

	public static $clear_cache = false;
	
	/**
	 * @inheritdoc
	 */
	public static function _init_manager($connection)
	{
		parent::_init_manager($connection);
		
		if (isset(static::$_logger)) {
			$current_logger = static::$_managers[$connection]->getConnection()->getConfiguration()->getSQLLogger(static::$_logger);
			if (!is_null($current_logger) && method_exists(static::$_logger, 'setLogger')) {
				static::$_logger->setLogger($current_logger);
			}
			static::$_managers[$connection]->getConnection()->getConfiguration()->setSQLLogger(static::$_logger);
		}
		
		if (empty($connection) || is_null($connection))
			$connection = static::$settings['active'];
		
		// First, add custom types from our config
		$types = \Arr::get(static::$settings, 'doctrine2.types', array());
		$em = static::$_managers[$connection];
		$platform = $em->getConnection()->getDatabasePlatform();
		
		foreach ($types as $type => $info) {
			Type::addType($type, $info['class']);
			if (isset($info['dbtype'])) $platform->registerDoctrineTypeMapping($info['dbtype'], $type);
		}
		
		// Now initialise any extensions from our config
		$evm = $em->getEventManager();
		$reader = $em->getConfiguration()->getMetadataDriverImpl()->getDefaultDriver()->getReader();
		$extensions = \Arr::get(static::$settings, 'doctrine2.extensions', array());

		// Add custom DQL functions
		$em->getConfiguration()->addCustomStringFunction('TYPE', 'CMF\\Doctrine\\Functions\\TypeFunction');

		// Ensure UTF-8 support
		$em->getEventManager()->addEventSubscriber(new MysqlSessionInit("utf8", "utf8_unicode_ci"));
		
		foreach ($extensions as $extension_class) {
			if (!is_subclass_of($extension_class, 'CMF\\Doctrine\\Extensions\\Extension'))
				throw new \Exception($extension_class.' is not a subclass of Doctrine\\Fuel\\Extensions\\Extension');
			
			$extension_class::init($em, $reader);
		}
	}
	
	/**
	 * @inheritdoc
	 */
	protected static function _init_metadata($config, $connection_settings)
	{
		$type = \Arr::get($connection_settings, 'metadata_driver', 'annotation');
		if (!array_key_exists($type, static::$metadata_drivers))
			throw new DoctrineException('Invalid Doctrine2 metadata driver: ' . $type);

		if ($type == 'annotation') {
			
			// Register the doctrine annotations
			AnnotationRegistry::registerFile(realpath(VENDORPATH).'/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');
			
			// Symfony Validator annotations
			AnnotationRegistry::registerAutoloadNamespace(
				'Symfony\\Component\\Validator\\Constraints', array(realpath(VENDORPATH).'/symfony/validator', PKGPATH.'cmf/classes')
			);
			
			// Create cached annotation reader
			$cachedAnnotationReader = new CachedReader(new AnnotationReader(), $config->getMetadataCacheImpl());
			
			// Create a driver chain for metadata reading
			$driverChain = new MappingDriverChain();
			
			// Initialise Gedmo with the driver chain and reader
			DoctrineExtensions::registerAbstractMappingIntoDriverChainORM(
			    $driverChain,
			    $cachedAnnotationReader
			);

			// Create the annotation driver
			$annotationDriver = new AnnotationDriver(
			    $cachedAnnotationReader,
			    \Arr::get($connection_settings, 'metadata_path', array())
			);
			
			// Add the driver for the configured namespaces
			$namespaces = array_unique(\Arr::get($connection_settings, 'entity_namespaces', array()));
			foreach ($namespaces as $namespace) {
				$driverChain->addDriver($annotationDriver, $namespace);
			}
			
			// And set it as the default driver for good measure
			$driverChain->setDefaultDriver($annotationDriver);
			
			return $driverChain;
		}

		$class = '\\Doctrine\\ORM\\Mapping\\Driver\\' . static::$metadata_drivers[$type];
		return new $class($connection_settings['metadata_path']);
	}
	
	/**
	 * @inheritdoc
	 * 
	 * Including a CMF mod: setting cache namespace / correct file caching path upon creation.
	 * The cache namespace is set in the 'db.doctrine2.cache_namespace' config setting.
	 */
	protected static function _init_cache($connection_settings)
	{
		$type = \Fuel::$is_cli ? 'array' : \Arr::get($connection_settings, 'cache_driver', 'array');
		if ($type) {
			if (!array_key_exists($type, static::$cache_drivers))
				throw new DoctrineException('Invalid Doctrine2 cache driver: ' . $type);

			$cache = false;
			$class = '\\Doctrine\\Common\\Cache\\' . static::$cache_drivers[$type];
			$namespace = \Arr::get($connection_settings, 'cache_namespace', basename(realpath(APPPATH.'..'.DS.'../')));
			
			switch ($type) {
				case 'file':
				case 'phpfile':
				case 'filesystem':
					$cache = new $class(APPPATH.'cache/doctrine2');
					break;
					
				default:
					$cache = new $class();
					$cache->setNamespace($namespace);
					break;
			}

			if (\Input::param('clearcache', false) !== false || static::$clear_cache === true) {
				try {
					$cache->flushAll();
					$cache->deleteAll();
				} catch (\Exception $e) {
					// Nothing
				}
			}
			
			return $cache;
		}
		
		return false;
	}
	
	/**
	 * @return \Symfony\Component\Validator\Validator
	 */
	public static function validator($connection = null)
	{
		$em = static::manager($connection);
		
		if (empty($connection) || is_null($connection))
			$connection = static::$settings['active'];
		
		if (!isset(static::$_validator))
			static::_init_validator($em);

		return static::$_validator;
	}
	
	/**
	 * Initialises up the Symfony Validator
	 * 
	 * @return void
	 */
	protected static function _init_validator($em)
	{
	    $annotationLoader = new ValidatorAnnotationLoader($em->getConfiguration()->getMetadataDriverImpl()->getDefaultDriver()->getReader());
	    
	    static::$_validator = new Validator(
	    	new ValidatorMetadataFactory($annotationLoader),
	    	new ConstraintValidatorFactory(),
	    	new DefaultTranslator()
	    );
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
