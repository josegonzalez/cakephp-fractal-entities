<?php
namespace FractalEntities\View;

use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\Utility\Inflector;
use Cake\View\SerializedView;
use Exception;
use League\Fractal;
use League\Fractal\Manager;
use League\Fractal\Serializer\DataArraySerializer;
use League\Fractal\Serializer\SerializerAbstract;
use League\Fractal\TransformerAbstract;

/**
 * TransformerView class
 */
class TransformerView extends SerializedView
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

        $serializer = $this->_serializer();
        $manager = new Manager;
        $manager->setSerializer(new $serializer());
        return json_encode($manager->createData($resource)->toArray());
    }


    /**
     * Retrieves a configured serializer instance. Defaults to an instance of
     * \League\Fractal\Serializer\DataArraySerializer
     *
     * You can configure this by setting either:
     * - `_serializer`: An instance of SerializerAbstract
     * - `_serializerClass`: A class that can be instantiated
     *
     * @return League\Fractal\Serializer\SerializerAbstract an instance of SerializerAbstract
     */
    protected function _serializer()
    {
        $serializer = $this->get('_serializer', null);
        $serializerClass = $this->get('_serializerClass', null);
        if ($serializer === null) {
            if ($serializerClass === null) {
                $serializer = new DataArraySerializer;
            } else {
                $serializer = new $serializerClass;
            }
        }
        if (!($serializer instanceof SerializerAbstract)) {
            throw new Exception(sprintf('Configured Serializer not instance of SerializerAbstract: %s', get_class($serializer)));
        }
        return $serializer;
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
            $namespace = Configure::read('App.namespace');
            $path = array_filter([
                $namespace,
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
            throw new Exception(sprintf('Invalid Transformer class: %s', $transformerClass));
        }

        $transformer = new $transformerClass;
        if (!($transformer instanceof TransformerAbstract)) {
            throw new Exception(sprintf('Transformer class not instance of TransformerAbstract: %s', $transformerClass));
        }
        return $transformer;
    }
}
