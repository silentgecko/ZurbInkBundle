<?php

/*
 * This file is part of the zurb-ink-bundle package.
 *
 * (c) Marco Polichetti <gremo1982@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gremo\ZurbInkBundle\Twig\Parser;

use Gremo\ZurbInkBundle\Twig\Node\InlineCssNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig_Token;
use Twig_TokenParser;

class InlineCssTokenParser extends AbstractTokenParser
{
    /**
     * {@inheritdoc}
     */
    public function parse(Token $token)
    {
        $parser = $this->parser;
        $stream = $parser->getStream();

        $stream->expect(Token::BLOCK_END_TYPE);
        $html = $this->parser->subparse([$this, 'decideBlockEnd'], true);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new InlineCssNode($html, $token->getLine(), $this->getTag());
    }

    /**
     * @param Token $token
     * @return bool
     */
    public function decideBlockEnd(Token $token)
    {
        return $token->test('endinlinestyle');
    }

    /**
     * {@inheritdoc}
     */
    public function getTag()
    {
        return 'inlinestyle';
    }
}
