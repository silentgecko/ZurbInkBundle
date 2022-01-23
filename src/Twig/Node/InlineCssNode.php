<?php

/*
 * This file is part of the zurb-ink-bundle package.
 *
 * (c) Marco Polichetti <gremo1982@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gremo\ZurbInkBundle\Twig\Node;

use Gremo\ZurbInkBundle\Twig\GremoZurbInkExtension;
use Twig\Compiler;
use Twig\Node\Node;

class InlineCssNode extends Node
{
    public function __construct(Node $html, $lineno = 0, $tag = 'inlinestyle')
    {
        parent::__construct(['html' => $html], [], $lineno, $tag);
    }

    /**
     * {@inheritdoc}
     */
    public function compile(Compiler $compiler)
    {
        $extensionName = GremoZurbInkExtension::class;
        $compiler
            ->addDebugInfo($this)
            ->write('ob_start();'.PHP_EOL)
            ->subcompile($this->getNode('html'))
            ->write('$html = ob_get_clean();'.PHP_EOL)
            ->write("\$extension = \$this->env->getExtension('{$extensionName}');".PHP_EOL)
            ->write('echo $extension->inlineCss($html);'.PHP_EOL)
            ->write('$extension->removeStylesheet();'.PHP_EOL)
        ;
    }
}
