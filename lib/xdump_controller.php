<?php

/**
 * Main controller for the packaege
 * 
 * @package com.stefanoforenza.xdump
 * @author Stefano Forenza
 * @access protected
 * 
 */

class XdumpController extends XdumpContextable {
	var $maxDepth=10; //max depth for parsing. This is to avoid recursion	
	//config
	var $_elementIdPrefix='xdmp';
	//run-time
	
	/**
	 * Xdump register current backtrace when starting the dump. This is meant to be used for diplaying
	 * the source code where it was invoked
	 */
	var $_backtrace=array();
	
	/**
	 * @access private
	 * Dump start time
	 * @var float $_startTime
	 */
	var $_startTime;
	
	/**
	 * @access private
	 * Dump end time
	 * @var float $_endTime
	 */
	var $_endTime;
	
	/**
	 * Call back function for PHP set_error_handler ()
	 * Handles errors dumping infos in a nice way
	 * @todo should be shorter (> 10 lines)
	 * @todo clean this code!
	 * @todo find a way to remove the depedency from the concrete context here
	 */	
	function _dumpError ($errorNumber, $errString, $errFile, $errLine){		
		//strict hack
		if ( $errorNumber == 2048 ) return false;
		
		$Context = new XdumpBacktraceContext ();
		$value = debug_backtrace();
		$Xdump =& $Context->getController();
		if ( $Xdump->isDebug() ){
			$suppressed = ( !error_reporting() ); //
			$errType = array (
	            1    => "Php Error",
	            2    => "Php Warning",
	            4    => "Parsing Error",
	            8    => "Php Notice",
	            16   => "Core Error",
	            32   => "Core Warning",
	            64   => "Compile Error",
	            128  => "Compile Warning",
	            256  => "Php User Error",
	            512  => "Php User Warning",
	            1024 => "Php User Notice"
        	);
			
			$ElementFactory =& $Context->getElementDumpFactory();
			$description ='';
			if ( isset( $errType[$errorNumber] ))
			{
				$description .= $errType[$errorNumber];
			} else {
				$description .= 'Error '.$errorNumber;
			}
			$description .= ": $errString on $errLine of $errFile";
			($suppressed)?$description='(@ suppressed) '.$description:'';
			$Element = $ElementFactory->getElementByValue( $value, $description );
			$out = $Xdump->_dump( $Element);				
			echo $out;
					switch ($errorNumber) {					
			case E_USER_ERROR:			
				exit(1);
			break;	
			return true;
		  }		
		}	
		return '';
	}
			
	/**
	 * Returns source code of invoking php code
	 * @param array $backtraceInfo debug_backtrace_output
	 * @param integer $sourceLinesToBeDisplayed number of source lines to display around the invoking line
	 */
	 
	function getSource( $backtraceInfo, $sourceLinesToBeDisplayed = 10 ){
		if (!isset($backtraceInfo['file'])){
			return false;
		}
		if (file_exists($backtraceInfo['file'])){
			$file = file($backtraceInfo['file']);
			$beginLine = $backtraceInfo['line'] - ceil($sourceLinesToBeDisplayed/2);
			$endLine = $backtraceInfo['line'] + floor($sourceLinesToBeDisplayed/2);
			if (!isset ($file[$beginLine])) $beginLine=0;
			if (!isset ($file[$endLine])) $endLine=count ($file)-1;
			$chunkOfCode = '';
			for ($line = $beginLine; $line <= $endLine; $line++){
				if ( $line+1 == $backtraceInfo['line'] ){
					$chunkOfCode .= '/* LINE '.$backtraceInfo['line'].' --> */';
				}
				$chunkOfCode .= $file[ $line ];
			}
			return $chunkOfCode;
		} 
		return false;
	}

	/**
	 * Returns unique id of the element.
	 * Actually used only for generating XHTML ids
	 * @static
	 * @return string $unique_id unique_id of element
	 */	
	function newId(){
		static $id;
		if (!isset($id) || !$id){
			$id=1;
		}
		$id++;
		$unique_id = $id;
		return $unique_id;	
	}

	function _registerBacktrace( $file = '', $line = '' ){
		$all_backtrace = debug_backtrace();

		while (count($all_backtrace)){
			$debugInfo = array_shift($all_backtrace);
			if (
				
					!isset ($debugInfo['file']) 
					||  !$debugInfo['file']
					|| strpos(strtolower ($debugInfo['file']),'xdump') === false
			
							
			 ){
				$all_backtrace=array();
				$backtrace=$debugInfo;
			}
			unset ($debugInfo);
		}
		if (!isset($backtrace))	{
			return false;
		}
		
		$backtrace['xdump_class'] = get_class($this);
		$this->_backtrace = $backtrace;	
		return true;
	}
	
	/**
	 * Starts the dumping process 
	 * @param object $Element should be a xdumpElement or descendant
	 */	
	function _dump(&$Element){
		$this->_registerBacktrace();	
		$writer = $this->_Context->getWriterFactory();
		return $writer->render ($Element, $this);
	}	
	function _startTimer(){
		$this->_startTime = microtime(true);	
	}
	function _stopTimer(){
		$this->_endTime = microtime(true);
	}
	
	/**
	 * Returns dumping time
	 * @return float time Time in microseconds
	 */
	function getTime(){		
		return $this->_endTime - $this->_startTime;
	}
	/**
	 * tells if debug mode is triggered or not
	 * override this function to tune Xdebug with your framework environment and turn it off
	 * automatically when in production mode<br />
	 *
	 * you can also use it to change Xdebug parameters at run-time<br />
	 *
	 * this function get called from all public functions just before running the dump routine.<br />
	 *
	 * you can set it to return everything you want. Xdump, however will only check if the result is empty or not. If it is empty, dump will be stopped
	 *
	 *
	 * @return integer $result any non empty value will make Xdump work, empty value will stop it
	 */
	
	function isDebug( $debugMode = null){
		static $debugModeStatus = true;
		if ( !is_null( $debugMode ))
		{
			$debugModeStatus = $debugMode;
		}
		return $debugModeStatus;
	}	
}


?>