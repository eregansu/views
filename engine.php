<?php

/*
 * Copyright 2012 Mo McRoberts.
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

abstract class ViewsEngine
{
	protected $info;

	public function __construct($info)
	{
		$this->info = $info;
	}
	
	public function outputFile($name, $matchedExt, $config)
	{
		$ext = isset($config['global']['outext']) ? $config['global']['outext'] : '.phtml';
		return substr($name, 0, 0 - strlen($matchedExt)) . $ext;
	}

	abstract public function process($input, $output, $config);

	protected function locateModule($name, $callerPath = null, $reportErrors = true)
	{
		if(file_exists(MODULES_ROOT . $name))
		{
			return MODULES_ROOT . $name . '/';
		}
		if(file_exists(INSTANCE_ROOT . $name))
		{
			return INSTANCE_ROOT . $name . '/';
		}
		if(file_exists(PLATFORM_ROOT . $name))
		{
			return PLATFORM_ROOT . $name . '/';
		}
		if($callerPath !== null && file_exists($callerPath . '/' . $name))
		{
			return realpath($callerPath . '/' . $name) . '/';
		}
		if($reportErrors)
		{
			trigger_error('Unable to locate module "' . $name . '"', E_USER_ERROR);
		}
		return null;
	}
}
