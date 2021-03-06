<?php

namespace RedditApiClient\Test;

require_once '../Link.php';
require_once 'PHPUnit/Framework/TestCase.php';

use \PHPUnit_Framework_TestCase;
use \RedditApiClient\Link;

/**
 * LinkTest 
 *
 * @author    Henry Smith <henry@henrysmith.org> 
 * @copyright 2011 Henry Smith
 * @license   GPLv2.0
 * @package   Reddit API Client
 * @version   0.00
 */
class LinkTest extends PHPUnit_Framework_TestCase {

	private $link;

	public function setUp()
	{
		$this->link = new Link;
	}

	/**
	 * Verifies the correctness of some methods that expose data
	 */
	public function testGetters()
	{
		$this->link->setData(array(
			'id' => 'sdfgh',
			'ups' => 10,
			'downs' => 9,
			'score' => 9001,
			'num_comments' => 235,
			'author' => 'I_RAPE_CATS',
			'title' => 'IAMA unit test AMA',
			'url' => 'Edit: FRONT PAGE OMG! Edit: Its 2AM Im going to bed',
		));

		$this->assertEquals('sdfgh', $this->link->getId());
		$this->assertEquals('10', $this->link->getUpvotes());
		$this->assertEquals('9', $this->link->getDownvotes());
		$this->assertEquals('9001', $this->link->getScore());
		$this->assertEquals('235', $this->link->countComments());
		$this->assertEquals('I_RAPE_CATS', $this->link->getAuthorName());
		$this->assertEquals('IAMA unit test AMA', $this->link->getTitle());
		$this->assertEquals('Edit: FRONT PAGE OMG! Edit: Its 2AM Im going to bed', $this->link->getUrl());
	}

	/**
	 * Verifies that Link can correctly identify if it represents a self-post
	 */
	public function testKnowsIfSelfPost()
	{
		$this->link->setData(array('is_self' => true));
		$this->assertTrue($this->link->isSelfPost());

		$this->link->setData(array('is_self' => false));
		$this->assertFalse($this->link->isSelfPost());
	}

}

