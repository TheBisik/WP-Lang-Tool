/* global jQuery, thebisikLpiData */
jQuery( document ).ready( function ( $ ) {

	// -------------------------------------------------------------------------
	// Element references
	// -------------------------------------------------------------------------
	var $submitBtn    = $( '#thebisik-lpi-submit-btn' );
	var $bulkDeleteBtn = $( '#thebisik-lpi-bulk-delete-btn' );
	var $deleteAllBtn  = $( '#thebisik-lpi-delete-all-btn' );
	var $summaryDiv   = $( '#thebisik-lpi-progress-summary' );
	var $summaryText  = $summaryDiv.find( '.thebisik-lpi-progress-text' );

	// -------------------------------------------------------------------------
	// Select All checkbox handler (previously inline <script> in PHP template)
	// -------------------------------------------------------------------------
	var $selectAll = $( '#thebisik-lpi-select-all' );
	if ( $selectAll.length ) {
		$selectAll.on( 'change', function () {
			$( '.thebisik-lpi-checkbox:not(:disabled)' ).prop( 'checked', $selectAll.prop( 'checked' ) );
		} );
	}

	// -------------------------------------------------------------------------
	// UI helpers
	// -------------------------------------------------------------------------

	/**
	 * Disable all interactive UI elements during an async operation.
	 */
	function disableUI() {
		$( '.thebisik-lpi-checkbox, #thebisik-lpi-select-all' ).prop( 'disabled', true );
		$( '.thebisik-lpi-delete-btn' ).prop( 'disabled', true );
		$submitBtn.prop( 'disabled', true );
		$bulkDeleteBtn.prop( 'disabled', true );
		$deleteAllBtn.prop( 'disabled', true );
	}

	/**
	 * Re-enable UI elements after an async operation completes.
	 * The en_US checkbox remains hard-disabled as set in the HTML.
	 */
	function enableUI() {
		$( '.thebisik-lpi-checkbox:not([value="en_US"])' ).prop( 'disabled', false );
		$( '#thebisik-lpi-select-all' ).prop( 'disabled', false ).prop( 'checked', false );
		$( '.thebisik-lpi-delete-btn' ).prop( 'disabled', false );
		$submitBtn.prop( 'disabled', false );
		$bulkDeleteBtn.prop( 'disabled', false );
		$deleteAllBtn.prop( 'disabled', false );
	}

	/**
	 * Helper: wraps a jQuery AJAX call in a Promise.
	 *
	 * @param {string} action - The wp_ajax_{action} name.
	 * @param {string} locale - The locale string (e.g. 'pl_PL').
	 * @return {Promise}
	 */
	function doAjaxRequest( action, locale ) {
		return new Promise( function ( resolve, reject ) {
			$.ajax( {
				url: thebisikLpiData.ajaxUrl,
				type: 'POST',
				data: {
					action: action,
					nonce:  thebisikLpiData.nonce,
					locale: locale,
				},
				success: function ( response ) {
					resolve( response );
				},
				error: function ( jqXHR, textStatus, errorThrown ) {
					reject( errorThrown );
				},
			} );
		} );
	}

	// -------------------------------------------------------------------------
	// Bulk Install click handler
	// -------------------------------------------------------------------------
	$submitBtn.on( 'click', async function ( e ) {
		e.preventDefault();

		var selectedLocales = [];
		$( '.thebisik-lpi-checkbox:checked:not(:disabled)' ).each( function () {
			selectedLocales.push( $( this ).val() );
		} );

		if ( selectedLocales.length === 0 ) {
			alert( 'Please select at least one language to install.' );
			return;
		}

		disableUI();
		$summaryDiv.show();

		var total        = selectedLocales.length;
		var successCount = 0;
		var errorCount   = 0;

		for ( var i = 0; i < total; i++ ) {
			var locale      = selectedLocales[ i ];
			var $row        = $( 'tr[data-locale="' + locale + '"]' );
			var $statusText = $row.find( '.thebisik-lpi-status-text' );
			var $spinner    = $row.find( '.thebisik-lpi-spinner' );

			$summaryText.text( 'Installing ' + ( i + 1 ) + ' of ' + total + '...' );
			$statusText.text( thebisikLpiData.textInstalling ).css( 'color', '' );
			$spinner.addClass( 'is-active' );

			try {
				var response = await doAjaxRequest( 'thebisik_lpi_install_language', locale );

				$spinner.removeClass( 'is-active' );
				if ( response.success ) {
					successCount++;
					$statusText.html( thebisikLpiData.textInstalled ).css( 'color', '#46b450' );
					$row.find( '.thebisik-lpi-checkbox' ).prop( 'checked', false );
					if ( locale !== 'en_US' && $row.find( '.thebisik-lpi-delete-btn' ).length === 0 ) {
						$row.find( '.thebisik-lpi-actions-cell' ).html(
							'<button type="button" class="button button-link-delete thebisik-lpi-delete-btn" data-locale="' + locale + '">Delete</button>'
						);
					}
				} else {
					errorCount++;
					$statusText.html( thebisikLpiData.textError + ': ' + ( response.data ? response.data.message : '' ) ).css( 'color', '#d63638' );
				}
			} catch ( error ) {
				errorCount++;
				$spinner.removeClass( 'is-active' );
				$statusText.html( thebisikLpiData.textError ).css( 'color', '#d63638' );
			}
		}

		enableUI();
		$summaryText.text( 'Finished! Successfully installed: ' + successCount + ', Errors: ' + errorCount );
		$summaryDiv.find( '.spinner' ).removeClass( 'is-active' );
	} );

	// -------------------------------------------------------------------------
	// Bulk Delete click handler
	// -------------------------------------------------------------------------
	$bulkDeleteBtn.on( 'click', async function ( e ) {
		e.preventDefault();

		var selectedLocales = [];
		$( '.thebisik-lpi-checkbox:checked:not(:disabled)' ).each( function () {
			selectedLocales.push( $( this ).val() );
		} );

		if ( selectedLocales.length === 0 ) {
			alert( 'Please select at least one language to delete.' );
			return;
		}

		if ( ! confirm( thebisikLpiData.textBulkDeleteConfirm ) ) {
			return;
		}

		disableUI();
		$summaryDiv.show();

		var total        = selectedLocales.length;
		var successCount = 0;
		var errorCount   = 0;

		for ( var i = 0; i < total; i++ ) {
			var locale      = selectedLocales[ i ];
			var $row        = $( 'tr[data-locale="' + locale + '"]' );
			var $statusText = $row.find( '.thebisik-lpi-status-text' );
			var $spinner    = $row.find( '.thebisik-lpi-spinner' );

			$summaryText.text( 'Deleting ' + ( i + 1 ) + ' of ' + total + '...' );
			$statusText.text( thebisikLpiData.textDeleting ).css( 'color', '' );
			$spinner.addClass( 'is-active' );

			try {
				var response = await doAjaxRequest( 'thebisik_lpi_uninstall_language', locale );

				$spinner.removeClass( 'is-active' );
				if ( response.success ) {
					successCount++;
					$statusText.html( thebisikLpiData.textNotInstalled ).css( 'color', '' );
					$row.find( '.thebisik-lpi-delete-btn' ).remove();
					$row.find( '.thebisik-lpi-checkbox' ).prop( 'checked', false );
				} else {
					errorCount++;
					$statusText.html( thebisikLpiData.textError + ': ' + ( response.data ? response.data.message : '' ) ).css( 'color', '#d63638' );
				}
			} catch ( error ) {
				errorCount++;
				$spinner.removeClass( 'is-active' );
				$statusText.html( thebisikLpiData.textError ).css( 'color', '#d63638' );
			}
		}

		enableUI();
		$summaryText.text( 'Finished! Successfully deleted: ' + successCount + ', Errors: ' + errorCount );
		$summaryDiv.find( '.spinner' ).removeClass( 'is-active' );
	} );

	// -------------------------------------------------------------------------
	// Single Delete button click handler (event delegation for dynamically added buttons)
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.thebisik-lpi-delete-btn', async function ( e ) {
		e.preventDefault();

		if ( ! confirm( thebisikLpiData.textDeleteConfirm ) ) {
			return;
		}

		var $btn        = $( this );
		var locale      = $btn.data( 'locale' );
		var $row        = $btn.closest( 'tr' );
		var $statusText = $row.find( '.thebisik-lpi-status-text' );
		var $spinner    = $row.find( '.thebisik-lpi-spinner' );

		disableUI();
		$statusText.text( thebisikLpiData.textDeleting ).css( 'color', '' );
		$spinner.addClass( 'is-active' );

		try {
			var response = await doAjaxRequest( 'thebisik_lpi_uninstall_language', locale );

			$spinner.removeClass( 'is-active' );
			if ( response.success ) {
				$statusText.html( thebisikLpiData.textNotInstalled ).css( 'color', '' );
				$btn.remove();
				$row.find( '.thebisik-lpi-checkbox' ).prop( 'checked', false );
			} else {
				alert( thebisikLpiData.textError + ': ' + ( response.data ? response.data.message : '' ) );
				$statusText.html( thebisikLpiData.textInstalled ).css( 'color', '#46b450' );
			}
		} catch ( error ) {
			$spinner.removeClass( 'is-active' );
			alert( thebisikLpiData.textError );
		}

		enableUI();
	} );

	// -------------------------------------------------------------------------
	// Delete All click handler
	// -------------------------------------------------------------------------
	$deleteAllBtn.on( 'click', async function ( e ) {
		e.preventDefault();

		var $deleteButtons = $( '.thebisik-lpi-delete-btn' );
		if ( $deleteButtons.length === 0 ) {
			alert( 'No languages are currently installed (or only English is installed).' );
			return;
		}

		if ( ! confirm( thebisikLpiData.textDeleteAllConfirm ) ) {
			return;
		}

		disableUI();
		$summaryDiv.show();

		var total        = $deleteButtons.length;
		var successCount = 0;
		var errorCount   = 0;

		for ( var i = 0; i < total; i++ ) {
			var $btn        = $( $deleteButtons[ i ] );
			var locale      = $btn.data( 'locale' );
			var $row        = $btn.closest( 'tr' );
			var $statusText = $row.find( '.thebisik-lpi-status-text' );
			var $spinner    = $row.find( '.thebisik-lpi-spinner' );

			$summaryText.text( 'Deleting ' + ( i + 1 ) + ' of ' + total + '...' );
			$statusText.text( thebisikLpiData.textDeleting ).css( 'color', '' );
			$spinner.addClass( 'is-active' );

			try {
				var response = await doAjaxRequest( 'thebisik_lpi_uninstall_language', locale );

				$spinner.removeClass( 'is-active' );
				if ( response.success ) {
					successCount++;
					$statusText.html( thebisikLpiData.textNotInstalled ).css( 'color', '' );
					$btn.remove();
					$row.find( '.thebisik-lpi-checkbox' ).prop( 'checked', false );
				} else {
					errorCount++;
					$statusText.html( thebisikLpiData.textError + ': ' + ( response.data ? response.data.message : '' ) ).css( 'color', '#d63638' );
				}
			} catch ( error ) {
				errorCount++;
				$spinner.removeClass( 'is-active' );
				$statusText.html( thebisikLpiData.textError ).css( 'color', '#d63638' );
			}
		}

		enableUI();
		$summaryText.text( 'Finished deletion! Successfully deleted: ' + successCount + ', Errors: ' + errorCount );
		$summaryDiv.find( '.spinner' ).removeClass( 'is-active' );
	} );

} );
