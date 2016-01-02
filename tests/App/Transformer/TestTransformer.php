<?php
namespace FractalEntities\Test\App\Transformer;

use Cake\Datasource\EntityInterface;
use League\Fractal\TransformerAbstract;

class TestTransformer extends TransformerAbstract
{
    public function transform($data)
    {
        if (is_object($data) && $data instanceof EntityInterface) {
            return $data->toArray();
        }
        return $data;
    }
}
