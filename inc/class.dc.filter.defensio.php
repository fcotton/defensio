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

class dcFilterDefensio extends dcSpamFilter
{
	public $name = 'Defensio';
	public $has_gui = true;
	public $active = false;
	
	public function __construct($core)
	{
		parent::__construct($core);
		
		if (defined('DC_DEFENSIO_SUPER') && DC_DEFENSIO_SUPER && !$core->auth->isSuperAdmin()) {
			$this->has_gui = false;
		}
	}
	
	protected function setInfo()
	{
		$this->description = __('Defensio spam filter');
	}
	
	public function getStatusMessage($status,$comment_id)
	{
		return sprintf(__('Filtered by %s.'),$this->guiLink());
	}
	
	private function defensioInit()
	{
		$blog = $this->core->blog;
		
		if (!$blog->settings->defensio_key) {
			return false;
		}
		
		return new defensio($blog->url,$blog->settings->defensio_key);
	}
	
	public function isSpam($type,$author,$email,$site,$ip,$content,$post_id,&$status)
	{
		if (($defensio = $this->defensioInit()) === false) {
			return;
		}
		
		$blog = $this->core->blog;
		
		try {
			$post = $blog->getPosts(array('post_id' => $post_id));
			$resp = $defensio->auditComment(
				$post->getURL(),
				$type,
				$post->getDate('%Y/%m/%d'),
				$author,
				$email,
				$site,
				$content
			);
			
			if ($defensio->isSuccessful($resp)) {
				$status = $resp['signature'].'|'.$resp['spaminess'];
				if ($resp['spam'] == 'true') {
					return true;
				}
			}
		} catch (Exception $e) {
			//die($e->getMessage());
		} # If http or Defensio is dead, we don't need to know it
	}
	
	public function trainFilter($status,$filter,$type,$author,$email,$site,$ip,$content,$rs)
	{
		# We handle only false positive from Defensio
		if ($status == 'spam' && $filter != 'dcFilterDefensio') {
			return;
		}
		
		$f = $status == 'spam' ? 'reportFalseNegatives' : 'reportFalsePositives';
		
		if (($defensio = $this->defensioInit()) === false) {
			return;
		}
		
		try {
			list($signature,$spaminess) = explode('|',$rs->comment_spam_status);
			$defensio->{$f}($signature);
		} catch (Exception $e) {
			//die($e->getMessage());
		} # If http or Defensio is dead, we don't need to know it
	}
	
	public function gui($url)
	{
		$blog = $this->core->blog;
		
		$blog->settings->addNameSpace('defensio');
		$defensio_key = $blog->settings->defensio->defensio_key;
		$defensio_verified = null;
		
		if (isset($_POST['defensio_key'])) {
			try {
				$defensio_key = $_POST['defensio_key'];
				$blog->settings->defensio->put('defensio_key',$defensio_key,'string');
				http::redirect($url.'&up=1');
			}
			catch (Exception $e) {
				$this->core->error->add($e->getMessage());
			}
		}
		
		if ($blog->settings->defensio->defensio_key) {
			try {
				$defensio = new defensio($blog->url,$blog->settings->defensio->defensio_key);
				$defensio_verified = $defensio->validateKey();
			}
			catch (Exception $e) {
				$this->core->error->add($e->getMessage());
			}
		}
		
		$res =
		'<form action="'.html::escapeURL($url).'" method="post">'.
		'<p><label class="classic">'.__('Defensio API key:').' '.
		form::field('defensio_key',12,128,$defensio_key).'</label>';
		
		if ($defensio_verified !== null) {
			if ($defensio_verified) {
				$res .= ' <img src="images/check-on.png" alt="" /> '.__('API key verified');
			}
			else {
				$res .= ' <img src="images/check-off.png" alt="" /> '.__('API key not verified');
			}
		}
		
		$res .= '</p>';
		$res .=
		'<p><a href="http://defensio.com/signup/">'.__('Sign up and get your own API key').'</a></p>'.
		'<p><input type="submit" value="'.__('save').'" />'.
		$this->core->formNonce().'</p>'.
		'</form>';
		
		if ($defensio_verified) {
			$stats = $defensio->getStats();
			
			$res .=
				'<h3>'.__('Defensio Statistics').'</h3>'.
				'<div id="defension-stats">'.
				'<p><strong>'.__('Recent Accuracy').' : '.sprintf('%.2f', 100 * (float)$stats['accuracy']).'%</strong><br/>'.
				__('Spam').' : '.(integer)$stats['spam'].'<br/>'.
				__('Innocent').' : '.(integer)$stats['ham'].'<br/>'.
				__('False negative').' : '.(integer)$stats['false-negatives'].'<br/>'.
				__('False positive').' : '.(integer)$stats['false-positives'].'</p>';
				
			if ($stats['learning'] == 'true') {
				$res .= '<p class="defensio-learning">'.__($stats['learning-status']).'</p>';
			} 
			
			$res .= '</div>';
		}
		return $res;
	}
}
?>