<?php

namespace Admin;

class Controller_Benchmarks extends \Controller_Base {
	
	protected $current_label;
	protected $start_time;
	protected $stats = array();
	
	// get_called_class - to cache or not to cache?
	
	public function action_methods()
	{
		$this->runTest('gcc', 100000, 'get_called_class every time');
		$this->runTest('gcc_cached', 100000, 'caching get_called_class');
	}
	
	protected static $called_class = null;
	
	protected function gcc()
	{
		$class = get_called_class();
	}
	
	protected function gcc_cached()
	{
		$class = (static::$called_class === null) ? get_called_class() : static::$called_class;
	}
	
	public function action_metadata()
	{
		$this->runTest('metadata1', 10000, 'Metadata straight from Doctrine');
		$this->runTest('metadata2', 10000, 'Metadata cached by the class in static var (once from doctrine)');
	}
	
	protected function metadata1()
	{
		$metadata = \DoctrineFuel::manager()->getClassMetadata('CMF\Model\Base');
	}
	
	protected function metadata2()
	{
		$metadata = \CMF\Model\Base::metadata();
	}
	
	/**
	 * Runs a test using the method on this class specified
	 * 
	 * @param  string $method Method name to run
	 * @param  int $amount How many times to run it
	 * @param  string $label  What to label it as in the output
	 * @return void
	 */
	protected function runTest($method, $amount, $label)
	{
		sleep(0.5);
		$start_time = microtime(true);
		
		for ($i = 0; $i < $amount; $i++)
		{
			$this->$method;
		}
		
		$end_time = microtime(true);
		$total_time = $end_time - $start_time;
		
		$this->stats[$label] = array( 'time' => $total_time, 'amount' => $amount );
		
	}
	
	/**
	 * Renders the stored tests to the benchmarks template in the admin module
	 * 
	 * @param  \Fuel\Core\Response $response
	 * @return \Fuel\Core\Response
	 */
	public function after($response)
	{
		return \Response::forge(\View::forge('admin/benchmarks/benchmarks.twig', array( 'tests' => $this->stats ), false), 200, array());
	}
	
}