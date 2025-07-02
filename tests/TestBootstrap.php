<?php declare(strict_types=1);

use Shopware\Core\TestBootstrapper;

$loader = (new TestBootstrapper())
    ->addCallingPlugin()
    ->addActivePlugins('POSBasicExample')
    ->setForceInstallPlugins(true)
    ->bootstrap()
    ->getClassLoader();

$loader->addPsr4('POSBasicExample\\Tests\\', __DIR__);
