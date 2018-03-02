<?php

namespace Cli;
/**
 * Description of Cli
 *
 * @author Daniel Čekan <daniel.cekan@zcom.cz>
 */
abstract class Cli
{
	const MODE_HELP = 'help';
	
	protected $argv = array(
		self::MODE_HELP => false
	);
	
	private $_ready = false;
	
	public function __construct()
	{
		if( (defined('STDIN') && isset( $_SERVER['argv'] )) == false ){
			throw new \RuntimeException("Only CLI Usage");
		}
	}
	
	public function run()
	{
		if( $this->_ready == false ) {
			throw new \RuntimeException("Cli isnt ready, please call protected ready() method in class ".get_called_class());
		}
		try {
			$this->startup();
			reset($this->argv);
			while( $mode = key($this->argv) ) {
				$value = current($this->argv);
				if( $value == false ) {
					next($this->argv);
					continue;
				}
				$camelize_mode = str_replace('_', '', ucwords($mode, '_'));
				if(method_exists($this, 'run'. $camelize_mode)) {
					call_user_func(array($this, 'run'. $camelize_mode));
				}
				next($this->argv);
			}
			$this->success();
		} catch (AbortException $exc) {
			die($exc->getMessage());
		}
	}
	
	protected function addArg($mode)
	{
		if( isset($this->argv[$mode]) ) {
			throw new \UnexpectedValueException(sprintf("Mode %s is already set", $mode));
		}
		$this->argv[$mode] = false;
		return $this;
	}
	
	protected function setArg($mode, $value = false)
	{
		if( isset($this->argv[$mode]) == false ) {
			throw new \UnexpectedValueException(sprintf("Mode %s not exist", $mode));
		}
		$this->argv[$mode] = $value;
		return $this;
	}
	
	protected function getArgs($name = null)
	{
		$result = array();
		foreach( $this->argv as $arg => $value ) {
			if( $name && $arg == $name ) {
				return $value;
			} elseif( $value ) {
				$result[$arg] = $value;
			}
		}
		return $result;
	}
	
	protected function terminate($message = null)
	{
		throw new AbortException($message);
	}
	
	protected function ready()
	{
		$argv = $_SERVER["argv"];
		$mode_found = false;
		if( $argv ){
			foreach( $argv as $k => $arg ){
				$parts = explode("=", substr($arg, 2));
				$mode = trim($parts[0]);
				$value = isset($parts[1])? trim($parts[1]) : "";
				if( substr($arg, 0, 2) == '--' && isset($this->argv[$mode]) ){
					$this->setArg($mode, ($value)? $value : true);
					if($mode_found == false ){
						$mode_found = true;
					}
				}
			}
		}
		if( $mode_found == false ) {
			$this->argv[self::MODE_HELP] = true;
		}
		$this->_ready = true;
		return true;
	}
	
	abstract protected function startup();
	
	abstract protected function runHelp();
	
	abstract protected function success();
}
