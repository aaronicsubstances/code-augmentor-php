# code-augmentor-support

This package enables the use of PHP 7 as a scripting platform to generate code to serve the goals of Code Augmentor.

Code Augmentor is a set of libraries, plugins and tools for bringing code generation techniques to every programmer. For a more detailed explanation please visit the main Code Augmentor Github repository [here](https://github.com/aaronicsubstances/code-augmentor).

As far as this package and PHP 7 developers are concerned, it is enough to think of Code Augmentor as (1) a command-line application, (2) which is configured to run an [Apache Ant](https://ant.apache.org) XML build file, (3) which in turn runs a PHP 7 package or project written by a programmer, (4) with the aim of generating code for PHP 7 or  another target programming language, (5) using this package as a dependency.


## Installing using Composer

At a minimum your `composer.json` file should contain:

```json
{
    "require": {
        "aaronicsubstances/code-augmentor-support": "^1.0.1"    
    }
}
```

Then install with `composer install`

### Example

Below is a main script demonstrating how to set up the library for use with static functions defined in two client classes Snippets.php and Worker.php.

It requires input and ouput file command-line arguments, and optional third argument to enable verbose logging.

#### composer.json

```json
{
    "require": {
        "aaronicsubstances/code-augmentor-support": "^1.0.1"    
    },
    "autoload": {
        "classmap": [ "Snippets.php", "Worker.php" ]    
    }
}
```

#### main.php

```php
<?php declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';

$instance = new \aaronicsubstances\code_augmentor_support\ProcessCodeTask();
$instance->inputFile = $argv[1];
$instance->outputFile = $argv[2];
if ($argc > 3) {
    $instance->verbose = !!$argv[3];
}
assert(!!$instance->inputFile);
assert(!!$instance->outputFile);

$FUNCTION_NAME_REGEX = '/^((Snippets|Worker)\\.)[a-zA-Z]\\w*$/';
$instance->execute(function($functionName, $augCode, $context) use ($FUNCTION_NAME_REGEX) {
    // validate name.
    if (!preg_match($FUNCTION_NAME_REGEX, $functionName)) {
        throw new \Exception("Invalid/Unsupported function name: " . $functionName);
    }

    // name is valid but replace . with :: in function name.
    $functionName = str_replace('.', '::', $functionName);
    // now make function call "dynamically".
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
```

#### Snippets.php

```php
<?php  declare(strict_types=1);

class Snippets {

    public static function generateSerialVersionUID($augCode, $context) {
        return "private static final int serialVersionUID = 23L;";
    }    
}
```

#### Worker.php

```php
<?php  declare(strict_types=1);

class Worker {
    
    public static function stringify($augCode, $context) {
        $g = $context->newGenCode();
        for ($i = 0; $i < count($augCode->args); $i++) {
            $s = '"' . $augCode->args[$i];
            if ($i < count($augCode->args) - 1) {
                $s .= $augCode->lineSeparator . '" +';
            }
            else {
                $s .= '"';
            }
            $g->contentParts[] = $context->newContent($s, TRUE);
        }
        return $g;
    }
}
```

#### test-augCodes.json (sample input file)

```json
{ "genCodeStartDirective": "//:GS:", "genCodeEndDirective": "//:GE:", "embeddedStringDirective": "//:STR:", "embeddedJsonDirective": "//:JSON:", "skipCodeStartDirective": "//:SS:", "skipCodeEndDirective": "//:SE:", "augCodeDirective": "//:AUG_CODE:", "inlineGenCodeDirective": "//:GG:", "nestedLevelStartMarker": "[", "nestedLevelEndMarker": "]" }
{"fileId":1,"dir":"src","relativePath":"A1.py","augmentingCodes":[{"id":1,"directiveMarker":"//:AUG_CODE:","indent":"","lineNumber":1,"lineSeparator":"\n","nestedLevelNumber":0,"hasNestedLevelStartMarker":false,"hasNestedLevelEndMarker":false,"blocks":[{"stringify":false,"jsonify":false,"content":" Snippets.generateSerialVersionUID "}]}]}
{"fileId":2,"dir":"src","relativePath":"B2.py","augmentingCodes":[{"id":1,"directiveMarker":"//:AUG_CODE:","indent":"","lineNumber":1,"lineSeparator":"\n","nestedLevelNumber":0,"hasNestedLevelStartMarker":false,"hasNestedLevelEndMarker":false,"blocks":[{"stringify":false,"jsonify":false,"content":" Worker.stringify "},{"stringify":true,"jsonify":false,"content":" SELECT * FROM contacts "},{"stringify":true,"jsonify":false,"content":" WHERE contacts.id = ? "}]},{"id":2,"directiveMarker":"//:AUG_CODE:","indent":"","lineNumber":19,"lineSeparator":"\n","nestedLevelNumber":0,"hasNestedLevelStartMarker":false,"hasNestedLevelEndMarker":false,"blocks":[{"stringify":false,"jsonify":false,"content":" Snippets.generateSerialVersionUID "},{"stringify":false,"jsonify":true,"content":"{ \"name\": \"expired\", \"type\": \"boolean\" } "}]}]}

```

#### test-genCodes.json (expected output file)

```json
{}
{"fileId":1,"generatedCodes":[{"id":1,"contentParts":[{"content":"private static final int serialVersionUID = 23L;","exactMatch":false}]}]}
{"fileId":2,"generatedCodes":[{"id":1,"contentParts":[{"content":"\" SELECT * FROM contacts \n\" +","exactMatch":true},{"content":"\" WHERE contacts.id = ? \"","exactMatch":true}]},{"id":2,"contentParts":[{"content":"private static final int serialVersionUID = 23L;","exactMatch":false}]}]}

```

## Usage

The library's functionality is contained in the method `execute` of the class `ProcessCodeTask` in the `aaronicsubstances\code_augmentor_support` namespace of this package. The `execute` method takes a function object used for evaluating code generation requests and producing generated code snippets.

Instances of `ProcessCodeTask` have the following public fields:

   * `inputFile` - path to the code generation request. Must be the aug code file result of running the *code_aug_prepare* Ant task.
   * `outputFile` - path for writing out code generation response. Will be used as the gen code file input to the *code_aug_complete* Ant task.
   * `verbose` - boolean field which can be used with default verbose logging mechansim to enable printing of verbose mesages to standard output.
   * `allErrors` - array which contains any errors encountered during execution.
   
These methods can be overriden in a subclass:
   * `logVerbose`, `logInfo`, `logWarn` - methods which are called with a single string argument when a verbose message, normal message, or warning message is issued. By default normal and warning messages are printed to standard output, and verbose messages are ignored.

The `evalFunction` function argument of the `execute` method is called with 3 arguments. The first is name of a function to invoke in the current PHP scope, and the remaining two are an augmenting code object and a helper instance of the `ProcessCodeContext` class in the same `aaronicsubstances\code_augmentor_support` namespace. These remaining two arguments are the arguments passed to the function to be invoked.

The `evalFunction` is called with every augmenting code object encountered in the input file. It is expected to in turn call client-defined functions dynamically and receive from them a correponding generated code object to be written to the output file. As a convenience, it can return strings, content parts, and arrays of generated code objects.


### Public Fields and Methods of `ProcessCodeContext` instances

   * header - JSON object resulting from parsing first line of input file.
   * globalScope - an array provided for use by clients which remains throughout parsing of entire input file.
   * fileScope - an array provided for use by clients which is reset at the start of processing every line of input file.
   * fileAugCodes - JSON object resulting of parsing current line of input file other than first line.
   * augCodeIndex - index of `augCode` parameter in `fileAugCodes->augmentingCodes` array
   * newGenCode() - convenience method available to clients for creating a generated code object with empty `contentParts` array field.
   * newContent(content, exactMatch=false) - convenience method available to clients for creating a new content part object with fields set with arguments supplied to the function.

## Note on JSON serialization

This library deserializes JSON objects into instances of the buitlin `stdClass`. It similarly requires objects to be serialized to be either dictionaries or instances of `stdClass`. 

By so doing clients are provided with the convenience that any arbitrary field can be set on a JSON object, and it will get serialized.

## Further Information

For more information on the structure of augmenting code object, generated code object and other considerations, refer to [wiki](https://github.com/aaronicsubstances/code-augmentor/wiki/Documentation-for-Code-Generator-Scripts) in the main Code Augmentor repository.