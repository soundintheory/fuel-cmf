<?php

namespace CMF\Twig;

use Twig_TokenParser,
    Twig_Token,
    Twig_Parser;

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Base class for all token parsers.
 *
 * @package twig
 * @author  Fabien Potencier <fabien@symfony.com>
 */
class NoCacheTokenParser extends Twig_TokenParser
{
    /**
     * Sets the parser associated with this token parser
     *
     * @param $parser A Twig_Parser instance
     */
    public function setParser(Twig_Parser $parser)
    {
        $this->parser = $parser;
    }
    
    /**
     * Parses a token and returns a node.
     *
     * @param Twig_Token $token A Twig_Token instance
     *
     * @return Twig_NodeInterface A Twig_NodeInterface instance
     */
    public function parse(Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $filename = $stream->getFilename();
        $expr = null;
        
        if ($stream->test(Twig_Token::BLOCK_END_TYPE)) {
            $stream->next();
            $body = $this->parser->subparse(array($this, 'decideBlockEnd'), true);
        } else {
            $body = $this->parser->getExpressionParser()->parseExpression();
        }
        $stream->expect(Twig_Token::BLOCK_END_TYPE);
        
        return new NoCacheNode($body, $lineno, $filename, $this->getTag());
    }
    
    public function decideBlockEnd(Twig_Token $token)
    {
        return $token->test('endnocache');
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @param string The tag name
     */
    public function getTag()
    {
        return 'nocache';
    }
}
