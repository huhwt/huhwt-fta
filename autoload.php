<?php

use Composer\Autoload\ClassLoader;

$loader = new ClassLoader();
$loader->addPsr4('HuHwt\\WebtreesMods\\FamilyTreeAssistant\\', __DIR__);
$loader->addPsr4('HuHwt\\WebtreesMods\\FamilyTreeAssistant\\Exceptions\\', __DIR__ . "/Exceptions");
$loader->addPsr4('HuHwt\\WebtreesMods\\FamilyTreeAssistant\\Http\\RequestHandlers\\', __DIR__ . "/Http/Requesthandlers");
$loader->addPsr4('HuHwt\\WebtreesMods\\FamilyTreeAssistant\\Module\\InteractiveTree\\', __DIR__ . "/Module");
$loader->addPsr4('HuHwt\\WebtreesMods\\FamilyTreeAssistant\\Module\\InteractiveTree\\', __DIR__ . "/Module/InteractiveTree");

$loader->register();
