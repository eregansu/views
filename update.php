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

require_once(dirname(__FILE__) . '/engine.php');

/* This class is responsible for generating PHP template files from sources
 * (which may be in any supported template language).
 *
 * It is an Eregansu CommandLine class, and so intended to be invoked
 * using the 'cli update-views' command (the route for which is installed
 * by install.php).
 *
 * Template engines can be registered by adding their details to the
 * global $TEMPLATE_ENGINES array.
 *
 * By default, template processing will recurse into the 'public' directory
 * and process any files which match known template file name patterns within
 * it, as well as any subdirectories.
 */
class ViewsUpdate extends CommandLine
{
	public $engines;	
	public $defaults = array(
		'global' => array(
			'out' => '.phtml',
			),
		);

	protected $extensions;

	protected $options = array(
		'verbose' => array('value' => 'v', 'description' => 'Output progress information'),
		'force' => array('value' => 'f', 'description' => 'Forcibly rebuild templates'),
		);

	public function __construct()
	{
		global $TEMPLATE_ENGINES;

		parent::__construct();
		if(!isset($TEMPLATE_ENGINES))
		{
			$TEMPLATE_ENGINES = array();
		}
		$this->engines =& $TEMPLATE_ENGINES;
		$this->initExtensions();
	}

	protected function initExtensions()
	{
		foreach($this->engines as $k => $info)
		{
			if(!isset($info['ext']))
			{
				continue;
			}
			$exts = is_array($info['ext']) ? $info['ext'] : array($info['ext']);
			foreach($exts as $i => $ext)
			{
				if(substr($ext, 0, 1) != '.')
				{
					$ext = '.' . $ext;
				}
				$exts[$i] = $ext;
				$this->extensions[$ext] =& $this->engines[$k];
			}
			$this->engines[$k]['ext'] = $exts;
		}
		ksort($this->extensions);
	}

	public function main($args)
	{
		/* Reset the umask to something permitting world read */
		umask(022);
		$this->scanPath('.');
	}

	protected function scanPath($path, $config = null)
	{
		static $processed = array();

		if($config === null)
		{
			$config = array();
		}
		$real = realpath($path);
		if(isset($processed[$real]))
		{
			return false;
		}
		$processed[$real] = true;
		if(substr($path, -1) !== '/')
		{
			$path .= '/';
		}
		if(file_exists($path . 'views.ini'))
		{
			$local = parse_ini_file($path . 'views.ini', true);
			if(is_array($local))
			{
				/* Perform a limited-depth recursive merge */
				foreach($local as $section => $values)
				{
					if(isset($config[$section]))
					{
						$config[$section] = array_merge($config[$section], $values);
					}
					else
					{
						$config[$section] = $values;
					}
				}
			}
		}
		$d = opendir($path);
		while(false !== ($de = readdir($d)))
		{
			if(substr($de, 0, 1) === '.')
			{
				continue;
			}
			/* Recurse into subdirectories */
			if(is_dir($path . $de))
			{
				$this->scanPath($path . $de, $config);
				continue;
			}
			/* Skip output files */
			$l = strlen(@$config['global']['outext']);
			if($l)
			{
				if(!strcmp(substr($de, 0 - $l), $config['global']['outext']))
				{
					continue;
				}
			}
			$this->processFile($path . $de, $config);
		}
		closedir($d);
	}

	protected function processFile($pathname, $config)
	{
		/* A given file, whose full path is $pathname, and which
		 * is within $directory, check whether it should be
		 * processed and do so if needed.
		 */
		$engine = $this->engineForPath($pathname, $ext);
		if($engine === null)
		{
			return false;
		}
		$out = $engine->outputFile($pathname, $ext, $config);
		$rebuild = !empty($this->options['force']['flag']);
		if(!$rebuild && !file_exists($out))
		{
			$rebuild = true;
		}
		if(!$rebuild)
		{
			$inInfo = stat($pathname);
			$outInfo = stat($out);
			if($inInfo['mtime'] > $outInfo['mtime'])
			{
				$rebuild = true;
			}
		}
		if(!$rebuild)
		{
			if(!empty($this->options['verbose']['flag']))
			{
				fprintf($this->request->stderr, "%s => %s (skipped, source is unchanged)\n", basename($pathname), basename($out));
			}
			return false;
		}
		if(!empty($this->options['verbose']['flag']))
		{
			fprintf($this->request->stderr, "%s => %s\n", basename($pathname), basename($out));
		}
		return $engine->process($pathname, $out, $config);
	}

	protected function engineForPath($pathname, &$matchedExt)
	{
		static $instances = array();
		
		$matchedExt = null;
		foreach($this->extensions as $ext => $info)
		{
			$l = strlen($ext);
			if(!strcmp($ext, substr($pathname, 0 - $l)))
			{
				$matchedExt = $ext;
				if(isset($instances[$ext]))
				{
					return $instances[$ext];
				}
				if(!isset($info['class']))
				{
					trigger_error('No class defined for processor for "' . $ext . '"', E_USER_ERROR);
					return;
				}
				Loader::load($info);
				$class = $info['class'];
				$inst = new $class($info);
				$instances[$ext] = $inst;
				return $inst;
			}
		}
		return;
	}
}
