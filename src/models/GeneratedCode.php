<?php declare(strict_types=1);
namespace aaronicsubstances\code_augmentor_support\models;

class GeneratedCode implements \JsonSerializable {

   public $id = 0;
   public $contentParts = [];
   public $indent;
   public $skipped = FALSE;
   public $replaceAugCodeDirectives = FALSE;
   public $replaceGenCodeDirectives = FALSE;
   public $disableAutoIndent = FALSE;
   
    public function jsonSerialize() : array {
        return [
            'id' => $this->id,
            'contentParts' => $this->contentParts,
            'indent' => $this->indent,
            'skipped' => $this->skipped,
            'replaceAugCodeDirectives' => $this->replaceAugCodeDirectives,
            'replaceGenCodeDirectives' => $this->replaceGenCodeDirectives,
            'disableAutoIndent' => $this->disableAutoIndent
        ];
    }
}