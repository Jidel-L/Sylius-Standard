<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ECSPrefix202306\Symfony\Component\HttpKernel\Bundle;

use ECSPrefix202306\Symfony\Component\DependencyInjection\ContainerAwareInterface;
use ECSPrefix202306\Symfony\Component\DependencyInjection\ContainerBuilder;
use ECSPrefix202306\Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
/**
 * BundleInterface.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface BundleInterface extends ContainerAwareInterface
{
    /**
     * Boots the Bundle.
     */
    public function boot();
    /**
     * Shutdowns the Bundle.
     */
    public function shutdown();
    /**
     * Builds the bundle.
     *
     * It is only ever called once when the cache is empty.
     */
    public function build(ContainerBuilder $container);
    /**
     * Returns the container extension that should be implicitly loaded.
     */
    public function getContainerExtension() : ?ExtensionInterface;
    /**
     * Returns the bundle name (the class short name).
     */
    public function getName() : string;
    /**
     * Gets the Bundle namespace.
     */
    public function getNamespace() : string;
    /**
     * Gets the Bundle directory path.
     *
     * The path should always be returned as a Unix path (with /).
     */
    public function getPath() : string;
}
