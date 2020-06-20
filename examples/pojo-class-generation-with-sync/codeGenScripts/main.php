<?php declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';

$instance = new \aaronicsubstances\code_augmentor_support\ProcessCodeTask();
$instance->inputFile = $argv[1];
$instance->outputFile = $argv[2];
if ($argc > 3) {
    $instance->verbose = !!$argv[3];
}

$FUNCTION_NAME_REGEX = '/^(((.*CodeAugmentorFunctions)|MainFunctions|OtherFunctions)\\.)[a-zA-Z]\\w*$/';
$instance->execute(function($functionName, $augCode, $context) use ($FUNCTION_NAME_REGEX) {
    // validate name.
    if (!preg_match($FUNCTION_NAME_REGEX, $functionName)) {
        throw new \Exception("Invalid/Unsupported function name: " . $functionName);
    }

    // make function call "dynamically".
    $result = call_user_func($functionName, $augCode, $context);
    return $result;
});

if ($instance->allErrors) {
    fwrite(STDERR, count($instance->allErrors) . " error(s) found." . PHP_EOL,);
    foreach ($instance->allErrors as $errMsg) {
        fwrite(STDERR, $errMsg . PHP_EOL);
    }
    exit(1);
}