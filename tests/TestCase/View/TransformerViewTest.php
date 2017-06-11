<?php
namespace FractalEntities\Test\TestCase;

use App\Transformer\TestTransformer;
use Cake\TestSuite\TestCase;
use FractalEntities\View\TransformerView;
use stdClass;

class TransformerViewTest extends TestCase
{
    public function testLoadHelpers()
    {
        $view = new TransformerView;
        $this->assertEquals(0, count($view->helpers()->loaded()));

        $viewOptions = ['viewVars' => []];
        $view = new TransformerView(null, null, null, $viewOptions);
        $this->assertEquals(0, count($view->helpers()->loaded()));

        $viewOptions = ['viewVars' => [], 'helpers' => ['Html']];
        $view = new TransformerView(null, null, null, $viewOptions);
        $this->assertEquals(1, count($view->helpers()->loaded()));

        $viewOptions = ['viewVars' => ['_serialize' => 'data']];
        $view = new TransformerView(null, null, null, $viewOptions);
        $this->assertEquals(0, count($view->helpers()->loaded()));

        $viewOptions = ['viewVars' => ['_serialize' => 'data'], 'helpers' => ['Html']];
        $view = new TransformerView(null, null, null, $viewOptions);
        $this->assertEquals(0, count($view->helpers()->loaded()));
    }

    public function testRender()
    {
        $view = new TransformerView;
        $view->set('data', [['key' => 'value']]);
        $result = $view->render('file');
        $this->assertEquals('[{"key":"value"}]', $result);
    }

    public function testNullRender()
    {
        $view = new TransformerView;
        $view->set('data', [['key' => 'value']]);
        $result = $view->render('file');
        $this->assertEquals('[{"key":"value"}]', $result);
    }

    public function testSerializeRender()
    {
        $request = $this->getMockBuilder('\Cake\Network\Request')
            ->getMock();
        $request->expects($this->at(3))
            ->method('param')
            ->will($this->returnValue('Test'));

        $view = new TransformerView($request);
        $view->set('data', [['key' => 'value']]);
        $view->set('_serialize', 'data');
        $result = $view->render();
        $this->assertEquals(json_encode(['data' => [['key' => 'value']]]), $result);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage You can only serialize a single variable
     */
    public function testInvalidSerializeArray()
    {
        $view = new TransformerView;
        $view->set('_serialize', ['data', 'data']);
        $view->render();
    }

    public function testValidSerializeArray()
    {
        $request = $this->getMockBuilder('\Cake\Network\Request')
            ->getMock();
        $request->expects($this->at(3))
            ->method('param')
            ->will($this->returnValue('Test'));

        $view = new TransformerView($request);
        $view->set('data', [['key' => 'value']]);
        $view->set('_serialize', 'data');
        $result = $view->render();
        $this->assertEquals(json_encode(['data' => [['key' => 'value']]]), $result);
    }

    public function testValidSerializeEntity()
    {
        $request = $this->getMockBuilder('\Cake\Network\Request')
            ->getMock();
        $request->expects($this->at(3))
            ->method('param')
            ->will($this->returnValue('Test'));

        $entity = $this->getMockBuilder('\Cake\Datasource\EntityInterface')
            ->getMock();
        $entity->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue(['key' => 'value']));

        $view = new TransformerView($request);
        $view->set('data', $entity);
        $view->set('_serialize', 'data');
        $result = $view->render();
        $this->assertEquals(json_encode(['data' => ['key' => 'value']]), $result);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Unserializable variable
     */
    public function testInvalidValidSerialize()
    {
        $request = $this->getMockBuilder('\Cake\Network\Request')
            ->getMock();
        $request->expects($this->at(3))
            ->method('param')
            ->will($this->returnValue('Test'));

        $view = new TransformerView($request);
        $view->set('data', new stdClass);
        $view->set('_serialize', 'data');
        $view->render();
    }

    public function testValidSerializer()
    {
        $view = new TransformerView;
        $mock = $this->getMockBuilder('\League\Fractal\Serializer\SerializerAbstract')->getMock();
        $view->set('_serializer', $mock);
        $result = $this->protectedMethodCall($view, '_serializer');
        $this->assertInstanceOf('\League\Fractal\Serializer\SerializerAbstract', $result);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Configured Serializer not instance of SerializerAbstract: stdClass
     */
    public function testInvalidSerializer()
    {
        $view = new TransformerView;
        $view->set('_serializer', new stdClass);
        $this->protectedMethodCall($view, '_serializer');
    }

    public function testValidAutomaticSerializer()
    {
        $view = new TransformerView;
        $result = $this->protectedMethodCall($view, '_serializer');
        $this->assertInstanceOf('\League\Fractal\Serializer\SerializerAbstract', $result);
        $this->assertInstanceOf('\League\Fractal\Serializer\DataArraySerializer', $result);
    }

    public function testValidSerializerByClass()
    {
        $view = new TransformerView;
        $view->set('_serializerClass', '\League\Fractal\Serializer\ArraySerializer');
        $result = $this->protectedMethodCall($view, '_serializer');
        $this->assertInstanceOf('\League\Fractal\Serializer\SerializerAbstract', $result);
        $this->assertInstanceOf('\League\Fractal\Serializer\ArraySerializer', $result);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Invalid Serializer class: \InvalidSerializer
     */
    public function testInvalidSerializerByClass()
    {
        $view = new TransformerView;
        $view->set('_serializerClass', '\InvalidSerializer');
        $result = $this->protectedMethodCall($view, '_serializer');
        $this->assertInstanceOf('\League\Fractal\Serializer\SerializerAbstract', $result);
        $this->assertInstanceOf('\League\Fractal\Serializer\ArraySerializer', $result);
    }

    public function testValidTransformer()
    {
        $view = new TransformerView;
        $mock = $this->getMockBuilder('\League\Fractal\TransformerAbstract')->getMock();
        $view->set('_transformer', $mock);
        $result = $this->protectedMethodCall($view, '_transformer');
        $this->assertInstanceOf('\League\Fractal\TransformerAbstract', $result);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Configured Transformer not instance of TransformerAbstract: stdClass
     */
    public function testInvalidTransformer()
    {
        $view = new TransformerView;
        $view->set('_transformer', new stdClass);
        $this->protectedMethodCall($view, '_transformer');
    }

    public function testValidAutomaticTransformerClass()
    {
        $request = $this->getMockBuilder('\Cake\Network\Request')->getMock();
        $request->expects($this->at(3))
            ->method('param')
            ->will($this->returnValue('Test'));

        $view = new TransformerView($request);
        $result = $this->protectedMethodCall($view, '_transformer');
        $this->assertInstanceOf('\League\Fractal\TransformerAbstract', $result);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Invalid Transformer class: \FractalEntities\Test\App\Transformer\Transformer
     */
    public function testNonexistentAutomaticTransformerClass()
    {
        $view = new TransformerView;
        $this->protectedMethodCall($view, '_transformer');
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Transformer class not instance of TransformerAbstract: \FractalEntities\Test\App\Transformer\InvalidTransformer
     */
    public function testInvalidAutomaticTransformerClass()
    {
        $request = $this->getMockBuilder('\Cake\Network\Request')
            ->getMock();
        $request->expects($this->at(3))
            ->method('param')
            ->will($this->returnValue('Invalid'));

        $view = new TransformerView($request);
        $this->protectedMethodCall($view, '_transformer');
    }

    /**
     * Call a protected method on an object
     *
     * @param Object $object object
     * @param string $name method to call
     * @param array $args arguments to pass to the method
     * @return mixed
     */
    public function protectedMethodCall($obj, $name, array $args = [])
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }
}
