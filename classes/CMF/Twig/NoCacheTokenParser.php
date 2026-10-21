<?php

namespace CMF\Twig;

use Twig\TokenParser\AbstractTokenParser,
    Twig\Token;

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Parses {% nocache %}...{% endnocache %} blocks
 */
class NoCacheTokenParser extends AbstractTokenParser
{
    /**
     * Parses a token and returns a node.
     *
     * @param Token $token
     *
     * @return NoCacheNode
     */
    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $template_name = $stream->getSourceContext()->getName();

        if ($stream->test(Token::BLOCK_END_TYPE)) {
            $stream->next();
            $body = $this->parser->subparse(array($this, 'decideBlockEnd'), true);
        } else {
            $body = $this->parser->parseExpression();
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new NoCacheNode($body, $lineno, $template_name);
    }

    public function decideBlockEnd(Token $token)
    {
        return $token->test('endnocache');
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag()
    {
        return 'nocache';
    }
}
