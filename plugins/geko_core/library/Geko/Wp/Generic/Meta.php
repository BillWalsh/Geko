<?php

//// !!! Re-factoring in progress...

//
class Geko_Wp_Generic_Meta extends Geko_Wp_Options_Meta
{
	
	protected static $aMetaCache = array();
	
	protected $_sParentFieldName = 'generic_id';
	
	
	//// init
	
	//
	public function affix() {
		
		global $wpdb;
		
		Geko_Wp_Options_MetaKey::init();
		
		$sTableName = 'geko_generic_meta';
		Geko_Wp_Db::addPrefix( $sTableName );
		
		$oSqlTable = new Geko_Sql_Table();
		$oSqlTable
			->create( $wpdb->$sTableName, 'jm' )
			->fieldBigInt( 'jmeta_id', array( 'unsgnd', 'notnull', 'autoinc', 'prky' ) )
			->fieldBigInt( $this->_sParentFieldName, array( 'unsgnd', 'notnull' ) )
			->fieldSmallInt( 'mkey_id', array( 'unsgnd', 'notnull' ) )
			->fieldLongText( 'meta_value' )
			->fieldLongText( 'flags' )
			->indexKey( 'gen_mkey_id', array( 'generic_id', 'mkey_id' ) )
		;
		
		$this->addTable( $oSqlTable );
		
		
		$sTableName2 = 'geko_generic_meta_members';
		Geko_Wp_Db::addPrefix( $sTableName2 );
		
		$oSqlTable2 = new Geko_Sql_Table();
		$oSqlTable2
			->create( $wpdb->$sTableName2, 'jmm' )
			->fieldBigInt( 'jmeta_id', array( 'unsgnd', 'key' ) )
			->fieldBigInt( 'member_id', array( 'unsgnd', 'key' ) )
			->fieldLongText( 'member_value' )
			->fieldLongText( 'flags' )
		;
		
		$this->addTable( $oSqlTable2, FALSE );
		
		
		return $this;
	}
	
	
	
	// create table
	public function install() {
		
		global $wpdb;
		
		Geko_Wp_Options_MetaKey::install();
		
		$this->createTable( $this->getPrimaryTable() );
		$this->createTable( $wpdb->geko_generic_meta_members );
		
		return $this;
	}
	
	
	//
	public function getPrimaryTable() {
		
		if ( $this->_sInstanceClass != __CLASS__ ) {
			$oMng = Geko_Singleton_Abstract::getInstance( __CLASS__ );
			return $oMng->getPrimaryTable();
		}
		
		return parent::getPrimaryTable();
	}
	
	
	
	
	
	//// accessors
	
	//
	public function getStoredOptions() {
		
		$iGenericId = intval( $_GET[ 'generic_id' ] );
		
		if ( $iGenericId ) {
			
			$this->setMetaCache( $iGenericId );
			
			$aMeta = array();
			$aElemsGroup = parent::getElemsGroup();			// yields correct result!
			$aMetaCache = self::$aMetaCache[ $iGenericId ];
			
			foreach ( $aElemsGroup as $sMetaKey => $aElem ) {
				if ( isset( $aMetaCache[ $sMetaKey ] ) ) {
					$aMeta[ $sMetaKey ] = $aMetaCache[ $sMetaKey ];
				}
			}
			
			return $aMeta;
		
		} else {
			return array();
		}
	}
	
	
	//
	public function getMeta( $iGenericId, $sMetaKey = '' ) {
		
		$this->setMetaCache( $iGenericId );
		
		if ( $sMetaKey ) {
			return self::$aMetaCache[ $iGenericId ][ $this->getPrefixWithSep() . $sMetaKey ];
		} else {
			return self::$aMetaCache[ $iGenericId ];
		}
	}
	
	
	
	//// cache helpers
	
	//
	protected function setMetaCache( $iGenericId ) {
		
		if ( !isset( self::$aMetaCache[ $iGenericId ] ) ) {
			
			global $wpdb;
			
			$aFmt = Geko_Wp_Db::getResultsHash(
				$wpdb->prepare(
					"	SELECT			m.jmeta_id,
										m.generic_id,
										k.meta_key,
										m.meta_value,
										m.flags
						FROM			$wpdb->geko_generic_meta m
						LEFT JOIN		$wpdb->geko_meta_key k
							ON			k.mkey_id = m.mkey_id
						WHERE			m.generic_id = %d
					",
					$iGenericId
				),
				'meta_key'
			);
			
			////
			$aSubVals = $this->gatherSubMetaValues( $aFmt, 'geko_generic_meta_members', 'jmeta_id' );
			
			// filter real elements (excluding flag checkboxes), $aFlagData is not used
			list( $aElemsReal, $aFlagData ) = $this->getFlagData( $this->getElemsGroup(), NULL );
			
			$aRet = array();
			foreach ( $aElemsReal as $sMetaKey => $aElem ) {
				$oItem = $aFmt[ $sMetaKey ];
				
				if ( isset( $aSubVals[ $oItem->jmeta_id ] ) ) {
					$aRet[ $sMetaKey ] = $aSubVals[ $oItem->jmeta_id ];
				} else {
					$aRet[ $sMetaKey ] = maybe_unserialize( $oItem->meta_value );
				}
				
				if ( $oItem->flags ) {
					$aFlags = explode( ',', $oItem->flags );
					foreach ( $aFlags as $sFlag ) $aRet[ $sMetaKey . '--' . $sFlag ] = 1;
				}
			}
			
			self::$aMetaCache[ $iGenericId ] = $aRet;
		}
		
	}
	
	
	
	
	//// crud methods
	
	//
	public function delete( $oGeneric ) {
		// cleanup all orphaned metadata
		global $wpdb;
		
		// meta
		$wpdb->query("
			DELETE FROM		$wpdb->geko_generic_meta
			WHERE			generic_id NOT IN (
				SELECT			generic_id
				FROM			$wpdb->geko_generic
			)
		");
		
		// members
		$wpdb->query("
			DELETE FROM		$wpdb->geko_generic_meta_members
			WHERE			jmeta_id NOT IN (
				SELECT			jmeta_id
				FROM			$wpdb->geko_generic_meta
			)
		");
		
	}
		



	// save the data
	public function save(
		$oGeneric, $sMode = 'insert', $sGroupTypeSlug = '', $aParams = NULL, $aDataVals = NULL, $aFileVals = NULL
	) {
		
		global $wpdb;
		
		//
		$aElemsGroup = isset( $aParams[ 'elems_group' ] ) ? 
			$aParams[ 'elems_group' ] : 
			$this->getElemsGroup()
		;
		
		if ( 'update' == $sMode ) {
			$aMeta = Geko_Wp_Db::getResultsHash(
				$wpdb->prepare(
					"	SELECT			m.jmeta_id,
										m.generic_id,
										k.meta_key,
										m.meta_value,
										m.flags
						FROM			$wpdb->geko_generic_meta m
						LEFT JOIN		$wpdb->geko_meta_key k
							ON			k.mkey_id = m.mkey_id
						WHERE			m.generic_id = %d
					",
					$oGeneric->getId()
				),
				'meta_key'
			);
		} else {
			$aMeta = array();
		}
		
		list( $aElemsReal, $aFlagData ) = $this->getFlagData( $aElemsGroup, $aDataVals );
		
		$this->commitMetaData(
			array(
				'elems_group' => $aElemsReal,
				'meta_data' => $aMeta,
				'entity_id' => $iEntityId,
				'meta_table' => 'geko_generic_meta',
				'meta_member_table' => 'geko_generic_meta_members',
				'meta_entity_id_field_name' => 'generic_id',
				'meta_id_field_name' => 'jmeta_id',
				'flag_data' => $aFlagData,
				'has_flags' => ( is_array( $aFlagData ) ) ? TRUE : FALSE,
				'use_mkey_id' => TRUE
			),
			$aDataVals,
			$aFileVals
		);
		
	}
	
	
	
	//
	protected function commitMetaDataValue( $aVals, $oMeta, $sMetaKey, $aParams ) {

		$sFlags = '';
		
		if ( $aParams[ 'has_flags' ] ) {
			
			$aFlags = ( is_array( $aParams[ 'flag_data' ][ $sMetaKey ] ) ) ?
				$aParams[ 'flag_data' ][ $sMetaKey ] : 
				array()
			;
			
			$sFlags = implode( ',', $aFlags );
		}
		
		$aVals[ 'flags' ] = $sFlags;
		
		return $aVals;
	}
	
	//
	protected function commitMetaDataValueChanged( $aVals, $oMeta ) {
		return ( $oMeta->flags != $aVals[ 'flags' ] );
	}

	
	
	//// helpers
	
	/*
	
	// flags are essentially checkboxes
	
	// eg:
	Color: <input type="text" id="color" name="color" />
	Is Primary? <input type="checkbox" id="color--primary" name="color--primary" />
	Is Vibrant? <input type="checkbox" id="color--vibrant" name="color--vibrant" />
	Size: <input type="text" id="size" name="size" /> <!-- no flags -->
	
	// with possible prefixing:
	Color: <input type="text" id="pfx-color" name="pfx-color" />
	Is Primary? <input type="checkbox" id="pfx-color--primary" name="pfx-color--primary" />
	Is Vibrant? <input type="checkbox" id="pfx-color--vibrant" name="pfx-color--vibrant" />	
	Size: <input type="text" id="pfx-size" name="pfx-size" /> <!-- no flags -->
	
	| jmeta_id | generic_id | mkey_id | meta_value | flags           |
	+----------+------------+---------+------------+-----------------+
	| 1        | 1          | 1       | red        | primary,vibrant |
	| 2        | 1          | 2       | big        |                 |
	| 3        | 2          | 1       | green      | vibrant         |
	| 4        | 2          | 2       | small      |                 |
	
	// flag setup would be:
	
	array(
		'color' => array( 'primary', 'vibrant' ),
		'size' => array()
	)

	array(
		'pfx-color' => array( 'primary', 'vibrant' ),
		'pfx-size' => array()
	)
	
	// other use:
	First Name: <input type="text" id="first_name" name="first_name" />
	Private? <input type="checkbox" id="first_name--private" name="first_name--private" />	
	
	Last Name: <input type="text" id="last_name" name="last_name" />
	Private? <input type="checkbox" id="last_name--private" name="last_name--private" />	
	
	Age: <input type="text" id="age" name="age" />
	Private? <input type="checkbox" id="age--private" name="age--private" />	
	
	Weight: <input type="text" id="weight" name="weight" />
	Private? <input type="checkbox" id="weight--private" name="weight--private" />	
	
	*/
	
	//
	public function getFlagData( $aElemsGroup, $aDataVals ) {
		
		// set values
		$aDataVals = $this->getDataValues( $aDataVals );
		
		$aFlagSetup = array();
		$aElemsReal = array();				// array of real elements, not flag checkboxes
		$bHasFlagElement = FALSE;
		
		// obtain flag setup from the elems group
		foreach ( $aElemsGroup as $sMetaKey => $aElem ) {
			if ( FALSE !== strpos( $sMetaKey, '--' ) ) {
				// detected flag element
				$bHasFlagElement = TRUE;
				list( $sKeyMain, $sKeyFlag ) = explode( '--', $sMetaKey );
				$aFlagSetup[ $sKeyMain ][] = $sKeyFlag;
			} else {
				// regular element
				if ( !$aFlagSetup[ $sMetaKey ] ) $aFlagSetup[ $sMetaKey ] = array();
				$aElemsReal[ $sMetaKey ] = $aElem;
			}
		}
		
		if ( $bHasFlagElement ) {
			
			$aFlagData = array();
			
			// obtain the flag values based on $aDataVals
			foreach ( $aFlagSetup as $sMetaKey => $aFlags ) {
				foreach ( $aFlags as $sFlag ) {
					if ( isset( $aDataVals[ $sMetaKey . '--' . $sFlag ] ) ) {
						$aFlagData[ $sMetaKey ][] = $sFlag;
					}
				}
			}
			
			return array( $aElemsReal, $aFlagData );
			
		}
		
		return array( $aElemsReal, FALSE );
		
	}
		
	
	
}

