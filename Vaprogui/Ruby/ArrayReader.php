<?php namespace Vaprogui\Ruby;

use Exception;

class ArrayReader
{
	protected $lines;
	protected $first_line;
	protected $last_line;
	protected $data_lines;

	protected $data_defaults_on = array();
	protected $data_defaults_off = array();

	public function __construct(array $lines)
	{
		$this->lines = $lines;

		$this->processLines();
	}

	/**
	 * Process imported lines and create useful data from them
	 * 
	 * @param  array  $lines
	 */
	protected function processLines()
	{
		if (count($this->lines) < 2) {
			throw new Exception('Invalid array.');
		} elseif (count($this->lines) == 2) {
			throw new Exception('To be programmed: return empty array');
		}

		$this->setLineVariables($this->lines);

		$this->processDataLines($this->data_lines);
	}

	/**
	 * Separate lines out
	 * 
	 * @param array $lines
	 */
	protected function setLineVariables(array $lines)
	{
		$this->first_line = reset($lines);
		$this->last_line = end($lines);
		$this->data_lines = array();

		for ($i = 1; $i < count($lines) - 1; $i++) {
			$this->data_lines[] = $lines[$i];
		}
	}

	/**
	 * Process data lines and convert to defaults on/off
	 * 
	 * @param  array $data_lines
	 */
	protected function processDataLines(array $data_lines)
	{
		foreach ($data_lines as $line)
		{
			// Trim spaces and trailing commas
			$line = trim($line);
			$line = trim($line, ',');

			$iscomment = $this->isDataLineComment($line);

			$line = trim($line, '#');
			$line = trim($line, '"');

			if ($iscomment) {
				$this->data_defaults_off[] = $line;
			} else {
				$this->data_defaults_on[] = $line;
			}
		}
	}

	/**
	 * Returns whether data line is a comment
	 * 
	 * @param  string  $line
	 * @return boolean
	 */
	protected function isDataLineComment($line)
	{
		return substr($line, 0, 1) === '#';
	}

	public function getData()
	{
		return array(
			'defaults_on' => $this->data_defaults_on,
			'defaults_off' => $this->data_defaults_off
		);
	}
}