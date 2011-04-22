<?php
// todo
// add errors checking
// improve functionality
class CURL {
    private $ch;
    private $headers;
    private $customOptions = array( );

    private $defaultOptions = array(
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => 'CURL',
        CURLOPT_HEADER => false,
        CURLOPT_NOBODY => false,
    );

    public function __construct( ) {
        $cookiesFile = tempnam( '/tmp', 'CURL_' );
        $this->defaultOptions[ CURLOPT_COOKIEFILE ] = $cookiesFile;
        $this->defaultOptions[ CURLOPT_COOKIEJAR ] = $cookiesFile;
        $this->customOptions = $this->defaultOptions;
        $this->ch = curl_init( );
    }

    public function addOption( $name, $value ) {
        $this->customOptions[ $name ] = $value;
    }

    public function setLog( ) {
        $log = fopen( dirname( __FILE__ ) . '/CURL.log', 'a' );
        if( $log ) {
            fwrite( $log, str_repeat( '=', 80 ) . PHP_EOL );
            $this->addOption( CURLOPT_STDERR, $log );
            $this->addOption( CURLOPT_VERBOSE, true );
        }
    }

    public function put( $url = null ) {
        return $this->send( 'PUT', $url );
    }

    public function get( $url = null ) {
        return $this->send( 'GET', $url );
    }

    public function post( $url = null ) {
        return $this->send( 'POST', $url );
    }

    public function head( $url = null ) {
        return $this->send( 'HEAD', $url );
    }

    public function getHeadersInfo( $param = false ) {
        if( $param ) {
            return $this->getHeaderItem( 'info', $param );
        }
        else {
            return $this->headers[ 'info' ];
        }
    }

    public function getHeadersData( $param = false ) {
        if( $param ) {
            return $this->getHeaderItem( 'data', $param );
        }
        return $this->headers[ 'data' ];
    }

    private function send( $method, $url ) {
        if( $url === null ) {
            if( !isset( $this->customOptions[ CURLOPT_URL ] ) || empty( $this->customOptions[ CURLOPT_URL ] ) ) {
                exit( 'empty url' );
            }
        }
        $this->addOption( CURLOPT_CUSTOMREQUEST, $method );
        $this->addOption( CURLOPT_URL, $url );
        return $this->exec( );
    }

    private function setOptions( ) {
        if( $this->customOptions[ CURLOPT_HEADER ] ) {
            $this->addOption( CURLINFO_HEADER_OUT, true );
        }

        $options = $this->customOptions + $this->defaultOptions;
        curl_setopt_array( $this->ch, $options );
    }

    private function exec( ) {
        $this->setOptions( );
        $response = curl_exec( $this->ch );

        if( $this->customOptions[ CURLOPT_HEADER ] ) {
            $this->headers[ 'info' ] = curl_getinfo( $this->ch );
            $this->headers[ 'info' ][ 'request_header' ] = trim( $this->headers[ 'info' ][ 'request_header' ] );
            $this->processHeaders( $response );
        }

        curl_close( $this->ch );

        return $response;
    }

    private function processHeaders( &$data ) {
        $tmp = explode( "\r\n\r\n", $data, 2 );

        $this->headers[ 'info' ][ 'response_header' ] = $tmp[ 0 ];
        $data = $tmp[ 1 ];

        $tmp = explode( "\r\n", $this->headers[ 'info' ][ 'response_header' ] );
        $this->headers[ 'data' ][ 'Message' ] = $tmp[ 0 ];
        for( $i = 1, $size = count( $tmp ); $i < $size; ++$i ) {
            $string = explode( ': ', $tmp[ $i ], 2 );
            $this->headers[ 'data' ][ $string[ 0 ] ] = $string[ 1 ];
        }
    }

    private function getHeaderItem( $what, $name ) {
        if( isset( $this->headers[ $what ][ $name ] ) ) {
            return $this->headers[ $what ][ $name ];
        }
        else {
            return null;
        }
    }
}