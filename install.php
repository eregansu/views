<?php

/*
 * Copyright 2013 Mo McRoberts.
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

class BuiltinViewsModuleInstall extends ModuleInstaller
{
	public $coexists = true;

	public function writeAppConfig($file, $isSoleWebModule = false, $chosenSoleWebModule = null)
	{
		fwrite($file, "\$CLI_ROUTES['update-views'] = array('file' => '" . addslashes(dirname(__FILE__)) . "/update.php', 'class' => 'ViewsUpdate', 'description' => 'Rebuild templates within the current directory');\n");
		fwrite($file, "\$TEMPLATE_ENGINES['twig'] = array('file' => '" . addslashes(dirname(__FILE__)) . "/twig.php', 'ext' => 'twig', 'class' => 'TwigViewsEngine');\n");
	}
}
