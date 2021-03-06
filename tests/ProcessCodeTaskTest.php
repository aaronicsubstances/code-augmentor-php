<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

use aaronicsubstances\code_augmentor_support\ProcessCodeTask;

/**
 * Main purpose of tests in this project is to test
 * error cases and the formatting of thrown exceptions.
 * More thorough testing of success case scenerios is dealt with outside this
 * project.
 */
final class ProcessCodeTaskTest extends TestCase
{
    public static $tmpdir;
    
    public function testBasicUsage(): void {
        // test that output dir can be recreated if absent.
        // do this only here, so subsequent tests verify that
        // existing output dir can be used successfully.
        if (file_exists(self::$tmpdir)) {
            $objects = scandir(self::$tmpdir);
            foreach ($objects as $object) {
                // NB: . and .. also appear as directory entries
                if (!is_dir($object)) {
                    unlink(self::$tmpdir . DIRECTORY_SEPARATOR . $object);
                }
            }
            rmdir(self::$tmpdir);
        }

        $task = new ProcessCodeTask;
        $task->inputFile = __DIR__ . DIRECTORY_SEPARATOR . 'resources' . 
            DIRECTORY_SEPARATOR .'aug_codes-00.json';
        $task->outputFile = self::$tmpdir . DIRECTORY_SEPARATOR . 'actual_gen_codes.json';
        
        self::printHeader(__METHOD__);
        $task->execute(function($functionName, $augCode, $context) {
            $augCodeStr = print_r($augCode, TRUE);
            return "Received: $functionName: $augCodeStr, $context";
        });
        self::printErrors($task);
        $this->assertEmpty($task->allErrors);
    }

    public function testUsageProducingUnsetIds(): void {
        $task = new ProcessCodeTask;
        $task->inputFile = __DIR__ . DIRECTORY_SEPARATOR . 'resources' .
            DIRECTORY_SEPARATOR . 'aug_codes-00.json';
        $task->outputFile = self::$tmpdir . DIRECTORY_SEPARATOR . 'genCodes-php-ignore.json';
        
        self::printHeader(__METHOD__);
        $task->execute(function($functionName, $augCode, $context) {
            $genCode = $context->newGenCode();
            //$genCode->id = $augCode['id'];
            $genCode->contentParts[] = $context->newContent("Received: $functionName");
            return [ $genCode ];
        });
        self::printErrors($task);
        $this->assertEquals(2, count($task->allErrors));
        print "Expected " . count($task->allErrors) . ' error(s)' . PHP_EOL;
    }

    public function testUsageProducingDuplicateIds(): void {
        $task = new ProcessCodeTask;
        $task->inputFile = __DIR__ . DIRECTORY_SEPARATOR . 'resources' .
            DIRECTORY_SEPARATOR . 'aug_codes-01.json';
        $task->outputFile = self::$tmpdir . DIRECTORY_SEPARATOR . 'genCodes-php-ignore.json';
        
        self::printHeader(__METHOD__);
        $task->execute(function($functionName, $augCode, $context) {
            $genCode = $context->newGenCode();
            $genCode->id = 1;
            $genCode->contentParts[] = $context->newContent("Received: $functionName");
            return [ $genCode ];
        });
        self::printErrors($task);
        $this->assertEquals(1, count($task->allErrors));
        print "Expected " . count($task->allErrors) . ' error(s)' . PHP_EOL;
    }
    
    public function testUsageWithProductionEvaler(): void {
        $task = new ProcessCodeTask;
        $task->inputFile = __DIR__ . DIRECTORY_SEPARATOR . 'resources' .
            DIRECTORY_SEPARATOR . 'aug_codes-01.json';
        $task->outputFile = self::$tmpdir . DIRECTORY_SEPARATOR . 'genCodes-php-ignore.json';
        
        self::printHeader(__METHOD__);
        $task->execute(function($functionName, $augCode, $context) {
            return call_user_func($functionName, $augCode, $context);
        });
        self::printErrors($task);
        $this->assertEquals(2, count($task->allErrors));
        print "Expected " . count($task->allErrors) . ' error(s)' . PHP_EOL;
    }
    
    public function testUsageWithEvalerWithoutReturn(): void {
        $task = new ProcessCodeTask;
        $task->inputFile = __DIR__ . DIRECTORY_SEPARATOR . 'resources' .
            DIRECTORY_SEPARATOR . 'aug_codes-01.json';
        $task->outputFile = self::$tmpdir . DIRECTORY_SEPARATOR . 'genCodes-php-ignore.json';
        
        self::printHeader(__METHOD__);
        $task->execute(function($functionName, $augCode, $context) {
        });
        self::printErrors($task);
        $this->assertEquals(1, count($task->allErrors));
        print "Expected " . count($task->allErrors) . ' error(s)' . PHP_EOL;
    }
    
    public function testContextScopeMethodAccessEvaler(): void {
        $task = new ProcessCodeTask;
        $task->inputFile = __DIR__ . DIRECTORY_SEPARATOR . 'resources' .
            DIRECTORY_SEPARATOR . 'aug_codes-02.json';
        $task->outputFile = self::$tmpdir . DIRECTORY_SEPARATOR . 'genCodes-php-02.json';
        
        self::printHeader(__METHOD__);
        $task->execute(function($f, $a, $c) {
            if ($f != "\"testUseOfGetScopeVar\"") {
                return call_user_func($f, $a, $c);
            }
            self::assertEquals("NewTown", $c->getScopeVar("address"));
            self::assertEquals("ICT", $c->getScopeVar("serviceType"));
            self::assertEquals("ICT,Agric", $c->getScopeVar("allServiceTypes"));
            self::assertEquals("OldTown", $c->globalScope["address"]);
            self::assertEquals("    ", $c->getScopeVar("codeAugmentor_indent"));
            return $c->newSkipGenCode();
        });
        self::printErrors($task);
        $this->assertEmpty($task->allErrors);
        $actualOutput = file_get_contents($task->outputFile);
        $actualOutput = preg_replace("/\r\n|\n|\r/", "\n", $actualOutput);
        $expectedOutput = "{}\n" .
            "{\"fileId\":1,\"generatedCodes\":[" .
            "{\"skipped\":true,\"id\":1}," .
            "{\"skipped\":true,\"id\":2}," .
            "{\"skipped\":true,\"id\":3}]}\n";
        self::assertEquals($expectedOutput, $actualOutput);
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