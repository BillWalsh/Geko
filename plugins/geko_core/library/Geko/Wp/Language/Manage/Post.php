<?php

//
class Geko_Wp_Language_Manage_Post extends Geko_Wp_Language_Manage
{
	protected $_aSubOptions = array();
	
	protected $sFilterLangCode = '';
	
	
	
	//
	public function affix() {
		
		Geko_Wp_Language_Manage_Post_QueryHooks::register();		
		return $this;
	}
	
	//
	public function affixAdmin() {
		
		// post		
		// add_action( 'submitpost_box', array( $this, 'addPostSelector' ) );
		// add_action( 'submitpage_box', array( $this, 'addPostSelector' ) );
		add_action( 'delete_post', array( $this, 'deletePost' ) );		
		add_action( 'save_post', array( $this, 'savePost' ) );
		add_action( 'admin_init', array( $this, 'addPostMetabox' ) );
		
		add_filter( 'manage_posts_columns', array( $this, 'addCustomColumn' ) );
		add_filter( 'manage_pages_columns', array( $this, 'addCustomColumn' ) );
		
		add_action( 'manage_posts_custom_column', array( $this, 'addCustomColumnValues' ), 10, 2 );
		add_action( 'manage_pages_custom_column', array( $this, 'addCustomColumnValues' ), 10, 2 );
		
		add_action( 'admin_init_post_list', array( $this, 'modifyRequest' ) );
		add_action( 'admin_init_page_list', array( $this, 'modifyRequest' ) );
		
		// page
		add_action( 'admin_init_page_add', array( $this, 'filterPageAdminPages' ) );
		add_action( 'admin_init_page_edit', array( $this, 'filterPageAdminPages' ) );
		
		return $this;
	}
	
	//
	public function modifyRequest() {
		add_filter( 'request', array( $this, 'addQueryVars' ) );
	}
	
	
	//
	public function attachPage() { }
	
	
	////// actions and filters
	
	
	//// details
	
	//
	public function addPostMetabox() {
		
		if ( function_exists( 'add_meta_box' ) ) {
			add_meta_box( 'geko-language', __('Language', 'geko-expiry_textdomain'), array( $this, 'addPostSelector' ), 'post', 'side' );
			add_meta_box( 'geko-language', __('Language', 'geko-expiry_textdomain'), array( $this, 'addPostSelector' ), 'page', 'side' );
		} else {
			add_action( 'dbx_post_advanced', array( $this, 'addPostSelector' ) );
			add_action( 'dbx_page_advanced', array( $this, 'addPostSelector' ) );		
		}	
	}
	
	//
	public function addPostSelector() {
		
		global $post;
		
		$aVer = Geko_Wp::version();

		$iLangId = intval( $_GET[ 'post_lang_id' ] );
		$iLangGroupId = intval( $_GET[ 'post_lgroup_id' ] );
		$iPostId = intval( $_GET[ 'post' ] );
		$bNewSibling = ( !$iLangGroupId || !$iLangId ) ? FALSE : TRUE;
		$sType = ( intval( $aVer[0] ) >= 3 ) ?
			'post' :
			( ( 'page' == $post->post_type ) ? 'page' : 'post' )
		;
		
		$aLinks = $this->getSelectorLinks(
			$iLangGroupId,
			$iLangId,
			$iPostId,
			'post',
			array( 'type' => $sType )
		);
		
		// determine if a language is assigned to post
		if ( $aLinks ) {
			
			echo implode( ' | ', $aLinks );
			
			if ( $bNewSibling ) {			
				$this->echoLanguageHidden( $iLangGroupId, $iLangId );
			}
			
		} else {
			$this->echoLanguageSelect();
		}
		
	}
	
	//
	public function getSelExistLink( $aParams ) {
		return sprintf(
			'<a href="%s/wp-admin/%s.php?action=edit&post=%d">%s</a>',
			get_bloginfo('url'),
			$aParams[ 'type' ],
			$aParams[ 'obj_id' ],
			$aParams[ 'title' ]
		);
	}
	
	//
	public function getSelNonExistLink( $aParams ) {
		
		global $post;
		
		$aVer = Geko_Wp::version();
		
		return sprintf(
			'<a href="%s/wp-admin/%s-new.php?post_lgroup_id=%d&post_lang_id=%d%s">%s</a>',
			get_bloginfo('url'),
			$aParams[ 'type' ],
			$aParams[ 'lgroup_id' ],
			$aParams[ 'lang_id' ],
			( ( intval( $aVer[0] ) >= 3 ) && ( 'page' == $post->post_type ) ) ? '&post_type=page' : '',
			$aParams[ 'title' ]
		);
	}
	
	
	
	
	//// listing
	
	//
	public function addCustomColumn( $aDefaults ) {
		
		// cb, title, author, categories, tags, comments, date
		
		$aReorder = array(
			'cb' => $aDefaults[ 'cb' ],
			'title' => $aDefaults[ 'title' ],
			'lang' => 'Language'
		);
		
		unset( $aDefaults[ 'cb' ] );
		unset( $aDefaults[ 'title' ] );
		
		return array_merge( $aReorder, $aDefaults );
	}
	
	//
	public function addCustomColumnValues( $sColumnName, $iId ) {
		
		if ( 'lang' == $sColumnName ) {
		
			global $post;
			static $oUrl = NULL;
			
			if ( $post->lang_code ):
			
				if ( NULL === $oUrl ) $oUrl = new Geko_Uri();
				$oUrl->setVar( 'lang', $post->lang_code );
				
				?><a href="<?php echo strval( $oUrl ); ?>"><?php echo $post->lang_title; ?></a><?php
			else:
				?>No Language<?php
			endif;
			
		}
		
	}
	
	//
	public function addQueryVars( $aQueryVars ) {
		
		$aQueryVars[ 'add_lang_fields' ] = 1;
		
		$oResolver = Geko_Wp_Language_Resolver::getInstance();
		
		if ( $sLangCode = $oResolver->getCurLang() ) {
			$aQueryVars[ $oResolver->getLangQueryVar() ] = $sLangCode;
		}
		
		return $aQueryVars;
	}
	
	
	//// commit
	
	//
	public function savePost( $iPostId, $aVals = NULL ) {
		
		global $wpdb;
		
		// set vals
		if ( NULL === $aVals ) {
			
			// use $_POST array for values, minding the prefix
			$aVals = array();
			
			// list of recognized fields
			$aFields = array( 'geko_lang_id', 'geko_lgroup_id' );
			foreach ( $aFields as $sField ) {
				if ( isset( $_POST[ $sField ] ) ) {
					$aVals[ $sField ] = stripslashes( $_POST[ $sField ] );
				}
			}
			
		}
		
		// save post
		
		$oPost = get_post( $iPostId );
		
		if (
			( 'inherit' != $oPost->post_status ) && 
			( $iLangId = intval( $aVals[ 'geko_lang_id' ] ) )
		) {
			
			if ( !$iLangGroupId = intval( $aVals[ 'geko_lgroup_id' ] ) ) {
				
				// create a lang group
				$wpdb->insert(
					$wpdb->geko_lang_groups,
					array( 'type_id' => Geko_Wp_Options_MetaKey::getId( 'post' ) )
				);
				
				// create a lang group member
				$iLangGroupId = $wpdb->get_var( 'SELECT LAST_INSERT_ID()' );
			}
			
			$wpdb->insert(
				$wpdb->geko_lang_group_members,
				array(
					'lgroup_id' => $iLangGroupId,
					'obj_id' => $iPostId,
					'lang_id' => $iLangId
				)
			);
			
		}
		
		return TRUE;
	}
	
	// clean-up
	public function deletePost( $iPostId ) {
		
		global $wpdb;
		
		$sSql = "SELECT ID FROM $wpdb->posts";
		
		$this->cleanUpEmptyLangGroups( 'post', $sSql, $iPostId );
		
	}
	
	
	
	//// filter pages by language
	
	//
	public function filterPageAdminPages() {
		
		$sLangCode = '';
		
		if ( $iPostId = $_REQUEST[ 'post' ] ) {
			$oObj = Geko_Wp_Language_Member::getOne( array( 'obj_id' => $iPostId, 'type' => 'post' ), FALSE );
			if ( $oObj->isValid() ) $sLangCode = $oObj->getLangCode();
		} elseif ( $iLangId = $_REQUEST[ 'post_lang_id' ] ) {
			$sLangCode = $this->getLanguage( $iLangId )->getSlug();
		}
		
		if ( $sLangCode ) $this->sFilterLangCode = $sLangCode;
		
		add_filter( 'get_pages', array( $this, 'pageFilterQuery' ), 10, 2 );
	}
	
	// works with the 'get_pages' filter
	public function pageFilterQuery( $aPages, $aArgs ) {
		
		$this->getLanguages();		// initialize lang array
		
		if ( $this->sFilterLangCode ) $aArgs[ 'lang' ] = $this->sFilterLangCode;
		
		if ( $sLangCode = $aArgs[ 'lang' ] ) {
			
			global $wpdb;
			
			$bLangIsDefault = ( self::$oDefaultLang->getSlug() == $sLangCode );
			
			$aPageIds = $wpdb->get_col( "
				SELECT			m.obj_id
				FROM			$wpdb->geko_lang_group_members m
				LEFT JOIN		$wpdb->geko_lang_groups g
					ON			g.lgroup_id = m.lgroup_id
				LEFT JOIN		$wpdb->geko_languages l
					ON			l.lang_id = m.lang_id
				WHERE			( g.type_id = ( SELECT mkey_id FROM $wpdb->geko_meta_key WHERE meta_key = 'post' ) ) AND 
								( l.code " . ( $bLangIsDefault ? '!' : '' ) . "= '$sLangCode' )
			" );
			
			$aFiltered = array();
			
			foreach ( $aPages as $oPage ) {
				$bInArray = in_array( $oPage->ID, $aPageIds );
				if (
					( $bLangIsDefault && !$bInArray ) || 
					( !$bLangIsDefault && $bInArray )
				) {
					$aFiltered[] = $oPage;
				}
			}
			
			$aPages = $aFiltered;
			
		}
		
		return $aPages;	
	}

	
}


