<?php
namespace PepperjamAPI;

abstract class AbstractRequest {
	const BASE_URL = 'https://api.pepperjamnetwork.com';

	const GET = 'GET';
	const POST = 'POST';
	const PUT = 'PUT';
	const DELETE = 'DELETE';

	public $env;

	protected $config = array(
		'version' => '20120402',
		'max_retries' => 3
	);

	private $logger;

	/**
	 * @param array $config
	 * @param string $env
	 * @throws \Exception
	 */
	public function __construct(array $config = array(), $logger = null) {
		// check that the necessary keys are set
		if(!isset($config['api-key'])) {
			throw new \Exception('Configuration missing access-token');
		}
		if(!isset($config['version'])) {
			throw new \Exception('Configuration missing version');
		}
	
		// Apply some defaults.
		$this->config = array_merge_recursive($this->config, $config);
		
		$this->logger = $logger;
	}

	public function get($parameters = array()) {
		return $this->request(self::GET, $parameters);
	}

	public function post($parameters, $data) {
		return $this->request(self::POST, $parameters, $data);
	}

	public function put($parameters, $data) {
		return $this->request(self::PUT, $parameters, $data);
	}

	public function delete($parameters) {
		return $this->request(self::DELETE, $parameters);
	}

	private function request($method, $parameters = array(), $data = array()) {
		$result = false;

		$url = self::BASE_URL.'/'.$this->config['version'].'/'.$this->getPath();

		$curl = curl_init();

		$options = array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $url.'?'.http_build_query(
				array_merge(
					$parameters,
					array(
						'apiKey' => $this->config['api-key'],
						'format' => 'json'
					)
				)
			),
			CURLOPT_HEADER => 1,
			CURLOPT_RETURNTRANSFER => 1,
			CURLINFO_HEADER_OUT => true
		);

		$httpHeaders = array();

		if($method == self::GET) {
			$this->log('GET '.$options[CURLOPT_URL]);
			$responseClass = 'PepperjamAPI\GetResponse';
		} else if($method == self::PUT) {
			$options[CURLOPT_POST] = 1;
			$options[CURLOPT_POSTFIELDS] = $this->getPostFields($data);
			$options[CURLOPT_CUSTOMREQUEST] = 'PUT';
			$this->log('PUT '.$options[CURLOPT_URL]);
			$this->log(json_encode($data));
			$responseClass = 'PepperjamAPI\PutResponse';
		} else if($method == self::POST) {
			$options[CURLOPT_POST] = 1;
			$options[CURLOPT_POSTFIELDS] = $this->getPostFields($data);
			$this->log('POST '.$options[CURLOPT_URL]);
			$this->log(json_encode($data));
			$responseClass = 'PepperjamAPI\PostResponse';
		} else if($method == self::DELETE) {
			$options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
			$this->log('DELETE '.$options[CURLOPT_URL]);
			$responseClass = 'PepperjamAPI\DeleteResponse';
		}

		$options[CURLOPT_HTTPHEADER] = $this->getHeaders($options[CURLOPT_URL], $method, $httpHeaders);

		curl_setopt_array($curl, $options);

		$response = curl_exec($curl);
		$information = curl_getinfo($curl);
		
		$this->log($information['request_header']);

		if($response !== false) {
			$this->log($response);
			
			$headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

			$headers = substr($response, 0, $headerSize);
			$body = substr($response, $headerSize);

			$result = new $responseClass($headers, $body);

			unset($headerSize, $headers, $body);
		} else {
			$this->log(curl_error($curl));
		}
		
		curl_close($curl);

		return $result;
	}
	
	protected function getPostFields($data) {
		return http_build_query($data);
	}
	
	protected function getPostContentType() {
		// return 'Content-Type: application/json';
	}

	protected abstract function getPath();

	public function getHeaders($url, $method, $headers = array()) {
		$headers[] = 'Accept: application/json';

		return $headers;
	}

	private function log($message) {
		if($this->logger) $this->logger->info($message);
	}
}