<?php


/**
 * Factory responsible for elmeents instantation.
 */

class XdumpElementFactory extends XdumpContextable {
	var $_elementPrefix = 'xDumpElement'; //used for naming
		
	function &getElementByValue(&$value, $description = null){
			$Context =& $this->getContext();
			$type = gettype($value);
			$className = $this->_elementPrefix.ucfirst($type);
			if (class_exists($className)){
				$Element = new $className($value, $description, $Context);
			} else {
				$className = $this->_elementPrefix;
				$Element = new $className ($value, $description, $Context);
			}	
			return $Element;		
	}
} 



/**
 * Super class for elements
 * An element is the xdump container for Php values. It's meant to store type specific
 * value-analysis methods, and provide uniform interface beetwen php value types.
 */

class xDumpElement {
	/**
	 * Holds a reference to the concrete value of the variable it represents
	 * @var mixed $value
	 */
	var $value;
	var $key; //key of the element in the father (array/object)
	
	/**
	 * Reference to the current contect
	 */
	var $_Context;	
	/**
	 * Reference to the invoking controller
	 */
	var $_Controller;	
	/**
	 * Reference to the invoking factory
	 */
	var $_Factory;
	//computed properties
	var $type=null;
	
	/**
	 * Reference to the parent element
	 */
	var $_Parent=null;
	/**
	 * Id will be assigned first time getId() will be called (kind of lazy loading)
	 * Do not access this var directly! Use getId() instear
	 * @access private
	 * 
	 */
	var $_id=null; 
	
	/**
	 * Reference to children elements
	 */
	var $_childs=null; //null: unassigned, otherwise array of childs (can be empty)
	/**
	 * @param mixed $value Reference to the current value 
	 * @param string $key key description for the element. This could be the hask key in the parent array element, 
	 * or the parent object variable name. Shuold not be used for computing purpouses, it's only informative.
	 * For root elements key can be a user defined string
	 * @param object Context Reference the current Service Locator. Service Locator will be queried to obtain current 
	 * Controller and current element factory
	 */

	function xDumpElement(&$value, $key=null, &$Context){
		$this->_Context =& $Context;

		$this->_Controller =& $this->_Context->getController();
		$this->_Factory =& $this->_Context->getElementDumpFactory();

		$this->value =& $value;
		if ( is_null($key) ){ //default name for element key
			$this->key ='(dumped '.$this->type.')';
		} else {
			$this->key = $key;
		}

		$this->_analyseElement();
	}
	
	function _analyseElement(){
		$this->type=gettype($this->value);
	}
	/**
	 * @return integer $level Nesting level of the element inside the current dump
	 */
	function getLevel(){
		if (is_null($this->_Parent)){
			return 0;
		} 
		return $this->_Parent->getLevel()+1;		
	}
	/**
	 * @return boolean $isRoot
	 */

	function isRoot (){
		return ($this->getLevel()===0);
	}
	
	/**
	 * Asks controller for the current element unique id.
	 */

	function getId(){
		if (is_null($this->_id)){
			$this->_id = $this->_Controller->newId();		
		}
		return $this->_id;
	}
	/**
	 * Sets element as child of another element.
	 * @param object $Parent object of type xdumpElement.
	 */
	function childOf(&$Parent){
		$this->_Parent = & $Parent;
	}
	
	/**
	 * Determines if element is recursive or not
	 */
	function isRecursive(){
		return false;
	}
	
	/**
	 * recursive recursion :-) check
	 * @access private
	 * @return boolean $recursive
	 */
	
	function _recursionCheck( &$Original ) {		
		if ($this->_gotPinged()) return $this->getId(); //getting pinged means recursion		
		if ( $this->isRoot() ) return false;
		$Parent =& $this->getParent();
		return $Parent->_recursionCheck($Original );
	}	
	
	/**
	 * returns a reference to children elements
	 * @return array $childs array of references to children elements. Childrens should be XdumpElement's instances.
	 * Returned array can be empty.
	 */
	
	function &getChilds(){	
		return array();
	}	
	
	function hasChilds (){
		return false;
	}

	/**
	 * @return object $Parent instance of XdumpElement. Can be null
	 */
	function &getParent (){
		return $this->_Parent;
	}
		
	/**
	 * Pings the object  (used by recursion routine)
	 */
	function _ping (){
		return false;		
	}
	/**
	 * rollback ping changes (used by recursion routine)
	 */
	function _unPing (){
		return false;
	}
	
	/**
	 * detect a ping (used by recursion routine)
	 */	
	function _gotPinged(){
		return false;
	}

}
/**
 * Rappresents any value type that can have children
 */ 
class XDumpChildishElement extends xDumpElement{	
	function isRecursive(){
		if ( $this->isRoot() || !$this->hasChilds() ){
			return false;
		}		
		$this->_ping();
		$Parent =& $this->getParent();
		$result = $Parent->_recursionCheck( $this->value );				
		$this->_unPing();
		return $result;
	}
}
/**
 * note: implements a php5 to avoid properties overloading conflicts
 */ 
class XDumpElementObject extends XDumpChildishElement {	
	var $forcePing = false; //for testing purpouse
	function &getChilds(){
		if ( is_null ($this->_childs )){
			$vars = get_object_vars($this->value);
			foreach (array_keys($vars) as $key){
				$NewChild =& $this->_Factory->getElementByValue( $vars[$key],$key);
				$NewChild->childOf ( $this );
				$this->_childs[] =& $NewChild; unset ($NewChild);
			}
		} //is_null
		return $this->_childs;
	}
	
	function hasChilds(){
		if ((count (get_object_vars($this->value))) > 0){
			return true;
		}
		return false;
	}
	
	function _ping (){		
		if ( $this->_usePing() ){
			$this->value->___ping = 1;
			return true;
		}			
		return false;
	}	
	function _unPing (){	
		if ( $this->_usePing() ){
			unset($this->value->___ping);		
		} else {
			return true;	
		}		
		return true;
	}	
	function _gotPinged(){
		if ( $this->_usePing() ){
			return isset($this->value->___ping);
		} else {
			return false;

		}		
	}
	function getClassName(){
		return get_class ($this->value);
	}
	function getAncestors () {				
		$classes = array();
		$class= $this->getClassName();
		while($class = get_parent_class($class)) { 
			$classes[] = $class; 
		}
		return $classes;
	}
	
	function _usePing (){
		return ( version_compare(phpversion(), '5.0') < 0 || $this->forcePing );
	}
	
	function _recursionCheck ( &$Original ) {
		if ($this->_usePing()){
			if ($this->_gotPinged()) return $this->getId(); //getting pinged means recursion
		} else {
			if ( $this->value === $Original ) return $this->getId();			
		}			
		if ( $this->isRoot() ) return false;
		$Parent =& $this->getParent();
		return $Parent->_recursionCheck( $Original );
	}	
}
class XDumpElementArray extends XDumpChildishElement {
	
	function &getChilds(){
		if ( is_null ($this->_childs )){					
			foreach (array_keys ($this->value) as $key){
				$NewChild = $this->_Factory->getElementByValue ( $this->value[$key],$key );
				$NewChild->childOf ( $this );
				$this->_childs[] =& $NewChild; unset ($NewChild);
			}
			
		} //is_null
		return $this->_childs;
	}		
	function hasChilds (){
		$childsNumber=count( $this->value );
		return ( !empty( $childsNumber ));
	}	
	function _ping (){		
		$this->value['___ping'] = 1;
		return true;
	}
	function _unPing (){
		unset($this->value['___ping']);
		return true;
	}	
	/**
	 * detect a ping
	 */	
	function _gotPinged(){
		return isset($this->value['___ping']);
	}
}



/*
class xDumpElementResource extends xDumpElementArray{
	var $resource;
	function xDumpElementResource(&$value, $key=null, &$Controller){
		$this->resource = &$value;
		unset ($value);

		$inspectedValue = $this->_inspectResource();
		
		parent::xDumpElement(&$inspectedValue, $key, &$Controller);
	}
	function isRecursive (){
		return false;
	}
	function _analyseElement(){
		$this->type = 'resource';
	}
	
	function _inspectResource(){
		$this->kind = get_resource_type($this->resource);
		parent::_analyseElement();
		switch ($this->kind){
			case 'mysql':	
				$db = 'mysql';		
				$numrows=call_user_func($db."_num_rows",$this->resource);
				$numfields=call_user_func($db."_num_fields",$this->resource);		
				$value['table statistics']['rows count'] = $numrows;
				$value['table statistics']['fields count'] = $numfields;
				$value['table fields']=array();
				for($i=0;$i<$numfields;$i++) {
					$field = call_user_func($db."_fetch_field",$this->resource,$i);
					$value['table fields']["{$field->name}"]=$field;
				}
			break;
			default:
			$value = null;
		}
		return $value;
	}
}
*/


?>