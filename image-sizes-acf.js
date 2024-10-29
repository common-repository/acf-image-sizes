jQuery( function( $ ) {
	var perc_inc = 0;
	var perc_loop = 0;
	var imgSetArr = [];
	var imgIndex = 0;
	var spaceSaved = 0;
	var filesRemoved = 0;
	var filesCreated = 0;
	var cleanOption = false;
	var generateOption = false;
	var imageDeleteOption = true;
	var imageCreateOption = true;
	var cancelStatus = false;
	var processes = 0;

	$( '#image-sizes-acf-cleanup' ).on( 'click', startCleanup );
	$( '#image-sizes-acf-cancel' ).on( 'click', stopCleanup );

	function stopCleanup() {

		if ( confirm( image_sizes_acf.text.cleanupConfirm ) ) {
			cancelStatus = true;
			ajaxManager.stop();
			$( '#image-sizes-acf-cancel' ).prop( 'disabled', true );
			$( '#image-sizes-acf-cleanup' ).prop( 'disabled', false );
			imageSizesACF_cleanup();
			$( '#optimisation-progress-container' ).hide();
		}
	}

	function startCleanup() {


		cancelStatus = false;
		imageSizesACF_cleanup();
		imageDeleteOption = $( '#image-sizes-acf-delete' ).is( ':checked' );
		imageCreateOption = $( '#image-sizes-acf-create' ).is( ':checked' );

		processes += imageDeleteOption ? 1 : 0;
		processes += imageCreateOption ? 1 : 0;

		if ( processes === 0 ) {

			// INITIALIZE CLEANUP & KILL THE SCRIPT
			imageSizesACF_cleanup();
			$( '#optimisation-progress-container' ).hide();
			return;

		}

		$( '#optimisation-progress-container' ).show();

		$( '#image-sizes-acf-cleanup' ).prop( 'disabled', true );
		$( '#image-sizes-acf-cancel' ).prop( 'disabled', false );
		$( '#image-sizes-acf-status' ).text( image_sizes_acf.text.fetchingFields );
		// Trigger ACF field descovery
		imageSizesACF_ACFDiscovery();

	}


	function imageSizesACF_ACFDiscovery() {
		$.ajax({

			type : 'POST',
			url : image_sizes_acf.adminUrl,
			dataType : 'json',
			data : { action : 'cleanup_acf_discovery_ajax', imageSizesACF_nonce : image_sizes_acf.nonce },
			success : function ( res ) {

				if ( res.error ) {
					$( '#image-sizes-acf-error-text' ).text( res.error );
					$( '#image-sizes-acf-error-text-container' ).show();
					return;
				}

				$( '#image-sizes-acf-fields-found' ).text( Object.keys( res ).length );
				$( '#image-sizes-acf-status' ).text( image_sizes_acf.text.fetchingImages );

				$( '.images-found-out' ).show();
				imageSizesACF_ImageDiscovery( res );
			}

		});

	}

	function imageSizesACF_ImageDiscovery( fieldSet ) {
		var imageList = null;
		$.ajax({

			type : 'POST',
			url : image_sizes_acf.adminUrl,
			dataType : 'json',
			data : { action : 'cleanup_image_discovery_ajax', imageSizesACF_nonce : image_sizes_acf.nonce, field_set : fieldSet },
			success : function ( res ) {

				if ( res.error ) {
					$( '#image-sizes-acf-error-text' ).text( res.error );
					$( '#image-sizes-acf-error-text-container' ).show();
					return;
				}

				$( '#image-size-acf-images-found' ).text( Object.keys( res ).length );
				$( '#progress-bar-perc' ).css( { width: '0%' } );


				imageList = res;
				for( var i in res ) {

					if ( "undefined" === typeof imgSetArr[ imgIndex ] ) {

						imgSetArr[ imgIndex ] = [];

					}

					if ( imgSetArr[ imgIndex ].length === 10 ) {

						imgIndex++;
						imgSetArr[ imgIndex ] = [];

					}

					imgSetArr[ imgIndex ].push( {
						image : i,
						sizes : res[i].sizes,
						name : res[i].name
					} );

				}

				perc_inc = ( 100 / imgSetArr.length ) / processes;
				perc_loop = 0;

				$( '#image-sizes-acf-total-batches' ).text( imgSetArr.length );

				for ( var item in imgSetArr ) {

					if ( imageDeleteOption ) {

						imageSizesACF_addDeleteProcess( imgSetArr[ item ], parseInt( item ) );

					}

					if ( imageCreateOption ) {

						imageSizesACF_addCreateProcess( imgSetArr[ item ], parseInt( item ) );

					}


				}

			}

		})

	}

	function imageSizesACF_addDeleteProcess( imgSet, batchCount ) {

		ajaxManager.addReq( {
			type : 'POST',
			url : image_sizes_acf.adminUrl,
			dataType : 'json',
			data : { action : 'cleanup_acf_images_ajax', imageSizesACF_nonce : image_sizes_acf.nonce, imageSet : imgSet },
			beforeSend : function ( imgSet, batchCount ) {
				if ( !cancelStatus ) {
					return function() {
						$( '#image-sizes-acf-status' ).text( image_sizes_acf.text.cleaningImages );
						$( '#image-sizes-acf-current-batch' ).text( batchCount + 1 );
						$( '#image-sizes-acf-image-list' ).html('');
						for( image in imgSet ) {
							$( '#image-sizes-acf-image-list' ).append(
								$('<p>').text( imgSet[ image ].name )
							)
						}
					}
				}

			}( imgSet, batchCount ),
			success : function ( res ) {
				if ( ! cancelStatus ) {

					if ( res.error ) {
						$( '#image-sizes-acf-error-text' ).text( res.error );
						$( '#image-sizes-acf-error-text-container' ).show();
						return;
					}

					perc_loop++;
					prog_perc = perc_inc * perc_loop;
					$( '#progress-bar-perc' ).css( { width: prog_perc + '%' } );
					$( '#image-sizes-acf-percent-number' ).text( prog_perc.toFixed( 0 ) );
					spaceSaved += ( res.space_saved );
					spaceSavedOut = spaceSaved;
					if ( spaceSavedOut < 0 ) {
						spaceSavedOut = -spaceSaved;
						$( '#image-sizes-acf-space-text' ).text( image_sizes_acf.text.spaceUsed );
					} else {
						$( '#image-sizes-acf-space-text' ).text( image_sizes_acf.text.spaceSaved );
					}
					filesRemoved += ( res.deleted_count );

					$( '#image-sizes-acf-space-saved' ).text( bytesToSize( spaceSavedOut ) );
					$( '#image-sizes-acf-images-deleted' ).text( filesRemoved );

					if ( 100 == prog_perc.toFixed( 0 ) && ! imageCreateOption ) {
						$( '#image-sizes-acf-status' ).text( image_sizes_acf.text.finished );
						$( '#image-sizes-acf-cleanup' ).prop( 'disabled', false );
						$( '#image-sizes-acf-cancel' ).prop( 'disabled', true );
					}

				}

			}
		});

	}

	function imageSizesACF_addCreateProcess( imgSet, batchCount ) {

		ajaxManager.addReq( {
			type : 'POST',
			url : image_sizes_acf.adminUrl,
			dataType : 'json',
			data : { action : 'generate_acf_images_ajax', imageSizesACF_nonce : image_sizes_acf.nonce, imageSet : imgSet },
			beforeSend : function ( imgSet, batchCount ) {
				if ( ! cancelStatus ) {

					return function() {
						$( '#image-sizes-acf-status' ).text( image_sizes_acf.text.creatingImages );
						$( '#image-sizes-acf-current-batch' ).text( batchCount + 1 );
						$( '#image-sizes-acf-image-list' ).html('');
						for( image in imgSet ) {
							$( '#image-sizes-acf-image-list' ).append(
								$('<p>').text( imgSet[ image ].name )
							)
						}
					}

				}

			}( imgSet, batchCount ),
			success : function ( res ) {

				if ( ! cancelStatus ) {

					if ( res.error ) {
						$( '#image-sizes-acf-error-text' ).text( res.error );
						$( '#image-sizes-acf-error-text-container' ).show();
						return;
					}

					perc_loop++;
					prog_perc = perc_inc * perc_loop;
					$( '#progress-bar-perc' ).css( { width: prog_perc + '%' } );
					$( '#image-sizes-acf-percent-number' ).text( prog_perc.toFixed( 0 ) );
					filesCreated += ( res.images_added );
					spaceSaved -= ( res.space_used );
					spaceSavedOut = spaceSaved;
					if ( spaceSavedOut < 0 ) {
						spaceSavedOut = -spaceSaved;
						$( '#image-sizes-acf-space-text' ).text( image_sizes_acf.text.spaceUsed );
					} else {
						$( '#image-sizes-acf-space-text' ).text( image_sizes_acf.text.spaceSaved );
					}
					$( '#image-sizes-acf-space-saved' ).text( bytesToSize( spaceSavedOut ) );
					$( '#image-sizes-acf-images-created' ).text( filesCreated );

					if ( 100 == prog_perc.toFixed( 0 ) ) {
						$( '#image-sizes-acf-status' ).text( image_sizes_acf.text.finished );
						$( '#image-sizes-acf-cleanup' ).prop( 'disabled', false );
						$( '#image-sizes-acf-cancel' ).prop( 'disabled', true );
					}

				}

			}
		});

	}

	function imageSizesACF_cleanup() {
		$( '#image-sizes-acf-status' ).text( image_sizes_acf.text.standingBy );
		$( '#image-sizes-acf-fields-found' ).text( 0 );
		$( '#image-size-acf-images-found' ).text( 0 );
		$( '#image-sizes-acf-status' ).text( 0 );
		$( '#image-sizes-acf-total-batches' ).text( 0 );
		$( '#image-sizes-acf-current-batch' ).text( 0 );
		$( '#image-sizes-acf-image-list' ).html('');
		$( '#image-sizes-acf-space-saved' ).text( '0 Bytes' );
		$( '#image-sizes-acf-images-deleted' ).text( 0 );
		$( '#image-sizes-acf-images-created' ).text( 0 );
		$( '#progress-bar-perc' ).css( { width: '0%' } );
		$( '#image-sizes-acf-percent-number' ).text( 0 );

		perc_inc = 0;
		perc_loop = 0;
		imgSetArr = [];
		imgIndex = 0;
		spaceSaved = 0;
		filesRemoved = 0;
		filesCreated = 0;
		cleanOption = false;
		generateOption = false;
		imageDeleteOption = true;
		imageCreateOption = true;
		processes = 0;

	}

	function bytesToSize(bytes) {
		var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
		if (bytes == 0) return '0 Byte';
		var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
		return (bytes / Math.pow(1024, i)).toFixed( 2 ) + ' ' + sizes[i];
	};

})


var ajaxManager = (function() {
     var requests = [], running = false;

     return {
        addReq:  function(opt) {

            requests.push(opt);
            this.run();

        },
        removeReq:  function(opt) {

            if( jQuery.inArray(opt, requests) > -1 ) {

                requests.splice(jQuery.inArray(opt, requests), 1);

            }

        },
        run: function() {

            if( running === true ) { return; }

            var self = this,
                oriSuc;
            if( requests.length ) {

                running = true;
                oriSuc = requests[0].complete;

                requests[0].complete = function() {

                     if( typeof( oriSuc ) === 'function' ) oriSuc();
                    requests.shift();
                    running = false;
                    self.run()

                };

                jQuery.ajax(requests[0]);

            }
        },
        stop:  function() {
            requests = [];
            running = false;

        }
     };
}());