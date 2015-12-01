<?php
defined('BASEPATH') OR exit('No direct script access allowed');


class RestApi_model extends CI_Model {

	public function __construct() {
		parent::__construct();
		$this->load->database();
	}

	/**
	 * Search in stored & ordered datas
	 * @param string $term
	 * @return null|array
	 */
	public function searchInCachedItems($term = '') {
		$result = null;
		if (!empty($term)) {
			$term = $this->db->escape($term);

			$sql = '
				SELECT
					mm_test_pl_items.*
				FROM
					mm_test_pl_items
				WHERE
					mm_test_pl_items.song_hash LIKE "%' . $term . '%"
				OR
					mm_test_pl_items.song_title LIKE "%' . $term . '%"
				OR
					mm_test_pl_items.song_artist LIKE "%' . $term . '%"
				OR
					mm_test_pl_items.song_album LIKE "%' . $term . '%";
			';

			$query = $this->db->query($sql);

			if (0 < $query->num_rows) {
				$result = $query->result_array();
			}
			$query->free_result();
		}

		return $result;
	}

	/**
	 * Search in raw responses
	 * @param string $term
	 * @return null|array
	 */
	public function searchInResponses($term = '') {
		$result = null;
		if (!empty($term)) {
			$sql = '
				SELECT
					mm_test_pl_cache_request.*
				FROM
					mm_test_pl_cache_request
				WHERE
					mm_test_pl_cache_request.requested_term LIKE "%' . $this->db->escape($term) . '%";
			';

			$query = $this->db->query($sql);

			if (0 < $query->num_rows) {
				$result = $query->result_array();
			}
			$query->free_result();
		}

		return $result;
	}

	/**
	 * Retrieve stored playlist from database
	 * @return null|array
	 */
	public function showStoredPlaylist() {
		$result = null;

		$sql = '
			SELECT
				mm_test_pl_playlist.*
			FROM
				mm_test_pl_playlist
			WHERE
				mm_test_pl_playlist.item_id <> 0
			ORDER BY
				mm_test_pl_playlist.crdate ASC;
		';

		$query = $this->db->query($sql);

		if (0 < $query->num_rows) {
			$result = $query->result_array();
		}
		$query->free_result();

		return $result;
	}


	/**
	 * Store raw request/response into database
	 * @param string $term the search keyword
	 * @param int $request_type 0 - artist, 1 - album, 2 - track
	 * @param string $results result, usually json from the api
	 * @return bool
	 */
	public function storeResult($term = '', $request_type = 0, $results = '') {
		if (!empty($term) && !empty($results)) {
			$term = $this->db->escape($term);
			$results = $this->db->escape($results);

			$sql = '
				INSERT INTO
					mm_test_pl_cache_request
					(
						requested_term,
						request_type,
						stored_response,
						crdate
					)
				VALUES
					(
						"' . $term . '",
						"' . $request_type . '",
						"' . $results . '"
						"' . date('Y-m-d H:i:s') . '"
					);
			';

			$query = $this->db->query($sql);
			$query->free_result();

			// trigger sub-process for cache!
			$this->fetchItems($results);

			return true;
		}

		return false;
	}

	/**
	 * Add a new item into the playlist
	 * @param string $itemId
	 * @return bool
	 */
	public function addItemToPlaylist($itemId = '') {
		$result = false;

		if (!empty($itemId)) {
			$itemId = $this->db->escape($itemId);

			// check item in itemlist!
			$sql = '
				SELECT
					mm_test_pl_items.id,
					mm_test_pl_items.song_hash,
					mm_test_pl_items.song_title
				FROM
					mm_test_pl_items
				WHERE
					mm_test_pl_items.id = "' . $itemId . '"
				LIMIT 1;
			';
			$query = $this->db->query($sql);
			if (0 < $this->db->num_rows()) {

				// check member on playlist
				if (false == $this->checkItemOnPlaylist($itemId)) {
					$sql = '
						INSERT INTO
							mm_test_pl_playlist
						(
							item_id,
							crdate
						)
						VALUES
						(
							"' . $itemId . '",
							"' . date('Y-m-d H:i:s') . '"
						);
					';
					$query = $this->db->query($sql);

					$result = true;
				}
			}

			$query->free_results();
		}

		return $result;
	}

	/**
	 * Remove one item from playlist by Id
	 * @param string $itemId the playlist id
	 * @return bool
	 */
	public function removeItemFromPlaylist($itemId = '') {
		if (!empty($itemId)) {
			$itemId = $this->db->escape($itemId);

			if (true == $this->checkItemOnPlaylist($itemId)) {
				$sql = '
					DELETE FROM
						mm_test_pl_playlist
					WHERE
						mm_test_pl_playlist.id = "' . $itemId . '";
				';
				$this->db->query($sql);

				return true;
			}
		}

		return false;
	}

	/**
	 * Check item is member or not in the playlist
	 * @param string $itemId
	 * @return bool
	 */
	private function checkItemOnPlaylist($itemId = '') {
		if (!empty($itemId)) {
			$itemId = $this->db->escape($itemId);

			$sql = '
				SELECT
					mm_test_pl_playlist.id
				FROM
					mm_test_pl_playlist
				WHERE
					mm_test_pl_playlist.item_id = "' . $itemId . '"
				ORDER BY
					mm_test_pl_playlist.crdate
				ASC
				LIMIT 1;
			';
			$query = $this->db->query($sql);

			if (0 < $this->db->num_rows()) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Fetch & store results in database
	 * @param string $items json response from the api
	 * @return bool
	 */
	protected function fetchItems($items = '')
	{
		if (!empty($items)) {
			$temp = json_decode($items, true);
			if (is_array($temp)) {
				// response[tracks][items]
				if (isset($temp['tracks']) && isset($temp['tracks']['items']) && is_array($temp['tracks']['items'])) {
					$itemArray = $temp['tracks']['items'];

					for ($i = 0; $i < count($itemArray); $i++) {
						if (
							isset($itemArray['id']) && !empty($itemArray['id']) &&
							isset($itemArray['name']) && !empty($itemArray['name']) &&
							isset($itemArray['artist']) && !empty($itemArray['artist']) &&
							isset($itemArray['artist']['name']) && !empty($itemArray['artist']['name'])
						) {
							// postfix hack :)
							if (!isset($itemArray['album']['name'])) {
								$itemArray['album']['name'] = '';
							}
							if (!isset($itemArray['duration_ms'])) {
								$itemArray['duration_ms'] = '0';
							}

							$sql = '
								INSERT INTO
									mm_test_pl_items
								(
									song_hash,
									song_title,
									song_artis,
									song_album,
									song_duration
								)
								VALUES
								(
									"' . $itemArray['id'] . '",
									"' . $itemArray['name'] . '",
									"' . $itemArray['artists'][0]['name'] . '",
									"' . $itemArray['album']['name'] . '",
									"' . $itemArray['duration_ms'] . '"
								);
							';
							$this->db->query($sql);
						}
					}
					// for end

					return true;
				}
				// if isset end
			}
			// if is_array end
		}
		// if !empty end

		return false;
	}
}
