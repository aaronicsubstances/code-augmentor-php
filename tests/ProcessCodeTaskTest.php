<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

use aaronicsubstances\code_augmentor_support\tasks\ProcessCodeTask;

final class ProcessCodeTaskTest extends TestCase
{
    public static $tmpdir;
    
    public function testBasicUsage(): void {
        $task = new ProcessCodeTask;
        $task->inputFile = __DIR__ . DIRECTORY_SEPARATOR .'basic_usage_aug_codes.json';
        $task->outputFile = self::$tmpdir . DIRECTORY_SEPARATOR . 'basic_usage_gen_codes-00.json';
        
        self::printHeader(__METHOD__);
        $task->execute(function($functionName, $augCode, $context) {
            $augCodeStr = print_r($augCode, TRUE);
            return "Received: $functionName: $augCodeStr, $context";
        });
        self::printErrors($task);
        $this->assertEmpty($task->allErrors);
    }

    public function testUsageWithArrayReturnResult(): void {
        $task = new ProcessCodeTask;
        $task->inputFile = __DIR__ . DIRECTORY_SEPARATOR .'basic_usage_aug_codes.json';
        $task->outputFile = self::$tmpdir . DIRECTORY_SEPARATOR . 'basic_usage_gen_codes-01.json';
        
        self::printHeader(__METHOD__);
        $task->execute(function($functionName, $augCode, $context) {
            $genCode = $context->newGenCode();
            $genCode->id = $augCode['id'];
            $genCode->contentParts[] = $context->newContent("Received: $functionName");
            return [ $genCode ];
        });
        self::printErrors($task);
        $this->assertEmpty($task->allErrors);
    }
    
    public function testBasicEvalError(): void {
        $task = new ProcessCodeTask;
        $task->inputFile = __DIR__ . DIRECTORY_SEPARATOR .'basic_usage_aug_codes.json';
        $task->outputFile = self::$tmpdir . DIRECTORY_SEPARATOR . 'basic_usage_gen_codes-02.json';
        
        self::printHeader(__METHOD__);
        $task->execute(function($functionName, $augCode, $context) {
            return call_user_func($functionName, $augCode, $context);
        });
        self::printErrors($task);
        $this->assertGreaterThan(0, count($task->allErrors));
        print "Expected " . count($task->allErrors) . ' error(s)' . PHP_EOL;
    }
    
    static function printHeader($methodName) {
        print PHP_EOL;
        print $methodName . PHP_EOL;
        print '----------------' . PHP_EOL;
    }
    
    static function printErrors($task) {
        foreach ($task->allErrors as $ex) {
            print $ex . PHP_EOL;
            //fwrite(STDERR, $ex . PHP_EOL);
        }
        print PHP_EOL;
    }
}
ProcessCodeTaskTest::$tmpdir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'code-augmentor-support-php';