<?php

namespace OxygenSuite\PhpAst\AST;

use RuntimeException;

/**
 * A formula the tokenizer cannot read: an unknown function, a missing argument, a wrong argument count or text left
 * over.
 */
class ParseException extends RuntimeException {}
