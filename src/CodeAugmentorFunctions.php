<?php declare(strict_types=1);
namespace aaronicsubstances\code_augmentor_support;

class CodeAugmentorFunctions {

    public static function setScopeVar($augCode, $context) {
        self::modifyScope($context->fileScope, $augCode);
        return $context->newSkipGenCode();
    }

    public static function setGlobalScopeVar($augCode, $context) {
        self::modifyScope($context->globalScope, $augCode);
        return $context->newSkipGenCode();
    }
    
    private static function modifyScope(&$scope, $augCode) {
        foreach ($augCode->args as $arg) {
            foreach ($arg as $key => $value) {
                $scope[$key] = $value;
            }
        }
    }
}