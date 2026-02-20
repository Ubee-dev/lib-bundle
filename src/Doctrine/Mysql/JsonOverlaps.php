<?php

namespace UbeeDev\LibBundle\Doctrine\Mysql;

use Doctrine\ORM\Query\AST\ASTException;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

class JsonOverlaps extends FunctionNode
{
    public ?Node $firstArgument = null;
    public ?Node $secondArgument = null;

    /**
     * @throws QueryException
     */
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER); // Nom de la fonction
        $parser->match(TokenType::T_OPEN_PARENTHESIS); // Ouverture de la parenthèse

        // Premier argument
        $this->firstArgument = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_COMMA); // Virgule entre les arguments

        // Second argument
        $this->secondArgument = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS); // Fermeture de la parenthèse
    }

    /**
     * @throws ASTException
     */
    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'JSON_OVERLAPS(' .
            $this->firstArgument->dispatch($sqlWalker) . ', ' .
            $this->secondArgument->dispatch($sqlWalker) .
        ')';
    }
}
