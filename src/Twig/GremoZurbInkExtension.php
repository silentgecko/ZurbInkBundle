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
use Symfony\Component\HttpKernel\Kernel;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig_SimpleFunction;

class GremoZurbInkExtension extends AbstractExtension
{
    const NAME = 'gremo_zub_ink';

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
    public function getTokenParsers()
    {
        return [
            new InkyTokenParser(),
            new InlineCssTokenParser(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
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
    public function addStylesheet($resource, $alsoOutput = false)
    {
        if (!isset($this->inlineResources[$resource])) {
            $this->inlineResources[$resource] = $this->getAbsolutePath($resource);
        }

        if ($alsoOutput) {
            return $this->getContents($resource);
        }
    }

    /**
     * @param null|string $resource
     */
    public function removeStylesheet($resource = null)
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
    public function inlineCss($html)
    {
        return $this->htmlUtils->inlineCss($html, $this->getContents($this->inlineResources));
    }

    /**
     * @param string $contents
     * @return string
     */
    public function convertInkyToHtml($contents)
    {
        return $this->htmlUtils->parseInky($contents);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param array|string $resources
     * @return string
     */
    private function getContents($resources)
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
    private function getAbsolutePath($resource)
    {
        // It seems that there is no way in Symfony 4 to get the absolute path to a given file in the "assets" folder.
        // So we first try the file locator (which, in the first place, will handle all resources starting with "@").
        // The service will also look in the right folders for Symfony 2/3, but will fail for Symfony 4 with its new
        // directory structure (fail in the sense that it doesn't look into the "assets" folder).
        try {
            return $this->fileLocator->locate($resource);
        } catch (\Exception $exception) {
            // Only for Symfony 4, try also the "assets" folder (this will not work for customs "assets" folder)
            if (version_compare(Kernel::VERSION, 4, '>=')) {
                $assetsDir = realpath(rtrim($this->rootDir, '\\/').'/assets');
                if ($assetsDir) {
                    return $this->fileLocator->locate($resource, $assetsDir);
                }
            }

            throw $exception;
        }
    }
}
