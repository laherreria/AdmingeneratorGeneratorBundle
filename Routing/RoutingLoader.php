<?php

namespace Admingenerator\GeneratorBundle\Routing;

use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Finder\Finder;

class RoutingLoader extends FileLoader
{
    // Assoc beetween a controller and is route path
    //@todo make an object for this
    protected $actions = array(
        'list' => array(
                    'pattern'      => '/',
                    'defaults'     => array(),
                    'requirements' => array(),
                ),
        'batch' => array(
                    'pattern'      => '/batch',
                    'defaults'     => array(),
                    'requirements' => array(
                        '_method' => 'POST'
                    ),
                    'controller'   => 'list',
                ),
        'delete' => array(
                    'pattern'      => '/{pk}/delete',
                    'defaults'     => array(),
                    'requirements' => array(),
                ),
        'edit' => array(
                    'pattern'      => '/{pk}/edit',
                    'defaults'     => array(),
                    'requirements' => array(),
                ),
        'update' => array(
                    'pattern'      => '/{pk}/update',
                    'defaults'     => array(),
                    'requirements' => array(),
                    'controller'   => 'edit',
                ),
        'show' => array(
                    'pattern'      => '/{pk}/show',
                    'defaults'     => array(),
                    'requirements' => array()
                ),
        'new' => array(
                    'pattern'      => '/new',
                    'defaults'     => array(),
                    'requirements' => array(),
                ),
        'create' => array(
                    'pattern'      => '/create',
                    'defaults'     => array(),
                    'requirements' => array(),
                    'controller'   => 'new',
                ),
        'filters' => array(
                    'pattern'      => '/filter',
                    'defaults'     => array(),
                    'requirements' => array(),
                    'controller'   => 'list',
                ),
        'scopes' => array(
                    'pattern'      => '/scope/{group}/{scope}',
                    'defaults'     => array(),
                    'requirements' => array(),
                    'controller'   => 'list',
                ),
    );

    public function load($resource, $type = null)
    {
        $collection = new RouteCollection();

        $resource = str_replace('\\', '/', $resource);
        $namespace = $this->getNamespaceFromResource($resource);
        $fullBundleName = $this->getFullBundleNameFromResource($resource);
        $bundle_name = $this->getBundleNameFromResource($resource);

        foreach ($this->actions as $controller => $datas) {
            $action = 'index';

            $loweredNamespace = str_replace(array('/', '\\'), '_', $namespace);
            if ($controller_folder = $this->getControllerFolder($resource)) {
                $route_name = $loweredNamespace . '_' . $bundle_name . '_' . $controller_folder . '_' . $controller;
            } else {
                $route_name = $loweredNamespace . '_' . $bundle_name . '_' . $controller;
            }

            if (isset($datas['controller'])) {
                $action     = $controller;
                $controller = $datas['controller'];
            }

            $controllerName = $resource.ucfirst($controller).'Controller.php';
            if (is_file($controllerName)) {
                if ($controller_folder) {
                    $datas['defaults']['_controller'] = $namespace . '\\'
                            . $bundle_name . '\\Controller\\'
                            . $controller_folder . '\\'
                            . ucfirst($controller) . 'Controller::'
                            . $action . 'Action';
                } else {
                    $datas['defaults']['_controller'] = $loweredNamespace
                            . $bundle_name . ':'
                            . ucfirst($controller) . ':' . $action;
                }

                $route = new Route($datas['pattern'], $datas['defaults'], $datas['requirements']);
                $collection->add($route_name, $route);
                $collection->addResource(new FileResource($controllerName));
            }
        }

        // Import other routes from a controller directory (@Route annotation)
        if ($controller_folder) {
            $annotationRouteName = '@' . $fullBundleName . '/Controller/' . $controller_folder . '/';
            $collection->addCollection($this->import($annotationRouteName, 'annotation'));
        }

        return $collection;
    }

    public function supports($resource, $type = null)
    {
        return 'admingenerator' == $type;
    }

    protected function getControllerFolder($resource)
    {
        preg_match('#.+/.+Bundle/Controller?/(.*?)/?$#', $resource, $matches);

        return $matches[1];
    }

    protected function getFullBundleNameFromResource($resource)
    {
        // Find the *Bundle.php
        $finder = Finder::create()
            ->name('*Bundle.php')
            ->depth(0)
            ->in(realpath($resource.'/../../')) // ressource is controller folder
            ->getIterator();

        foreach ($finder as $file) {
            return $file->getBasename('.'.$file->getExtension());
        }
    }

    protected function getBundleNameFromResource($resource)
    {
        preg_match('#.+/(.+Bundle)/Controller?/(.*?)/?$#', $resource, $matches);

        return $matches[1];
    }

    protected function getNamespaceFromResource($resource)
    {
        $finder = Finder::create()
            ->name('*Bundle.php')
            ->depth(0)
            ->in(realpath($resource.'/../../')) // ressource is controller folder
            ->getIterator();

        foreach ($finder as $file) {
            preg_match('/namespace (.+);/', file_get_contents($file->getRealPath()), $matches);

            return implode('\\', explode('\\', $matches[1], -1)); // Remove the short bundle name
        }

    }
}
