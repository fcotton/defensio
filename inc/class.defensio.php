<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of defensio, a plugin for Dotclear 2.
#
# Copyright (c) 2008-2010 Pep and contributors
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------
if (!defined('DC_RC_PATH')) return;

class defensio extends netHttp
{
	protected $defensio_host = 'api.defensio.com';
	protected $defensio_version = '1.2';
	protected $defensio_path = '/blog/%s/%s/%s.xml';	
	protected $defensio_key = null;
	
	protected static $api = array(
		'validate-key' => array(
			'required' => array(),
			'optional' => array(),
			'response' => array()
		),
		'announce-article' => array(
			'required' => array(
				'article-author',
				'article-author-email',
				'article-title',
				'article-content',
				'permalink'
			),
			'optional' => array(),
			'response' => array()
		),
		'audit-comment' => array(
			'required' => array(
				'user-ip',
				'article-date',
				'comment-author',
				'comment-type'
			),
			'optional' => array(
				'comment-content',
				'comment-author-email',
				'comment-author-url',
				'permalink',
				'referrer',
				'user-logged-in',
				'trusted-user',
				'test-force'
			),
			'response' => array(
				'signature',
				'spam',
				'spaminess'
			)
		),
		'report-false-negatives' => array(
			'required' => array(
				'signatures'
			),
			'optional' => array(),
			'response' => array()
		),
		'report-false-positives' => array(
			'required' => array(
				'signatures'
			),
			'optional' => array(),
			'response' => array()
		),
		'get-stats' => array(
			'required' => array(),
			'optional' => array(),
			'response' => array(
				'accuracy',
				'spam',
				'ham',
				'false-positives',
				'false-negatives',
				'learning',
				'learning-message'
			)
		)
	);
	
	protected $blog_url;
	protected $timeout = 5;
	
	public function __construct($blog_url,$api_key)
	{
		$this->blog_url = $blog_url;
		$this->defensio_key = $api_key;
		
		$this->defensio_path = sprintf($this->defensio_path,$this->defensio_version,'%s',$this->defensio_key);
		
		parent::__construct($this->defensio_host,80);
	}
	
	public function validateKey()
	{
		$this->host = $this->defensio_host;
		$path = sprintf($this->defensio_path,'validate-key');

		if ($this->post($path,array('owner-url' => $this->blog_url),'UTF-8')) {
			return $this->isSuccessful($this->getContent());
		}
		
		return false;
	}
	
	public function getStats()
	{
		return $this->callfunc('get-stats');
	}
	
	public function auditComment($permalink,$type,$post_date,$author,$email,$url,$content)
	{
		$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		$params = array(
			'user-ip' => http::realIP(),
			'article-date' => $post_date,
			'comment-author' => $author,
			'comment-type' => $type,
			'comment-content' => $content,
			'comment-author-email' => $email,
			'comment-author-url' => $url,
			'permalink' => $permalink,
			'referrer' => $referer,
			'user-logged-in' => false,
			'trusted-user' => false
		);
		return $this->callfunc('audit-comment',$params);
	}
	
	public function reportFalseNegatives($signatures)
	{
		if (is_array($signatures)) {
			$signatures = implode(',',$signatures);
		}
		$this->callFunc('report-false-negatives',array('signatures' => $signatures));
		return true;
	}
	
	public function reportFalsePositives($signatures)
	{
		if (is_array($signatures)) {
			$signatures = implode(',',$signatures);
		}
		$this->callFunc('report-false-positives',array('signatures' => $signatures));
		return true;
	}
	
	protected function callFunc($function,$args=array())
	{
		$data = array_merge(array('owner-url' => $this->blog_url),$args);
		$this->checkParams($function,$args);
		
		$this->host = $this->defensio_host;
		$path = sprintf($this->defensio_path,$function);
		
		if (!$this->post($path,$data,'UTF-8')) {
			throw new Exception('HTTP error: '.$this->getError());
		}
		
		return $this->convertResponse($this->getContent());
	}

	protected function checkParams($action,$params)
	{
		if (!array_key_exists($action,self::$api)) {
			throw new Exception('Method '.$action.' does not exist.');
		}
		$required = self::$api[$action]['required'];
		foreach ($required as $req) {
			if(!array_key_exists($req,$params)) {
				throw new Exception("{$req} is a required parameter for method {$action}");
			}
		}
		$args = array_keys($params);
		$supported = array_merge(self::$api[$action]['required'],self::$api[$action]['optional']);
		foreach ($args as $arg) {
			if(!in_array($arg,$supported)) {
				throw new Exception("{$arg} is not a supported parameter for method {$action}");
			}
		}
	}	
	
	protected function convertResponse($response)
	{
		$resp = array();
		$xml = new DOMDocument();
		$xml->loadXML($response);
		$root = $xml->childNodes->item(0);
		
		if($root->hasChildNodes()) {
			foreach($root->childNodes as $node) {
				if($node->nodeType == XML_ELEMENT_NODE) {
					$resp[$node->nodeName] = $node->nodeValue;
				}
			}
		}
		return $resp;
	}
	
	public function isSuccessful($response)
	{
		if (is_array($response)) {
			$resp = $response;
		}
		else {
			$resp = $this->convertResponse($response);
		}
		return empty($resp) ? false : ($resp['status'] == 'success');
	}
}
?>