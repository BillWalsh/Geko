<?php

//
class Geko_Wp_Generic_Location_Manage extends Geko_Wp_Location_Manage
{
	protected $_sGenericType = 'generic';
	protected $_sSectionLabel = 'Address';
	
	
	
	
	// return a prefix
	public function getPrefix() {
		return $this->_sGenericType;
	}
	
	//
	public function addAdmin() {
		
		parent::addAdmin();
		
		add_action( $this->_sInstanceClass . '_form', array( $this, 'outputForm' ) );
		add_action( $this->_sInstanceClass . '_fields', array( $this, 'echoSetupFields' ) );
		
		if ( $this->_sCurrentPage == $this->_sSubOptionParentClass ) {
			add_action( 'admin_geko_generic_add', array( $this, 'insertType' ) );
			add_action( 'admin_geko_generic_edit', array( $this, 'updateType' ), 10, 2 );
			add_action( 'admin_geko_generic_delete', array( $this, 'deleteType' ) );
		}
		
		return $this;
	}
	
	
	//
	public function initEntities() {
		
		$iGenericId = intval( $_GET[ 'generic_id' ] );		// Hacky!
		
		if ( $iGenericId ) {
			parent::initEntities( array(
				'object_id' => $iGenericId,
				'object_type' => 'generic'
			) );
		}
		
		return $this;
	}

	
	
	//// form processing/injection methods

	// plug into the add category form
	public function setupFields() {
		
		$aParts = $this->extractParts();
		$sFields = '';
		
		foreach ( $aParts as $aPart ) {
			
			$sLabel = Geko_String::sw( '<label for="%s$1">%s$0</label>', $aPart[ 'label' ], $aPart[ 'name' ] );
			$sFieldGroup = Geko_String::sw( '%s<br />', $aPart[ 'field_group' ] );
			$sRowId = Geko_String::sw( ' id="%s"', $aPart[ 'row_id' ] );
			
			$sFields .= '
				<tr class="form-field"' . $sRowId . '>
					<th>' . $sLabel . '</th>
					<td>
						' . $sFieldGroup . '
						' . Geko_String::sw( '<span class="description">%s</span>', $aPart[ 'description' ] ) . '
					</td>
				</tr>
			';
		}
		
		return $sFields;
	}
	
	// Hacky
	public function echoSetupFields() {
		echo $this->setupFields();
	}
	
	
	//
	public function extractPart( $aPart, $oPq ) {
		
		$aAddId = array(
			$this->getPrefixForDoc() . 'province_id',
			$this->getPrefixForDoc() . 'country_id',
			$this->getPrefixForDoc() . 'continent_id'
		);
		
		if ( in_array( $aPart[ 'name' ], $aAddId ) ) {
			$aPart[ 'row_id' ] = $aPart[ 'name' ] . '-row';
		}
		
		return $aPart;
	}
	
	//
	public function changeDoc( $oDoc ) {
		$oDoc[ 'input.text' ]->addClass( 'regular-text' );
		$oDoc[ 'input, select, textarea' ]->attr( '_skip_save', 'yes' );
	}
	
	//
	public function outputForm() {
		?>
		<h3><?php echo $this->_sSectionLabel; ?></h3>
		<?php $this->preFormFields(); ?>
		<table class="form-table">
			<?php echo $this->setupFields(); ?>
		</table>
		<?php
	}
	
	//
	public function insertType( $mGeneric ) {
		
		if ( is_object( $mGeneric ) ) {
			$iGenericId = $mGeneric->getId();
		} else {
			$iGenericId = $mGeneric;
		}
		
		$aParams = array(
			'object_id' => $iGenericId,
			'object_type' => 'generic'
		);
		
		$this->save( $aParams );
	}
	
	//
	public function updateType( $mOldGeneric, $mNewGeneric ) {
		
		// $mOldGeneric unused for now

		if ( is_object( $mNewGeneric ) ) {
			$iGenericId = $mNewGeneric->getId();
		} else {
			$iGenericId = $mNewGeneric;
		}
		
		$aParams = array(
			'object_id' => $iGenericId,
			'object_type' => 'generic'
		);
		
		$this->save( $aParams, 'update' );
	}
	
	//
	public function deleteType( $mGeneric ) {
		
		global $wpdb;

		if ( is_object( $mGeneric ) ) {
			$iGenericId = $mGeneric->getId();
		} else {
			$iGenericId = $mGeneric;
		}
		
		$wpdb->query( $wpdb->prepare(
			"	DELETE FROM			$wpdb->geko_location_address
				WHERE				( object_id = %d ) AND 
									( objtype_id = %d )
			",
			$iGenericId,
			Geko_Wp_Options_MetaKey::getId( 'generic' )
		) );
	}
	
}

