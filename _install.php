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
if (!defined('DC_CONTEXT_ADMIN')) return;

$package_version = $core->plugins->moduleInfo('defensio','version');
$installed_version = $core->getVersion('defensio');
if (version_compare($installed_version,$package_version,'>=')) {
	return;
}

$core->setVersion('defensio',$package_version);
return true;
?>