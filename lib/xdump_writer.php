<?php

/**
 * Standard View for Dumps. You may wonder why html is hardcoded. It's for performance, I swear.
 */
 
class xDumpXhtmlWriter extends XdumpContextable {	
	var $_elementClassPrefix='xdmp';
	var $autoExpand=1	; //auto expand first N levels
	var $_Controller;
	var $_elementIdPrefix = 'xdmp';
	
	function render (&$Element){
		$Controller =& $this->_Context->getController();		
		$this->_Controller = & $Controller;
		$this->_Controller->_startTimer();
		$renderResult = $this->_renderFactory ($Element);
		$this->_Controller->_stopTimer();
		return $this->_renderOnce().$this->_renderRoot($renderResult);	
	}
	function _highlightSource ( $source )
	{
		$checkSource = strtolower( $source );
		if (
			!strpos( $source, '<span style="color')
		){
			$source = "<?php \n".$source;
			$source = highlight_string( $source, true );
			$source = explode ( "\n", $source );
			array_shift( $source );
			$source = join( "\n", $source );
			return $source;
		} 
		return highlight_string( $source, true );
	}
	function _renderRoot ( &$renderResult ){
		$sourceId ='xdbgsource_'.$this->_Controller->newId();		
		return 
		'<!-- XDUMP BEGIN -->
		<div  class="xdebugmain">
		<hr />
			<div class="backtrace">'.
				$this->_Controller->_backtrace['xdump_class']
				.' invoked on '
				.'<a href="javascript:void(0)" onclick="xdbgToogle(\''.$sourceId.'\')">
					line '.@$this->_Controller->_backtrace['line']
				.' of file '.@$this->_Controller->_backtrace['file'].
			 	'</a>
			</div>
			<blockquote class="source" id="'.$sourceId.'">'.
				$this->_highlightSource($this->_Controller->getSource($this->_Controller->_backtrace))
			.'
			</blockquote>
			<ul class="xdebugroot">'.
				$renderResult.					
			'</ul>'.
			'<div class="backtrace">'.
				'took '.$this->_Controller->getTime().' seconds to process,'.
				'output is '.strlen ($renderResult).' bytes long.'.
			'</div>'.			
		'<hr />
		</div>
		<!-- XDUMP END -->'
		;		
	}	
	
	function _renderFactory(&$Element){		
		$method = '_renderElement'.ucfirst( $Element->type );
		if (method_exists( $this, $method )){
			return $this->$method( $Element );
		} 
		return $this->_renderElementDefault( $Element );		
	}
	
	function _renderChilds (&$childs){		
		$renderResult = '';
		for ($n = 0; $n < count ($childs); $n++){
			$renderResult .= $this->_renderFactory ($childs[$n]);
		}		
		return $renderResult;
	}	
	function _renderElementObject (&$Element){
		$id = $Element->getId();
		$initialDisplay = $this->_displayElement($Element->getLevel());
		if ($recursiveWith = $Element->isRecursive()){
			$childsRenderResult = '* recursive with element:'.$recursiveWith.' *';
		} elseif ($Element->getLevel() < 10 && $Element->hasChilds ()){
			$childs =& $Element->getChilds();
			$childsRenderResult = 
			sprintf (
				'<ul id="'.$this->_elementIdPrefix.'%s" style="%s">'
				, $id
				, $initialDisplay
			)
			.$this->_renderChilds( $childs )
			.'</ul>';
		} else {
			$childsRenderResult = '';
		}
		$ancestorsText='';
		if (count ($Element->getAncestors())){
			$ancestorsText = '<em> extends ('.join (', ',$Element->getAncestors()).')</em>';
		}

		return
		'<li><a href="javascript:void(0)"'
		.((!$recursiveWith)?'onclick="xdbgToogle ( \''
		.$this->_elementIdPrefix.$Element->getId()
		.'\' );"':'onclick="xdbgHighlight(\''.$this->_elementIdPrefix.$recursiveWith.'\' );"').
		 '><b>'.$Element->key.'</b></a>: Object of class <b>'.$Element->getClassName().'</b> '.$ancestorsText.' '.$childsRenderResult.'</li>'
		;
	}
	function _renderElementArray (&$Element)
	{	
		$id = $Element->getId();
		$initialDisplay = $this->_displayElement($Element->getLevel());			
		if ($recursiveWith = $Element->isRecursive()){
			$childsRenderResult = '* recursive with element:'.$recursiveWith.' *';
		} elseif ($Element->getLevel() < 10 && $Element->hasChilds ()){
			$childs =& $Element->getChilds();
			$childsRenderResult = '<ul id="'.$this->_elementIdPrefix.$id.'" style="'.$initialDisplay.'">'
				.$this->_renderChilds ( $childs )
				.'</ul>';
		} else {
			$childsRenderResult = '';
		}
		
		return 
		'<li>'
		.(($Element->hasChilds())?'<a href="javascript:void(0)" '
		.((!$recursiveWith)?'onclick="xdbgToogle ( \''.$this->_elementIdPrefix.$id.'\' );"':'onclick="xdbgHighlight(\''.$this->_elementIdPrefix.$recursiveWith.'\' );"')
		.'>':'')
		.'<b>'.$Element->key.'</b>'
		.(($Element->hasChilds())?'</a>':'')
		.': Array('.count($Element->value).') '.$childsRenderResult.' </li>';		
		//<ul id="%s" style="display: %s">#subitems#</ul>
	}
	/**
	 * @todo warning id is on the wrong element here, shuold not be on PRE.
	 */
	function _renderElementString (&$Element){
		$stringDisplay = (strlen ($Element->value)<180)?'"'.htmlentities($Element->value).'"':'<pre id="'.$this->_elementIdPrefix.$Element->getId().'" onclick="xdbgToogleString (\''.$this->_elementIdPrefix.$Element->getId().'\')">'.htmlentities($Element->value).'</pre>';
		return '<li><b>'.$Element->key.'</b>: '.$Element->type.'('.strlen($Element->value).'): '.$stringDisplay.' </li>';
	}
	/**
	 * @todo remove sprintf!
	 */
	function _renderElementBoolean (&$Element){
		return sprintf (
		'<li><b>%s</b>: %s: %s </li>'
		,$Element->key
		,$Element->type	
		,($Element->value?'<span style="font-weight:bold;color:green">TRUE</span>':'<span style="font-weight:bold;color:red">FALSE</span>')
		);
	}
	function _renderElementDefault (&$Element){
		return sprintf (
		'<li><b>%s</b>: %s: %s </li>'
		,$Element->key
		,$Element->type	
		,htmlentities((string)$Element->value)
		);
	}
	/*
	function _renderElementResource (&$Element){
		return $this->_renderElementArray(&$Element);
		
	}
	*/
	//--- various stuff
	function _displayElement ($level){
		if ($level >= ( $this->autoExpand - 1 ) ){
			$style='display:none;';
		} else {
			$style='';
		}
		
		$color_number=245-(10*($level+1));
		if ($color_number < 128){
			$color_number=128;
		}
		$style.= 'background:RGB('.$color_number.','.$color_number.','.$color_number.')';
		return $style;
	}
	
	function _renderOnce(){
		static $rendered=false;
		if ( !$rendered ){
			//define javascript functions
			$toBePrinted="
	<script type=\"text/javascript\">
			//--- X-Debug js functions			
			function xdbgToogle(index){
				try {
					var element = document.getElementById(index);
					if (element.style.display=='none'){
						element.style.display='block';
					} else {
						element.style.display='none';
					}
				} catch (e) {
					alert ('xdebug javascript error - exception got catched, so the rest of page will keep working.');
				}
			}
			function xdbgToogleString(index){
				try {
					var element = document.getElementById(index);
					if (!element.className){
						element.className='expanded';
					} else {
						element.className='';
					}
				} catch (e) {
				alert (index,e);
					alert ('xdebug javascript error - exception got catched, so the rest of page will keep working.');
				}
			}
			
			function xdbgHighlight(index, undo){
				try {
					var element = document.getElementById(index);
					if (!undo){
						timerID  = setTimeout('xdbgHighlight(\''+index+'\',true)', 1000);
						element.className='highlighted';
					} else {
						element.className='';
					}
				} catch (e) {
				alert (index,e);
					alert ('xdebug javascript error - exception got catched, so the rest of page will keep working.');
				}
			
			   /*
				try {
					var element = document.getElementById(index);
					
					if (element.style.background != 'orange'){
						timerID  = setTimeout('xdbgUnHighlight(\''+index+'\',\''+element.style.background+'\')', 1000);
						element.style.background='orange';
					}		
				} catch (e) {
				alert (index);
				alert (e);
					alert ('xdebug javascript error - exception got catched, so the rest of page will keep working.');
				}
				*/
			}		
			
			</script>
			<style type=\"text/css\">
			.xdebugmain, .xdebugmain ul, xdebugmain li {						
				border:1px solid gray;
				font-size:12px;
				font-family:arial;			
			}
			.xdebugmain {margin:10px 0px;background: rgb(245,245,245)}
			.xdebugmain ul {padding:5px 0px; margin-top:5px; margin-left:0; list-style-position:inside;}
			.xdebugmain li {padding:5px 10px;margin:0;}
			.xdebugmain .backtrace {text-align:right; font-size:11px;}			
			.xdebugmain ul.xdebugroot {
				border:0 none;
				padding:0;
				margin:0;
			}
			.xdebugmain pre {display:block;height:50px; width:98%; overflow:hidden;padding:10px;border:1px gray dashed;}			
			.xdebugmain pre.expanded {

			border:1px black solid;
			width:90%;
			height:300px;
		 	overflow:auto;
			}
			.xdebugmain blockquote.source {
				display:none; 
				border: 1px gray dotted;
				margin: 0px 10px;
				background:#eeeecc;
				
			}
			
			.highlighted {
				background:orange !important;
			}
			
			.xdebugmain hr {
				display: none;
			}
			
			</style>
			";
			
			$rendered=true;
			return $toBePrinted;
		}
		return '';
	}

}
class XdumpBacktraceXhtmlWriter extends xDumpXhtmlWriter {
	function _renderFactory(&$Element){		
		if ( $Element->type == 'array' && $Element->getLevel() == 1){
			return $this->_renderElementBacktrace( $Element );
		}
		$method = '_renderElement'.ucfirst( $Element->type );
		if (method_exists( $this, $method )){
			return $this->$method( $Element );
		} 
		return $this->_renderElementDefault( $Element );		
	}
	function _makeLabel ( $Element )
	{
		$label  = $Element->key.' - ';
		if ( isset ($Element->value['class'])) $label .= $Element->value['class'].' :: ';
		if ( isset ($Element->value['function'])) $label .= $Element->value['function'];
		if ( isset ($Element->value['file']) && isset ($Element->value['line']) )
		{
			$label .= ' in ['.$Element->value['file'].' line '.$Element->value['line'].']';
		} else {
			$label .= ' in [unknown location]';
		}
		return $label;
	}
	function _renderElementBacktrace ( &$Element ) {
		$id = $Element->getId();
		$initialDisplay = $this->_displayElement($Element->getLevel());			
		$source = $this->_Controller->getSource($Element->value,20);
		$label = $this->_makeLabel( $Element );
		
		if ($source) {
			$sourceHtml = '
		  <blockquote class="source" style="display:block">'.
			$this->_highlightSource( $source )
		.'</blockquote>
		';
		} else {
			$sourceHtml ='';
		}

		if ($recursiveWith = $Element->isRecursive()){
			$childsRenderResult = '* recursive with element:'.$recursiveWith.' *';
		} elseif ($Element->getLevel() < 10 && $Element->hasChilds ()){
			$childs =& $Element->getChilds();
			$childsRenderResult = '<ul id="'.$this->_elementIdPrefix.$id.'" style="'.$initialDisplay.'">'
			.$this->_renderChilds ( $childs )
			.$sourceHtml
			.'</ul>';
		} else {
			$childsRenderResult = '';
		}		
		return 
		'<li>'
		.(($Element->hasChilds())?'<a href="javascript:void(0)" '
		.((!$recursiveWith)?'onclick="xdbgToogle ( \''.$this->_elementIdPrefix.$id.'\' );"':'onclick="xdbgHighlight(\''.$this->_elementIdPrefix.$recursiveWith.'\' );"')
		.'>':'')
		.'<b>'.$label.'</b>'
		.(($Element->hasChilds())?'</a>':'')
		.': Array('.count($Element->value).') '.$childsRenderResult.' </li>';
	}
}
?>