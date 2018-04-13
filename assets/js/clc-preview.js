( function( $ ) {

	// Live edits
    /* Templates */
    wp.customize( 'clc-options[templates]', function( value ) {
      value.bind( function( to ) {

        if ( '01' === to ) {
          $( 'body' ).addClass( 'ml-half-screen' );
        } else {
          $( 'body' ).removeClass( 'ml-half-screen' );
        }
      } );
    } );

    $( '.clc-preview-event' ).click( function( evt ){
      wp.customize.preview.send( 'clc-focus-section', $( this ).data( 'section' ) );
    });

} )( jQuery );