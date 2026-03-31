/* global jQuery, wpltData */
jQuery( document ).ready( function( $ ) {

	// Elements
	var $submitBtn = $( '#wplt-submit-btn' );
	var $bulkDeleteBtn = $( '#wplt-bulk-delete-btn' );
	var $deleteAllBtn = $( '#wplt-delete-all-btn' );
	var $summaryDiv = $( '#wplt-progress-summary' );
	var $summaryText = $summaryDiv.find( '.wplt-progress-text' );
	
	function disableUI() {
		$( '.wplt-checkbox, #wplt-select-all' ).prop( 'disabled', true );
		$( '.wplt-delete-btn' ).prop( 'disabled', true );
		$submitBtn.prop( 'disabled', true );
		$bulkDeleteBtn.prop( 'disabled', true );
		$deleteAllBtn.prop( 'disabled', true );
	}

	function enableUI() {
		// Only re-enable non-disabled ones (like en_US checkbox is hard-disabled in HTML)
		$( '.wplt-checkbox:not([value="en_US"])' ).prop( 'disabled', false );
		$( '#wplt-select-all' ).prop( 'disabled', false ).prop( 'checked', false );
		$( '.wplt-delete-btn' ).prop( 'disabled', false );
		$submitBtn.prop( 'disabled', false );
		$bulkDeleteBtn.prop( 'disabled', false );
		$deleteAllBtn.prop( 'disabled', false );
	}

	/**
	 * Bulk Install Click Handler
	 */
	$submitBtn.on( 'click', async function( e ) {
		e.preventDefault();
		
		var selectedLocales = [];
		$( '.wplt-checkbox:checked:not(:disabled)' ).each( function() {
			selectedLocales.push( $( this ).val() );
		} );

		if ( selectedLocales.length === 0 ) {
			alert( 'Please select at least one language to install.' );
			return;
		}

		disableUI();
		$summaryDiv.show();
		
		var total = selectedLocales.length;
		var successCount = 0;
		var errorCount = 0;

		for ( var i = 0; i < total; i++ ) {
			var locale = selectedLocales[i];
			var $row = $( 'tr[data-locale="' + locale + '"]' );
			var $statusText = $row.find( '.wplt-status-text' );
			var $spinner = $row.find( '.wplt-spinner' );

			$summaryText.text( 'Installing ' + (i + 1) + ' of ' + total + '...' );
			$statusText.text( wpltData.textInstalling ).css( 'color', '' );
			$spinner.addClass( 'is-active' );

			try {
				var response = await doAjaxRequest( 'wplt_install_language', locale );
				
				$spinner.removeClass( 'is-active' );
				if ( response.success ) {
					successCount++;
					$statusText.html( wpltData.textInstalled ).css( 'color', '#46b450' );
					$row.find( '.wplt-checkbox' ).prop( 'checked', false );
					// Show delete button
					if ( locale !== 'en_US' && $row.find('.wplt-delete-btn').length === 0 ) {
						$row.find('.wplt-actions-cell').html( '<button type="button" class="button button-link-delete wplt-delete-btn" data-locale="' + locale + '">Delete</button>' );
					}
				} else {
					errorCount++;
					$statusText.html( wpltData.textError + ': ' + (response.data ? response.data.message : '') ).css( 'color', '#d63638' );
				}
			} catch ( error ) {
				errorCount++;
				$spinner.removeClass( 'is-active' );
				$statusText.html( wpltData.textError ).css( 'color', '#d63638' );
			}
		}

		enableUI();
		$summaryText.text( 'Finished! Successfully installed: ' + successCount + ', Errors: ' + errorCount );
		$summaryDiv.find( '.spinner' ).removeClass( 'is-active' );
	});

	/**
	 * Bulk Delete Click Handler
	 */
	$bulkDeleteBtn.on( 'click', async function( e ) {
		e.preventDefault();
		
		var selectedLocales = [];
		$( '.wplt-checkbox:checked:not(:disabled)' ).each( function() {
			selectedLocales.push( $( this ).val() );
		} );

		if ( selectedLocales.length === 0 ) {
			alert( 'Please select at least one language to delete.' );
			return;
		}

		if ( ! confirm( wpltData.textBulkDeleteConfirm ) ) {
			return;
		}

		disableUI();
		$summaryDiv.show();
		
		var total = selectedLocales.length;
		var successCount = 0;
		var errorCount = 0;

		for ( var i = 0; i < total; i++ ) {
			var locale = selectedLocales[i];
			var $row = $( 'tr[data-locale="' + locale + '"]' );
			var $statusText = $row.find( '.wplt-status-text' );
			var $spinner = $row.find( '.wplt-spinner' );

			$summaryText.text( 'Deleting ' + (i + 1) + ' of ' + total + '...' );
			$statusText.text( wpltData.textDeleting ).css( 'color', '' );
			$spinner.addClass( 'is-active' );

			try {
				var response = await doAjaxRequest( 'wplt_uninstall_language', locale );
				
				$spinner.removeClass( 'is-active' );
				if ( response.success ) {
					successCount++;
					$statusText.html( wpltData.textNotInstalled ).css( 'color', '' );
					$row.find( '.wplt-delete-btn' ).remove();
					$row.find( '.wplt-checkbox' ).prop( 'checked', false );
				} else {
					errorCount++;
					$statusText.html( wpltData.textError + ': ' + (response.data ? response.data.message : '') ).css( 'color', '#d63638' );
				}
			} catch ( error ) {
				errorCount++;
				$spinner.removeClass( 'is-active' );
				$statusText.html( wpltData.textError ).css( 'color', '#d63638' );
			}
		}

		enableUI();
		$summaryText.text( 'Finished! Successfully deleted: ' + successCount + ', Errors: ' + errorCount );
		$summaryDiv.find( '.spinner' ).removeClass( 'is-active' );
	});

	/**
	 * Delete Button Click Handler (Single)
	 */
	$( document ).on( 'click', '.wplt-delete-btn', async function( e ) {
		e.preventDefault();
		
		if ( ! confirm( wpltData.textDeleteConfirm ) ) {
			return;
		}

		var $btn = $( this );
		var locale = $btn.data( 'locale' );
		var $row = $btn.closest( 'tr' );
		var $statusText = $row.find( '.wplt-status-text' );
		var $spinner = $row.find( '.wplt-spinner' );

		disableUI();
		$statusText.text( wpltData.textDeleting ).css( 'color', '' );
		$spinner.addClass( 'is-active' );

		try {
			var response = await doAjaxRequest( 'wplt_uninstall_language', locale );
			
			$spinner.removeClass( 'is-active' );
			if ( response.success ) {
				$statusText.html( wpltData.textNotInstalled ).css( 'color', '' );
				$btn.remove();
				$row.find( '.wplt-checkbox' ).prop( 'checked', false );
			} else {
				alert( wpltData.textError + ': ' + (response.data ? response.data.message : '') );
				$statusText.html( wpltData.textInstalled ).css( 'color', '#46b450' );
			}
		} catch ( error ) {
			$spinner.removeClass( 'is-active' );
			alert( wpltData.textError );
		}
		
		enableUI();
	});

	/**
	 * Delete All Button Click Handler
	 */
	$deleteAllBtn.on( 'click', async function( e ) {
		e.preventDefault();

		var deleteButtons = $( '.wplt-delete-btn' );
		if ( deleteButtons.length === 0 ) {
			alert( 'No languages are currently installed (or only English is installed).' );
			return;
		}

		if ( ! confirm( wpltData.textDeleteAllConfirm ) ) {
			return;
		}

		disableUI();
		$summaryDiv.show();
		
		var total = deleteButtons.length;
		var successCount = 0;
		var errorCount = 0;

		$summaryText.text( 'Deleting 1 of ' + total + '...' );

		for ( var i = 0; i < total; i++ ) {
			var $btn = $( deleteButtons[i] );
			var locale = $btn.data( 'locale' );
			var $row = $btn.closest( 'tr' );
			var $statusText = $row.find( '.wplt-status-text' );
			var $spinner = $row.find( '.wplt-spinner' );

			$summaryText.text( 'Deleting ' + (i + 1) + ' of ' + total + '...' );
			$statusText.text( wpltData.textDeleting ).css( 'color', '' );
			$spinner.addClass( 'is-active' );

			try {
				var response = await doAjaxRequest( 'wplt_uninstall_language', locale );
				
				$spinner.removeClass( 'is-active' );
				if ( response.success ) {
					successCount++;
					$statusText.html( wpltData.textNotInstalled ).css( 'color', '' );
					$btn.remove();
					$row.find( '.wplt-checkbox' ).prop( 'checked', false );
				} else {
					errorCount++;
					$statusText.html( wpltData.textError + ': ' + (response.data ? response.data.message : '') ).css( 'color', '#d63638' );
				}
			} catch ( error ) {
				errorCount++;
				$spinner.removeClass( 'is-active' );
				$statusText.html( wpltData.textError ).css( 'color', '#d63638' );
			}
		}

		enableUI();
		$summaryText.text( 'Finished deletion! Successfully deleted: ' + successCount + ', Errors: ' + errorCount );
		$summaryDiv.find( '.spinner' ).removeClass( 'is-active' );
	});


	/**
	 * Helper function for AJAX wrap as Promise
	 */
	function doAjaxRequest( action, locale ) {
		return new Promise( function( resolve, reject ) {
			$.ajax({
				url: wpltData.ajaxUrl,
				type: 'POST',
				data: {
					action: action,
					nonce: wpltData.nonce,
					locale: locale
				},
				success: function( response ) {
					resolve( response );
				},
				error: function( jqXHR, textStatus, errorThrown ) {
					reject( errorThrown );
				}
			});
		});
	}

});
