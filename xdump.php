<?php
/*
 Copyright (c) 2006 Stefano Forenza Permission is hereby granted, free of
     charge, to any person obtaining a copy of this software and associated
     documentation files (the "Software"), to deal in the Software without
   restriction, including without limitation the rights to use, copy, modify,
merge, publish, distribute, sublicense, and/or sell copies of the Software, and
  to permit persons to whom the Software is furnished to do so, subject to the
  following conditions: The above copyright notice and this permission notice
  shall be included in all copies or substantial portions of the Software. THE
SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A
    PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
    IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
   CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

Author: Stefano Forenza <stefano@stefanoforenza.com>
Version: 0.3 Alpha
WWW: http://www.stefanoforenza.com/projects/xdump/
Contact: stefano@stefanoforenza.com
*/
require_once ('lib/'.'xdump_context.php'); //service locator
require_once ('lib/'.'xdump_controller.php'); //concrete class for dumping services
require_once ('lib/'.'xdump_element.php'); //element classes (responsible for vlaue handling and analisys)
require_once ('lib/'.'xdump_writer.php'); // rendering classes

/*
 * PHP formatted Dump utility
 * <br />
 * <br />
 * (have you ever tried to read a backtrace?)
 *
 * @package com.stefanoforenza.xdump
 * @version 0.3
 * @author Stefano Forenza
 * @copyright 2006 Stefano Forenza
 *
 *
 * Revision: $WCREV$
 *
 * <br />
 *
 * <?
 * //example
 * echo Xdump:dump($GLOBALS);
 * ?>
 *
 * @todo Add class reflection
 * @todo object public properties (functions?) should be put first? maybe not
 * @todo object public properties shuold be emphatised
 * @todo create_function spoofing or guessing
 * 
 * @todo add url type parameter passing in public functions
 * @todo add nojavascript / all open option
 * 
 * Features
 * 
 * @todo Add some more backtrace to the dump (file/line dump invocation already added but will surely broke on subclasses)
 * @todo Add method to auto replace php error_handler (should display a source) and catching exceptions
 *  
 *  
 * 
 * @todo access key to make xdump appear on the page
 * 
 * @todo (?) man() (manual) function
 * @todo (?) phpinfo
 *  * 
 * Configuration
 * @todo time limit management (dynamically add time to the time_limit, set some max limit as well)
 * @todo option to avoid reference parsing (?)
 *  
 *  *
 * PHP 5
 * <p>
 * @todo Add more accurate object reflection (public/private methods, interfaces implemented, presence of overloading (__get/..) etc)
 * @todo Add private / protected / static vars reflection using var_export or (better!) reflection class
 * 
 *
 * Making it safe:
 *
 * @todo Add maximum element limit (ezpdf, with fonts loaded has 6000+ elements)
 * @todo Compress Html!
 * @todo Detect Reference and group up html?
 * @todo cheap modes, disabling array/object recursions
 * @ping shuold check if ping property already exists
 *
 * Making it right:
 * 
 * @todo timer should check only parsing process time
 * @todo recursion links shuold highlight element name, not childs
 *
 * Interface<br />
 *
 * @todo add convenience click, on the the entire element (and css cursor)
 *
 * Other things:<br />
 *
 * @todo beware of PHP 5 odd static get_class() behaviour (!)
 *
 * BUGS
 * 
 * Objects without members shuold not display as links
 *
 *
 * IDEE DA CUI PRENDERE SPUNTO
 * <br />
 * <br />
 * - parsing specializzato di varie tipi di resource (mysql, xml etc)
 * - parsing di files
 * <br />
 *
 * CHANGELOG
 *
 * v. 0.001
 * <br />
 *
 * - object elements show they super-classes
 *
 * <br />
 *
 * v. 0.001a
 *
 * - added maxDepth limit, to avoid infinite loops on recursions *
 *
 * <br />
 *
 *
 * v 0.002a
 * - refactoring methods from all static to concrete (Xdump::dump() now
 * - changed element template from DIV to UL/LI lists (now it looks even nicer without CSS
 * - added "invoked on line/file" note in root element
 * - javascript now depends on a static var, all functions moved in single SCRIPT block
 *   wich only prints itslef once per page now
 *   
 * rev 0.0010
 * - bringing back recursion check, now work on objects and arrays
 * - fixing some php4 issues
 * 
 * rev 0.0012
 * - implemented php5 === operator in order to avoid override conflicts
 * - fixing php4 conflicts on the testcases
 * 
 * rev 0.0014 (?)
 * 
 * - rendering and element dependencies deeply refactored 
 * - elements childs now are elements themselves
 * - added recursion highlighting (has to be still refined, though)
 * - added invoking-chunk-of-code display to backtrace info
 * - improved (30-40% i guess) parsing speed
 * - dump size reduced to 20% of original size on bigger dumps
 * - styles now are (not compliant and) grouped and more easily manageable
 * - added request() public function
 * - added declarations() public function
 * - long strings now are contractible and display in a scrollable fixed-dimension's box
 * - added parsing time info (to be refined)
 * - added examples to the package
 * - added first draft of (readme) main page
 * 
 * rev 0.0015 - 0.0018
 * 
 * - added invoking code dump, with syntax highlighting (should add some hr to display better without css
 * - deeply refactored factories. Now xdump use a Service Locator to factory elements and rendering strategies
 * - added source code dump for every item of backtrace() public function.	
 * - adding basic resource sniffing
 * 
 * rev 0.0019
 * 
 * - Removed all time-call pass-by-reference from the package.
 * - Fixed mdump which was broken.
 * - Examples now have a common header and link to the test directory.
 */

/**
 * @package com.stefanoforenza.xdump
 * 
 * The only public class in the package
 * This class is meant to be static.
 * 
 * NOTE:
 * While static classes are not considered good cause their lack of fleibility that shuoldn't apply to this one.
 * This class is only meant to be an aggregate of pubblic functions.
 * Any function invoke a Service Locator and use it to retrieve for that every object needed, like controllers 
 * and writers (views).
 * So, to change the logic of the application, simply replace it with one of your liking.
 * 
 * @access public
 * @abstract returns a nice formatted/contractible dump of a given var
 */
 
class xDump  {	
	/**
	 * Public abstract function, returns the HTML formatted dump of $value
	 *
	 * @param mixed $value variable to dump
	 * @param string $description optional description of the dump. Description will appear on the expand/contract link
	 * @access public
	 * @return string $output HTML of the dump
	 */ 
	function dump ( &$value , $description = null ){	
		$Context = new XdumpContext ();
		$Xdump =& $Context->getController();	
		if ( $Xdump->isDebug() ){
			$ElementFactory =& $Context->getElementDumpFactory();
			$Element = $ElementFactory->getElementByValue($value, $description);
			$out = $Xdump->_dump( $Element );		
			return $out;
		}
		return '';
	}
	
	/**
	 * Just like dump but dumps multiple values.
	 *
	 *  Uses func_get_args to and cicles on it producing multiple x-dumps
	 * unlike dump() doesn't allow for attaching a description.
	 * 
	 * Anoter difference: dump() accept references only. 
	 * Here we can not use a reference, we have to retrieve a copy only
	 *
	 * @param mixed $values $arg1, $arg2, ...
	 * @param unknown_type $description
	 * @return unknown
	 */
	
	function mdump (){	
		
		$value = func_get_args();
		$keys= array_keys($value);
		foreach ($keys as $key){
			$value['multiple dump - element #'.$key] =& $value[$key];
			unset ($value[$key]);
		}
		unset ($key, $keys);	
		
		$Context = new XdumpContext ();
		$Xdump =& $Context->getController();
		if ( $Xdump->isDebug() ){		
			$ElementFactory =& $Context->getElementDumpFactory();
			$Element = $ElementFactory->getElementByValue($value, 'Multiple Dump');
			$out = $Xdump->_dump( $Element );	
			return $out;
		}
		return '';
	}
	/**
	 * Sets debug mode true or false
	 * @param boolean $debugMode
	 */
	function setDebug ( $debugMode )
	{
		$Context = new XdumpContext ();
		$Xdump =& $Context->getController();
		$Xdump->isDebug ( $debugMode );
	}
	
	/**
	 * Dumps all declarared functions, classes, etc
	 */
	
	function declarations (){
		
		$value = array (
			'Declared Functions' => get_defined_functions(),
			'Declared Classes' => get_declared_classes(),		
			'Declared Constants' => get_defined_constants()
		);
		if ( version_compare(phpversion(), '5.0') >= 0 ){
			$value['Declared Interfaces'] = get_declared_interfaces();
		}
	
		$Context = new XdumpContext ();
		$Xdump =& $Context->getController();	
		if ( $Xdump->isDebug() ){
			$ElementFactory =& $Context->getElementDumpFactory();
			$Element = $ElementFactory->getElementByValue($value, 'Declarations');		
			$out = $Xdump->_dump( $Element );			
			return $out;
		}
	}
	
	/**
	 * Dumps everything about the request
	 */
	function request (){	
		$value = array (
		'GET' => $_GET,
		'POST' => $_POST,
		'COOKIES' => $_COOKIE,
		'FILES' => $_FILES,
		'REQUEST' => $_REQUEST,
		'SESSION' => isset($_SESSION)?$_SESSION:null,
		 '----------------------'=>null,
		'SERVER' => $_SERVER,
		'magic_quotes local setting' => ini_get('magic_quotes_gpc'),
		'post_max_size' => ini_get('post_max_size'),
		'session settings' => ini_get_all('session'),
		'file upload settings' => array (
			'upload_max_filesize' => ini_get('upload_max_filesize'),
			'upload_tmp_dir' => ini_get ('upload_tmp_dir')
			)
		
		);
		$Context = new XdumpContext ();
		$Xdump =& $Context->getController();	
		if ( $Xdump->isDebug() ){
			$ElementFactory =& $Context->getElementDumpFactory();
			$Element = $ElementFactory->getElementByValue($value, 'Request Info');		
			$out = $Xdump->_dump( $Element );			
			return $out;
		}
	}
	/**
	 * Sets xdump as the current ErrorHandler
	 * 
	 * Every error will now dump nicely on the screen, with the related backtrace.
	 * Can this be useful? Suppose you have a loop wich gives you only 2-3 warnings.
	 * If call this function just before starting the loop and you will get the backtrace
	 * for those cycles that trigger error.
	 * 
	 */
	function handleErrors (){
		$Context = new XdumpContext ();
		$Xdump =& $Context->getController();	
		set_error_handler(array($Xdump,'_dumpError'));
		
	}
	
	/**
	 * Returns a nice formatted backtrace, along with the source code line for every backtrace item.
	 */
	
	function backtrace (){
		$Context = new XdumpBacktraceContext ();
		$Xdump =& $Context->getController();	
		if ( $Xdump->isDebug() ){
			$backtrace = debug_backtrace();
			$ElementFactory =& $Context->getElementDumpFactory();
			$Element = $ElementFactory->getElementByValue($backtrace, 'Backtrace Info');		
			//return $xdump->_dump( $value ,  array( 'key' => $description ) );
			$out = $Xdump->_dump( $Element );		
			return $out;
		}
		return '';
	}
	
	
	
}



?>