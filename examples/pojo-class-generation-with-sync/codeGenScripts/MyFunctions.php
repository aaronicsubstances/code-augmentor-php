<?php declare(strict_types=1);

class MyFunctions {
    
    public static function theClassProps($augCode, $context) {
        $context->fileScope['theClassProps'] = $augCode->args[0];
        $context->fileScope['theClassName'] = basename($context->fileAugCodes->relativePath, '.java');
        $out = '';
        foreach ($context->fileScope['theClassProps'] as $propSpec) {
            $out .= "private {$propSpec->type} {$propSpec->name};";
            $out .= $augCode->lineSeparator;
        }
        return $out;
    }

    public static function generateClassProps($augCode, $context) {
        $out = '';
        $defaultIndent = $context->getScopeVar('codeAugmentor_indent');
        foreach ($context->fileScope['theClassProps'] as $propSpec) {
            $capitalized = ucfirst($propSpec->name);
            $out .= "public {$propSpec->type} get{$capitalized}() {";
            $out .= $augCode->lineSeparator;
            $out .= "{$defaultIndent}return {$propSpec->name};";
            $out .= $augCode->lineSeparator;
            $out .= "}{$augCode->lineSeparator}";
            $out .= "public void set{$capitalized}({$propSpec->type} {$propSpec->name}) {";
            $out .= $augCode->lineSeparator;
            $out .= "{$defaultIndent}this.{$propSpec->name} = {$propSpec->name};";
            $out .= $augCode->lineSeparator;
            $out .= "}{$augCode->lineSeparator}";
            $out .= $augCode->lineSeparator;
        }
        return $out;
    }

    public static function generateEqualsAndHashCode($augCode, $context) {
        // don't override if empty.
        if (count($context->fileScope['theClassProps']) == 0) {
            return '';
        }
        
        $out = '';
        $defaultIndent = $context->getScopeVar('codeAugmentor_indent');

        // generate equals() override
        $out .= "@Override{$augCode->lineSeparator}";
        $out .= "public boolean equals(Object obj) {";
        $out .= $augCode->lineSeparator;
        $out .= "{$defaultIndent}if (!(obj instanceof {$context->fileScope['theClassName']})) {";
        $out .= $augCode->lineSeparator;
        $out .= "{$defaultIndent}{$defaultIndent}return false;";
        $out .= $augCode->lineSeparator;
        $out .= "{$defaultIndent}" . '}';
        $out .= $augCode->lineSeparator;
        $out .= "{$defaultIndent}{$context->fileScope['theClassName']} other = ({$context->fileScope['theClassName']}) obj;";
        $out .= $augCode->lineSeparator;
        
        foreach ($context->fileScope['theClassProps'] as $propSpec) {
            if (ctype_upper($propSpec->type[0])) {
                $out .= $defaultIndent;
                $out .= 'if (!Objects.equals(this.';
                $out .= $propSpec->name;
                $out .= ', other.'; 
                $out .= $propSpec->name;
                $out .= ')) {';
            }
            else {
                $out .= $defaultIndent;
                $out .= 'if (this.';
                $out .= $propSpec->name;
                $out .= ' != other.';
                $out .= $propSpec->name;
                $out .= ') {';
            }
            $out .= $augCode->lineSeparator;
            $out .= "{$defaultIndent}{$defaultIndent}return false;";
            $out .= $augCode->lineSeparator;
            $out .= $defaultIndent . '}';
            $out .= $augCode->lineSeparator;
        }

        $out .= "{$defaultIndent}return true;{$augCode->lineSeparator}";
        $out .= '}';
        $out .= $augCode->lineSeparator;
        $out .= $augCode->lineSeparator;
        
        // generate hashCode() override with Objects.hashCode()
        $out .= "@Override{$augCode->lineSeparator}";
        $out .= "public int hashCode() {";
        $out .= $augCode->lineSeparator;
        if (count($context->fileScope['theClassProps']) == 1) {
            $out .= "{$defaultIndent}return Objects.hashCode(";
            $out .= $context->fileScope['theClassProps'][0]->name;
        }
        else {
            $out .= "{$defaultIndent}return Objects.hash(";
            for ($i = 0; $i < count($context->fileScope['theClassProps']); $i++) {
                if ($i > 0) {
                    $out .= ', ';
                }
                $out .= $context->fileScope['theClassProps'][$i]->name;
            }
        }
        $out .= ");{$augCode->lineSeparator}";
        $out .= '}';
        $out .= $augCode->lineSeparator;
        return $out;
    }

    public static function generateToString($augCode, $context) {
        $defaultIndent = $context->getScopeVar('codeAugmentor_indent');
        $out = '';
        $out .= "@Override{$augCode->lineSeparator}";
        $out .= "public String toString() {";
        $out .= $augCode->lineSeparator;
        $out .= "{$defaultIndent}return String.format(getClass().getSimpleName() + ";
        $exactOut = '"{';
        $outArgs = '';
        for ($i = 0; $i < count($context->fileScope['theClassProps']); $i++) {
            if ($i > 0) {
                $exactOut .= ', ';
                $outArgs .= ', ';
            }
            $exactOut .= $context->fileScope['theClassProps'][$i]->name . '=%s';
            $outArgs .= $context->fileScope['theClassProps'][$i]->name;
        }
        $exactOut .= '}"';
        $g = $context->newGenCode();
        $g->contentParts[] = $context->newContent($out);
        $g->contentParts[] = $context->newContent($exactOut, TRUE);
        $out = '';
        if ($outArgs) {
            $out .= ",";
            $out .= $augCode->lineSeparator;
            $out .= $defaultIndent;
            $out .= $defaultIndent;
        }
        $out .= $outArgs;
        $out .= ");{$augCode->lineSeparator}";
        $out .= '}';
        $out .= $augCode->lineSeparator;
        $g->contentParts[] = $context->newContent($out);
        return $g;
    }
}