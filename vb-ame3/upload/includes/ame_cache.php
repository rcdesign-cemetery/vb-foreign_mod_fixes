<?php

/**
 * AME - The Automatic Media Embedder 3.0.1
 * Copyright ©2009-2010 All rights reserved by sweetsquared.com
 * This code may not be used in whole or part without explicit written
 * permission from Samuel Sweet [samuel@sweetsquared.com].
 * You may not distribute this or any of the associated files in whole or significant part
 * without explicit written permission from Samuel Sweet [samuel@sweetsquared.com]
 */

if (!defined("AME"))
{
	
	die("You cannot directly call this file from outside of the AME system.");
	
}

/**
 * This baby takes an array (i.e. data structure)
 * and writes it out into a php file which can be 'included'
 * into code instead of querying the DB and building the array up.
 * Note: This class asserts all data is correct!
 */
class AME_file_cache
{
	
	/**
	 * Full path to writeable folder where we dump our load
	 *
	 * @var unknown_type
	 */
	var $path;
	
	/**
	 * The name of the file we will overwrite. We are appending .php
	 *
	 * @var unknown_type
	 */
	var $filename;
	
	/**
	 * The array we will be flattening out
	 *
	 * @var unknown_type
	 */
	var $data;
	
	/**
	 * The textual representation of the array
	 *
	 * @var unknown_type
	 */
	var $content;
	
	/**
	 * header. Used to include a defined check by default
	 *
	 * @var unknown_type
	 */
	var $header;	
	
	/**
	 * Constructor
	 *
	 * @param string 	$path		writeable folder
	 * @param string 	$filename	filename to write
	 * @return AME_file_cache
	 */
	function AME_file_cache($path = '', $filename = '')
	{
		
		$this->path 	= $path;
		$this->filename	= $filename;
		$this->header	= "if (!defined(AME))\n{\n\tdie(\"You cannot directly call this file from outside of the AME system.\");\n}";
	
	}
	
	/**
	 * Verifies path is indeed a valid directory
	 *
	 * @param 	string 	$path
	 * @return 	boolean
	 */
	function verify_path_exists($path = '')
	{
		
		if (!$path && $this->path)
		{
			
			$path = $this->path;
			
		}
		
		return dir($path);
				
	}	
	
	/**
	 * Tests path is writeable
	 *
	 * @param	string $path to test
	 * @return	boolean
	 */	
	function verify_path_writeable($path = '')
	{
		
		$result = false;
		
		if (!$path && $this->path)
		{
			
			$path = $this->path;
			
		}
		
		$this->write(
		
			array(
			
				'path' 		=> $path,
				'filename' 	=> 'testiname',
				'content' 	=> '//yo there'
				
			)
			
		);

		if (file_exists($path . "/testiname.php"))
		{
			
			unlink($path . "/testiname.php");
			$result = true;
			
		}
	
		return $result;
		
	}
	
    /**
     * Creates textual version of an array
     *
     * @param array $array	array to convert
     * @param string $title	i.e. $ameinfo
     * @return string		textual content of array
     */
    function save($array, $root)
    {
    	
    	$this->data = $array;
    	$this->root = $root;
    	
		if (is_array($array))
		{
			
			$return	= "<?php\n$root = array(\n";
			$return .= $this->write_array_sub($this->data, 1);
			$return .=");\n?>";
			
		}
            
		$this->content = $return;
		$this->write();
		
    }

    /**
     * designed to be overloaded to walk nested arrays
     *
     * @param array $array		array to walk
     * @param int 	$depth		depth of array (for recursion)
     * @return string			textual representation of array and children
     */
    protected function write_array_sub($array, $depth = 1)
    {
    	
            $pre 	= str_pad("\t", $depth * 3);
            $return = "";

            foreach($array as $key => $value)
            {
            	
                    if (is_array($value))
                    {
                    	
                            $return .= "$pre'$key' => array(\n";
                            $return .= $this->write_array_sub($value, ($depth + 1));
                            $return .= "$pre),\n";
                            
                    }
                    else
                    {
                    	
                            $return .= "$pre'$key'\t\t\t=>'" . str_replace(array("\'", "'"), array("\\\'", "\'"), $value) . "',\n";
                    
                    }
            }
            
            return $return;
    }
    
    /**
     * Wrties $data to the $filename.php in the $path.
     * @param optional array that can override internal variables
     */
    protected function write($info = array())
    {
    	
    	$path 		= !$info['path'] && $this->path ? $this->path : $info['path'];
    	$content 	= !$info['content'] && $this->content ? $this->content : $info['content'];
    	$filename	= !$info['filename'] && $this->filename ? $this->filename : $info['filename'];
    	
    	if ($content)
    	{
    		
            if (is_dir($path))
            {
            	
                    $fput = @fopen($path . $filename . ".php", "w");
                    @fputs ($fput, $content);
                    @fclose($fput);
                    
            }
            
    	}
    	
    }    
	
}


?>