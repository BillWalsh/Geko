<?php

// static class container for form enumerations
class Geko_Wp_Enumeration extends Geko_Wp_Entity
{
	
	protected $_sEntityIdVarName = 'geko_enum_id';
	protected $_sEntitySlugVarName = 'geko_enum_slug';
	
	//
	public function init() {
		
		parent::init();
		
		$this
			->setEntityMapping( 'id', 'enum_id' )
			->setEntityMapping( 'content', 'description' )
		;
		
		return $this;
	}
	
	
}


