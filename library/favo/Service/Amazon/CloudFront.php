<?php
/**
 * CloudFront class to handle invalidation using of objects
 * be aware that there is a limit of 1000 objects and a maximum of 3 parallel invalidation queues
 *
 * @category   favo
 * @package    favo_Service
 * @subpackage Amazon
 * @copyright  Copyright (c) 2011 Mario Micklisch
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

class favo_Service_Amazon_CloudFront extends Zend_Service_Amazon_Abstract {
	
	private $serviceUrl = "https://cloudfront.amazonaws.com/";
	private $responseBody;
	private $responseCode;
	private $distributionId;
	private $logger = null;
	private $limit = 1000;
	
	
	/**
	 * Constructs a CloudFront object and assigns required account values
	 * @param $accessKey		{String} AWS access key
	 * @param $secretKey		{String} AWS secret key
	 */
	function __construct($accessKey=null, $secretKey=null){
		parent::__construct($accessKey, $secretKey);
	}
	
	
	/**
	 *	sets the service url
	 *	@param $id	{string} the cloud fronts service url
	 */
	public function setServiceUrl($url) {
		$this->serviceUrl = $url;
	}
	
	/**
	 *	sets the distribution id
	 *	@param $id	{string} the cloud fronts distribution id
	 */
	public function setId ($id) {
		$this->distributionId = $id;
	}
	
	/**
	 * sets an optional logger object
	 * @param $logger 	{Object} instance of an Zend_Log
	 */ 
	public function setLogger (Zend_Log $logger) {
		$this->logger = $logger;
	}
	
	/**
	 * Invalidates object with passed key on CloudFront
	 * @param $key 	{String|Array} Key of object to be invalidated, or set of such keys
	 */   
	function invalidate($keys){
		// validate distribution id
		if ( !$this->distributionId ) {
			throw new Exception("no distribution id provided");
		}
	
		if (!is_array($keys)){
			$keys = array($keys);
		}

		// validate size of itemes
		if ( count($keys) > $this->limit ) {
			throw new Exception("you are trying to invalidate too many objects at once, please limit your object count to 1000");
		}

		$date       = gmdate("D, d M Y G:i:s T");
		$requestUrl = $this->serviceUrl."2010-08-01/distribution/" . $this->distributionId . "/invalidation";
		// assemble request body
		$body  = "<InvalidationBatch>";
		foreach($keys as $key){
			$key   = (preg_match("/^\//", $key)) ? $key : "/" . $key;
			$body .= "<Path>".$key."</Path>";
		}
		$callerReference = md5(join('', $keys)).time();
		$body .= "<CallerReference>".$callerReference."</CallerReference>";
		$body .= "</InvalidationBatch>";

		// make and send request
		$httpClient = new Zend_Http_Client(
											$requestUrl,
												array(
											    	'maxredirects' => 5,
													'timeout'      => 10
												)
										);
        $signature = base64_encode(Zend_Crypt_Hmac::compute($this->_getSecretKey(), 'sha1', utf8_encode($date), Zend_Crypt_Hmac::BINARY));
		$response = $httpClient->setHeaders('Date', $date)
								->setHeaders('Authorization', 'AWS '.$this->_getAccessKey().':'.$signature)
								->setHeaders('Content-Type', 'text/xml')
								->setRawData($body)
								->request('POST');

		$this->responseCode = $response->getStatus();
		if ($this->logger) {
			$logDetail = array();
			array_push($logDetail, "CloudFront: Invalidating Object: $key");
			array_push($logDetail, $requestUrl);
			array_push($logDetail, "body: $body");
			array_push($logDetail, "CallerReference: $callerReference");
			array_push($logDetail, "response string: " . $response->getBody());
			array_push($logDetail, "");
			array_push($logDetail, "response code: " . $this->responseCode);
			array_push($logDetail, "");
			$this->logger->log(join("\r\n", $logDetail), Zend_Log::INFO);
		}
		return ($this->responseCode === 201);
	}
}