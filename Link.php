<?php

namespace RedditApiClient;

require_once 'Entity.php';

/**
 * Link 
 *
 * Represents a reddit post or submission
 *
 * The following is an example of the sort of data links contain:
 * 
 * [data] => Array
 * (
 *     [domain]      => code.reddit.com
 *     [media_embed] => Array
 * 	(
 * 	)
 * 
 *     [levenshtein]            =>
 *     [subreddit]              => programming
 *     [selftext_html]          =>
 *     [selftext]               =>
 *     [likes]                  =>
 *     [saved]                  =>
 *     [id]                     => 6nw57
 *     [clicked]                =>
 *     [title]                  => Reddit has gone Open Source !!
 *     [media]                  =>
 *     [score]                  => 1435
 *     [over_18]                =>
 *     [hidden]                 =>
 *     [thumbnail]              =>
 *     [subreddit_id]           => t5_2fwo
 *     [author_flair_css_class] =>
 *     [downs]                  => 276
 *     [is_self]                =>
 *     [permalink]              => /r/programming/comments/6nw57/reddit_has_gone_open_source/
 *     [name]                   => t3_6nw57
 *     [created]                => 1213794727
 *     [url]                    => http://code.reddit.com/
 *     [author_flair_text]      =>
 *     [author]                 => ropiku
 *     [created_utc]            => 1213794727
 *     [num_comments]           => 212
 *     [ups]                    => 1711
 * )
 *
 * 
 * @author    Henry Smith <henry@henrysmith.org> 
 * @copyright 2011 Henry Smith
 * @license   GPLv2.0
 * @package   Reddit API Client
 * @version   0.00
 */
class Link extends Entity {

	/**
	 * An array of the comments in reply to the link
	 * 
	 * @access private
	 * @var    array
	 */
	private $comments = null;

	/**
	 * Returns the link's unique 't3_*' ID
	 * 
	 * @access public
	 * @return string
	 */
	public function getThingId()
	{
		return $this['name'];
	}

	/**
	 * Returns the unique ID of the link
	 * 
	 * @access public
	 * @return string
	 */
	public function getId()
	{
		return $this['id'];
	}

	/**
	 * Returns the number of upvotes received by the link
	 * 
	 * @access public
	 * @return integer
	 */
	public function getUpvotes()
	{
		return $this['ups'];
	}

	/**
	 * Returns the number of downvotes received by the link
	 * 
	 * @access public
	 * @return integer
	 */
	public function getDownvotes()
	{
		return $this['downs'];
	}

	/**
	 * Returns the score of the link
	 * 
	 * Score is a function of upvotes, downvotes, and time since creation
	 * 
	 * @access public
	 * @return integer
	 */
	public function getScore()
	{
		return $this['score'];
	}

	/**
	 * Returns the number of comments made about the link
	 * 
	 * @access public
	 * @return integer
	 */
	public function countComments()
	{
		return $this['num_comments'];
	}

	/**
	 * Returns the username of the user who submitted the link
	 * 
	 * @access public
	 * @return string
	 */
	public function getAuthorName()
	{
		return $this['author'];
	}

	/**
	 * Returns the title of the link
	 * 
	 * @access public
	 * @return string
	 */
	public function getTitle()
	{
		return $this['title'];
	}

	/**
	 * Returns the URL of the link
	 *
	 * If the link is a self-post, the URL will contain the text body of the post
	 * instead.
	 * 
	 * @access public
	 * @return string
	 */
	public function getUrl()
	{
		return $this['url'];
	}

	/**
	 * Returns the plain-text version of the text given as part of a self-post
	 * 
	 * @access public
	 * @return string
	 */
	public function getSelfText()
	{
		return $this['selftext'];
	}

	/**
	 * Returns the array of comments in reply to the link
	 * 
	 * @access public
	 * @return array
	 */
	public function getComments()
	{
		if (!is_array($this->comments)) {

			$this->comments = array();

			$linkId = $this->getId();

			$link = $this->reddit->getLink($linkId, true);

			$this->comments = $link->getComments();

		}

		return $this->comments;
	}

	/**
	 * Indicates whether the link is a self-post or not
	 * 
	 * @access public
	 * @return boolean
	 */
	public function isSelfPost()
	{
		if ($this['is_self']) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Stores the given array of comments
	 * 
	 * @access public
	 * @param  array $comments 
	 */
	public function setComments(array $comments)
	{
		$this->comments = $comments;
	}

	/**
	 * Casts a vote on the link
	 * 
	 * @access public
	 * @param  integer $direction  1 for an upvote, -1 for down, 0 to remove votes
	 * @return boolean
	 */
	public function vote($direction)
	{
		$thingId = $this->getThingId();

		return $this->reddit->vote($thingId, $direction);
	}

	/**
	 * Posts a comment on the link
	 * 
	 * @access public
	 * @param  string $text 
	 * @return boolean
	 */
	public function reply($text)
	{
		$thingId = $this->getThingId();

		return $this->reddit->postComment($thingId, $text);
	}

	/**
	 * Saves the link
	 * 
	 * @access public
	 * @return boolean
	 */
	public function save()
	{
		$thingId = $this->getThingId();

		return $this->reddit->save($thingId);
	}

	/**
	 * Unsaves the link
	 * 
	 * @access public
	 * @return boolean
	 */
	public function unsave()
	{
		$thingId = $this->getThingId();

		return $this->reddit->unsave($thingId);
	}

	/**
	 * Hides the link
	 * 
	 * @access public
	 * @return boolean
	 */
	public function hide()
	{
		$thingId = $this->getThingId();

		return $this->reddit->hide($thingId);
	}

	/**
	 * Unhides the link
	 * 
	 * @access public
	 * @return boolean
	 */
	public function unhide()
	{
		$thingId = $this->getThingId();

		return $this->reddit->unhide($thingId);
	}

}

