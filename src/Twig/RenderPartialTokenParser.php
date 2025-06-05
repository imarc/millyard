<?php

namespace Imarc\Millyard\Twig;

use Twig\Node\Expression\ArrayExpression;
use Twig\TokenParser\AbstractTokenParser;
use Twig\Token;
use Twig\Node\Node;

/**
 * Token parser for the {% render_partial %} tag.
 *
 * This parser allows rendering a partial template while ensuring that view composers
 * are properly applied to the template context. This is necessary because the standard
 * {% include %} tag does not trigger the timber/render/data filter that view composers
 * rely on.
 *
 * Usage in Twig templates:
 * {% render_partial 'partial-name.twig' %}
 *
 * @see \Imarc\Millyard\Views\ComposerRegistry
 * @see \Imarc\Millyard\Twig\RenderPartialNode
 */
class RenderPartialTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $templateExpr = $this->parser->getExpressionParser()->parseExpression();

        $withContextExpr = new ArrayExpression([], $lineno);

        // Check for "with"
        if ($stream->nextIf(Token::NAME_TYPE, 'with')) {
            $withContextExpr = $this->parser->getExpressionParser()->parseExpression();
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new RenderPartialNode(
            ['template' => $templateExpr, 'with_context' => $withContextExpr],
            [],
            $lineno,
            $this->getTag()
        );
    }

    public function getTag(): string
    {
        return 'render_partial';
    }
}
