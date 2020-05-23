<?php declare(strict_types=1);
namespace aaronicsubstances\code_augmentor_support;

class ProcessCodeContext {
   public $header;
   public $globalScope = [];
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

    public function newContent(string $content, bool $exactMatch = FALSE) {
        $c = new \stdClass;
        $c->content = $content;
        $c->exactMatch = $exactMatch;
        return $c;
    }
    
    /**
     * For testing purposes so converting instances of this class to strings succeeds.
     */
    public function __toString() {
        return get_class($this) . ": " . $this->srcFile;
    }
}