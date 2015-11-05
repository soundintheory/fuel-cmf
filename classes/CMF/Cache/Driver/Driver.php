<?php

namespace CMF\Cache\Driver;

interface Driver
{

	/**
	 * Returns the cache stored for the current URL or false
	 *
	 * @return string|false
	 */
	public function get($url);

	/**
	 * Sets the content to cache for the current URL
	 *
	 * @param   mixed
	 * @return  mixed
	 */
	public function set($response);
	
	/**
	 * Serves up cached content
	 *
	 * @param   mixed
	 * @return  void
	 */
	public function serve($content);

	/**
	 * Stops the cache driver
	 *
	 * @param   mixed
	 * @return  void
	 */
	public function stop();
	
}