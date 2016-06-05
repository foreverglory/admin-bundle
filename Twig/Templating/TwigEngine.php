<?php

/*
 * This file is part of the current project.
 * 
 * (c) ForeverGlory <http://foreverglory.me/>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glory\Bundle\AdminBundle\Twig\Templating;

use Symfony\Bundle\TwigBundle\TwigEngine as BaseEngine;
use Symfony\Component\Templating\EngineInterface;

/**
 * Description of TwigEngine
 *
 * Twig引擎重写，增加render时，判断是否属于后台页面，替换模板
 * 如果属于后台页面，则使用后台模板"GloryAdminBundle::layout.html.twig"，并且加载当前模板里面的 block title/stylesheets/javascripts/content
 * 
 * @author ForeverGlory <foreverglory@qq.com>
 */
class TwigEngine extends BaseEngine
{

    /**
     * @var EngineInterface 
     */
    protected $engine;
    protected $admin;

    public function setEngine($engine)
    {
        $this->engine = $engine;
    }

    public function setAdmin($admin)
    {
        $this->admin = $admin;
    }

    /**
     * {@inheritdoc}
     */
    public function render($name, array $parameters = array())
    {
        if (!$this->admin->inAdmin()) {
            return $this->engine->render($name, $parameters);
        }
        $layoutName = 'GloryAdminBundle::layout.html.twig';
        $template = $this->load($name);
        if (in_array($layoutName, $this->getTemplates($template))) {
            return $this->engine->render($name, $parameters);
        }

        $layout = $this->load($layoutName);
        $blocks = $this->getBlocks($template, $parameters);

//        $blocks = [];
//        foreach (['title', 'stylesheets', 'javascripts', 'content'] as $name) {
//            if (false !== $block = $this->getBlock($template, $name)) {
//                $blocks[$name] = $block;
//            }
//        }

        ob_start();
        try {
            $layout->display($parameters, $blocks);
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }
        return ob_get_clean();
    }

    protected function getTemplates(\Twig_Template $template)
    {
        $names = [];
        $names[] = $template->getTemplateName();
        if (false !== $parent = $template->getParent([])) {
            $names = array_merge($names, $this->getTemplates($parent));
        }
        return $names;
    }

    protected function getBlock(\Twig_Template $template, $name)
    {
        if ($template->hasBlock($name)) {
            $blocks = $template->getBlocks();
            return $blocks[$name];
        } elseif (false !== $parent = $template->getParent([])) {
            return $this->getBlock($parent, $name);
        }
        return false;
    }

    protected function getBlocks(\Twig_Template $template, $parameters = [])
    {
        $blocks = [];
        if (false !== $parent = $template->getParent($parameters)) {
            $blocks = $this->getBlocks($parent);
        }
        return array_merge($blocks, $template->getBlocks());
    }

    public function exists($name)
    {
        return $this->engine->exists($name);
    }

    public function stream($name, array $parameters = array())
    {
        return $this->engine->stream($name, $parameters);
    }

    public function supports($name)
    {
        return $this->engine->supports($name);
    }

}