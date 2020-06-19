<?php declare(strict_types=1);
namespace aaronicsubstances\code_augmentor_support;

class ProcessCodeContext {
   public $header;
   public $globalScope = [ 'codeAugmentor_indent' => '    ' ];
   public $fileScope = [];
   public $fileAugCodes;
   public $augCodeIndex = 0;
   public $srcFile;
   
    public function newGenCode() {
        $g = new \stdClass;
        $g->id= 0;
        $g->contentParts = [];
        return $g;
    }

    public function newSkipGenCode() {
        $g = new \stdClass;
        $g->skipped = TRUE;
        return $g;
    }

    public function newContent(string $content, bool $exactMatch = FALSE) {
        $c = new \stdClass;
        $c->content = $content;
        $c->exactMatch = $exactMatch;
        return $c;
    }

    public function getScopeVar(string $name) {
        if (array_key_exists($name, $this->fileScope)) {
            return $this->fileScope[$name];
        }
        if (array_key_exists($name, $this->globalScope)) {
            return $this->globalScope[$name];
        }
        return NULL;
    }
    
    /**
     * For testing purposes so converting instances of this class to strings succeeds.
     */
    public function __toString() {
        return get_class($this) . ": " . $this->srcFile;
    }
}