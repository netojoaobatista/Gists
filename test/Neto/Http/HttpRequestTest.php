<?php
namespace Neto\Http;

/**
 * HttpRequest test case.
 */
class HttpRequestTest extends \PHPUnit_Framework_TestCase
{
    public function testGetANonexistentHeaderWillReturnNull()
    {
        $httpRequest = new HttpRequest();

        $this->assertNull($httpRequest->getHeader('Content-Type'));
    }

    public function testAddANonScalarHeaderWillThrowsAnException()
    {
        $this->setExpectedException('\InvalidArgumentException');

        $httpRequest = new HttpRequest();
        $httpRequest->addHeader(array(), array());
    }

    public function testAfterAddAHeaderWeCanGetItBack()
    {
        $httpRequest = new HttpRequest();
        $httpRequest->addHeader('Content-Type', 'test/php');

        $this->assertEquals('test/php', $httpRequest->getHeader('Content-Type'));
    }

    public function testAddAHeaderTwiceWithoutOverridingWillNotChangeItsValue()
    {
        $httpRequest = new HttpRequest();
        $httpRequest->addHeader('Content-Type', 'test/php');
        $httpRequest->addHeader('Content-Type', 'php/test', false);

        $this->assertEquals('test/php', $httpRequest->getHeader('Content-Type'));

    }

    public function testGetANonexistentParamWillReturnNull()
    {
        $httpRequest = new HttpRequest();

        $this->assertNull($httpRequest->getParam('param'));
    }

    public function testSetANonScalarParamWillThrowsAnException()
    {
        $this->setExpectedException('\InvalidArgumentException');

        $httpRequest = new HttpRequest();
        $httpRequest->setParam(array(), array());
    }

    public function testAfterAddAParamWeCanGetItBack()
    {
        $httpRequest = new HttpRequest();
        $httpRequest->setParam('name', 'value');

        $this->assertEquals('value', $httpRequest->getParam('name'));
    }

    public function testInitializingTheRequestWithInvalidHostnameWillThrowsAnException()
    {
        $this->setExpectedException('\InvalidArgumentException');

        $httpRequest = new HttpRequest();
        $httpRequest->initialize('Invalid Hostname');
    }

    public function testInitializingTheRequestWithNonHttpSchemeWillThrowsAnException()
    {
        $this->setExpectedException('\UnexpectedValueException');

        $httpRequest = new HttpRequest();
        $httpRequest->initialize('https://host.com');
    }

    public function testInitializingTheRequestWithThePortIncludedInHostnameWillDefineThePort()
    {
        $httpRequest = new HttpRequest();
        $httpRequest->initialize('http://host.com:8080');

        $this->assertEquals(8080, $httpRequest->getPort());
    }

    public function testInitializingTheRequestWithThePortIncludedInHostnameAndPassingThePortArgumentWillUseThePortArgument()
    {
        $httpRequest = new HttpRequest();
        $httpRequest->initialize('http://host.com:8080', 80);

        $this->assertEquals(80, $httpRequest->getPort());
    }

    public function testInitializingTheRequestWithNonNumericPortNumberWillThrowsAnException()
    {
        $this->setExpectedException('\InvalidArgumentException',
                                    'Port number and timeout must be integer values');

        $httpRequest = new HttpRequest();
        $httpRequest->initialize('http://host.com', 'neto');
    }

    public function testInitializingTheRequestWithAHostnameThatContainsASchemeAndAPortGethostnameWillReturnOnlyTheHostname()
    {
        $httpRequest = new HttpRequest();
        $httpRequest->initialize('http://user@host.com:8080/path');

        $this->assertEquals('host.com', $httpRequest->getHostname());
    }

    public function testInitializingTheRequestWithNonNumericTimeoutWillThrowsAnException()
    {
        $this->setExpectedException('\InvalidArgumentException',
            'Port number and timeout must be integer values');

        $httpRequest = new HttpRequest();
        $httpRequest->initialize('http://host.com', 80, 'neto');
    }

    public function testInitializingTheRequestWithOnlyHostnameWillSetDefaultPortAndTimeoutConfigurations()
    {
        $httpRequest = new HttpRequest();
        $httpRequest->initialize('http://host.com');

        $this->assertEquals(HttpRequest::DEFAULT_PORT, $httpRequest->getPort());
        $this->assertEquals(HttpRequest::DEFAULT_TIMEOUT, $httpRequest->getTimeout());
    }

    public function testCallingGethostnameBeforeInitializeWillThrowsAnException()
    {
        $this->setExpectedException('\BadMethodCallException');

        $httpRequest = new HttpRequest();
        $httpRequest->getHostname();
    }

    public function testExecutingARequestToAHostnameShouldSendTheRequestToThatHostname()
    {
        $streamWrapper = $this->getMockBuilder('HttpWrapper')
                              ->setMethods(array('stream_open'))
                              ->getMock();

        $streamWrapper->expects($this->at(0))
                      ->method('stream_open')
                      ->with('http://test.com/', 'rb', null)
                      ->will($this->returnValue(true));

        StreamWrapperProxy::register($streamWrapper);

        $httpRequest = new HttpRequest();
        $httpRequest->initialize('http://test.com');
        $httpRequest->execute();
    }

    public function testExecutingAGetRequestToAHostnameWithParametersShouldSendTheRequestToTheHostnameWithThoseParameters()
    {
        $streamWrapper = $this->getMockBuilder('HttpWrapper')
                              ->setMethods(array('stream_open'))
                              ->getMock();

        $streamWrapper->expects($this->at(0))
                      ->method('stream_open')
                      ->with('http://test.com/?param=value&other', 'rb', null)
                      ->will($this->returnValue(true));

        HttpWrapperProxy::register($streamWrapper);

        $httpRequest = new HttpRequest();
        $httpRequest->setParam('param', 'value');
        $httpRequest->setParam('other');
        $httpRequest->initialize('http://test.com');
        $httpRequest->execute();
    }

    public function testExecutingARequestWithSpecificMethodWillSendTheRequestWithThatMethod()
    {
        $streamWrapper = $this->getMockBuilder('HttpWrapper')
                              ->setMethods(array('stream_open'))
                              ->getMock();

        $streamWrapper->expects($this->any())
                      ->method('stream_open')
                      ->with('http://test.com:8080/', 'rb', null)
                      ->will($this->returnValue(true));

        HttpWrapperProxy::register($streamWrapper);

        $httpRequest = new HttpRequest();
        $httpRequest->initialize('http://test.com', 8080);
        $httpRequest->execute('/', HttpRequest::GET);

        $this->assertEquals(HttpRequest::GET, HttpWrapperProxy::getRequestMethod());

        $httpRequest->execute('/', HttpRequest::POST);

        $this->assertEquals(HttpRequest::POST, HttpWrapperProxy::getRequestMethod());
    }

    public function testExecutingAValidRequestWillGetAValidResponse()
    {
        $streamWrapper = $this->getMockBuilder('HttpWrapper')
                              ->setMethods(array('stream_open', 'stream_read', 'stream_eof'))
                              ->getMock();

        $streamWrapper->expects($this->at(0))
                      ->method('stream_open')
                      ->with('http://test.com/', 'rb', null)
                      ->will($this->returnValue(true));

        $streamWrapper->expects($this->at(1))
                      ->method('stream_read')
                      ->will($this->onConsecutiveCalls('valid response', null));

        $streamWrapper->expects($this->at(2))
                      ->method('stream_eof')
                      ->will($this->returnValue(true));

        HttpWrapperProxy::register($streamWrapper);

        $httpRequest = new HttpRequest();
        $httpRequest->initialize('http://test.com');
        $response = $httpRequest->execute('/', HttpRequest::GET);

        $this->assertEquals('valid response', $response);

    }

    public function testExecutingRequestWithSomeHeadersWillSendTheHeadersWithRequest()
    {
        $streamWrapper = $this->getMockBuilder('HttpWrapper')
                              ->setMethods(array('stream_open'))
                              ->getMock();

        $streamWrapper->expects($this->at(0))
                      ->method('stream_open')
                      ->with('http://test.com/', 'rb', null)
                      ->will($this->returnValue(true));

        HttpWrapperProxy::register($streamWrapper);

        $httpRequest = new HttpRequest();
        $httpRequest->initialize('http://test.com');
        $httpRequest->addHeader('Content-Type', 'php/test');
        $httpRequest->addHeader('User-Agent', 'php');
        $httpRequest->execute();

        $headers = HttpWrapperProxy::getRequestHeaders();

        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('User-Agent', $headers);
        $this->assertEquals($headers['Content-Type'], 'php/test');
        $this->assertEquals($headers['User-Agent'], 'php');
    }

    public function testExecutingRequestWithPostMethodWillSendTheParametersWithRequestBody()
    {
        $streamWrapper = $this->getMockBuilder('HttpWrapper')
                              ->setMethods(array('stream_open'))
                              ->getMock();

        $streamWrapper->expects($this->at(0))
                      ->method('stream_open')
                      ->with('http://test.com/path', 'rb', null)
                      ->will($this->returnValue(true));

        HttpWrapperProxy::register($streamWrapper);

        $httpRequest = new HttpRequest();
        $httpRequest->initialize('http://test.com');
        $httpRequest->setParam('name', 'value');
        $httpRequest->execute('/path', HttpRequest::POST);

        $content = 'name=value';

        $this->assertEquals($content, HttpWrapperProxy::getContent());
    }
}