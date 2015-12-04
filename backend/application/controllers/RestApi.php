<?php
defined('BASEPATH') OR exit('No direct script access allowed');
session_start(); // for token handling and avoiding CI core error

/**
 * Class RestApi
 */
class RestApi extends CI_Controller {
	private $apiSession;
	private $api;
	private $model;

	/**
	 * Init Spotify Web Api (oficial)
	 */
	public function __construct() {
		parent::__construct();

		require_once APPPATH . 'third_party/SpotifyWebApi/SpotifyWebAPI.php';
		require_once APPPATH . 'third_party/SpotifyWebApi/Session.php';
		require_once APPPATH . 'third_party/SpotifyWebApi/SpotifyWebAPIException.php';
		require_once APPPATH . 'third_party/SpotifyWebApi/Request.php';

		$this->apiSession = new SpotifyWebAPI\Session(
			$this->config->item('spotify_client_id'),
			$this->config->item('spotify_client_secret'),
			$this->config->item('spotify_redirect_uri')
		);

		$this->api = new SpotifyWebAPI\SpotifyWebAPI();
		$this->model = $this->load->model('RestApi_model', 'apimodel', true);
		$this->load->helper('cookie');
	}

	/**
	 * Index method, api handler - for Spotify Exchange token, not used to all but good for test the API
	 */
	public function index() {
		if (isset($_GET['code'])) {
			$this->apiSession->requestAccessToken($_GET['code']);
			$this->api->setAccessToken($this->apiSession->getAccessToken());
			$_SESSION['token'] = $_GET['code'];
			$cookie = array(
				'name'   => 'token',
				'value'  => $_GET['code'],
				'expire' => '86500',
				'domain' => '.cyrusmaus.hu',
				'path'   => '/',
				'prefix' => 'myprefix_',
				'secure' => TRUE
			);
			set_cookie($cookie);
		} else {
			$scopes = array(
				'scope' => array(
					'user-read-email',
					'user-library-modify',
				),
			);

			header('Location: ' . $this->apiSession->getAuthorizeUrl($scopes));
		}
	}

	/**
	 * The main aritst search method
	 * @param string $term the searchable term
	 */
	public function search($term = '') {
		$data = array();

		if (true == $this->checkToken()) {
			if (!isset($term) || empty($term)) {
				$term = $this->input->post('term');
			}
			$term = htmlspecialchars(strip_tags($term));

			if (!empty($term)) {
				$searchResult = $this->apimodel->searchInCachedItems($term);

				if (!is_array($searchResult)) {
					// check via API
					$searchResult = $this->api->search($term, 'artist');
					$searchResult = json_decode(json_encode($searchResult), true);
					$this->apimodel->storeResult($term, '0', $searchResult);
				} else {
					$temp = array();
					$temp['artists']['items'] = $this->artistDataHelper($searchResult);
					$searchResult = $temp;
				}

				$data['response'] = $searchResult;
			}
		}
		$this->load->view('simple_render', $data);
	}

	/**
	 * Retrieve track list for an artist, artist hash via ajax post
	 * @param string $artist_hash
	 */
	public function getTracksByArtist($artist_hash = '') {
		$data = array();

		if (true == $this->checkToken()) {
			if (!isset($artist_hash) || empty($artist_hash)) {
				$artist_hash = $this->input->post('artist_hash');
			}
			$artist_hash = htmlspecialchars(strip_tags($artist_hash));

			if (!empty($artist_hash)) {
				$searchResult = $this->apimodel->getArtistTracks($artist_hash);

				if (!is_array($searchResult)) {
					$searchResult = $this->api->getArtistTopTracks($artist_hash, array('country' => 'se'));
					$searchResult = json_decode(json_encode($searchResult), true);
					$this->apimodel->storeResult($artist_hash, '2', $searchResult);
				} else {
					$temp = array();
					$temp['tracks'] = $this->trackDataHelper($searchResult);
					$searchResult = $temp;
				}
				$data['response'] = $searchResult;
			}
		}

		$this->load->view('simple_render', $data);
	}

	/**
	 * Dummy playlist getter
	 */
	public function getPlaylist() {
		$data = array();
		if (true == $this->checkToken()) {
			$data['response'] = $this->apimodel->showStoredPlaylist();
		}

		$this->load->view('simple_render', $data);
	}

	/**
	 * Add a song/track to the playlist
	 * @param string $song_hash hash|ID of a song
	 * @return string|bool
	 */
	public function addTrackToPlaylist($song_hash = '') {
		$data = array();
		if (true == $this->checkToken()) {
			if (!isset($song_hash) || empty($song_hash)) {
				$song_hash = $this->input->post('song_hash');
			}
			$song_hash = htmlspecialchars(strip_tags($song_hash));

			if ($song_hash) {
				$data['response'] = $this->apimodel->addItemToPlaylist($song_hash);
			}
		}

		$this->load->view('simple_render', $data);
	}

	/**
	 * Remove an element of playlist
	 * @param string $song_id hash|ID of a song by playlist
	 * @return bool
	 */
	public function removeTrackFromPlaylist($song_id = '') {
		$data = array();

		if (true == $this->checkToken()) {
			if (!isset($song_id) || empty($song_id)) {
				$song_id = $this->input->post('song_id');
			}
			$song_id = htmlspecialchars(strip_tags($song_id));

			if ($song_id) {
				$data['response'] = $this->apimodel->removeItemFromPlaylist($song_id);
			}
		}
		$this->load->view('simple_render', $data);
	}

	/**
	 * Get/generate CRSF token
	 */
	public function token() {
		if (!$_SESSION || !isset($_SESSION['token'])) {
			$_SESSION['token'] = md5(base64_encode(openssl_random_pseudo_bytes(32))); //md5(mt_rand(1, 100000000000000));
		}
		$data['response'] = $_SESSION['token'];

		$this->load->view('simple_render', $data);
	}

	/**
	 * CRSF token check
	 * @return bool
	 */
	private function checkToken() {
		$result = false;
		$hash = $this->input->post('token');
		$hash = htmlspecialchars(strip_tags($hash));

		if ($hash == $_SESSION['token']) {
			$result = true;
		}

		return $result;
	}

	/**
	 * Check stored/cached results
	 * @param string $term
	 * @return array
	 */
	private function checkCache($term = '') {
		$result = array();

		if (!empty($term)) {
			$result = $this->apimodel->searchInCachedItems($term);
		}

		return $result;
	}

	/**
	 * Data structure helper for artist(s)
	 * @param string $data
	 * @return string
	 */
	private function artistDataHelper($data = '') {
		$temp = '';

		if (!empty($data)) {
			for ($i = 0; $i < count($data); $i++) {
				$temp[$i]['name'] = $data[$i]['song_artist'];
				$temp[$i]['id'] = $data[$i]['song_artist_hash'];
			}
			unset($data, $i);
		}

		return $temp;
	}

	/**
	 * Data structure helper for tracks/songs
	 * @param string $data
	 * @return string
	 */
	private function trackDataHelper($data = '') {
		$temp = '';

		if ($data) {
			for ($i = 0; $i < count($data); $i++) {
				$temp[$i]['name'] = $data[$i]['song_title'];
				$temp[$i]['id'] = $data[$i]['song_hash'];
				$temp[$i]['duration_ms'] = $data[$i]['song_duration'];
				$temp[$i]['album']['name'] = $data[$i]['song_album'];
				$temp[$i]['artists'][0]['name'] = $data[$i]['song_artist'];
				$temp[$i]['artists'][0]['id'] = $data[$i]['song_artist_hash'];
				$temp[$i]['myid'] = $data[$i]['id'];
			}
			unset($i, $data);
		}

		return $temp;
	}
}
