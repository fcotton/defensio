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

global $__autoload, $core;

$__autoload['defensio']			= dirname(__FILE__).'/inc/class.defensio.php';
$__autoload['dcFilterDefensio']	= dirname(__FILE__).'/inc/class.dc.filter.defensio.php';

$core->spamfilters[] = 'dcFilterDefensio';
?>