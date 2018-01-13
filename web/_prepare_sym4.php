<?php
require_once __DIR__.'/../vendor/autoload.php';

use Silex\Application;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCompiler;
use Symfony\Component\Yaml\Yaml;

class RouteDumper
{
    public static function dump(Application $app)
    {
        $app->boot();
        $app->flush();
        foreach ($app['routes'] as $name => $route) {
            self::dumpRoute($name, $route);
        }
    }
    private static function dumpRoute(string $name, Route $route)
    {
        $dumpArray = ['path' => $route->getPath()];
        if ($route->getMethods()) {
            $dumpArray['methods'] = $route->getMethods();
        }
        $defaults = $route->getDefaults();
        if (isset($defaults['_controller'])) {
            $dumpArray['controller'] = $defaults['_controller'];
            unset($defaults['_controller']);
        }
        if ($defaults) {
            $dumpArray['defaults'] = $defaults;
        }
        if ($route->getRequirements()) {
            $dumpArray['requirements'] = $route->getRequirements();
        }
        $options = $route->getOptions();
        if (isset($options['compiler_class']) && RouteCompiler::class === $options['compiler_class']) {
            unset($options['compiler_class']);
        }
        if ($options) {
            $dumpArray['options'] = $route->getOptions();
        }
        if ($route->getHost()) {
            $dumpArray['host'] = $route->getHost();
        }
        if ($route->getSchemes()) {
            $dumpArray['schemes'] = $route->getSchemes();
        }
        if ($route->getCondition()) {
            $dumpArray['condition'] = $route->getCondition();
        }
        echo Yaml::dump([$name => $dumpArray]) . "\n";
    }
}

// Initialize your application here.
$app = require __DIR__.'/../src/app.php';
require __DIR__.'/../config/prod.php';
require __DIR__.'/../src/controllers.php';

RouteDumper::dump($app);
