<?php
/**
 * @package	wp_wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @copyright nekudo.com
 * @author Simon Samtleben <support@nekudo.com>
 */
class ModelRuleset
{
	/**
	 * @var wpdb $wpdb Wordpress database object.
	 */
	private $wpdb;

	public function __construct($wpdb)
	{
		$this->wpdb = $wpdb;
	}

	/**
	 * Fetches ruleset data from database.
	 *
	 * @param int $rulesetId Primary key of ruleset.
	 * @return bool|mixed Array containing ruleset data or false on error.
	 */
	public function getRulesetData($rulesetId)
	{
		if(empty($rulesetId))
		{
			return false;
		}
		$row = $this->wpdb->get_row("SELECT *
			FROM " . $this->wpdb->rulesets . " rs
			WHERE rs.id = " . (int)$rulesetId, ARRAY_A);
		return $row;
	}

	/**
	 * Fetches a list with all rulesets from database.
	 *
	 * @return array $rulesets List with all rulesets.
	 */
	public function getRulesets()
	{
		$rulesets = $this->wpdb->get_results("SELECT * FROM " . $this->wpdb->rulesets . " rs ORDER BY rs.name", ARRAY_A);
		return $rulesets;
	}


	/**
	 * Saves a ruleset to database.
	 *
	 * @param array $rulesetData The data to save.
	 * @param int $rulesetId Primary key of ruleset if it already exists.
	 * @return bool|int Id of saved rule on success or false on error.
	 */
	public function saveRuleset($rulesetData, $rulesetId = 0)
	{
		if(empty($rulesetData))
		{
			return false;
		}
		if(!empty($rulesetId))
		{
			$updateResult = $this->wpdb->update(
				$this->wpdb->rulesets,
				$rulesetData,
				array('id' => $rulesetId),
				array(
					'%s', // name
					'%d', // filecheck
					'%s', // scandir
					'%d', // regex_check
					'%d', // hash_check
					'%s', // hash_check_blacklist
					'%s', // filetypes
					'%d', // filesize_max
					'%s', // regex_db
				),
				array('%d')
			);
			return ($updateResult === false) ? false : $rulesetId;
		}
		else
		{
			$this->wpdb->insert(
				$this->wpdb->rulesets,
				$rulesetData,
				array(
					'%s', // name
					'%d', // filecheck
					'%s', // scandir
					'%d', // regex_check
					'%d', // hash_check
					'%s', // hash_check_blacklist
					'%s', // filetypes
					'%d', // filesize_max
					'%s', // regex_db
				)
			);
			$rulesetId = $this->wpdb->insert_id;
			return (empty($rulesetId)) ? false : $rulesetId;
		}
	}

	/**
	 * Delete a specfic ruleset from database.
	 *
	 * @param int $rulesetId Primary key of the ruleset.
	 * @return bool True if ruleset was deleted or false otherwise.
	 */
	public function deleteRuleset($rulesetId)
	{
		if(empty($rulesetId))
		{
			return false;
		}
		$deleteResult = $this->wpdb->delete($this->wpdb->rulesets, array('id' => (int)$rulesetId));
		return ($deleteResult === false) ? false : true;
	}
}