<?php

declare(strict_types=1);

namespace App\Extension;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

class MatchAgainstExtension extends FunctionNode
{
    public Node $column;
    public Node $pattern;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->column = $parser->StringPrimary();
        $parser->match(TokenType::T_COMMA);
        $this->pattern = $parser->StringPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        $queryString = 'MATCH(';
        $queryString .= $this->column->dispatch($sqlWalker);
        $queryString .= ') AGAINST (';
        $queryString .= $this->pattern->dispatch($sqlWalker);
        $queryString .= ' IN BOOLEAN MODE)';

        return $queryString;
    }
}
