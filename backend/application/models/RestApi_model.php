<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class RestApi_model
 */
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
			$term = str_replace("'", "", $term);

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
					mm_test_pl_items.song_album LIKE "%' . $term . '%"
				ORDER BY
					crdate
				LIMIT 50;
			';

			$query = $this->db->query($sql);

			if (0 < $query->num_rows()) {
				$result = $query->result_array();
			}
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
			$term = $this->db->escape($term);
			$term = str_replace("'", "", $term);

			$sql = '
				SELECT
					mm_test_pl_cache_request.*
				FROM
					mm_test_pl_cache_request
				WHERE
					mm_test_pl_cache_request.requested_term LIKE "%' . $term . '%";
			';

			$query = $this->db->query($sql);

			if (0 < $query->num_rows()) {
				$result = $query->result_array();
			}
		}

		return $result;
	}

	/**
	 * Check database for stored tracks for an artist, by id/hash
	 * @param string $artist_hash the api based id/hash for an artist
	 * @return null|array
	 */
	public function getArtistTracks($artist_hash = '') {
		$result = null;

		if (!empty($artist_hash)) {
			$artist_hash = $this->db->escape($artist_hash);
			$artist_hash = str_replace("'", "", $artist_hash);

			$sql = '
				SELECT
					mm_test_pl_items.*
				FROM
					mm_test_pl_items
				WHERE
					MD5(mm_test_pl_items.song_artist_hash) = "' . md5($artist_hash) . '"
				AND
					mm_test_pl_items.song_hash <> ""
				ORDER BY
					crdate ASC
				LIMIT 50;
			';

			$query = $this->db->query($sql);

			if (0 < $query->num_rows()) {
				$result = $query->result_array();
			}
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
				mm_test_pl_playlist.id as myid,
				mm_test_pl_items.song_hash as id,
				mm_test_pl_items.song_title as song_title,
				mm_test_pl_items.song_artist as song_artist,
				mm_test_pl_items.song_artist_hash as song_artist_hash
			FROM
				mm_test_pl_playlist
			LEFT JOIN
				mm_test_pl_items
			ON
				mm_test_pl_items.id = mm_test_pl_playlist.item_id
			WHERE
				mm_test_pl_playlist.item_id <> 0
			ORDER BY
				mm_test_pl_playlist.crdate ASC;
		';
		$query = $this->db->query($sql);

		if (0 < $query->num_rows()) {
			$result = $query->result_array();
		}

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
		if (!empty($term) && !empty($results) && 0 != $results) {
			$term = $this->db->escape($term);
			$results = $this->db->escape($results);
			$term = str_replace("'", "", $term);

			$sql = '
				SELECT
					mm_test_pl_cache_request.id
				FROM
					mm_test_pl_cache_request
				WHERE
					mm_test_pl_cache_request.requested_term = "' . $term . '"
				AND
					mm_test_pl_cache_request.request_type = "' . $request_type . '"
				LIMIT 1
			';
			$query = $this->db->query($sql);

			if (0 == $query->num_rows()) {
				$sql = "
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
							'" . $term . "',
							'" . $request_type . "',
							'" . base64_encode(serialize($results)) . "',
							'" . date('Y-m-d H:i:s') . "'
						);
				";
				$query = $this->db->query($sql);

				// trigger sub-process for cache!
				$this->fetchItems($results, $request_type);

				return true;
			}
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
			$itemId = str_replace("'", "", $itemId);

			// check item in itemlist!
			$sql = '
				SELECT
					mm_test_pl_items.id,
					mm_test_pl_items.song_hash,
					mm_test_pl_items.song_title
				FROM
					mm_test_pl_items
				WHERE
					md5(mm_test_pl_items.id) = "' . md5($itemId) . '"
				OR
					md5(mm_test_pl_items.song_hash) = "' . md5($itemId) . '"
				LIMIT 1;
			';
			$query = $this->db->query($sql);
			if (0 < $query->num_rows()) {

				// check member on playlist
				$row = $query->row_array();
				$itemId = $row['id'];

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
					$this->db->query($sql);

					$result = true;
				}
			}
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
			$itemId = str_replace("'", "", $itemId);

			if (true == $this->checkItemOnPlaylist($itemId)) {
				$sql = '
					DELETE FROM
						mm_test_pl_playlist
					WHERE
						MD5(mm_test_pl_playlist.id) = "' . md5($itemId) . '";
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
			$itemId = str_replace("'", "", $itemId);

			$sql = '
				SELECT
					mm_test_pl_playlist.id
				FROM
					mm_test_pl_playlist
				WHERE
					MD5(mm_test_pl_playlist.id) = "' . md5($itemId) . '"
				ORDER BY
					mm_test_pl_playlist.crdate
				ASC
				LIMIT 1;
			';
			$query = $this->db->query($sql);

			if (0 < $query->num_rows()) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Fetch & store results in database
	 * @param string $items json response from the api
	 * @param int $request_type the response type 0 - artist, 1 - album, 2 - track
	 * @return bool
	 */
	protected function fetchItems($items = '', $request_type = 0) {
		if (!empty($items)) {
			$temp = $items;

			if (is_array($temp)) {
				// artist
				if ( 0 == $request_type) {
					if (isset($temp['artists']) && isset($temp['artists']['items']) && is_array($temp['artists']['items'])) {
						$itemArray = $temp['artists']['items'];

						for ($i = 0; $i < count($itemArray); $i++) {
							if (
								isset($itemArray[$i]['name']) &&
								isset($itemArray[$i]['id'])
							) {
								$sql = '
									SELECT
										mm_test_pl_items.id
									FROM
										mm_test_pl_items
									WHERE
										MD5(mm_test_pl_items.song_artist_hash) = "' . md5($itemArray[$i]['id']) . '"
									LIMIT 1
								';
								$checkResult = $this->db->query($sql);

								if (0 == $checkResult->num_rows()) {
									$sql = '
										INSERT INTO
											mm_test_pl_items
										(
											song_artist_hash,
											song_artist,
											crdate
										)
										VALUES
										(
											' . $itemArray[$i]['id'] . ',
											' . $itemArray[$i]['name'] . ',
											"' . date('Y-m-d H:i:s') . '"
										);
									';
									$this->db->query($sql);
								}
							}
						}
						// for end

						return true;
					}
				}

				// track
				if (2 == $request_type) {
					if (isset($temp['tracks']) && isset($temp['tracks']) && is_array($temp['tracks'])) {
						$itemArray = $temp['tracks'];

						for ($i = 0; $i < count($itemArray); $i++) {
							if (
								isset($itemArray[$i]['id']) && !empty($itemArray[$i]['id']) &&
								isset($itemArray[$i]['name']) && !empty($itemArray[$i]['name']) &&
								isset($itemArray[$i]['artists']) && !empty($itemArray[$i]['artists']) &&
								isset($itemArray[$i]['artists'][0]['name']) && !empty($itemArray[$i]['artists'][0]['name'])
							) {
								// postfix hack :)
								if (!isset($itemArray[$i]['album']['name'])) {
									$itemArray[$i]['album']['name'] = '';
								}
								if (!isset($itemArray[$i]['duration_ms'])) {
									$itemArray[$i]['duration_ms'] = '0';
								}

								$sql = '
									SELECT
										mm_test_pl_items.id
									FROM
										mm_test_pl_items
									WHERE
										MD5(mm_test_pl_items.song_hash) = "' . md5($itemArray[$i]['id']) . '"
									LIMIT 1;
								';
								$checkQuery = $this->db->query($sql);

								if (0 == $checkQuery->num_rows()) {
									$sql = "
										INSERT INTO
											mm_test_pl_items
										(
											song_hash,
											song_title,
											song_artist,
											song_artist_hash,
											song_album,
											song_duration,
											crdate
										)
										VALUES
										(
											" . $itemArray[$i]['id'] . ",
											" . $itemArray[$i]['name'] . ",
											" . $itemArray[$i]['artists'][0]['name'] . ",
											" . $itemArray[$i]['artists'][0]['id'] . ",
											" . $itemArray[$i]['album']['name'] . ",
											'" . $this->formatSeconds($itemArray[$i]['duration_ms']) . "',
											'" . date('Y-m-d H:i:s') . "'
										);
									";
									$this->db->query($sql);
								}
							}
						}
						// for end

						return true;
					}
					// if isset end
				}
			}
			// if is_array end
		}
		// if !empty end

		return false;
	}

	/**
	 * Time helper, milliseconds to time
	 * @param int $milliseconds
	 * @return string
	 */
	protected function formatSeconds($milliseconds = 0) {
		$result = '00:00:00';

		if (!empty($milliseconds)) {
			$seconds = floor($milliseconds / 1000);
			$minutes = floor($seconds / 60);
			$hours = floor($minutes / 60);
			$milliseconds = $milliseconds % 1000;
			$seconds = $seconds % 60;
			$minutes = $minutes % 60;

			$format = '%u:%02u:%02u.%03u';
			$time = sprintf($format, $hours, $minutes, $seconds, $milliseconds);
			$result = rtrim($time, '0');
		}

		return $result;
	}
}
