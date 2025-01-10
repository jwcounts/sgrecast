<?php
require 'vendor/autoload.php';
use Dotenv\Dotenv;

class HPM_SGRecast {
	protected $options;
	protected $jwt;

	public function __construct() {
		$dotenv = Dotenv::createImmutable( __DIR__ );
		$dotenv->load();
		$this->options = [
			'username' => $_ENV['RECAST_USERNAME'],
			'password' => $_ENV['RECAST_PASSWORD'],
			'api_root' => $_ENV['RECAST_API_ROOT'],
			'client_id' => $_ENV['RECAST_CLIENT_ID'],
			'client_secret' => $_ENV['RECAST_CLIENT_SECRET']
		];

		if ( file_exists( __DIR__ . '/auth/token.json' ) ) {
			$this->jwt = $this->token_check( json_decode( file_get_contents( __DIR__ . '/auth/token.json' ), true ) );
		} else {
			$this->jwt = $this->get_token();
		}
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
			CURLOPT_HEADER => false
		]);
		$response = curl_exec( $curl );
		curl_close( $curl );
		return $response;
	}

	public function token_check( $token ) {
		if ( !empty( $token['expires_in'] ) && !empty( $token['generated_at'] ) ) {
			if ( ( $token['expires_in'] + $token['generated_at'] ) > time() ) {
				return $token;
			} else {
				return $this->refresh_token();
			}
		} else {
			return $this->get_token();
		}
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
		$response_j = json_decode( $response, true );
		$response_j['generated_at'] = time();
		file_put_contents( 'auth/token.json', json_encode( $response_j ) );
		return $response_j;
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
		$response_j = json_decode( $response, true );
		$response_j['generated_at'] = time();
		file_put_contents( 'auth/token.json', json_encode( $response_j ) );
		return $response_j;
	}

	public function list_podcasts() {
		$opts = $this->options;
		$token = $this->jwt;
		return $this->request(
			$opts['api_root'] . '/api/v1/sgrecast/podcasts/',
			[],
			[ 'Content-Type: multipart/form-data', 'Authorization: Bearer ' . $token['access_token'] ],
			'GET'
		);
	}

	public function get_podcasts_feeds() {
		$opts = $this->options;
		$token = $this->jwt;
		return $this->request(
			$opts['api_root'] . '/api/v1/sgrecast/podcasts/feeds/',
			[],
			[ 'Content-Type: multipart/form-data', 'Authorization: Bearer ' . $token['access_token'] ],
			'GET'
		);
	}

	public function get_podcast( $id ) {
		$opts = $this->options;
		$token = $this->jwt;
		return $this->request(
			$opts['api_root'] . '/api/v1/sgrecast/podcasts/feeds/view/' . $id,
			[],
			[ 'Content-Type: multipart/form-data', 'Authorization: Bearer ' . $token['access_token'] ],
			'GET'
		);
	}

	public function get_podcast_episodes( $id ) {
		$opts = $this->options;
		$token = $this->jwt;
		return $this->request(
			$opts['api_root'] . '/api/v1/sgrecast/podcasts/feeds/episodes/' . $id,
			[],
			[ 'Content-Type: multipart/form-data', 'Authorization: Bearer ' . $token['access_token'] ],
			'GET'
		);
	}

	public function get_list( $endpoint, $page = '1', $length = 25, $sort = 'createdAt', $order = 'desc' ) {
		$opts = $this->options;
		$token = $this->jwt;
		$query = [
			'page' => $page,
			'length' => $length,
			'sort' => $sort,
			'order' => $order
		];
		return $this->request(
			$opts['api_root'] . '/api/v1/' . $endpoint . '/?' . http_build_query( $query ),
			[],
			[ 'Content-Type: multipart/form-data', 'Authorization: Bearer ' . $token['access_token'] ],
			'GET'
		);
	}

	public function get_content_single( $id ) {
		$opts = $this->options;
		$token = $this->jwt;
		return $this->request(
			$opts['api_root'] . '/api/v1/sgrecast/content/view/' . $id,
			[],
			[ 'Content-Type: multipart/form-data', 'Authorization: Bearer ' . $token['access_token'] ],
			'GET'
		);
	}
	public function upload_podcast_episode( $podcast_id, $file ) {
		$opts = $this->options;
		$token = $this->jwt;
		$body = [
			"group" => "3",
			"files[]" => "@" . $file,
			"description" => "Example Newscast",
			"podcasts[]" => $podcast_id,
			"restart_broadcasts" => "1",
			"name" => "SGRecast Test"
		];
		return $this->request(
			$opts['api_root'] . '/api/v1/sgrecast/podcasts/add/episode',
			$body,
			[ 'Content-Type: application/form-data', 'Authorization: Bearer ' . $token['access_token'], 'Accepts: application/json' ],
			'POST'
		);
	}
	public function add_content( $file ) {
		$opts = $this->options;
		$token = $this->jwt;
		$body = [
			"group" => "6",
			"files" => new \CurlFile($file, 'audio/mpeg', 'newscast-test.mp3'),
			"description" => "Example Newscast",
			"name" => "SGRecast Test"
		];
		return $this->request(
			$opts['api_root'] . '/api/v1/sgrecast/podcasts/add/episode',
			$body,
			[ 'Content-Type: multipart/form-data', 'Authorization: Bearer ' . $token['access_token'] ],
			'POST'
		);
	}

	public function schedule_import() {
		$opts = $this->options;
		$token = $this->jwt;
		$body = [
			[
				"name" => "Newscast Ingest Test",
				"description" => "Here is an example file to be imported into your SGrecast system.",
				"url" => "https://cdn.houstonpublicmedia.org/assets/newscast.mp3",
				"pub_date" => "2025-01-09 16:54:00",
				"custom_id" => "hpm-newscast-test"
			]
		];
		return $this->request(
			$opts['api_root'] . '/api/upload',
			[ 'file' => json_encode( $body ) ],
			[ 'Content-Type: application/json', 'Authorization: Bearer ' . $token['access_token'] ],
			'POST'
		);
	}

	public function output( $response ) {
		echo( $response );
	}
}
global $sgrecast;
$sgrecast = new HPM_SGRecast();
$sgrecast->output(
//	//$sgrecast->get_podcast( 5 )
//	//$sgrecast->list_podcasts()
	$sgrecast->upload_podcast_episode( 11, __DIR__ . '/newscast.mp3' )
);
