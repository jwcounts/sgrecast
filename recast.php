<?php
require 'vendor/autoload.php';
use Dotenv\Dotenv;

class HPM_SGRecast {
	protected $options;
	protected $jwt;

	public function __construct() {
		// define( 'HPM_SGRECAST_PLUGIN_DIR', plugin_dir_path(__FILE__) );
		// define( 'HPM_SGRECAST_PLUGIN_URL', plugin_dir_url(__FILE__) );
		// add_action( 'plugins_loaded', [ $this, 'init' ] );
		// add_action( 'init', [ $this, 'create_type' ] );
		$dotenv = Dotenv::createImmutable( __DIR__ );
		$dotenv->load();
		$this->options = [
			'username' => $_ENV['RECAST_USERNAME'],
			'password' => $_ENV['RECAST_PASSWORD'],
			'api_root' => $_ENV['RECAST_API_ROOT'],
			'client_id' => $_ENV['RECAST_CLIENT_ID'],
			'client_secret' => $_ENV['RECAST_CLIENT_SECRET']
		];

		if ( file_exists( 'auth/token.json' ) ) :
			$this->jwt = json_decode( file_get_contents( 'auth/token.json' ), true );
		else :
			$this->jwt = $this->get_token();
		endif;
	}

	public function request( $url, $postfields, $headers, $type ) {
		$curl = curl_init();
		curl_setopt_array( $curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => $type,
			CURLOPT_POSTFIELDS => $postfields,
			CURLOPT_HTTPHEADER => $headers,
		]);
		$response = curl_exec( $curl );
		curl_close( $curl );
		return $response;
	}

	public function get_token() {
		$opts = $this->options;
		$response = $this->request(
			$opts['api_root'] . '/oauth/token',
			[
				'grant_type' => 'password',
				'client_id' => $opts['client_id'],
				'client_secret' => $opts['client_secret'],
				'username' => $opts['username'],
				'password' => $opts['password'],
				'scope' => '*'
			],
			[ 'Content-Type: multipart/form-data' ],
			'POST'
		);
		file_put_contents( 'auth/token.json', $response );
		return json_decode( $response, true );
	}

	public function refresh_token() {
		$token = $this->jwt;
		$opts = $this->options;
		$response = $this->request(
			$opts['api_root'] . '/oauth/token',
			[
				'grant_type' => 'password',
				'client_id' => $opts['client_id'],
				'client_secret' => $opts['client_secret'],
				'refresh_token' => $token['refresh_token'],
				'scope' => '*'
			],
			[ 'Content-Type: multipart/form-data' ],
			'POST'
		);
		file_put_contents( 'auth/token.json', $response );
		$this->jwt = json_decode( $response, true );
	}

	public function list_podcasts() {
		$opts = $this->options;
		$token = $this->jwt;
		$response = $this->request(
			$opts['api_root'] . '/api/v1/sgrecast/podcasts/',
			[],
			[ 'Content-Type: multipart/form-data', 'Authorization: Bearer ' . $token['access_token'] ],
			'GET'
		);
		print_r( json_decode( $response, true ) );
	}

	public function get_podcasts_feeds() {
		$opts = $this->options;
		$token = $this->jwt;
		$response = $this->request(
			$opts['api_root'] . '/api/v1/sgrecast/podcasts/feeds/',
			[],
			[ 'Content-Type: multipart/form-data', 'Authorization: Bearer ' . $token['access_token'] ],
			'GET'
		);
		print_r( json_decode( $response, true ) );
	}

	public function get_podcast( $id ) {
		$opts = $this->options;
		$token = $this->jwt;
		$response = $this->request(
			$opts['api_root'] . '/api/v1/sgrecast/podcasts/feeds/view/' . $id,
			[],
			[ 'Content-Type: multipart/form-data', 'Authorization: Bearer ' . $token['access_token'] ],
			'GET'
		);
		print_r( json_decode( $response, true ) );
	}

	public function get_podcast_episodes( $id ) {
		$opts = $this->options;
		$token = $this->jwt;
		$response = $this->request(
			$opts['api_root'] . '/api/v1/sgrecast/podcasts/feeds/episodes/' . $id,
			[],
			[ 'Content-Type: multipart/form-data', 'Authorization: Bearer ' . $token['access_token'] ],
			'GET'
		);
		print_r( json_decode( $response, true ) );
	}
}
global $sgrecast;
$sgrecast = new HPM_SGRecast();
$sgrecast->get_podcast( 5 );