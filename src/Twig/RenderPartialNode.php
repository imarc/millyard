<?php

namespace Imarc\Millyard\Twig;

use Twig\Compiler;
use Twig\Node\Node;

/**
 * Node class for the {% render_partial %} tag.
 *
 * This node compiles the render_partial tag into PHP code that uses Timber::compile()
 * to render a template while ensuring that view composers are properly applied to
 * the template context.
 *
 * The compiled code:
 * 1. Gets the template name from the expression
 * 2. Uses Timber::compile() to render the template
 * 3. Applies the timber/render/data filter to ensure view composers are triggered
 *
 * @see \Imarc\Millyard\Twig\RenderPartialTokenParser
 * @see \Imarc\Millyard\Views\ComposerRegistry
 */
class RenderPartialNode extends Node
{
    public function __construct($nodes, $attributes, $lineno, $tag = null)
    {
        parent::__construct($nodes, $attributes, $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)

            ->write('$template_name = ')
            ->subcompile($this->getNode('template'))
            ->raw(";\n")

            ->write('$with_context = ')
            ->subcompile($this->getNode('with_context'))
            ->raw(";\n")

            ->write('$base_context = apply_filters("timber/render/data", \\Timber\\Timber::context(), $template_name);' . "\n")
            ->write('$merged_context = array_merge($base_context, $with_context);' . "\n")

            ->write('echo \\Timber\\Timber::compile($template_name, $merged_context);' . "\n");

    }
}
