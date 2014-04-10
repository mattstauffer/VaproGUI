<?php

/**
 * Die & Dump
 * 
 * @param  mixed $var
 */
function dd($var)
{
	echo '<pre>';
	var_dump($var);
	echo '</pre>';
	exit;
}