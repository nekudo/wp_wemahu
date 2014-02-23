<?php
/**
 * @package	Wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @author Simon Samtleben <support@nekudo.com>
 */
namespace Wemahu;

use Wemahu\Audit;
use Wemahu\AuditBase;
use Wemahu\FileStorageEngine;
use Wemahu\ReportItem;

class AuditFilecheck extends AuditBase implements Audit
{
	// default audit settings:
	protected $scanDir = '.';
	protected $tmpDir = '/tmp';
	protected $regexCheck = true;
	protected $hashCheck = true;
	protected $hashCheckBlacklist = array();
	protected $extensionFilter = 'php,php4,php5';
	protected $sizeFilter = 0;
	protected $maxResultsFile = 5;
	protected $maxResultsTotal = 100;
	protected $pathRegexDb = 'db/regex_complete.wmdb';
	protected $pathRegexWhitelist = 'db/regex_whitelist.wmdb';
	protected $pathRegexWhitelistUser = 'db/regex_whitelist_user.wmdb';
	protected $pathFilehashDb = './filehashes.wmdb';

	// indicates wether the filestack was completely created:
	private $_filestackCreated = false;
	// counter for total files in filestack:
	private $_filesInStack = 0;
	// index of the last file that was processed:
	private $_lastFileScanned = 0;
	// counts results of all checks done on files:
	private $_resultsTotal = 0;
	// contains the regex-pattens to search for malicious code:
	private $_maliciousCodePatterns = array();
	// contains hashes for whitelisted regex-matches:
	private $_codePatternWhitelist = array();
	// tells if extension filter is enabled:
	private $_extensionCheck = true;
	// contains file-extensions which should be included in filestack:
	private $_allowedExtensions = '';
	// indicates whether the filehash database needs to be created during filestack creation:
	private $_createFilehashDb = false;
	// saves  the last path processed by filestack creation:
	private $_filestackPath = '';
	// saves the position of last file processed with a folder during filestack creation:
	private $_filestackKey = 0;
	private $_Filestack;
	private $_Dirstack;


	public function init()
	{
		$this->_clearFilestack();
		$this->_clearReportitems();
		$this->_prepareExtensionFilter();

		// load malicious code regex-patterns:
		if($this->regexCheck === true)
		{
			$this->_prepareRegexCheck();
		}

		// check if filehash database needs to be created:
		if($this->hashCheck === true)
		{
			$this->_validateFilehashDb();
			$this->_prepareHashcheckBlacklist();
		}

		$this->Storage->set('filesInStack', $this->_filesInStack, 'filecheck');
		$this->Storage->set('filestackCreated', $this->_filestackCreated, 'filecheck');
		$this->Storage->set('lastFileScanned', $this->_lastFileScanned, 'filecheck');
		$this->Storage->set('createFilehashDb', $this->_createFilehashDb, 'filecheck');
		$this->Storage->set('filestackPath', $this->_filestackPath, 'filecheck');
		$this->Storage->set('filestackKey', $this->_filestackKey, 'filecheck');

		// Display hint if max_nesting_level ist set. (May cause problems during filestack creation.)
		$maxNestingLevel = ini_get('xdebug.max_nesting_level');
		if(!empty($maxNestingLevel))
		{
			$this->setMessage('Warning: Xdebug max_nesting_level is set. This may cause problems.');
		}

		$this->setMessage('Initialization completed.');
	}

	public function reinit()
	{
		$this->_filesInStack = $this->Storage->get('filesInStack', 'filecheck');
		$this->_filestackCreated = $this->Storage->get('filestackCreated', 'filecheck');
		$this->_lastFileScanned = $this->Storage->get('lastFileScanned', 'filecheck');
		$this->_createFilehashDb = $this->Storage->get('createFilehashDb', 'filecheck');
		$this->_filestackPath = $this->Storage->get('filestackPath', 'filecheck');
		$this->_filestackKey = $this->Storage->get('filestackKey', 'filecheck');

		$this->_prepareExtensionFilter();

		// load malicious code regex-patterns:
		if($this->regexCheck === true)
		{
			$this->_prepareRegexCheck();
		}

		if($this->hashCheck === true)
		{
			$this->_prepareHashcheckBlacklist();
		}
	}

	public function runAudit()
	{
		// filestack needs to be created:
		$this->_loadFilestack($this->scanDir);
		if($this->_filestackCreated === false)
		{
			return true;
		}

		while(true)
		{
			// check if interval limit is reached:
			if($this->intervalLimitIsReached() === true)
			{
				$this->setMessage(sprintf('%d of %d files scanned.', $this->_lastFileScanned, $this->_filesInStack));
				return true;
			}

			// check if max-results is reached:
			if($this->_resultsTotal >= $this->maxResultsTotal)
			{
				$this->auditCompleted = true;
				return true;
			}

			$this->Database->setQuery("SELECT * FROM #__wm_filestack WHERE mode = " . $this->Database->q($this->runMode) . " LIMIT 1")->execute();
			$row = $this->Database->getRow();
			if(empty($row))
			{
				break;
			}
			$path = trim($row->path);
			$this->_lastFileScanned++;
			$this->Storage->set('lastFileScanned', $this->_lastFileScanned, 'filecheck');

			$this->Database->setQuery("DELETE FROM #__wm_filestack WHERE path = " . $this->Database->q($row->path) . " AND mode = " . $this->Database->q($this->runMode) . " LIMIT 1")->execute();

			// run tests on file:
			if($this->regexCheck === true)
			{
				$this->_regexCheck($path);
			}
			if($this->hashCheck == true)
			{
				$this->_hashCheck($path);
			}
		}

		$this->auditCompleted = true;
		return true;
	}

	/**
	 * Add an item to the user-whitelist.
	 *
	 * @todo Submit data to nekudo.com for analysis.
	 *
	 * @throws AuditException
	 * @param ReportItem $ReportItem The item that should be added to whitelist.
	 * @return boolean True if item was added to whitelist false on error.
	 */
	public function addToWhitelist(ReportItem $ReportItem)
	{

		if(!file_exists($this->pathRegexWhitelistUser))
		{
			$wmdbHeader = "> WMDBVN - " . date('Ymd') . "01\n";
			$wmdbHeader.="> WMDBFN - id,match_hash\n";
			$wmdbHeader.="> WMDBPK - id\n";
			if(false === file_put_contents($this->pathRegexWhitelistUser, $wmdbHeader))
			{
				throw new AuditException('Could not create user whitelist database file.');
			}
		}
		if(empty($ReportItem->matchSnippet))
		{
			return false;
		}
		$matchHash = sha1($ReportItem->matchSnippet . '###' . basename($ReportItem->affectedFile));
		$RegexWhitelist = new FileStorageEngine($this->pathRegexWhitelistUser);
		$whitelistItem = array(
			'match_hash' => $matchHash,
		);
		return $RegexWhitelist->add($whitelistItem);
	}

	private function _prepareExtensionFilter()
	{
		// prepare extension filter:
		if(!empty($this->extensionFilter))
		{
			$allowedExtensions = array();
			$tmp = explode(',',  $this->extensionFilter);
			foreach($tmp as $extension)
			{
				$extension = trim($extension);
				if(empty($extension))
				{
					continue;
				}
				$allowedExtensions[$extension] = true;
			}
			$this->_allowedExtensions = $allowedExtensions;
		}
		if(empty($this->_allowedExtensions))
		{
			$this->_extensionCheck = false;
		}
	}

	private function _prepareRegexCheck()
	{
		$PatternDb = new FileStorageEngine($this->pathRegexDb);
		$this->_maliciousCodePatterns = $PatternDb->getAll();
		$RegexWhitelist = new FileStorageEngine($this->pathRegexWhitelist);
		$whitelistData = $RegexWhitelist->getAll();
		$this->_prepareWhitelist($whitelistData);
		unset($RegexWhitelist);

		if(file_exists($this->pathRegexWhitelistUser))
		{
			$RegexWhitelistUser = new FileStorageEngine($this->pathRegexWhitelistUser);
			$whitelistDataUser = $RegexWhitelistUser->getAll();
			$this->_prepareWhitelist($whitelistDataUser);
		}
	}

	private function _clearFilestack()
	{
		$this->Database->setQuery("DELETE FROM #__wm_filestack WHERE mode = " . $this->Database->q($this->runMode))->execute();
		return $this->Database->setQuery("DELETE FROM #__wm_dirstack WHERE mode = " . $this->Database->q($this->runMode))->execute();
	}

	private function _clearReportitems()
	{
		return $this->Database->setQuery("DELETE FROM #__wm_reportitems WHERE mode = " . $this->Database->q($this->runMode))->execute();
	}

	/**
	 * Check id filehash database needs to be re-created.
	 */
	private function _validateFilehashDb()
	{
		// id databse is empty it needs to be created:
		$this->Database->setQuery("SELECT COUNT(*) filehash_count FROM #__wm_filehashes WHERE mode = " . $this->Database->q($this->runMode))->execute();
		$row = $this->Database->getRow();
		$filehashCount = (int)$row->filehash_count;
		if($filehashCount === 0)
		{
			$this->_createFilehashDb = true;
			return false;
		}

		// if database was created for another path it needs to be re-created:
		$this->Database->setQuery("SELECT wm_value as filehash_db_path FROM #__wm_kvs WHERE wm_key = 'filehash_db_path' AND mode = " . $this->Database->q($this->runMode))->execute();
		$row = $this->Database->getRow();
		if(empty($row))
		{
			$this->_createFilehashDb = true;
			return false;
		}
		$hashtableChecksum = $this->_getHashtableChecksum();
		if($hashtableChecksum !== $row->filehash_db_path)
		{
			$this->Database->setQuery("DELETE FROM #__wm_filehashes WHERE mode = " . $this->Database->q($this->runMode))->execute();
			$this->_createFilehashDb = true;
			return false;
		}
		return true;
	}

	private function _loadFilestack($dir)
	{
		if(empty($dir) || !is_dir($dir))
		{
			throw new AuditException('Scan Directory does not exists.');
		}

		// check if filestack already created:
		if($this->_filestackCreated === true)
		{
			return true;
		}

		// save hash of scandir to prevent duplicate hash-creation:
		if($this->_createFilehashDb === true)
		{
			$hashtableChecksum = $this->_getHashtableChecksum();
			$this->Database->setQuery("INSERT INTO #__wm_kvs (wm_key, wm_value, mode) VALUES('filehash_db_path', " . $this->Database->q($hashtableChecksum) . ", " . $this->Database->q($this->runMode) . ")
				ON DUPLICATE KEY UPDATE wm_value = " . $this->Database->q($hashtableChecksum))->execute();
		}

		// start filestack creation:
		$this->_Filestack = new Pathstack($this->Database, '#__wm_filestack');
		$this->_Dirstack = new Pathstack($this->Database, '#__wm_dirstack');
		if(!empty($this->_filestackPath))
		{
			$dir = $this->_filestackPath;
		}
		$processDirResult = $this->_filestackProcessDir($dir);
		if($processDirResult === false)
		{
			return false;
		}

		$this->_filestackCreated = true;
		$this->Storage->set('filestackCreated', $this->_filestackCreated, 'filecheck');
		//exit('filestack creation done.');
		return true;
	}

	private function _filestackProcessDir($dir)
	{
		$Iterator = new \DirectoryIterator($dir);
		if($this->_filestackKey > 0)
		{
			$Iterator->seek($this->_filestackKey);
		}

		while($Iterator->valid())
		{
			// timeout check:
			if($this->intervalLimitIsReached() === true)
			{
				$this->_Dirstack->save();
				$this->_Filestack->save();
				$this->setMessage('Filestack creation: ' . $this->_filesInStack . ' files processed.');
				return false;
			}

			$this->_filestackKey = $Iterator->key();
			$this->Storage->set('filestackKey', $this->_filestackKey, 'filecheck');

			// skip dots:
			if($Iterator->isDot())
			{
				$Iterator->next();
				continue;
			}

			// if is directory save to database for later processing:
			$currentPath = $Iterator->getPathname();
			if($Iterator->isDir())
			{
				$this->_Dirstack->addPath($currentPath);
				$Iterator->next();
				continue;
			}

			// filesize check:
			$filesize = $Iterator->getSize();
			if(empty($filesize) || $filesize > $this->sizeFilter)
			{
				$Iterator->next();
				continue;
			}

			// filetype check:
			if($this->_extensionCheck === true)
			{
				$fileExtension = $Iterator->getExtension();
				if(empty($fileExtension) || !isset($this->_allowedExtensions[$fileExtension]))
				{
					$Iterator->next();
					continue;
				}
			}

			// add file to stack:
			$this->_Filestack->addPath($currentPath);

			// create hash (if not yet done):
			if($this->_createFilehashDb === true)
			{
				$pathhash = sha1($currentPath);
				$filehash = sha1_file($currentPath);
				$this->Database->setQuery("INSERT IGNORE INTO #__wm_filehashes (pathhash, filehash, mode) VALUES(" . $this->Database->q($pathhash) . "," . $this->Database->q($filehash) . ", " . $this->Database->q($this->runMode) . ")")->execute();
			}

			$this->_filesInStack++;
			$this->Storage->set('filesInStack', $this->_filesInStack, 'filecheck');


			$Iterator->next();
		}

		unset($Iterator);

		// current dir completed -> delete from stack:
		$this->_Dirstack->save();
		$this->_Dirstack->clear();
		$this->_Filestack->save();
		$this->_Filestack->clear();
		$this->Database->setQuery("DELETE FROM #__wm_dirstack WHERE path = " . $this->Database->q($dir) . "AND mode = " . $this->Database->q($this->runMode))->execute();
		$this->_filestackKey = 0;

		// select next dir from stack:
		$this->Database->setQuery("SELECT path FROM #__wm_dirstack WHERE mode = " . $this->Database->q($this->runMode) . " LIMIT 1")->execute();
		$row = $this->Database->getRow();
		if(empty($row))
		{
			return true;
		}

		$this->_filestackPath = $row->path;
		$this->Storage->set('filestackPath', $this->_filestackPath, 'filecheck');
		return $this->_filestackProcessDir($row->path);
	}

	private function _regexCheck($path)
	{
		$fileContent = file_get_contents($path);
		if($fileContent === false)
		{
			throw new AuditException('Could not read file. (File: ' . $path . ')');
		}

		$fileMatchCount = 0;
		foreach($this->_maliciousCodePatterns as $patternItem)
		{
			$patternMatches = array();
			$patternHits = preg_match_all('#' . $patternItem['pattern'] . '#isSU', $fileContent, $patternMatches, PREG_OFFSET_CAPTURE);
			$patternMatches = $patternMatches[0];
			if($patternHits > 0)
			{
				for($i = 0; $i < $patternHits; $i++)
				{
					// check if match is whitelisted:
					$matchSnippet = substr($fileContent, $patternMatches[$i][1] - 100, 250);
					if($this->_regexMatchIsWhitelisted($matchSnippet, $path) === true)
					{
						continue;
					}
					$ReportItem = new ReportItem($this->auditName, 'regexCheck');
					$ReportItem->affectedFile = $path;
					$ReportItem->match = $patternMatches[$i][0];
					$ReportItem->matchName = $patternItem['name'];
					$ReportItem->matchDescription = $patternItem['description'];
					$ReportItem->matchSnippet = $matchSnippet;
					$this->Report->addItem($ReportItem);
					$fileMatchCount++;
					$this->_resultsTotal++;

					if($fileMatchCount >= $this->maxResultsFile)
					{
						break(2);
					}
					if($this->_resultsTotal >= $this->maxResultsTotal)
					{
						break(2);
					}
				}
			}
		}
	}

	private function _hashCheck($path)
	{
		if($this->_pathIsBlacklisted($path) === true)
		{
			return true;
		}
		$pathhash = sha1($path);
		$filehash = sha1_file($path);
		$this->Database->setQuery("SELECT fh.filehash
			FROM #__wm_filehashes fh
			WHERE fh.pathhash = " . $this->Database->q($pathhash) . "
				AND fh.mode = " . $this->Database->q($this->runMode))->execute();
		$row = $this->Database->getRow();
		if(empty($row))
		{
			$this->Database->setQuery("INSERT INTO #__wm_filehashes (pathhash,filehash, mode) VALUES(".$this->Database->q($pathhash).",".$this->Database->q($filehash).",".$this->Database->q($this->runMode).")")->execute();
			$ReportItem = new ReportItem($this->auditName, 'hashCheck');
			$ReportItem->affectedFile = $path;
			$ReportItem->type = 'new_file';
			$ReportItem->lastmod = date("Y-m-d H:i:s", filemtime($path));
			$this->Report->addItem($ReportItem);
			return true;
		}
		if($filehash !== $row->filehash)
		{
			$this->Database->setQuery("UPDATE #__wm_filehashes SET filehash = " . $this->Database->q($filehash) . " WHERE pathhash = " . $this->Database->q($pathhash) . " AND mode = " . $this->Database->q($this->runMode))->execute();
			$ReportItem = new ReportItem($this->auditName, 'hashCheck');
			$ReportItem->affectedFile = $path;
			$ReportItem->type = 'modified_file';
			$ReportItem->lastmod = date("Y-m-d H:i:s", filemtime($path));
			$this->Report->addItem($ReportItem);
			return true;
		}

		return true;
	}

	private function _prepareWhitelist($whitelistData)
	{
		if(empty($whitelistData))
		{
			return false;
		}
		foreach($whitelistData as $item)
		{
			$this->_codePatternWhitelist[$item['match_hash']] = true;
		}
		return true;
	}

	private function _regexMatchIsWhitelisted($matchSnippet, $affectedFile)
	{
		if(empty($matchSnippet) || empty($affectedFile))
		{
			return false;
		}
		if(empty($this->_codePatternWhitelist))
		{
			return false;
		}
		$matchHash = sha1($matchSnippet . '###' . basename($affectedFile));
		if(isset($this->_codePatternWhitelist[$matchHash]))
		{
			return true;
		}
		return false;
	}

	/**
	 * Generates a checksum of parameters which have influence on the filehash-table
	 * like scanned extensions, scnanned folder, eg.
	 * If this checksum changes the hashtable has to be recreated.
	 *
	 * @return string Sha1 checksum of settings influencing the hashtable.
	 */
	private function _getHashtableChecksum()
	{
		$valueChain = $this->scanDir . '###' . $this->extensionFilter . '###' . $this->sizeFilter;
		$checksum = sha1($valueChain);
		return $checksum;
	}

	/**
	 * Converts relative blacklisted folders in to absolute paths and checks their existence.
	 *
	 * @return bool true.
	 */
	private function _prepareHashcheckBlacklist()
	{
		if(empty($this->hashCheckBlacklist))
		{
			return true;
		}
		$blacklistedPaths = array();
		foreach($this->hashCheckBlacklist as $subfolder)
		{
			$path = $this->scanDir . '/' . trim($subfolder, '/');
			if(file_exists($path) && is_dir($path))
			{
				$blacklistedPaths[] = $path;
			}
		}
		$this->hashCheckBlacklist = $blacklistedPaths;
		return true;
	}

	/**
	 * Checks if a path is in the path blacklist.
	 *
	 * @param strign $path The path to check.
	 * @return bool True if path is blacklisted or false otherwise.
	 */
	private function _pathIsBlacklisted($path)
	{
		if(empty($this->hashCheckBlacklist))
		{
			return false;
		}
		foreach($this->hashCheckBlacklist as $blacklistedPath)
		{
			if(strpos($path, $blacklistedPath) === 0)
			{
				return true;
			}
		}
		return false;
	}
}