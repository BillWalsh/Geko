<?php

class Geko_Wp_Category_Query extends Geko_Wp_Entity_Query
{
	
	// implement by sub-class to populate entities/total rows
	public function init() {
		
		// if using non-standard parameters, use our own query
		if ( $this->_aParams[ 'use_non_native_query' ] ) {
			return parent::init();
		}
		
		$this->_aEntities = ( 0 === $this->_aParams[ 'number' ] ) ?
			array() : 
			array_values( get_categories( $this->_aParams ) )
		;
		
		$this->_iTotalRows = count( $this->_aEntities );
		
		return $this;
	}
	
	//
	public function getDefaultParams() {
		
		// hacky!!!
		$aDefaultParams = parent::getDefaultParams();
		
		if ( $aDefaultParams[ 'category_name' ] ) {
			
			$aDefaultParams[ 'include' ] = Geko_Wp_Category::get_ID(
				$aDefaultParams[ 'category_name' ]
			);
			
			unset( $aDefaultParams[ 'category_name' ] );
		}
		
		return $aDefaultParams;
	}
	
	
	// only kicks in when "use_non_native_query" is set to TRUE
	public function modifyQuery( $oQuery, $aParams ) {
		
		global $wpdb;
		
		// apply super-class manipulations
		$oQuery = parent::modifyQuery( $oQuery, $aParams );
		
		$oQuery
			
			->distinct( TRUE )
			
			->field( 't.term_id' )
			->field( 't.name' )
			->field( 't.slug' )
			->field( 't.term_group' )
			
			->field( 'tx.term_taxonomy_id' )
			->field( 'tx.taxonomy' )
			->field( 'tx.description' )
			->field( 'tx.parent' )
			->field( 'tx.count' )
			
			->from( $wpdb->terms, 't' )
			->joinLeft( $wpdb->term_taxonomy, 'tx' )
				->on( 'tx.term_id = t.term_id' )
			->joinLeft( $wpdb->term_relationships, 'tr' )
				->on( 'tr.term_taxonomy_id = tx.term_taxonomy_id' )
			
			->where( 'tx.taxonomy = ?', 'category' )
			
		;
		
		if ( $iParentId = $aParams[ 'parent' ] ) {
			$oQuery->where( 'tx.parent = ?', $iParentId );
		}
		
		return $oQuery;
		
	}
	
	
	/* /
	// ???
	public function getSqlQuery()
	{
		// no idea what the original query is
	}
	/* */
	
	
	//
	public function getAsFlatNested() {
		
		$aCatGroup = array();
		foreach ( $this as $oCat ) {
			$aCatGroup[ $oCat->getParent() ][] = $oCat;
		}
		
		return $this->sortAsFlatNested( $aCatGroup );
	}
	
	// helper for $this->getAsFlatNested()
	public function sortAsFlatNested( $aCatGroup, $iParent = 0, $iLevel = 0 ) {
		$aRet = array();
		$aList = $aCatGroup[ $iParent ];
		if ( count( $aList ) > 0 ) {
			foreach ( $aList as $oCat ) {
				$aRet[] = $oCat->setData( 'level', $iLevel );
				$iCatId = $oCat->getId();
				if ( is_array( $aCatGroup[ $iCatId ] ) ) {
					$aRet = array_merge( $aRet, $this->sortAsFlatNested( $aCatGroup, $iCatId, $iLevel + 1 ) );
				}
			}
		}
		return $aRet;	
	}
	
}




