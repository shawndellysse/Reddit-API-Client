<?php

namespace RedditApiClient;

require_once 'Account.php';
require_once 'Comment.php';
require_once 'HttpRequest.php';
require_once 'HttpResponse.php';
require_once 'Link.php';
require_once 'RedditException.php';
require_once 'Subreddit.php';

/**
 * Reddit 
 *
 * The main class of the API client, handling its state and the sending of
 * queries to the API 
 * 
 * @author    Henry Smith <henry@henrysmith.org> 
 * @copyright 2011 Henry Smith
 * @license   GPLv2.0
 * @package   Reddit API Client
 * @version   0.00
 */
class Reddit {

	/**
	 * If logged in, stores the value of the reddit_session cookie
	 * 
	 * @access private
	 * @var    string
	 */
	private $sessionCookie;

	/**
	 * Stores the last seen modhash value
	 *
	 * The modhash is an anti-XSRF measure. It's a value returned with each
	 * set of data, different each time, that must be passed along with the
	 * next request.
	 * 
	 * @access private
	 * @var    string
	 */
	private $modHash;

	/**
	 * Creates the API client instance
	 *
	 * If given a username and password, will attempt to login.
	 * 
	 * @access public
	 * @param  string $username [optional]  The username to login with
	 * @param  string $password [optional]  The password to login with
	 */
	public function __construct($username = null, $password = null)
	{
		if ($username && $password && !$this->login($username, $password)) {
			$message = 'Unable to login to Reddit';
			$code    = RedditException::UNABLE_TO_LOGIN;
			throw new RedditException($message, $code);
		}
	}

	/**
	 * Tries to login to Reddit
	 * 
	 * @access public
	 * @return boolean
	 */
	public function login($username, $password)
	{
		$request = new HttpRequest;
		$request->setUrl('http://www.reddit.com/api/login');
		$request->setHttpMethod('POST');
		$request->setPostVariable('user', $username);
		$request->setPostVariable('passwd', $password);

		$response = $request->getResponse();
		$setCookie = $response->getHeader('Set-Cookie');

		if (!preg_match('/reddit_session=([^;]+);/', $setCookie, $matches)) {
			return false;
		}

		$cookie = $matches[1];

		$this->sessionCookie = $cookie;

		return true;
	}

	/**
	 * Sends a request to Reddit and returns the response received
	 * 
	 * @access public
	 * @param  string $verb  'GET', 'POST', ...
	 * @param  string $url   'http://www.reddit.com/comments/6nw57.json'
	 * @param  string $body 
	 * @return array
	 */
	public function getData($verb, $url, $body = '')
	{
		$request = new HttpRequest;
		$request->setUrl($url);
		$request->setHttpMethod($verb);

		if ($verb === 'POST' && is_array($body)) {
			foreach ($body as $name => $value) {
				$request->setPostVariable($name, $value);
			}
		}

		if ($this->sessionCookie !== null) {
			$request->setCookie('reddit_session', $this->sessionCookie);
		}

		$response = $request->getResponse();

		if (!($response instanceof HttpResponse)) {
			return null;
		}

		$responseBody = $response->getBody();
		$response = json_decode($responseBody, true);

		if (isset($response['data']['modhash'])) {
			$this->modHash = $response['data']['modhash'];
		} elseif (isset($response[0]['data']['modhash'])) {
			$this->modHash = $response[0]['data']['modhash'];
		}

		return $response;
	}

	/**
	 * Fetches and returns the link with the given ID
	 * 
	 * @access public
	 * @param  string $linkId 
	 * @return \RedditApiClient\Link
	 */
	public function getLink($linkId, $withComments = false)
	{
		$verb = 'GET';

		if ($withComments) {
			$url = "http://www.reddit.com/comments/{$linkId}.json";
		} else {
			$url = "http://www.reddit.com/by_id/t3_{$linkId}.json";
		}

		$response = $this->getData($verb, $url);

		$link = null;

		if (!$withComments && isset($response['data']['children'][0])) {

			$link = new Link($this);
			$link->setData($response['data']['children'][0]['data']);

		} elseif ($withComments && isset($response[0]['data']['children'][0]['data'])) {
			
			$link = new Link($this);
			$link->setData($response[0]['data']['children'][0]['data']);

		}

		$comments = array();

		if (isset($response[1]['data']['children'])) {

			foreach ($response[1]['data']['children'] as $data) {

				$comment = new Comment($this);
				$comment->setData($data['data']);

				if (isset($comment['author'])) {
					$comments[] = $comment;
				}
			}
		}

		if (($link instanceof Link) && $withComments) {
			$link->setComments($comments);
		}

		return $link;

	}

	/**
	 * Posts a comment in reply to a link or comment
	 * 
	 * @access public
	 * @param  string $parentId 
	 * @param  string $text 
	 * @return boolean
	 */
	public function postComment($parentId, $text)
	{
		if (!$this->isLoggedIn()) {
			$message = 'Cannot post a comment without a valid login';
			$code    = RedditException::LOGIN_REQUIRED;
			throw new RedditException($message, $code);
		}

		$verb = 'POST';
		$url  = 'http://www.reddit.com/api/comment';
		$data = array(
			'thing_id' => $parentId,
			'text'     => $text,
			'uh'       => $this->modHash,
		);

		$response = $this->getData($verb, $url, $data);

		return true;
	}

	/**
	 * Casts a vote for a comment or link
	 *
	 * @access public
	 * @param  string  $thingId 
	 * @param  integer $direction  1 for upvote, -1 for down, 0 to remove vote
	 * @return boolean
	 */
	public function vote($thingId, $direction)
	{
		if (!$this->isLoggedIn()) {
			$message = 'Cannot vote without a valid login';
			$code    = RedditException::LOGIN_REQUIRED;
			throw new RedditException($message, $code);
		}

		$verb = 'POST';
		$url  = 'http://www.reddit.com/api/vote';
		$data = array(
			'thing_id' => $thingId,
			'dir'      => $direction,
			'uh'       => $this->modHash,
		); 

		$response = $this->getData($verb, $url, $data);

		if (empty($response)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Returns an array of the links in a subreddit
	 * 
	 * @access public
	 * @param  string $subredditName  Plain-text name
	 * @return array
	 */
	public function getLinksBySubreddit($subredditName)
	{
		$verb = 'GET';
		$url  = "http://www.reddit.com/r/{$subredditName}.json";

		$response = $this->getData($verb, $url);

		$links = array();

		foreach ($response['data']['children'] as $child) {

			$link = new Link($this);
			$link->setData($child['data']);

			$links[] = $link;
			
		}

		return $links;
	}

	/**
	 * Indicates whether the client is logged in as a Reddit user
	 * 
	 * @access public
	 * @return boolean
	 */
	public function isLoggedIn()
	{
		if ($this->sessionCookie === null) {
			return false;
		}

		return true;
	}

	/**
	 * Fetches and returns an array of the subreddits to which the logged-in user
	 * is subscribed
	 * 
	 * @access public
	 * @return array
	 */
	public function getMySubreddits()
	{
		if (!$this->isLoggedIn()) {
			$message = 'No user is logged in to list subreddit subscriptions';
			$code    = RedditException::LOGIN_REQUIRED;
			throw new RedditException($message, $code);
		}

		$verb = 'GET';
		$url  = 'http://www.reddit.com/reddits/mine.json';

		$response = $this->getData($verb, $url);

		$subreddits = array();

		foreach ($response['data']['children'] as $child) {

			$subreddit = new Subreddit($this);
			$subreddit->setData($child['data']);

			$subreddits[] = $subreddit;

		}

		return $subreddits;
	}

	/**
	 * Saves a link
	 * 
	 * @access public
	 * @param  string $thingId 
	 * @return boolean
	 */
	public function save($thingId)
	{
		if (!$this->isLoggedIn()) {
			$message = 'Cannot save posts without being logged in';
			$code    = RedditException::LOGIN_REQUIRED;
			throw new RedditException($message, $code);
		}

		$verb = 'POST';
		$url  = 'http://www.reddit.com/api/save';
		$data = array(
			'id' => $thingId,
			'uh' => $this->modHash,
		);

		$response = $this->getData($verb, $url, $data);

		if (empty($response)) {
			return true;
		} else {
			return false;
		}

	}

	/**
	 * Unsaves a link
	 * 
	 * @access public
	 * @param  string $thingId 
	 * @return boolean
	 */
	public function unsave($thingId)
	{
		if (!$this->isLoggedIn()) {
			$message = 'Cannot unsave posts without being logged in';
			$code    = RedditException::LOGIN_REQUIRED;
			throw new RedditException($message, $code);
		}

		$verb = 'POST';
		$url  = 'http://www.reddit.com/api/unsave';
		$data = array(
			'id' => $thingId,
			'uh' => $this->modHash,
		);

		$response = $this->getData($verb, $url, $data);

		if (empty($response)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Hides a link
	 * 
	 * @access public
	 * @param  string $thingId 
	 * @return boolean
	 */
	public function hide($thingId)
	{
		if (!$this->isLoggedIn()) {
			$message = 'Cannot hide posts without being logged in';
			$code    = RedditException::LOGIN_REQUIRED;
			throw new RedditException($message, $code);
		}

		$verb = 'POST';
		$url  = 'http://www.reddit.com/api/hide';
		$data = array(
			'id' => $thingId,
			'uh' => $this->modHash,
		);

		$response = $this->getData($verb, $url, $data);

		if (empty($response)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Unhides a link
	 * 
	 * @access public
	 * @param  string $thingId 
	 * @return boolean
	 */
	public function unhide($thingId)
	{
		if (!$this->isLoggedIn()) {
			$message = 'Cannot hide posts without being logged in';
			$code    = RedditException::LOGIN_REQUIRED;
			throw new RedditException($message, $code);
		}

		$verb = 'POST';
		$url  = 'http://www.reddit.com/api/unhide';
		$data = array(
			'id' => $thingId,
			'uh' => $this->modHash,
		);

		$response = $this->getData($verb, $url, $data);

		if (empty($response)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Fetches and returns a user account
	 * 
	 * @access public
	 * @param  string $name 
	 * @return \RedditApiClient\Account
	 */
	public function getAccountByUsername($name)
	{
		$verb = 'GET';
		$url  = "http://www.reddit.com/user/{$name}/about.json";
		
		$response = $this->getData($verb, $url);

		$account = new Account($this);
		$account->setData($response['data']);

		return $account;
	}

	/**
	 * Returns an array of links posted by the account with the given username
	 * 
	 * @access public
	 * @param  string $name 
	 * @return array
	 */
	public function getLinksByUsername($name)
	{
		$verb = 'GET';
		$url  = "http://www.reddit.com/user/{$name}.json";
		
		$response = $this->getData($verb, $url);

		$links = array();

		foreach ($response['data']['children'] as $child) {

			$link = new Link($this);
			$link->setData($child['data']);

			$links[] = $link;

		}

		return $links;
	}

}

