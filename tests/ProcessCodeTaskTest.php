<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

use aaronicsubstances\code_augmentor_support\tasks\ProcessCodeTask;

final class ProcessCodeTaskTest extends TestCase
{
    public function testBasicUsage(): void {
        $task = new ProcessCodeTask;
        $task->inputFile = __DIR__ . DIRECTORY_SEPARATOR .'basic_usage_aug_codes.json';
        $tmpdir = sys_get_temp_dir();
        $task->outputFile = $tmpdir . DIRECTORY_SEPARATOR . 'basic_usage_gen_codes.json';
        
        // print blank line so execute output comes out nicely
        print PHP_EOL;
        $task->execute(function($functionName, $augCode, $context) {
            $augCodeStr = print_r($augCode, TRUE);
            return "Received: $functionName: $augCodeStr, $context";
        });
        $this->assertEmpty($task->allErrors, print_r($task->allErrors, true));
        print "Output successfully written to {$task->outputFile}" . PHP_EOL;
    }
    
    public function testBasicEvalError(): void {
        $task = new ProcessCodeTask;
        $task->inputFile = __DIR__ . DIRECTORY_SEPARATOR .'basic_usage_aug_codes.json';
        $tmpdir = sys_get_temp_dir();
        $task->outputFile = $tmpdir . DIRECTORY_SEPARATOR . 'basic_usage_gen_codes.json';
        
        // print blank line so execute output comes out nicely
        print PHP_EOL;
        $task->execute(function($functionName, $augCode, $context) {
            return call_user_func($functionName, $augCode, $context);
        });
        $this->assertGreaterThan(0, count($task->allErrors));
        // print blank line so execute output comes out nicely
        print PHP_EOL;
        print "Expected errors, and found " . count($task->allErrors) . PHP_EOL;
        foreach ($task->allErrors as $ex) {
            print $ex . PHP_EOL;
            //fwrite(STDERR, $ex . PHP_EOL);
        }
    }
}