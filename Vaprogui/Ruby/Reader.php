<?php namespace Vaprogui\Ruby;

use Exception;

class Reader
{
    protected $root_path = '../';
    protected $handle; 
    protected $currently_in_array = false;
    protected $currently_in_object = false;

    protected $file = array();
    protected $imported = array();

    public function __construct($file_path)
    {
        $use_path = $this->root_path . $file_path;

        $this->handle = fopen($use_path, "r");

        if ( ! $this->handle) {
            fclose($this->handle);
            throw new Exception('Error opening file ' . $use_path);
        }

        $this->importFile();
    }

    /**
     * Import the file line-by-line into $this->file
     */
    protected function importFile()
    {
        if ( ! empty($this->file)) {
            throw new Exception("Can't import file more than once.");
        }
        while (($line = fgets($this->handle)) !== false) {
            $this->file[] = $line;
        }
    }

    /**
     * Print the imported file line-by-line
     */
    public function printFile()
    {
        echo '<pre>';
        $this->iterateAll(function($line) {
            echo $line . "<br>";
        });
        echo '</pre>';
    }

    /**
     * Print out (for debugging) an imported file
     */
    public function printProcessedFile()
    {
        echo '<h2>file</h2>';
        echo '<pre>';
        var_dump($this->file);
        echo '/<pre>';
        echo '<h2>imported</h2>';
        echo '<pre>';
        var_dump($this->imported);
        echo '/<pre>';
                    /*
            if ($this->isComment($line)) {

                echo "<i>$line</i><br>";
            } elseif ($this->isConfig($line)) {
                $config = $this->splitConfig($line);
                echo "<b>{$config['key']}</b>: {$config['value']['value']} <span style='color: #ccc;'>({$config['value']['comment']})</span><br>";
            } elseif ($this->isBlank($line)) {
                echo "<br>";
            } else {
                // Code line
                echo "<pre>$line</pre>";
            }
            */
    }

    /**
     * Process the imported file into usable data arrays
     */
    public function processFile()
    {
        $this->iterateAll(function($line, $line_number) {
            if ($this->currently_in_array) {
                $this->reEngageIfArrayIsOver($line);
            } elseif ($this->currently_in_object) {
                $this->reEngageifObjectIsOver($line);
            } else {
                $this->importLine($line, $line_number);
            }
        });
    }

    /**
     * Import a line from the config file
     * 
     * @param string  $line        Full line
     * @param integer $line_number Line number
     */
    protected function importLine($line, $line_number)
    {
        $type = $this->getLineType($line);
        $data = $this->getLineData($line, $line_number);

        switch ($type) {
            case 'array':
                $line_numbers = $this->getArrayLineNumbers($line_number);
                $original_lines = $this->getArrayOriginalLines($line_number);
                break;
            case 'object':
                $line_numbers = $this->getObjectLineNumbers($line_number);
                $original_lines = $this->getObjectOriginalLines($line_number);
                break;
            default:
                $line_numbers = array($line_number);
                $original_lines = array($line);
        }

        $this->addToImported($line_numbers, $original_lines, $type, $data);
    }

    /**
     * Get the type of this line
     * 
     * @param  string $line Line
     * @return string       Type
     */
    protected function getLineType($line)
    {
        if ($this->isArrayOpener($line)) {
            return 'array';
        }
        if ($this->isObjectOpener($line)) {
            return 'object';
        }
        if ($this->isBlank($line)) {
            return 'blank';
        }
        if ($this->isComment($line)) {
            return 'comment';
        }
        if ($this->isConfig($line)) {
            return 'config';
        }
        return 'code';
    }

    /**
     * Convert a line to data
     * 
     * @param  string  $line        Line
     * @param  integer $line_number Line number
     * @return mixed                Data
     */
    protected function getLineData($line, $line_number)
    {
        switch ($this->getLineType($line)) {
            case 'array':
                return $this->splitConfig($line, $line_number); // ?
                break;
            case 'comment':
                return trim($line);
                break;
            case 'config':
                return $this->splitConfig($line, $line_number);
                break;
            case 'blank':
                return null;
                break;
            case 'code':
                return $line;
                break;
        }
    }

    /**
     * Get the line numbers for an entire array
     * 
     * @param  integer $start_line_number Line number for the beginning of the array
     * @return array                      Line numbers
     */
    protected function getArrayLineNumbers($start_line_number)
    {  
        return $this->getLineNumbersFromLineUntilCharacter($start_line_number, ']');
    }

    /**
     * Get the line numbers for entire object
     * 
     * @param  integer $start_line_number Line number for the beginning of the object
     * @return array                      Line numbers
     */
    protected function getObjectLineNumbers($start_line_number)
    {  
        return $this->getLineNumbersFromLineUntilCharacter($start_line_number, '}');
    }

    /**
     * Get the original lines for an array
     * 
     * @param  integer $start_line_number Line number for beginning
     * @return array                      Lines
     */
    protected function getArrayOriginalLines($start_line_number)
    {
        $line_numbers = $this->getArrayLineNumbers($start_line_number);
        return $this->getLinesFromLineNumbers($line_numbers);
    }

    /**
     * Get the original lines for an object
     * 
     * @param  integer $start_line_number Line number for beginning
     * @return array                      Lines
     */
    protected function getObjectOriginalLines($start_line_number)
    {
        $line_numbers = $this->getObjectLineNumbers($start_line_number);
        return $this->getLinesFromLineNumbers($line_numbers);
    }

    /**
     * Add an object to the imported array
     * 
     * @param array  $line_numbers   E.g. [1, 2, 3, 4]
     * @param array  $original_lines E.g. ['First line of instructions', 'second line']
     * @param string $type           
     * @param mixed  $data           Vaprogui-friendly data
     */
    protected function addToImported(array $line_numbers, array $original_lines, $type, $data)
    {
        $insert = array(
            'line_numbers' => $line_numbers,
            'original_lines' => $original_lines,
            'type' => $type,
            'data' => $data
        );
        $this->imported[] = $insert;
    }

    /**
     * Get a line_numbers array between start and next occurence of a character
     * (e.g. "]" to find the ending line of an array)
     * 
     * @param  integer $start_line_number Line to start from
     * @param  string  $character         Character to end at
     * @return array                      Line numbers
     */
    protected function getLineNumbersFromLineUntilCharacter($start_line_number, $character)
    {
        $line_numbers = array();

        for ($i = $start_line_number; $i <= count($this->file); $i++) {
            $line_numbers[] = $i;
            if (stripos($this->file[$i], $character) !== false) {
                break;
            }
        }

        return $line_numbers;
    }



    /**
     * Get actua lines given a line numbers array
     * 
     * @param  array  $line_numbers A list of the lines numbers to get (e.g. [1,2,3])
     * @return array                Lines for those numbers (e.g. ['first config line', 'second'])
     */
    protected function getLinesFromLineNumbers(array $line_numbers)
    {
        $lines = array();

        foreach ($line_numbers as $line_number) {
            $lines[] = $this->file[$line_number];
        }

        return $lines;
    }



    /**
     * If array is over, notify the parser to start parsing again after this line
     * 
     * @param  string $line Line
     */
    protected function reEngageIfArrayIsOver($line)
    {
        if (stripos($line, ']') !== false) {
            $this->currently_in_array = false;
        }
    }

    /**
     * If object is over, notify the parser to start parsing again after this line
     * 
     * @param  string $line Line
     */
    protected function reEngageIfObjectIsOver($line)
    {
        if (stripos($line, '}') !== false) {
            $this->currently_in_object = false;
        }
    }

    /**
     * Test whether this line is the opener of an array
     * 
     * @param  string  $line line
     * @return boolean
     */
    protected function isArrayOpener($line)
    {
        return stripos($line, '[') !== false;
    }

    /**
     * Test whether this line is the closer of an array
     * 
     * @param  string  $line line
     * @return boolean
     */
    protected function isArrayCloser($line)
    {
        return stripos($line, ']') !== false;
    }

    /**
     * Test whether this line is the opener of an object
     * 
     * @param  string  $line line
     * @return boolean
     */
    protected function isObjectOpener($line)
    {
        return stripos($line, '{') !== false;
    }

    /**
     * Test whether this line is the closer of an object
     * 
     * @param  string  $line line
     * @return boolean
     */
    protected function isObjectCloser($line)
    {
        return stripos($line, ']') !== false;
    }

    /**
     * Test whether this line is blank
     * 
     * @param  string  $line
     * @return boolean
     */
    protected function isBlank($line)
    {
        return trim($line) == '';
    }

    /**
     * Test whether this line is a comment
     * 
     * @param  string  $line
     * @return boolean
     */
    protected function isComment($line)
    {
        return substr(trim($line), 0, 1) == '#'; 
    }

    /**
     * Test whether this line is a config line
     * 
     * @param  string  $line
     * @return boolean
     */
    protected function isConfig($line)
    {
        return stripos($line, ' = ') !== false;
    }

    /**
     * Split a config line into key and value
     * 
     * @param  string  $line
     * @param  integer $line_number
     * @return array                ['key', 'value']
     */
    protected function splitConfig($line, $line_number)
    {
        $line = explode(' = ', $line);

        $return = array(
            'key' => trim($line[0]),
            'value' => $this->splitConfigValueAndComments($line[1], $line_number)
        );

        return $return;
    }


    /**
     * Split the config value from its comments and return
     * 
     * @param  string  $value
     * @param  integer $line_number
     * @return array                ['value', 'comment']
     */
    protected function splitConfigValueAndComments($value, $line_number)
    {
        if (stripos($value, '[') !== false) {
            // First line of an array
            return $this->splitConfigValueArrayStart($value, $line_number);
        }
        if (stripos($value, '{') !== false) {
            // First line of an object
            return $this->splitConfigValueObjectStart($value, $line_number);
        }

        $comment = '';

        if (stripos($value, '#') !== false) {
            // Strip out comments
            $start = stripos($value, '#') + 2; // strip out '# '
            $comment = trim(substr($value, $start));
        }

        // Get the value inside the quotes
        $data = preg_match('/\"([^\"]*?)\"/', $value, $matches);
        if (count($matches) < 2) {
            // @todo: Check for :symbols
            var_dump($value);
            exit;
        }
        
        return array(
            'value' => $matches[1],
            'comment' => $comment
        );
    }

    /**
     * Split the config value from its comments and return -- if array
     * 
     * @param  string  $value
     * @param  integer $line_number
     * @return array                ['value', 'comment']
     */
    protected function splitConfigValueArrayStart($value, $line_number)
    {
        $this->currently_in_array = true;
        $lines = $this->getArrayOriginalLines($line_number);
        dd($lines);
        // $this->getA
        // @todo: SHouldn't this get data??
    }

    /**
     * Split the config value from its comments and return -- if object
     * 
     * @param  string  $value
     * @param  integer $line_number
     * @return array                ['value', 'comment']
     */
    protected function splitConfigValueObjectStart($value, $line_number)
    {
        $this->currently_in_object = true;
        // @todo: SHouldn't this get data??
    }

    /**
     * Iterate over every line and apply a callback
     * 
     * @param  closure $callback_function
     */
    protected function iterateAll($callback_function)
    {
        $i = 0;
        foreach ($this->file as $line) {
            $return = $callback_function($line, $i);
            if ($return === false) {
                break;
            }
            $i++;
        }
    }

    /**
     * Iterate over only the lines from $start on
     * 
     * @param  integer $start             Start line
     * @param  closure $callback_function 
     */
    protected function iterateFrom($start, $callback_function)
    {
        $this->iterateAll(function($line, $line_number) use($start, $callback_function) {
            if ($line_number >= $start) {
                return $callback_function($line, $line_number);
            }
        });
    }

    public function __destruct()
    {
        if ($this->handle) {
            fclose($this->handle);
        }
    }
}
