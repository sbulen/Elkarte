<?php

/**
 * Utility class for search functionality.
 *
 * @package   ElkArte Forum
 * @copyright ElkArte Forum contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause (see accompanying LICENSE.txt file)
 *
 * This file contains code covered by:
 * copyright: 2011 Simple Machines (http://www.simplemachines.org)
 *
 * @version 2.0 dev
 *
 */

namespace ElkArte\Search;

use ElkArte\Database\AbstractResult;
use ElkArte\Database\QueryInterface;
use ElkArte\Search\API\Standard;

/**
 * Actually do the searches
 */
class Search
{
	/** @const the forum version but is repeated due to some people rewriting FORUM_VERSION. */
	public const FORUM_VERSION = 'ElkArte 2.0 dev';

	/** @var array */
	protected $_participants = [];

	/** @var SearchParams */
	protected $_searchParams;

	/** @var SearchArray Holds the words and phrases to be searched on */
	private $_searchArray;

	/** @var null|object Holds instance of the search api in use such as \ElkArte\Search\API\Standard_Search */
	private $_searchAPI;

	/** @var QueryInterface Database instance */
	private $_db;

	/** @var array Builds the array of words for use in the db query */
	private $_searchWords = [];

	/** @var array Words excluded from indexes */
	private $_excludedIndexWords = [];

	/** @var array Words not to be found in the subject (-word) */
	private $_excludedSubjectWords = [];

	/** @var array Phrases not to be found in the search results (-"some phrase") */
	private $_excludedPhrases = [];

	/** @var WeightFactors The weights to associate to various areas for relevancy */
	private $_weightFactors = [];

	/** @var bool If we are creating a tmp db table */
	private $_createTemporary;

	/** @var array common words that we will not index or search for */
	private $_blocklist_words = [];

	/**
	 * Constructor
	 * Easy enough, initialize the database objects (generic db and search db)
	 *
	 * @package Search
	 */
	public function __construct()
	{
		$this->_db = database();
		$db_search = db_search();

		// Create new temporary table(s) (if we can) to store preliminary results in.
		$db_search->skip_next_error();

		$this->_createTemporary = $db_search->createTemporaryTable(
				'{db_prefix}tmp_log_search_messages',
				[
					[
						'name' => 'id_msg',
						'type' => 'int',
						'size' => 10,
						'unsigned' => true,
						'default' => 0,
					]
				],
				[
					[
						'name' => 'id_msg',
						'columns' => ['id_msg'],
						'type' => 'primary'
					]
				]
			) !== false;

		// Skip the error as it is not uncommon for temp tables to be denied
		$db_search->skip_next_error();
		$db_search->createTemporaryTable('{db_prefix}tmp_log_search_topics',
			[
				[
					'name' => 'id_topic',
					'type' => 'mediumint',
					'unsigned' => true,
					'size' => 8,
					'default' => 0
				]
			],
			[
				[
					'name' => 'id_topic',
					'columns' => ['id_topic'],
					'type' => 'primary'
				]
			]
		);
	}

	/**
	 * Returns a search parameter.
	 *
	 * @param string $name - name of the search parameters
	 *
	 * @return bool|mixed - the value of the parameter
	 */
	public function param($name)
	{
		return $this->_searchParams[$name] ?? false;
	}

	/**
	 * Sets $this->_searchParams with all the search parameters.
	 */
	public function getParams()
	{
		$this->_searchParams->mergeWith([
			'min_msg_id' => $this->_searchParams->_minMsgID,
			'max_msg_id' => $this->_searchParams->_maxMsgID,
			'memberlist' => $this->_searchParams->_memberlist,
		]);
	}

	/**
	 * Returns the ignored words
	 */
	public function getIgnored()
	{
		return $this->_searchArray->getIgnored();
	}

	/**
	 * Set the weight factors
	 *
	 * @param WeightFactors $weight
	 */
	public function setWeights($weight)
	{
		$this->_weightFactors = $weight;
	}

	/**
	 * Set disallowed words etc.
	 *
	 * @param SearchParams $paramObject
	 * @param false $search_simple_fulltext
	 */
	public function setParams($paramObject, $search_simple_fulltext = false)
	{
		$this->_searchParams = $paramObject;
		$this->setBlockListedWords();
		$this->_searchArray = new SearchArray($this->_searchParams->search, $this->_blocklist_words, $search_simple_fulltext);
	}

	/**
	 * If any block-listed word has been found
	 *
	 * @return bool
	 */
	public function foundBlockListedWords()
	{
		return $this->_searchArray->foundBlockListedWords();
	}

	/**
	 * Returns the block-listed word array
	 *
	 * @return array
	 */
	public function getBlockListedWords()
	{
		if (empty($this->_blocklist_words))
		{
			$this->setBlockListedWords();
		}

		return $this->_blocklist_words;
	}

	/**
	 * Sets the block-listed word array
	 */
	public function setBlockListedWords()
	{
		// Unfortunately, searching for words like these is going to result in to many hits,
		// so we're blocking them.
		$blocklist_words = ['img', 'url', 'quote', 'www', 'http', 'the', 'is', 'it', 'are', 'if', 'in'];
		call_integration_hook('integrate_search_blocklist_words', [&$blocklist_words]);

		$this->_blocklist_words = $blocklist_words;
	}

	/**
	 * Get the search array from the SearchArray object.
	 *
	 * @return array The search array.
	 */
	public function getSearchArray()
	{
		return $this->_searchArray->getSearchArray();
	}

	/**
	 * Get the list of excluded words.
	 *
	 * @return array
	 */
	public function getExcludedWords()
	{
		return $this->_searchArray->getExcludedWords();
	}

	/**
	 * Get the excluded subject words.
	 *
	 * @return array The excluded subject words.
	 */
	public function getExcludedSubjectWords()
	{
		return $this->_excludedSubjectWords;
	}

	/**
	 * Returns the search parameters.
	 *
	 * @param bool $array If true returns an array, otherwise an object
	 *
	 * @return SearchParams|string[]
	 */
	public function getSearchParams($array = false)
	{
		if ($array)
		{
			return $this->_searchParams->get();
		}

		return $this->_searchParams;
	}

	/**
	 * Get the excluded phrases.
	 *
	 * @return array The excluded phrases.
	 */
	public function getExcludedPhrases()
	{
		return $this->_excludedPhrases;
	}

	/**
	 * Tell me, do I want to see the full message or just a piece?
	 */
	public function isCompact()
	{
		return empty($this->_searchParams['show_complete']);
	}

	/**
	 * Wrapper around SearchParams::compileURL
	 *
	 * @param array $search build param index with specific search term (did you mean?)
	 *
	 * @return string - the encoded string to be appended to the URL
	 */
	public function compileURLparams($search = [])
	{
		return $this->_searchParams->compileURL($search);
	}

	/**
	 * Finds the posters of the messages
	 *
	 * @param int[] $msg_list - All the messages we want to find the posters
	 * @param int $limit - There are only so many topics
	 *
	 * @return int[] - array of members id
	 */
	public function loadPosters($msg_list, $limit)
	{
		// Load the posters...
		$posters = [];
		$this->_db->fetchQuery('
			SELECT
				id_member
			FROM {db_prefix}messages
			WHERE id_member != {int:no_member}
				AND id_msg IN ({array_int:message_list})
			LIMIT {int:limit}',
			[
				'message_list' => $msg_list,
				'limit' => $limit,
				'no_member' => 0,
			]
		)->fetch_callback(
			static function ($row) use (&$posters) {
				$posters[] = (int) $row['id_member'];
			}
		);

		return $posters;
	}

	/**
	 * Finds the posters of the messages
	 *
	 * @param int[] $msg_list - All the messages we want to find the posters
	 * @param int $limit - There are only so many topics
	 *
	 * @return bool|AbstractResult
	 */
	public function loadMessagesRequest($msg_list, $limit)
	{
		global $modSettings;

		return $this->_db->query('', '
			SELECT
				m.id_msg, m.subject, m.poster_name, m.poster_email, m.poster_time, m.id_member, m.icon, m.poster_ip,
				m.body, m.smileys_enabled, m.modified_time, m.modified_name, first_m.id_msg AS id_first_msg,
				first_m.subject AS first_subject, first_m.icon AS first_icon, first_m.poster_time AS first_poster_time,
				first_mem.id_member AS first_id_member,
				COALESCE(first_mem.real_name, first_m.poster_name) AS first_display_name,
				COALESCE(first_mem.member_name, first_m.poster_name) AS first_member_name,
				last_m.id_msg AS id_last_msg, last_m.poster_time AS last_poster_time, last_mem.id_member AS last_id_member,
				COALESCE(last_mem.real_name, last_m.poster_name) AS last_display_name,
				COALESCE(last_mem.member_name, last_m.poster_name) AS last_member_name,
				last_m.icon AS last_icon, last_m.subject AS last_subject,
				t.id_topic, t.is_sticky, t.locked, t.id_poll, t.num_replies, t.num_views, t.num_likes,
				b.id_board, b.name AS bname, c.id_cat, c.name AS cat_name
			FROM {db_prefix}messages AS m
				INNER JOIN {db_prefix}topics AS t ON (t.id_topic = m.id_topic)
				INNER JOIN {db_prefix}boards AS b ON (b.id_board = t.id_board)
				INNER JOIN {db_prefix}categories AS c ON (c.id_cat = b.id_cat)
				INNER JOIN {db_prefix}messages AS first_m ON (first_m.id_msg = t.id_first_msg)
				INNER JOIN {db_prefix}messages AS last_m ON (last_m.id_msg = t.id_last_msg)
				LEFT JOIN {db_prefix}members AS first_mem ON (first_mem.id_member = first_m.id_member)
				LEFT JOIN {db_prefix}members AS last_mem ON (last_mem.id_member = first_m.id_member)
			WHERE m.id_msg IN ({array_int:message_list})' . ($modSettings['postmod_active'] ? '
				AND m.approved = {int:is_approved}' : '') . '
			ORDER BY FIND_IN_SET(m.id_msg, {string:message_list_in_set})
			LIMIT {int:limit}',
			[
				'message_list' => $msg_list,
				'is_approved' => 1,
				'message_list_in_set' => implode(',', $msg_list),
				'limit' => $limit,
			]
		);
	}

	/**
	 * Did the user find any message at all?
	 *
	 * @param AbstractResult $messages_request holds a query result
	 *
	 * @return bool
	 */
	public function noMessages($messages_request)
	{
		return $messages_request->num_rows() === 0;
	}

	/**
	 * Sets the query, calls the searchQuery method of the API in use
	 *
	 * @param Standard $searchAPI
	 * @return array
	 */
	public function searchQuery($searchAPI)
	{
		$this->_searchAPI = $searchAPI;
		$searchAPI->setExcludedPhrases($this->_excludedPhrases);
		$searchAPI->setWeightFactors($this->_weightFactors);
		$searchAPI->useTemporary($this->_createTemporary);
		$searchAPI->setSearchArray($this->_searchArray);
		if ($searchAPI->supportsExtended())
		{
			return $searchAPI->searchQuery($this->_searchArray->getSearchArray(), $this->_excludedIndexWords, $this->_participants);
		}

		return $searchAPI->searchQuery($this->searchWords(), $this->_excludedIndexWords, $this->_participants);
	}

	/**
	 * Builds the array of words for the query
	 */
	public function searchWords()
	{
		global $modSettings, $context;

		if (count($this->_searchWords) > 0)
		{
			return $this->_searchWords;
		}

		$orParts = [];
		$this->_searchWords = [];
		$searchArray = $this->_searchArray->getSearchArray();
		$excludedWords = $this->_searchArray->getExcludedWords();

		// All words/sentences must match.
		if (!empty($searchArray) && empty($this->_searchParams['searchtype']))
		{
			$orParts[0] = $searchArray;
		}
		// Any word/sentence must match.
		else
		{
			foreach ($searchArray as $index => $value)
			{
				$orParts[$index] = [$value];
			}
		}

		// Make sure the excluded words are in all or-branches.
		foreach (array_keys($orParts) as $orIndex)
		{
			foreach ($excludedWords as $word)
			{
				$orParts[$orIndex][] = $word;
			}
		}

		// Determine the or-branches and the fulltext search words.
		foreach (array_keys($orParts) as $orIndex)
		{
			$this->_searchWords[$orIndex] = [
				'indexed_words' => [],
				'words' => [],
				'subject_words' => [],
				'all_words' => [],
				'complex_words' => [],
			];

			$this->_searchAPI->setExcludedWords($excludedWords);

			// Sort the indexed words (large words -> small words -> excluded words).
			usort($orParts[$orIndex], [$this->_searchAPI, 'searchSort']);

			foreach ($orParts[$orIndex] as $word)
			{
				$is_excluded = in_array($word, $excludedWords, true);
				$this->_searchWords[$orIndex]['all_words'][] = $word;
				$subjectWords = text2words($word);

				if (!$is_excluded || count($subjectWords) === 1)
				{
					$this->_searchWords[$orIndex]['subject_words'] = array_merge($this->_searchWords[$orIndex]['subject_words'], $subjectWords);

					if ($is_excluded)
					{
						$this->_excludedSubjectWords = array_merge($this->_excludedSubjectWords, $subjectWords);
					}
				}
				else
				{
					$this->_excludedPhrases[] = $word;
				}

				// Have we got indexes to prepare?
				$this->_searchAPI->prepareIndexes($word, $this->_searchWords[$orIndex], $this->_excludedIndexWords, $is_excluded, $this->_excludedSubjectWords);
			}

			// Search_force_index requires all AND parts to have at least one fulltext word.
			if (!empty($modSettings['search_force_index']) && empty($this->_searchWords[$orIndex]['indexed_words']))
			{
				$context['search_errors']['query_not_specific_enough'] = true;
				break;
			}

			if ($this->_searchParams->subject_only && empty($this->_searchWords[$orIndex]['subject_words']) && empty($this->_excludedSubjectWords))
			{
				$context['search_errors']['query_not_specific_enough'] = true;
				break;
			}

			// Make sure we aren't searching for too many indexed words.
			$this->_searchWords[$orIndex]['indexed_words'] = array_slice($this->_searchWords[$orIndex]['indexed_words'], 0, 7);
			$this->_searchWords[$orIndex]['subject_words'] = array_slice($this->_searchWords[$orIndex]['subject_words'], 0, 7);
			$this->_searchWords[$orIndex]['words'] = array_slice($this->_searchWords[$orIndex]['words'], 0, 4);
		}

		return $this->_searchWords;
	}

	/**
	 * Returns the number of results obtained from the query.
	 *
	 * @return int
	 */
	public function getNumResults()
	{
		return $this->_searchAPI->getNumResults();
	}

	/**
	 * Get the participants of the event.
	 *
	 * @return array The participants of the event.
	 */
	public function getParticipants()
	{
		return $this->_participants;
	}
}
