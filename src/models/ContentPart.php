<?php declare(strict_types=1);
namespace aaronicsubstances\code_augmentor_support\models;

class ContentPart implements \JsonSerializable {

   public $content = '';
   public $exactMatch = FALSE;
   
   public function __construct($content, $exactMatch) {
       $this->content = $content;
       $this->exactMatch = $exactMatch;
   }
   
    public function jsonSerialize() {
        return [
            'content' => $this->content,
            'exactMatch' => $this->exactMatch
        ];
    }
}