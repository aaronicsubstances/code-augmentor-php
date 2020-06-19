<?php declare(strict_types=1);
namespace aaronicsubstances\code_augmentor_support;

class ProcessCodeTask {
   public $inputFile;
   public $outputFile;
   public $allErrors;
   public $verbose;
   
   public function execute($evalFunction) : void {
       assert(!!$this->inputFile, "inputFile property is not set");
       assert(!!$this->outputFile, "outputFile property is not set");
       assert(!!$evalFunction);

       $this->allErrors = [];
       
       // ensure dir exists for outputFile
       // mkdir fails if directory already exists,
       // so let's check that first.
       $outputFileDir = dirname($this->outputFile);
       if ($outputFileDir && ! is_dir($outputFileDir)) {
            mkdir($outputFileDir, 0777, TRUE);
       }
       
       $context = new ProcessCodeContext;
       
       $codeGenRequest = NULL;
       $codeGenResponse = NULL;
       try {
           $codeGenRequest = fopen($this->inputFile, 'rb');
           $codeGenResponse = fopen($this->outputFile, 'wb');
           
           // begin serialize by writing header to output
           fwrite($codeGenResponse, '{}'.PHP_EOL);
           
           $headerSeen = FALSE;
           while (($line = fgets($codeGenRequest)) !== FALSE) {
                // begin deserialize by reading header from input
                if (!$headerSeen) {
                    $context->header = self::jsonParse($line);
                    $headerSeen = TRUE;
                    continue;
                }
               
                $fileAugCodes = self::jsonParse($line);
                
                // set up context.
                $context->srcFile = $fileAugCodes->dir . DIRECTORY_SEPARATOR .
                    $fileAugCodes->relativePath;
                $context->fileAugCodes = $fileAugCodes;
                $context->fileScope = [];
                $this->logVerbose("Processing " . $context->srcFile);
                
                // fetch arguments and parse any json arguments found
                $fileAugCodesList = &$fileAugCodes->augmentingCodes;
                // due to PHP pass by value semantics
                // iterate by reference so changes
                // to augCode arrays can persist after
                // loop.
                foreach ($fileAugCodesList as &$augCode) {
                    $augCode->processed = FALSE;
                    $augCode->args = [];
                    foreach ($augCode->blocks as &$block) {
                        if ($block->jsonify) {
                            $parsedArg = self::jsonParse($block->content);
                            $augCode->args[] = $parsedArg;
                        }
                        elseif ($block->stringify) {
                            $augCode->args[] = $block->content;
                        }
                    }
                }
                    
                # process aug codes
                $fileGenCodes = (object) [
                    'fileId' => $fileAugCodes->fileId,
                    'generatedCodes' => []
                ];
                // fetch by reference so updates here affects $fileGenCodes
                $fileGenCodeList = &$fileGenCodes->generatedCodes;
                
                $beginErrorCount = count($this->allErrors);
                for ($i = 0; $i < count($fileAugCodesList); $i++) {
                    $augCode = &$fileAugCodesList[$i];
                    if ($augCode->processed) {
                        continue;
                    }
                        
                    $context->augCodeIndex = $i;
                    $functionName = self::retrieveFunctionName($augCode);
                    $genCodes = $this->processAugCode($evalFunction, $functionName, $augCode, $context);
                    foreach ($genCodes as $g) {
                        $fileGenCodeList[] = $g;
                    }
                }
                
                $this->validateGeneratedCodeIds($fileGenCodeList, $context);
                
                if (count($this->allErrors) > $beginErrorCount) {
                    $this->logWarn((count($this->allErrors) - $beginErrorCount) . 
                        " error(s) encountered in " . $context->srcFile);
                }
                
                if (! $this->allErrors) {
                    fwrite($codeGenResponse, self::compactJsonDump($fileGenCodes) . PHP_EOL);
                }
                
                $this->logInfo('Done processing ' . $context->srcFile);
           }
       }
       finally {
           if ($codeGenRequest) {
                fclose($codeGenRequest);
           }
           if ($codeGenResponse) {
                fclose($codeGenResponse);
           }
       }
    }
    
    public function logVerbose(string $message) : void {
        if ($this->verbose) {
            print "[VERBOSE] " . $message . PHP_EOL;
        }
    }
    
    public function logInfo(string $message) : void {
        print "[INFO] " . $message . PHP_EOL;
    }
    
    public function logWarn(string $message) : void {
        print "[WARN] " . $message . PHP_EOL;
    }

    private static function retrieveFunctionName(&$augCode) {
        $functionName = trim($augCode->blocks[0]->content);
        if (strpos($functionName, "CodeAugmentorFunctions") === 0) { // NB: 3, not 2 equals
            $functionName = "\\" . __NAMESPACE__  . "\\" .
                $functionName;
        }
        return $functionName;
    }
    
    public function processAugCode($evalFunction, string $functionName, object $augCode, ProcessCodeContext $context) : array {
        try {
            $result = $evalFunction($functionName, $augCode, $context);
            if ($result === NULL) {
                return [  $this->convertGenCodeItem(NULL) ];
            }
            $converted = [];
            if (is_iterable($result)) {
                $fileAugCodesList = &$context->fileAugCodes->augmentingCodes;
                foreach ($result as $item) {
                    $genCode = $this->convertGenCodeItem($item);
                    $converted[] = $genCode;
                    // try and mark corresponding aug code as processed.
                    if ($genCode->id > 0) {
                        foreach ($fileAugCodesList as &$a) {
                            if ($a->id == $genCode->id) {
                                $a->processed = TRUE;
                                break;
                            }
                        }
                    }
                }
            }
            else {
                $genCode = $this->convertGenCodeItem($result);
                $genCode->id = $augCode->id;
                $converted[] = $genCode;
            }
            return $converted;
        }
        catch (\Throwable $evalEx) {
            $this->createException($context, '', $evalEx);
            return [];
        }
    }

    private function convertGenCodeItem($item) {
        if ($item === NULL) {
            return (object) [
                'id' => 0
            ];
        }
        elseif (property_exists($item, 'skipped') || property_exists($item, 'contentParts')) {
            if (!property_exists($item, 'id')) {
                $item->id = 0;
            }
            return $item;
        }
        elseif (property_exists($item, 'content')) {
            return (object) [
                'id' => 0,
                'contentParts' => [ $item ]
            ];
        }
        else {
            // concatenation with strings isn't guaranteed to always succeed in PHP.
            // in particular arrays and classes without __toString() methods
            // are culprits.
            // try conversion to string via concatenation and let
            // any exception be handled higher up the stack.
            if ($item instanceof \stdClass) {
                $content = self::compactJsonDump($item);
            }
            else {
                $content = '' . $item;
            }
            $contentPart = (object) [
                'content' => $content,
                'exactMatch' => FALSE
            ];
            return (object) [
                'id' => 0,
                'contentParts' => [ $contentPart ]
            ];
        }
    }
    
    private function validateGeneratedCodeIds(array $genCodes, ProcessCodeContext $context) : void {
        $ids = array_map(function($x) {
            return $x->id;
        }, $genCodes);
        // Interpret use of -1 or negatives as intentional and skip
        // validating negative ids.
        $validIds = array_filter($ids, function($value) {
            return $value > 0;
        });
        $invalidIds = array_filter($ids, function($value) {
            return ! $value;
        });
        if (count($invalidIds)) {
            $this->createException($context, 'At least one generated code id was not set. Found: ' . print_r($ids, true));
        }
        else if (count(array_unique($validIds)) < count($validIds)) {
            $this->createException($context, 'Valid generated code ids must be unique, but found duplicates: ' . print_r($ids, true));
        }
    }            
    
    private function createException(ProcessCodeContext $context, string $message, \Throwable $evalEx=NULL) : void {
        $lineMessage = '';
        $stackTrace = '';
        if ($evalEx) {
            $augCode = $context->fileAugCodes->augmentingCodes[$context->augCodeIndex];
            $lineMessage = " at line {$augCode->lineNumber}";
            $message = $augCode->blocks[0]->content . ": " .
                get_class($evalEx) . ": " . $evalEx->getMessage();
            //$stackTrace = PHP_EOL . $evalEx->getTraceAsString();
            $stackTrace = PHP_EOL . self::jTraceEx($evalEx);
        }
        $exception = "in {$context->srcFile}$lineMessage: $message$stackTrace";
        $this->allErrors[] = $exception;
    }
    
    private static function compactJsonDump(object $obj) : string {
        return json_encode($obj, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
    
    private static function jsonParse(string $str) {
        return json_decode($str, FALSE, 512, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * jTraceEx() - provide a Java style exception trace
     * @param $exception
     * @param $seen      - array passed to recursive calls to accumulate trace lines already seen
     *                     leave as NULL when calling this function
     * @return array of strings, one entry per trace line
     */
    public static function jTraceEx($e, $seen=null) {
        $starter = $seen ? 'Caused by: ' : '';
        $result = array();
        if (!$seen) $seen = array();
        $trace  = $e->getTrace();
        $prev   = $e->getPrevious();
        $result[] = sprintf('%s%s: %s', $starter, get_class($e), $e->getMessage());
        $file = $e->getFile();
        $line = $e->getLine();
        while (true) {
            $current = "$file:$line";
            if (is_array($seen) && in_array($current, $seen)) {
                $result[] = sprintf(' ... %d more', count($trace)+1);
                break;
            }
            $result[] = sprintf(' at %s%s%s(%s%s%s)',
                                        count($trace) && array_key_exists('class', $trace[0]) ? str_replace('\\', '.', $trace[0]['class']) : '',
                                        count($trace) && array_key_exists('class', $trace[0]) && array_key_exists('function', $trace[0]) ? '.' : '',
                                        count($trace) && array_key_exists('function', $trace[0]) ? str_replace('\\', '.', $trace[0]['function']) : '(main)',
                                        $line === null ? $file : basename($file),
                                        $line === null ? '' : ':',
                                        $line === null ? '' : $line);
            if (is_array($seen))
                $seen[] = "$file:$line";
            if (!count($trace))
                break;
            $file = array_key_exists('file', $trace[0]) ? $trace[0]['file'] : 'Unknown Source';
            $line = array_key_exists('file', $trace[0]) && array_key_exists('line', $trace[0]) && $trace[0]['line'] ? $trace[0]['line'] : null;
            array_shift($trace);
        }
        $result = join(PHP_EOL, $result);
        if ($prev)
            $result  .= PHP_EOL . self::jTraceEx($prev, $seen);

        return $result;
    }
}