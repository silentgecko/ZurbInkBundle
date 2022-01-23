<?php

/*
 * This file is part of the zurb-ink-bundle package.
 *
 * (c) Marco Polichetti <gremo1982@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gremo\ZurbInkBundle\Twig;

use Gremo\ZurbInkBundle\Twig\Parser\InkyTokenParser;
use Gremo\ZurbInkBundle\Twig\Parser\InlineCssTokenParser;
use Gremo\ZurbInkBundle\Util\HtmlUtils;
use Symfony\Component\Config\FileLocatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class GremoZurbInkExtension extends AbstractExtension
{
    const NAME = 'gremo_zurb_ink';

    private $htmlUtils;
    private $fileLocator;
    private $rootDir;
    private $inlineResources = [];

    public function __construct(HtmlUtils $htmlUtils, FileLocatorInterface $fileLocator, $rootDir)
    {
        $this->htmlUtils = $htmlUtils;
        $this->fileLocator = $fileLocator;
        $this->rootDir = $rootDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenParsers(): array
    {
        return [
            new InkyTokenParser(),
            new InlineCssTokenParser(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('zurb_ink_add_stylesheet', [$this, 'addStylesheet']),
        ];
    }

    /**
     * @param string $resource
     * @param bool $alsoOutput
     * @return null|string
     */
    public function addStylesheet(string $resource, bool $alsoOutput = false): ?string
    {
        if (!isset($this->inlineResources[$resource])) {
            $this->inlineResources[$resource] = $this->getAbsolutePath($resource);
        }

        if ($alsoOutput) {
            return $this->getContents($resource);
        }

        return null;
    }

    /**
     * @param string|null $resource
     */
    public function removeStylesheet(string $resource = null)
    {
        if (null === $resource) {
            $this->inlineResources = [];

            return;
        }

        unset($this->inlineResources[$resource]);
    }

    /**
     * @param string $html
     * @return string
     */
    public function inlineCss(string $html): string
    {
        return $this->htmlUtils->inlineCss($html, $this->getContents($this->inlineResources));
    }

    /**
     * @param string $contents
     * @return string
     */
    public function convertInkyToHtml(string $contents): string
    {
        return $this->htmlUtils->parseInky($contents);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param array|string $resources
     * @return string
     */
    private function getContents($resources): string
    {
        $styles = [];
        foreach ((array) $resources as $key => $resource) {
            // Resource key already in the cache of inlined resources, avoid locating it
            if (isset($this->inlineResources[$key])) {
                $resource = $this->inlineResources[$key];
            } else {
                $resource = $this->getAbsolutePath($resource);
            }

            $styles[] = file_get_contents($resource);
        }

        return implode(PHP_EOL, $styles);
    }

    /**
     * @param string $resource
     * @return string
     */
    private function getAbsolutePath(string $resource): string
    {
        // It seems that there is no way in Symfony 4 to get the absolute path to a given file in the "assets" folder.
        // So we first try the file locator (which, in the first place, will handle all resources starting with "@").
        $assetsDir = realpath(rtrim($this->rootDir, '\\/').'/assets');
        return $this->fileLocator->locate($resource, $assetsDir);
    }
}
