<?php

declare (strict_types=1);
namespace Laminas\Code\Scanner;

use function array_key_last;
use function array_pop;
use function array_push;
use function current;
use function next;
use function preg_match;
use function reset;
use function strlen;
use function strpos;
use function substr;
use function trim;
/** @internal this class is not part of the public API of this package */
class Doc_Block_Scanner
{
    /** @var bool */
    protected $is_scanned = false;
    /** @var string */
    protected $short_description = '';
    /** @var string */
    protected $long_description = '';
    /** @var array */
    protected $tags = [];
    /**
     * @param  string $docComment
     */
    public function __construct(protected $doc_comment)
    {
    }
    /**
     * @return string
     */
    public function get_short_description()
    {
        $this->scan();
        return $this->short_description;
    }
    /**
     * @return string
     */
    public function get_long_description()
    {
        $this->scan();
        return $this->long_description;
    }
    /**
     * @return array
     */
    public function get_tags()
    {
        $this->scan();
        return $this->tags;
    }
    /**
     * @return void
     */
    protected function scan()
    {
        if ($this->is_scanned) {
            return;
        }
        $mode = 1;
        $tokens = $this->tokenize();
        $tag_index = null;
        reset($tokens);
        SCANNER_TOP:
        $token = current($tokens);
        switch ($token[0]) {
            case 'DOCBLOCK_NEWLINE':
                if ($this->short_description != '' && $tag_index === null) {
                    $mode = 2;
                } else {
                    $this->long_description .= $token[1];
                }
                goto SCANNER_CONTINUE;
            //goto no break needed
            case 'DOCBLOCK_WHITESPACE':
            case 'DOCBLOCK_TEXT':
                if ($tag_index !== null) {
                    $this->tags[$tag_index]['value'] .= $this->tags[$tag_index]['value'] == '' ? $token[1] : ' ' . $token[1];
                    goto SCANNER_CONTINUE;
                } elseif ($mode <= 2) {
                    if ($mode == 1) {
                        $this->short_description .= $token[1];
                    } else {
                        $this->long_description .= $token[1];
                    }
                    goto SCANNER_CONTINUE;
                }
            //gotos no break needed
            // no break
            case 'DOCBLOCK_TAG':
                array_push($this->tags, ['name' => $token[1], 'value' => '']);
                $tag_index = array_key_last($this->tags);
                $mode = 3;
                goto SCANNER_CONTINUE;
            //goto no break needed
            case 'DOCBLOCK_COMMENTEND':
                goto SCANNER_END;
        }
        SCANNER_CONTINUE:
        if (next($tokens) === false) {
            goto SCANNER_END;
        }
        goto SCANNER_TOP;
        SCANNER_END:
        $this->short_description = trim($this->short_description);
        $this->long_description = trim($this->long_description);
        $this->is_scanned = true;
    }
    /**
     * @phpcs:disable Generic.Formatting.MultipleStatementAlignment.NotSame
     */
    protected function tokenize(): array
    {
        static $CONTEXT_INSIDE_DOCBLOCK = 0x1;
        static $CONTEXT_INSIDE_ASTERISK = 0x2;
        $context = 0x0;
        $stream = $this->doc_comment;
        $stream_index = null;
        $tokens = [];
        $token_index = null;
        $current_char = null;
        $current_word = null;
        $current_line = null;
        $MACRO_STREAM_ADVANCE_CHAR = function ($positions_forward = 1) use (&$stream, &$stream_index, &$current_char, &$current_word, &$current_line): false|string {
            $positions_forward = $positions_forward > 0 ? $positions_forward : 1;
            $stream_index = $stream_index === null ? 0 : $stream_index + $positions_forward;
            if (!isset($stream[$stream_index])) {
                $current_char = false;
                return false;
            }
            $current_char = $stream[$stream_index];
            $matches = [];
            $current_line = preg_match('#(.*?)\r?\n#', $stream, $matches, 0, $stream_index) === 1 ? $matches[1] : substr($stream, $stream_index);
            if ($current_char === ' ') {
                $current_word = preg_match('#( +)#', $current_line, $matches) === 1 ? $matches[1] : $current_line;
            } else {
                $current_word = ($matches = strpos($current_line, ' ')) !== false ? substr($current_line, 0, $matches) : $current_line;
            }
            return $current_char;
        };
        $MACRO_STREAM_ADVANCE_WORD = function () use (&$current_word, &$MACRO_STREAM_ADVANCE_CHAR) {
            return $MACRO_STREAM_ADVANCE_CHAR(strlen((string) $current_word));
        };
        $MACRO_STREAM_ADVANCE_LINE = function () use (&$current_line, &$MACRO_STREAM_ADVANCE_CHAR) {
            return $MACRO_STREAM_ADVANCE_CHAR(strlen((string) $current_line));
        };
        $MACRO_TOKEN_ADVANCE = function () use (&$token_index, &$tokens): void {
            $token_index = $token_index === null ? 0 : $token_index + 1;
            $tokens[$token_index] = ['DOCBLOCK_UNKNOWN', ''];
        };
        $MACRO_TOKEN_SET_TYPE = function ($type) use (&$token_index, &$tokens): void {
            $tokens[$token_index][0] = $type;
        };
        $MACRO_TOKEN_APPEND_CHAR = function () use (&$current_char, &$tokens, &$token_index): void {
            $tokens[$token_index][1] .= $current_char;
        };
        $MACRO_TOKEN_APPEND_WORD = function () use (&$current_word, &$tokens, &$token_index): void {
            $tokens[$token_index][1] .= $current_word;
        };
        $MACRO_TOKEN_APPEND_LINE = function () use (&$current_line, &$tokens, &$token_index): void {
            $tokens[$token_index][1] .= $current_line;
        };
        $MACRO_STREAM_ADVANCE_CHAR();
        $MACRO_TOKEN_ADVANCE();
        TOKENIZER_TOP:
        if ($context === 0x0 && $current_char === '/' && $current_word === '/**') {
            $MACRO_TOKEN_SET_TYPE('DOCBLOCK_COMMENTSTART');
            $MACRO_TOKEN_APPEND_WORD();
            $MACRO_TOKEN_ADVANCE();
            $context |= $CONTEXT_INSIDE_DOCBLOCK;
            $context |= $CONTEXT_INSIDE_ASTERISK;
            if ($MACRO_STREAM_ADVANCE_WORD() === false) {
                goto TOKENIZER_END;
            }
            goto TOKENIZER_TOP;
        }
        if ($context & $CONTEXT_INSIDE_DOCBLOCK && $current_word === '*/') {
            $MACRO_TOKEN_SET_TYPE('DOCBLOCK_COMMENTEND');
            $MACRO_TOKEN_APPEND_WORD();
            $MACRO_TOKEN_ADVANCE();
            $context &= ~$CONTEXT_INSIDE_DOCBLOCK;
            if ($MACRO_STREAM_ADVANCE_WORD() === false) {
                goto TOKENIZER_END;
            }
            goto TOKENIZER_TOP;
        }
        if ($current_char === ' ' || $current_char === "\t") {
            $MACRO_TOKEN_SET_TYPE($context & $CONTEXT_INSIDE_ASTERISK ? 'DOCBLOCK_WHITESPACE' : 'DOCBLOCK_WHITESPACE_INDENT');
            $MACRO_TOKEN_APPEND_WORD();
            $MACRO_TOKEN_ADVANCE();
            if ($MACRO_STREAM_ADVANCE_WORD() === false) {
                goto TOKENIZER_END;
            }
            goto TOKENIZER_TOP;
        }
        if ($current_char === '*') {
            if ($context & $CONTEXT_INSIDE_DOCBLOCK && $context & $CONTEXT_INSIDE_ASTERISK) {
                $MACRO_TOKEN_SET_TYPE('DOCBLOCK_TEXT');
            } else {
                $MACRO_TOKEN_SET_TYPE('DOCBLOCK_ASTERISK');
                $context |= $CONTEXT_INSIDE_ASTERISK;
            }
            $MACRO_TOKEN_APPEND_CHAR();
            $MACRO_TOKEN_ADVANCE();
            if ($MACRO_STREAM_ADVANCE_CHAR() === false) {
                goto TOKENIZER_END;
            }
            goto TOKENIZER_TOP;
        }
        if ($current_char === '@') {
            $MACRO_TOKEN_SET_TYPE('DOCBLOCK_TAG');
            $MACRO_TOKEN_APPEND_WORD();
            $MACRO_TOKEN_ADVANCE();
            if ($MACRO_STREAM_ADVANCE_WORD() === false) {
                goto TOKENIZER_END;
            }
            goto TOKENIZER_TOP;
        }
        if ($current_char === "\n") {
            $MACRO_TOKEN_SET_TYPE('DOCBLOCK_NEWLINE');
            $MACRO_TOKEN_APPEND_CHAR();
            $MACRO_TOKEN_ADVANCE();
            $context &= ~$CONTEXT_INSIDE_ASTERISK;
            if ($MACRO_STREAM_ADVANCE_CHAR() === false) {
                goto TOKENIZER_END;
            }
            goto TOKENIZER_TOP;
        }
        $MACRO_TOKEN_SET_TYPE('DOCBLOCK_TEXT');
        $MACRO_TOKEN_APPEND_LINE();
        $MACRO_TOKEN_ADVANCE();
        if ($MACRO_STREAM_ADVANCE_LINE() === false) {
            goto TOKENIZER_END;
        }
        goto TOKENIZER_TOP;
        TOKENIZER_END:
        array_pop($tokens);
        return $tokens;
    }
}