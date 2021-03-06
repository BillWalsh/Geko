<?php

class Geko_Html_Element
{
	
	protected $_sElem;
	protected $_aAtts = array();
	protected $_aChildren = array();
	protected $_iHtmlVersion = 4;
	protected $_bHasContent = TRUE;
	
	
	
	//// global attributes
	
	// standard
	protected $_aGlobalAtts = array(
		'accesskey', 'class', 'dir', 'id', 'lang', 'style', 'tabindex', 'title'
	);
	
	// html 4 only
	protected $_aGlobalAtts4 = array();
	
	// html 5 only
	protected $_aGlobalAtts5 = array(
		'contenteditable', 'contextmenu', 'draggable', 'dropzone', 'hidden', 'spellcheck'
	);
	
	//// element attributes
	
	// standard
	protected $_aValidAtts = array();
	
	// html 4 only
	protected $_aValidAtts4 = array();
	
	// html 5 only
	protected $_aValidAtts5 = array();
	
	
	
	//// special attributes
	
	protected $_aClass = array();
	
	
	
	
	
	// factory method
	public static function create( $sElem, $aAtts = array(), $mContent = NULL ) {
		
		$sClass = 'Geko_Html_Element_' . ucfirst( trim( $sElem ) );
		
		if ( class_exists( $sClass ) ) {
			$oElem = new $sClass();
		} else {
			$sClass = __CLASS__;
			$oElem = new $sClass( $sElem );
		}
		
		$oElem
			->_setAtts( $aAtts )
			->append( $mContent )
		;
				
		return $oElem;
	}
	
	
	
	//// construct
	
	//
	public function __construct( $sElem = '' ) {
		if ( $sElem ) {
			$this->_sElem = $sElem;
		}
	}
	
	
	//// accessors
	
	//
	public function _setAtt( $sKey, $sValue ) {
		if ( 'class' == $sKey ) {
			$this->setClass( $sValue );
		} else {
			$this->_aAtts[ $sKey ] = $sValue;
		}
		return $this;
	}
	
	//
	public function _setAtts( $aAtts ) {
		foreach ( $aAtts as $sKey => $sValue ) {
			$this->_setAtt( $sKey, $sValue );
		}
		return $this;
	}
	
	//
	public function _unsetAtt( $sKey ) {
		if ( 'class' == $sKey ) {
			$this->unsetClass();
		} else {
			unset( $this->_aAtts[ $sKey ] );		
		}
		return $this;
	}
	
	//
	public function _unsetAtts() {
		$this->_aAtts = array();
		return $this;
	}
	
	//
	public function _setHtmlVersion( $iHtmlVersion ) {
		$this->_iHtmlVersion = $iHtmlVersion;
		return $this;
	}
	
	//
	public function append( $mElem ) {
		
		if ( is_string( $mElem ) ) {
			$oText = new Geko_Html_Element_Text();
			$oText->_setContent( $mElem );
			$this->_aChildren[] = $oText;
		} elseif ( is_object( $mElem ) && is_a( $mElem, __CLASS__ ) ) {
			$this->_aChildren[] = $mElem;
		} elseif ( is_array( $mElem ) ) {
			foreach ( $mElem as $oElem ) {
				$this->append( $oElem );
			}
		}
		
		return $this;
	}
	
	//
	public function prepend( $oElem ) {

		if ( is_string( $mElem ) ) {
			$oText = new Geko_Html_Element_Text();
			$oText->_setContent( $mElem );
			array_unshift( $this->_aChildren, $oText );
		} elseif ( is_object( $mElem ) && is_a( $mElem, __CLASS__ ) ) {
			array_unshift( $this->_aChildren, $mElem );
		} elseif ( is_array( $mElem ) ) {
			$aElems = array_reverse( $mElem );
			foreach ( $aElems as $oElem ) {
				$this->prepend( $oElem  );
			}
		}
		
		return $this;	
	}
	
	// removes all children
	public function remove() {
		$this->_aChildren = array();
		return $this;
	}
	
	//
	public function reset() {
		return $this
			->_unsetAtts()
			->unsetClass()
			->remove()
		;
	}
	
	
	
	//// special accessors
	
	//
	public function addClass( $sClass ) {
		$aClass = Geko_Array::explodeTrimEmpty( ' ', $sClass );
		foreach ( $aClass as $sItem ) {
			$this->_aClass[] = $sItem;
		}
		return $this;
	}
	
	//
	public function setClass( $sClass ) {
		$this->_aClass = array();
		return $this->addClass( $sClass );
	}
	
	//
	public function unsetClass() {
		$this->_aClass = array();
		return $this;
	}
	
	//
	public function removeClass( $sClass ) {
		$aRemove = Geko_Array::explodeTrimEmpty( ' ', $sClass );
		$aRet = array();
		foreach ( $this->_aClass as $sItem ) {
			if ( !in_array( $sItem, $aRemove ) ) {
				$aRet[] = $sItem;
			}
		}
		$this->_aClass = $aRet;
		return $this;
	}
	
	//
	public function hasClass( $sClass ) {
		$aHas = Geko_Array::explodeTrimEmpty( ' ', $sClass );
		foreach ( $aHas as $sItem ) {
			if ( !in_array( $sItem, $this->_aClass ) ) {
				return FALSE;
			}
		}
		return TRUE;
	}
	
	
	
	//// magic methods
	
	// output the completed query
	public function __toString() {
		
		$sOutput = '<' . $this->_sElem;
		
		//// add attributes
		
		// get list of valid attributes
		
		$aValidAtts = array();
		$aValidAtts = array_merge( $aValidAtts, $this->_aGlobalAtts, $this->_aValidAtts );
		
		if ( 5 == $this->_iHtmlVersion ) {
			$aValidAtts = array_merge( $aValidAtts, $this->_aGlobalAtts5, $this->_aValidAtts5 );		
		} else {
			// html 4 is default
			$aValidAtts = array_merge( $aValidAtts, $this->_aGlobalAtts4, $this->_aValidAtts4 );		
		}
		
		// integrate special attributes
		if ( count( $this->_aClass ) > 0 ) {
			$this->_aAtts[ 'class' ] = implode( ' ', $this->_aClass );
		}
		
		// go through attributes and check against valid attributes
		$aAttFmt = array();
		$sAtts = '';
		foreach ( $this->_aAtts as $sAtt => $sValue ) {
			if (
				( isset( $this->_aAtts[ $sAtt ] ) ) && 
				( in_array( $sAtt, $aValidAtts ) )
			) {
				$aAttFmt[] = sprintf( '%s="%s"', $sAtt, htmlspecialchars( $sValue ) );
			}
		}
		
		if ( count( $aAttFmt ) > 0 ) {
			$sAtts = ' ' . implode( ' ', $aAttFmt );
		}
		
		if ( !$this->_bHasContent ) {
			
			// close the element
			$sOutput .= $sAtts . ' />';
			
		} else {
			
			$sOutput .= $sAtts . '>';
			
			//// add content
			$sContent = '';
			foreach ( $this->_aChildren as $oElem ) {
				$sContent .= strval( $oElem );
			}
			
			$sOutput .= $sContent;
			
			//// close tag
			$sOutput .= '</' . $this->_sElem . '>';
		
		}
		
		return $sOutput;
		
	}
	
	//
	public function __call( $sMethod, $aArgs ) {
		
		if ( 0 === strpos( strtolower( $sMethod ), 'set' ) ) {
			$sAtt = substr_replace( $sMethod, '', 0, 3 );
			$this->_setAtt( strtolower( $sAtt ), $aArgs[ 0 ] );
			return $this;
		} elseif ( 0 === strpos( strtolower( $sMethod ), 'unset' ) ) {
			$sAtt = substr_replace( $sMethod, '', 0, 5 );
			$this->_unsetAtt( strtolower( $sAtt ) );
			return $this;
		}
		
		throw new Exception( 'Invalid method ' . __CLASS__ . '::' . $sMethod . '() called.' );
		
	}
	
	
}
