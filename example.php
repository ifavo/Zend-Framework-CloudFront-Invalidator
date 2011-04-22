<?
	// add the following action to your controller
	public function sampleAction () {

		// your AWS credentials
		$accessKey = '';
		$secretKey = '';
		$distributionId = '';
		
		// create cloudfront object
		$cloudFront = new favo_Service_Amazon_CloudFront($accessKey, $secretKey);
		$cloudFront->setId($distributionId);
		
		// set logger if you want detailed logging
		$logger = new Zend_Log();
		$writer = new Zend_Log_Writer_Stream('php://output');
		$logger->addWriter ($writer);
		$cloudFront->setLogger($logger);
		
		// define files to invalidate
		$files = array ( 'sample.jpg', 'cache.jpg', 'dummy.txt' );
		
		// invalidate them
		if ( $cloudFront->invalidate ($files) ) {
			// success
		}
		else {
			// failure
		}
		
		
		// disable view rendering for this example
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();

	}
