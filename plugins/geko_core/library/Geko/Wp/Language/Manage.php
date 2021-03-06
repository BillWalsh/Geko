<?php

//
class Geko_Wp_Language_Manage extends Geko_Wp_Options_Manage
{
	protected static $aLanguages = NULL;
	protected static $aLangCodeHash = NULL;
	protected static $oDefaultLang = NULL;
	
	protected $_bPrefixFormElems = FALSE;		// turn off prefixing
	
	protected $_sEntityIdVarName = 'lang_id';
	
	protected $_sSubject = 'Language';
	protected $_sDescription = 'An API/UI that handles language management.';
	protected $_sIconId = 'icon-users';
	protected $_sType = 'lang';
	protected $_sPrefix = 'geko_lang';
	
	protected $_aSubOptions = array( 'Geko_Wp_Language_String_Manage' );
	
	protected $_iEntitiesPerPage = 10;
	
	
	
	//// methods
	
	
	//
	public function affix() {
		
		global $wpdb;
		
		Geko_Wp_Options_MetaKey::init();
		
		
		$sTableName = 'geko_languages';
		Geko_Wp_Db::addPrefix( $sTableName );

		$oSqlTable = new Geko_Sql_Table();
		$oSqlTable
			->create( $wpdb->$sTableName, 'l' )
			->fieldSmallInt( 'lang_id', array( 'unsgnd', 'notnull', 'autoinc', 'prky' ) )
			->fieldVarChar( 'code', array( 'size' => 8, 'unq' ) )
			->fieldVarChar( 'title', array( 'size' => 256 ) )
			->fieldBool( 'is_default' )
			->fieldDateTime( 'date_created' )
			->fieldDateTime( 'date_modified' )
		;
		
		$this->addTable( $oSqlTable );
		
		
		$sTableName2 = 'geko_lang_groups';
		Geko_Wp_Db::addPrefix( $sTableName2 );

		$oSqlTable2 = new Geko_Sql_Table();
		$oSqlTable2
			->create( $wpdb->$sTableName2, 'lg' )
			->fieldBigInt( 'lgroup_id', array( 'unsgnd', 'notnull', 'autoinc', 'prky' ) )
			->fieldSmallInt( 'type_id', array( 'unsgnd', 'key' ) )
		;
		
		$this->addTable( $oSqlTable2, FALSE );
		
		
		$sTableName3 = 'geko_lang_group_members';
		Geko_Wp_Db::addPrefix( $sTableName3 );
		
		$oSqlTable3 = new Geko_Sql_Table();
		$oSqlTable3
			->create( $wpdb->$sTableName3, 'lgm' )
			->fieldBigInt( 'lgroup_id', array( 'unsgnd' ) )
			->fieldBigInt( 'obj_id', array( 'unsgnd' ) )
			->fieldSmallInt( 'lang_id', array( 'unsgnd' ) )
			->indexKey( 'lgroup_member', array( 'lgroup_id', 'obj_id', 'lang_id' ) )
		;
		
		$this->addTable( $oSqlTable3, FALSE );
		
		
		
		return $this;
	}
	
	
	
	
	// create table
	public function install() {
		
		global $wpdb;
		
		Geko_Wp_Options_MetaKey::install();
		
		$this->createTable( $this->getPrimaryTable() );
		
		$this->createTable( $wpdb->geko_lang_groups );
		$this->createTable( $wpdb->geko_lang_group_members );
		
		Geko_Wp_Language_String_Manage::getInstance()->install();
		
		return $this;
	}
	
	
	
	
	
	//// plugin management
	
	//
	public function registerPlugins() {
		
		if ( $this->_sInstanceClass == __CLASS__ ) {
			
			$aArgs = func_get_args();
			
			foreach ( $aArgs as $sClass ) {
				$sSubClass = __CLASS__ . '_' . $sClass;
				if ( @is_subclass_of( $sSubClass, __CLASS__ ) ) {
					Geko_Singleton_Abstract::getInstance( $sSubClass )->init();
				} elseif ( @is_subclass_of( $sClass, __CLASS__ ) ) {
					Geko_Singleton_Abstract::getInstance( $sClass )->init();				
				}
			}
			
		}
		
		return $this;
	}
	
	
	
	
	
	
	//// error message handling
		
	//
	public function echoNotificationHtml() {
		$this->notificationMessages();
	}
	
	//// helpers
	
	//
	public function getLanguages() {
		
		if ( !self::$aLanguages ) {
			
			$aParams = array();
			$aLangs = new Geko_Wp_Language_Query( $aParams, FALSE );
			
			foreach ( $aLangs as $oLang ) {
				self::$aLanguages[ $oLang->getId() ] = $oLang;
				self::$aLangCodeHash[ $oLang->getSlug() ] = $oLang->getId();
				if ( $oLang->getIsDefault() ) self::$oDefaultLang = $oLang;
			}
		}
		
		return self::$aLanguages;
	}
	
	//
	public function getLangCode( $iLangId = NULL ) {
		if ( $oLang = $this->getLanguage( $iLangId ) ) {
			return $oLang->getSlug();
		}
		return NULL;
	}
	
	// will return the default language id
	public function getLangId( $sLangSlug = NULL ) {
		
		$this->getLanguages();		// initialize lang array
		
		if ( NULL !== $sLangSlug ) {
			$oLang = $this->getLanguage( $sLangSlug );
			return $oLang->getId();
		}
		
		if ( self::$oDefaultLang ) {
			return self::$oDefaultLang->getId();
		}
		
		return NULL;
	}
	
	//
	public function getDefLangCode() {
		$this->getLanguages();		// initialize lang array
		if ( self::$oDefaultLang ) {
			return self::$oDefaultLang->getSlug();
		}
		return NULL;
	}
	
	//
	public function isDefLang() {
		if ( self::$oDefaultLang ) {
			return ( $this->getLangCode() == self::$oDefaultLang->getSlug() );
		}
		return NULL;
	}
	
	// $mLang can either be id or slug
	public function getLanguage( $mLang = NULL ) {
		$this->getLanguages();		// initialize lang array
		
		$iLangId = FALSE;
		if ( $mLang = trim( $mLang ) ) {
			if ( preg_match( '/^[0-9]+$/', $mLang ) ) {
				$iLangId = intval( $mLang );
			} elseif ( $mLang ) {
				$iLangId = self::$aLangCodeHash[ $mLang ];
			}
		}
		
		if ( $iLangId ) {
			return self::$aLanguages[ $iLangId ];
		} else {
			return self::$oDefaultLang;
		}
	}
	
	
	
	
	
	
	
	
	
	
	//
	public function echoLanguageSelect() {
		
		$aLangs = $this->getLanguages();
		
		?><select id="geko_lang_id" name="geko_lang_id">
			<?php foreach( $aLangs as $oLang ): ?>
				<option value="<?php $oLang->echoId(); ?>"><?php $oLang->echoTitle(); ?></option>
			<?php endforeach; ?>
		</select><?php
	}
	
	//
	public function echoLanguageHidden( $iLangGroupId, $iLangId ) {
		?>
		<input type="hidden" id="geko_lgroup_id" name="geko_lgroup_id" value="<?php echo $iLangGroupId; ?>" />
		<input type="hidden" id="geko_lang_id" name="geko_lang_id" value="<?php echo $iLangId; ?>" />
		<?php
	}
	
	//
	public function getSelectorLinks(
		$iLangGroupId, $iLangId, $iCurrObjId, $sType, $aParams = array()
	) {
		global $wpdb;
		
		//// get siblings

		$aSibsFmt = array();
		$aSibParams = array();
		
		// typically if the lang_group_id and lang_id are given, there is no current object
		if ( $iLangGroupId && $iLangId ) $aSibParams = array( 'lang_group_id' => $iLangGroupId );
		
		// typically, if there is an obj_id given, there is no lang_group_id and lang_id
		if ( $iCurrObjId ) $aSibParams = array( 'sibling_id' => $iCurrObjId, 'type' => $sType );
		
		// look for siblings if there is enough info
		if ( count( $aSibParams ) > 0 ) {

			$aSibs = new Geko_Wp_Language_Member_Query( $aSibParams, FALSE );
			
			// if there are siblings found, then it means there's language associations
			if ( $aSibs->count() > 0 ) {
				
				// organize siblings by language
				foreach ( $aSibs as $oSib ) {
					
					if ( $iCurrObjId && ( $oSib->getObjId() == $iCurrObjId ) ) {
						// assign values to these since they were not originally specified
						$iLangGroupId = $oSib->getLangGroupId();
						$iLangId = $oSib->getLangId();
					}
					
					$aSibsFmt[ $oSib->getLangId() ] = $oSib;
				}
				
				// get list of available languages
				$aLangs = $this->getLanguages();
				$aLinks = array();
				
				foreach( $aLangs as $oLang ) {
					
					if ( $oLang->getId() == $iLangId ) {
						
						// since already on the current item, just show the title
						$aParams[ 'title' ] = $oLang->getTitle();
						
						$aLinks[] = $this->getSelCurrLink( $aParams );
						
					} elseif ( $oSib = $aSibsFmt[ $oLang->getId() ] ) {
						
						// get link to existing sibling
						$aParams[ 'title' ] = $oLang->getTitle();
						$aParams[ 'obj_id' ] = $oSib->getObjId();
						
						$aLinks[] = $this->getSelExistLink( $aParams );
						
					} else {
						
						// get link to create item for the given language
						$aParams[ 'title' ] = $oLang->getTitle();
						$aParams[ 'lgroup_id' ] = $iLangGroupId;
						$aParams[ 'lang_id' ] = $oLang->getId();
						
						$aLinks[] = $this->getSelNonExistLink( $aParams );
						
					}
				}
				
				return $aLinks;
			}
		}
		
		return FALSE;
	}
	
	//
	public function getSelCurrLink( $aParams ) {
		return $aParams[ 'title' ];
	}
	
	// pretty useless, should be implemented by sub-class
	public function getSelExistLink( $aParams ) {
		return '<a href="#">' . $aParams[ 'title' ] . '</a>';
	}
	
	// pretty useless, should be implemented by sub-class
	public function getSelNonExistLink( $aParams ) {
		return '<a href="#">' . $aParams[ 'title' ] . '</a>';
	}
	
	
	
		
	
	
	
	//// page display
	
	//
	public function columnTitle() {
		?>
		<th scope="col" class="manage-column column-code">Code</th>
		<th scope="col" class="manage-column column-is-default">Is Default?</th>
		<th scope="col" class="manage-column column-date-created">Date Created</th>
		<th scope="col" class="manage-column column-date-created">Date Modified</th>
		<?php
	}
	
	//
	public function columnValue( $oEntity ) {
		?>
		<td class="column-title"><?php $oEntity->echoSlug(); ?></td>
		<td class="column-title"><?php echo ( $oEntity->getIsDefault() ) ? 'Yes' : 'No'; ?></td>
		<td class="date column-date-created"><abbr title="<?php $oEntity->echoDateTimeCreated( 'Y/m/d g:i A' ); ?>"><?php $oEntity->echoDateCreated( 'Y/m/d' ); ?></abbr></td>
		<td class="date column-date-created"><abbr title="<?php $oEntity->echoDateTimeModified( 'Y/m/d g:i A' ); ?>"><?php $oEntity->echoDateModified( 'Y/m/d' ); ?></abbr></td>
		<?php
	}
	
	
	
	//
	public function formFields() {
		
		?>
		<h3><?php echo $this->_sListingTitle; ?> Options</h3>
		<style type="text/css">
			
			.multi_row select.translation_key {
				width: 250px;
			}
			
			.multi_row textarea.translation_value {
				width: 350px;
			}
			
		</style>
		<table class="form-table">
			<?php $this->formDateFields(); ?>
			<tr>
				<th><label for="lang_title">Language</label></th>
				<td>
					<input id="lang_title" name="lang_title" type="text" class="regular-text" value="" />
				</td>
			</tr>
			<tr>
				<th><label for="lang_code">Slug</label></th>
				<td>
					<input id="lang_code" name="lang_code" type="text" class="regular-text" value="" />
				</td>
			</tr>
			<tr>
				<th><label for="lang_is_default">Is Default?</label></th>
				<td>
					<input id="lang_is_default" name="lang_is_default" type="checkbox" value="1" />
				</td>
			</tr>
			<?php $this->customFieldsMain(); ?>
		</table>
		<?php
		
		$this->parentEntityField();
		
	}
	
	
	// to be implemented by sub-class as needed
	public function customFields() { }

	


	//// crud methods
	
	// insert overrides
	
	//
	public function modifyInsertPostVals( $aValues ) {
		
		if ( !$aValues[ 'code' ] ) $aValues[ 'code' ] = $aValues[ 'title' ];
		$aValues[ 'code' ] =  Geko_Wp_Db::generateSlug(
			$aValues[ 'code' ], $this->getPrimaryTable(), 'code'
		);
		
		$sDateTime = Geko_Db_Mysql::getTimestamp();
		$aValues[ 'date_created' ] = $sDateTime;
		$aValues[ 'date_modified' ] = $sDateTime;
		
		if ( !isset( $aValues[ 'is_default' ] ) ) $aValues[ 'is_default' ] = 0;
		
		return $aValues;
		
	}
	
	//
	public function getInsertContinue( $aInsertData, $aParams ) {
		
		$bContinue = parent::getInsertContinue( $aInsertData, $aParams );
		
		list( $aValues, $aFormat ) = $aInsertData;
		
		//// do checks
		
		$sTitle = $aValues[ 'title' ];
		
		// check title
		if ( $bContinue && !$sTitle ) {
			$bContinue = FALSE;
			$this->triggerErrorMsg( 'm201' );										// empty title was given
		}
				
		return $bContinue;
		
	}
	
	
	
	// update overrides
	
	//
	public function modifyUpdatePostVals( $aValues, $oEntity ) {
		
		if ( !$aValues[ 'code' ] ) $aValues[ 'code' ] = $aValues[ 'title' ];
		if ( $aValues[ 'code' ] != $oEntity->getSlug() ) {
			$aValues[ 'code' ] = Geko_Wp_Db::generateSlug(
				$aValues[ 'code' ], $this->getPrimaryTable(), 'code'
			);
		}
			
		$sDateTime = Geko_Db_Mysql::getTimestamp();
		$aValues[ 'date_modified' ] = $sDateTime;
		
		if ( !isset( $aValues[ 'is_default' ] ) ) $aValues[ 'is_default' ] = 0;
		
		return $aValues;
	}
	
	
	
	// delete methods
	
	// hook method
	public function postDeleteAction( $aParams, $oEntity ) {
		
		global $wpdb;
		
		$wpdb->query( "
			DELETE FROM			$wpdb->geko_lang_group_members
			WHERE				lang_id NOT IN (
				SELECT				lang_id
				FROM				$wpdb->geko_languages
								)
		" );
		
		$wpdb->query( "
			DELETE FROM			$wpdb->geko_lang_groups
			WHERE				lgroup_id NOT IN (
				SELECT				lgroup_id
				FROM				$wpdb->geko_lang_group_members
								)
		" );
		
	}
	
	
	
	//
	public function doUpdateDefaultLang( $iLangId, $bIsDefault ) {
		
		if ( $bIsDefault ) {
			
			global $wpdb;
			
			$wpdb->query( $wpdb->prepare(
				"UPDATE $wpdb->geko_languages SET is_default = ( IF( lang_id = %d, 1, NULL ) )",
				$iLangId
			) );
			
		}
	}
	
	//
	public function cleanUpEmptyLangGroups( $sType, $sExcludeIdsSql, $iObjId ) {
		
		global $wpdb;
		
		// delete direct
		$wpdb->query( $sQuery = $wpdb->prepare(
			"	DELETE FROM				m
				USING					$wpdb->geko_lang_group_members m
				INNER JOIN				$wpdb->geko_lang_groups g
					ON					g.lgroup_id = m.lgroup_id
				WHERE					( g.type_id = %d ) AND 
										( m.obj_id = %d )
			",
			Geko_Wp_Options_MetaKey::getId( $sType ),
			$iObjId
		) );
		
		// delete non-existent
		$wpdb->query( $sQuery = $wpdb->prepare(
			"	DELETE FROM				m
				USING					$wpdb->geko_lang_group_members m
				INNER JOIN				$wpdb->geko_lang_groups g
					ON					g.lgroup_id = m.lgroup_id
				WHERE					( g.type_id = %d ) AND 
										( m.obj_id NOT IN( $sExcludeIdsSql ) )
			",
			Geko_Wp_Options_MetaKey::getId( $sType )
		) );
		
		// clean-up group
		$wpdb->query( "
			DELETE FROM				$wpdb->geko_lang_groups
			WHERE					lgroup_id NOT IN (
				SELECT					lgroup_id
				FROM					$wpdb->geko_lang_group_members
			)
		" );
		
	}
	
	
	
}


