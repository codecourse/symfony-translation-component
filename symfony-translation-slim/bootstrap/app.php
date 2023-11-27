<?php

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Finder\Finder;
use Noodlehaus\Config;

require __DIR__ . '/../vendor/autoload.php';

$app = new \Slim\App([
    'settings' => [
        'displayErrorDetails' => true,
    ]
]);

$container = $app->getContainer();

$container['config'] = function () {
    return new Config(__DIR__ . '/../config');
};

$container['translator'] = function ($container) {
    $config = $container->config;

    $translator = new Translator($config->get('app.locale'));
    $translator->setFallbackLocales([$config->get('app.default_locale')]);
    $translator->addLoader('array', new ArrayLoader);

    $finder = new Finder;
    $langDirs = $finder->directories()->ignoreUnreadableDirs()->in(__DIR__ . '/../resources/lang');

    foreach ($langDirs as $dir) {
        $translator->addResource(
            'array',
            (new Config($dir))->all(),
            $dir->getRelativePathName()
        );
    }

    return $translator;
};

$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig(__DIR__ . '/../resources/views', [
        'cache' => false
    ]);
    
    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($container['router'], $basePath));
    $view->addExtension(new App\Views\TranslateExtension($container['translator']));

    return $view;
};

require __DIR__ . '/../routes/web.php';
