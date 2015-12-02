<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link      http://cakephp.org CakePHP(tm) Project
 * @since     3.0.0
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Josegonzalez\FractalEntities\View;

use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\Utility\Inflector;
use Cake\View\View;
use Exception;
use League\Fractal;
use League\Fractal\Manager;
use League\Fractal\Serializer\DataArraySerializer;
use League\Fractal\TransformerAbstract;

/**
 * TransformerView class
 */
class TransformerView extends View
{
    /**
     * Skip loading helpers if this is a _serialize based view.
     *
     * @return void
     */
    public function loadHelpers()
    {
        if (isset($this->viewVars['_serialize'])) {
            return;
        }
        parent::loadHelpers();
    }

    /**
     * Render an api response
     *
     * @param string $view The view being rendered.
     * @param string $layout The layout being rendered.
     * @return string The rendered api response
     */
    public function render($view = null, $layout = null)
    {
        if (isset($this->viewVars['_serialize'])) {
            return $this->_serialize();
        }
        if ($view !== false && $this->_getViewFileName($view)) {
            return parent::render($view, false);
        }
    }

    /**
     * Serialize view vars.
     *
     * @return string The serialized data
     */
    protected function _serialize()
    {
        $_serialize = (array)$this->get('_serialize', null);
        if (is_array($_serialize) && count($_serialize) > 1) {
            throw new Exception('You can only serialize a single variable');
        }

        $transformer = $this->_transformer();
        $_serialize = $this->get(array_pop($_serialize), null);
        if (is_array($_serialize) || $_serialize instanceof Query || $_serialize instanceof ResultSet) {
            $resource = new Fractal\Resource\Collection($_serialize, $transformer);
        } elseif ($_serialize instanceof EntityInterface) {
            $resource = new Fractal\Resource\Item($_serialize, $transformer);
        } else {
            throw new Exception('Unserializable variable');
        }

        $manager = new Manager();
        $manager->setSerializer(new DataArraySerializer());
        return json_encode($manager->createData($resource)->toArray());
    }

    /**
     * Retrieves a configured transformer instance. By default it will
     * use the current request to figure out the proper transformer class path
     *
     * You can configure this by setting either:
     * - `_transformer`: An instance of TransformerAbstract
     * - `_transformerClass`: A class that can be instantiated
     *
     * @return League\Fractal\TransformerAbstract an instance of TransformerAbstract
     */
    protected function _transformer()
    {
        $transformer = $this->get('_transformer', null);
        if ($transformer !== null) {
            if (!($transformer instanceof TransformerAbstract)) {
                throw new Exception(sprintf('Configured Transformer not instance of TransformerAbstract: %s', get_class($transformer)));
            }
            return $transformer;
        }

        $transformerClass = $this->get('_transformerClass', null);
        if ($transformerClass === null) {
            $path = array_filter([
                'App',
                'Transformer',
                $this->request->param('plugin'),
                $this->request->param('prefix'),
                $this->request->param('controller'),
                $this->request->param('action') . 'Transformer',
            ], 'strlen');
            $transformerClass = '\\' . implode('\\', array_map(function ($part) {
                return Inflector::camelize($part);
            }, $path));
        }

        if (!class_exists($transformerClass)) {
            throw new Exception(sprintf('Invalid Trransformer class: %s', $transformerClass));
        }

        $transformer = new $transformerClass;
        if (!($transformer instanceof TransformerAbstract)) {
            throw new Exception(sprintf('Transformer class not instance of TransformerAbstract: %s', $transformerClass));
        }
        return $transformer;
    }
}
