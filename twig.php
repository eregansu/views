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

/*
 * This is an Eregansu Views engine for processing Twig templates:
 *
 * http://twig.sensiolabs.org/
 */

class TwigViewsEngine extends ViewsEngine
{
	protected $twig = null;
	protected $loader = null;	
	protected $twigPath = null;
	  
	public function process($in, $out, $config)
	{		
		if(!isset($this->twig))
		{
			$this->twigPath = $this->locateModule('Twig', dirname(__FILE__));
			require_once($this->twigPath . 'lib/Twig/Autoloader.php');
			Twig_Autoloader::register();
			$this->loader = new Twig_Loader_String();
			$this->twig = new Twig_Environment($this->loader);
		}
		$path = realpath($in);
		$name = md5($path);
		$result = $this->twig->compileSource(file_get_contents($path), $path);
		if(!strncmp($result, "<?php\n", 6))
		{
			$preamble = array(
				'<?php',
				'require_once("' . addslashes($this->twigPath . 'lib/Twig/Autoloader.php') . '");',
				'if(!class_exists("Twig_Template"))',
				'{',
				"\t" . 'Twig_Autoloader::register();',
				'}',
				);
			$postamble = array(
				'if(!isset($twig_environment))',
				'{',
				"\t" . '$twig_loader = new Twig_Loader_String();',
				"\t" . '$twig_environment = new Twig_Environment($twig_loader);',
				'}',
				'$twig_template_' . $name . ' = new __TwigTemplate_' . $name . '($twig_environment);',
				'echo $twig_template_' . $name . '->render($this->vars);',
				);
			$result = implode("\n", $preamble) . substr($result, 6) . implode("\n", $postamble) . "\n";
			file_put_contents($out, $result);
		}
		else
		{
			trigger_error('Unexpected preamble in template processing result', E_USER_ERROR);
		}
	}
}
