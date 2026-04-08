<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * MySQL JSON extraction: JSON_EXTRACT(column, path)
 *
 * Usage in DQL: JSON_GET_TEXT(p.price, '$.amount') >= :minPrice
 */
class JsonGetText extends FunctionNode
{
    private $jsonField;
    private $jsonPath;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            "JSON_EXTRACT(%s, %s)",
            $this->jsonField->dispatch($sqlWalker),
            $this->jsonPath->dispatch($sqlWalker)
        );
    }

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->jsonField = $parser->StateFieldPathExpression();
        $parser->match(TokenType::T_COMMA);
        $this->jsonPath = $parser->StringPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}
