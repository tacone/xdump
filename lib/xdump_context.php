<?php

/**
 * Any Contextable class should extend this one. Shuold be an interface
 */

class XdumpContextable {
	var $_Context;
	function &getContext (){
		return $this->_Context;
	}
	function setContext ( &$Context ){
		$this->_Context = $Context;
	}
} 

/**
 * Simple service locator for the package
 */

class XdumpContext {		
	/**
	 * Instantiates and returns the class to be user as controller
	 */
	function &getController(){
		//note: controller should be static, because render_once (js/css) stuff should be printed
		//only once on the page. This check should mqybe be moved on each Writer.
		static $Controller; 
		if (is_null($Controller)){
			$Controller = new XdumpController();
			$Controller->setContext($this );
		}
		return $Controller;
	}
	
	/**
	 * Instantiates and returns the element factory
	 */
	function &getElementDumpFactory (){	
		$Factory = new XdumpElementFactory();
		$Factory->setContext($this );
		return $Factory;
	}	
	/**
	 * Instantiate view to be used to print dump. Not really a factory, should change naming
	 */	
	function &getWriterFactory (){
		$Writer = new xDumpXhtmlWriter ();
		$Writer->setContext($this );
		return $Writer;
	}	
}

/**
 * Custom service locator for backtrace services
 */
class XdumpBacktraceContext extends XdumpContext {		
	function getWriterFactory (){
		$Writer = new xDumpBacktraceXhtmlWriter ();
		$Writer->setContext($this );
		return $Writer;
	}	
}

?>