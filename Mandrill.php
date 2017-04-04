<?php
	
namespace Spectral\MandrillBundle;
use Symfony\Bridge\Monolog\Logger;
use Spectral\MandrillBundle\Message;
use Spectral\MandrillBundle\Exceptions;

/**
 * Mandrill
 *
 * Copyright (c) 2017 Spectral, USA
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author: Wade Wece <pogeybait4883@gmail.com>
 * @copyright: 2017 Spectral, USA
 */

class Mandrill
{
	/**
     * Mandrill Api Key
     *
     * @var string
     */
	protected $apiKey;
	
	/**
     * CURL connection
     *
     * @var \Curl Object
     */
	protected $ch;
	
	protected $root;
	
	protected $debug;
	
	public static $error_map = array(
        "ValidationError" => "Mandrill_ValidationError",
        "Invalid_Key" => "Mandrill_Invalid_Key",
        "PaymentRequired" => "Mandrill_PaymentRequired",
        "Unknown_Subaccount" => "Mandrill_Unknown_Subaccount",
        "Unknown_Template" => "Mandrill_Unknown_Template",
        "ServiceUnavailable" => "Mandrill_ServiceUnavailable",
        "Unknown_Message" => "Mandrill_Unknown_Message",
        "Invalid_Tag_Name" => "Mandrill_Invalid_Tag_Name",
        "Invalid_Reject" => "Mandrill_Invalid_Reject",
        "Unknown_Sender" => "Mandrill_Unknown_Sender",
        "Unknown_Url" => "Mandrill_Unknown_Url",
        "Unknown_TrackingDomain" => "Mandrill_Unknown_TrackingDomain",
        "Invalid_Template" => "Mandrill_Invalid_Template",
        "Unknown_Webhook" => "Mandrill_Unknown_Webhook",
        "Unknown_InboundDomain" => "Mandrill_Unknown_InboundDomain",
        "Unknown_InboundRoute" => "Mandrill_Unknown_InboundRoute",
        "Unknown_Export" => "Mandrill_Unknown_Export",
        "IP_ProvisionLimit" => "Mandrill_IP_ProvisionLimit",
        "Unknown_Pool" => "Mandrill_Unknown_Pool",
        "NoSendingHistory" => "Mandrill_NoSendingHistory",
        "PoorReputation" => "Mandrill_PoorReputation",
        "Unknown_IP" => "Mandrill_Unknown_IP",
        "Invalid_EmptyDefaultPool" => "Mandrill_Invalid_EmptyDefaultPool",
        "Invalid_DeleteDefaultPool" => "Mandrill_Invalid_DeleteDefaultPool",
        "Invalid_DeleteNonEmptyPool" => "Mandrill_Invalid_DeleteNonEmptyPool",
        "Invalid_CustomDNS" => "Mandrill_Invalid_CustomDNS",
        "Invalid_CustomDNSPending" => "Mandrill_Invalid_CustomDNSPending",
        "Metadata_FieldLimit" => "Mandrill_Metadata_FieldLimit",
        "Unknown_MetadataField" => "Mandrill_Unknown_MetadataField"
    );
	
	
	public function __construct( Logger $logger, $apikey=null, $debug=false )
	{
		$this->apiKey = $apikey;
		$this->logger = $logger;
		$this->message = new Message( $this );
		$this->root = 'https://mandrillapp.com/api/1.0';
		$this->debug = $debug;
		
		$this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_USERAGENT, 'Mandrill-PHP/1.0.55');
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_HEADER, false);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 600);
	}
	
	/**
     * Main method for making each API call to Mandrill
     *
     * @return array
     */
	public function call( $url, $params )
	{
        $params['key'] = $this->apiKey;
        $params = json_encode( $params );
        $ch = $this->ch;

        curl_setopt( $ch, CURLOPT_URL, $this->root . $url . '.json' );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' ) );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
        curl_setopt( $ch, CURLOPT_VERBOSE, $this->debug );

        $start = microtime( true );
        $this->logger->debug( 'Call to ' . $this->root . $url . '.json: ' . $params );
        if($this->debug)
        {
            $curl_buffer = fopen( 'php://memory', 'w+' );
            curl_setopt( $ch, CURLOPT_STDERR, $curl_buffer );
        }

        $response_body = curl_exec( $ch );
        $info = curl_getinfo( $ch );
        $time = microtime( true ) - $start;
        if( $this->debug )
        {
            rewind( $curl_buffer );
            $this->logger->debug( stream_get_contents( $curl_buffer ) );
            fclose( $curl_buffer );
        }
        $this->logger->debug( 'Completed in ' . number_format( $time * 1000, 2 ) . 'ms' );
        $this->logger->debug( 'Got response: ' . $response_body );

        if( curl_error( $ch) )
        {
            throw new Mandrill_HttpError( "API call to $url failed: " . curl_error( $ch ) );
        }
        $result = json_decode( $response_body, true );
        if( $result === null ) throw new Mandrill_Error( 'We were unable to decode the JSON response from the Mandrill API: ' . $response_body );
        
        if( floor( $info['http_code'] / 100 ) >= 4 )
        {
            throw $this->castError( $result );
        }

        return $result;
    }
    
    /**
     * Cast Errors
     *
     * @return Mandrill_Error
     */
    private function castError($result) {
        if( $result['status'] !== 'error' || !$result['name'] ) throw new Mandrill_Error( 'We received an unexpected error: ' . json_encode( $result ) );

        $class = ( isset( self::$error_map[$result['name']] ) ) ? self::$error_map[$result['name']] : 'Mandrill_Error';
        return new $class( $result['message'], $result['code'] );
    }
	
	
	/**
     * Get Api Key
     *
     * @return string
     */
	public function getApiKey()
	{
		return $this->apiKey;
	}
}