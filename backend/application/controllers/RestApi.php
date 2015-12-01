<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class RestApi extends CI_Controller {

	private $session;
	private $api;
	private $model;

	public function __construct() {
		require_once APPPATH . 'third_party/SpotifyWebApi/SpotifyWebAPI.php';
		require_once APPPATH . 'third_party/SpotifyWebApi/Session.php';
		require_once APPPATH . 'third_party/SpotifyWebApi/SpotifyWebAPIException.php';
		require_once APPPATH . 'third_party/SpotifyWebApi/Request.php';

		$this->session = new SpotifyWebAPI\Session(
			$this->config->item('spotify_client_id'),
			$this->config->item('spotify_client_secret'),
			$this->config->item('spotify_redirect_uri')
		);

		$this->api = new SpotifyWebAPI\SpotifyWebAPI();
		$this->model = $this->load->model('RestApi_model', 'apimodel', true);
	}

	/**
	 * Index method, api handler
	 */
	public function index() {
		if (isset($_GET['code'])) {
			$this->session->requestAccessToken($_GET['code']);
			$this->api->setAccessToken($this->session->getAccessToken());

			print_r($this->api->me());
		} else {
			$scopes = array(
				'scope' => array(
					'user-read-email',
					'user-library-modify',
				),
			);

			header('Location: ' . $this->session->getAuthorizeUrl($scopes));
		}
	}

	/**
	 * The main aritst search method
	 * @param string $term the searchable term
	 */
	public function search($term = '') {
		$data = array();
		if (!empty($term)) {
			$searchResult = $this->apimodel->searchInCachedItems($term);

			if (!is_array($searchResult)) {
				// check via API
				$data['response'] = $this->api->search($term, 'artist');
				$this->apimodel->storeResult($term, '0', $data['response']);
			}

			$data['response'] = $searchResult;
		}

		$this->load->view('simple_render', $data);
	}


	public function simplesearch($term = '') {
		$data = array();
		if (!empty($term)) {
			$searchResult = $this->apimodel->searchInCachedItems($term);

			if (!is_array($searchResult)) {
				// check via API
				$data['response'] = $this->api->search($term, 'artist');
				$this->apimodel->storeResult($term, '0', $data['response']);
			}

			$data['response'] = $searchResult;
		}

		$this->load->view('simple_render', $data);
	}



	/**
	 * Dummy playlist getter
	 */
	public function getMyList() {
		$data['response'] = $this->apimodel->showStoredPlaylist();
		$this->load->view('simple_render', $data);
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

	private function requestSpotify($term = '', $type = '') {

	}
}
