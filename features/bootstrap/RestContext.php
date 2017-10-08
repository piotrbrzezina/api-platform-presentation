<?php declare(strict_types=1);

namespace Tests;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Behatch\Json\Json;
use Coduo\PHPMatcher\Factory\SimpleFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Defines application features from the specific contexbit.
 */
class RestContext extends RawMinkContext
{
    use KernelDictionary;

    private $defaultHeaders = [
        'HTTP_ACCEPT' => 'application/ld+json',
        'CONTENT_TYPE' => 'application/ld+json',
    ];

    /**
     * @When I send a :method request to :path
     * @When I send a :method request to :path with body:
     */
    public function iSendARequestToWithBody($method, $path, PyStringNode $body = null)
    {
        $body = $body instanceof PyStringNode ? $body->getRaw() : '{}';
        $this->sendRequest($method, $path, $body);
    }

    /**
     * @When I send a :method request to :path for type :type
     * @When I send a :method request to :path for type :type with body:
     */
    public function iSendARequestToForTypeWithBody($method, $path, $type, PyStringNode $body = null)
    {
        $headers = [
            'HTTP_ACCEPT' => $type,
            'CONTENT_TYPE' => $type,
        ];
        $body = $body instanceof PyStringNode ? $body->getRaw() : '{}';
        $this->sendRequest($method, $path, $body, $headers);
    }

    /**
     * @When I send a :method request as :role to :path
     * @When I send a :method request as :role to :path with body:
     */
    public function iSendARequestAsToWithBody($method, $role, $path, PyStringNode $body = null)
    {
        $headers = array_merge([
            'HTTP_X-AUTH-TOKEN' => 'token-' . strtolower($role),
        ], $this->defaultHeaders);
        $body = $body instanceof PyStringNode ? $body->getRaw() : '{}';

        $this->sendRequest($method, $path, $body, $headers);
    }

    /**
     * @When I send a :method request with :sid sid to :path
     */
    public function iSendARequestWithSidTo($method, $sid, $path)
    {
        $headers = array_merge([
            'HTTP_ESSO-SID' => $sid,
            'HTTP_X-AUTH-TOKEN' => 'token-x-' . $sid,
        ], $this->defaultHeaders);

        $this->sendRequest($method, $path, '{}', $headers);
    }

    /**
     * @When I send a :method request as :role to :path for type :type
     * @When I send a :method request as :role to :path for type :type with body:
     */
    public function iSendARequestAsForTypeToWithBody($method, $role, $path, $type, PyStringNode $body = null)
    {
        $headers = [
            'HTTP_ACCEPT' => $type,
            'CONTENT_TYPE' => $type,
            'HTTP_X-AUTH-TOKEN' => 'token-' . strtolower($role),
        ];
        $body = $body instanceof PyStringNode ? $body->getRaw() : '{}';

        $this->sendRequest($method, $path, $body, $headers);
    }

    /**
     * @When I send a :method request as :role to :path with attachment :file
     */
    public function iSendARequestToWithAttachment($method, $role, $path, $file)
    {
        $headers = array_merge($this->defaultHeaders,
            [
                'HTTP_X-AUTH-TOKEN' => 'token-' . strtolower($role),
                'CONTENT_TYPE' => 'multipart/form-data',
            ]
        );

        $files = [];
        if (file_exists('features/fixtures/files/' . $file)) {
            $files = ['data' => new UploadedFile('features/fixtures/files/' . $file, $file)];
        }
        $this->sendRequest($method, $path, '{}', $headers, $files);
    }

    /**
     * @Then the header :name should be equal to :value
     */
    public function theHeaderShouldBeEqualTo($name, $value)
    {
        $response = $this->getDriver()->getClient()->getResponse();
        $actual = $response->headers->get($name);
        if (strtolower($value) !== strtolower($actual)) {
            throw new \RuntimeException("Expected header '$value', but got '$actual'");
        }
    }

    /**
     * @Then the response should contain json:
     */
    public function theResponseShouldContainJson(PyStringNode $jsonString)
    {
        $factory = new SimpleFactory();
        $matcher = $factory->createMatcher();

        if (!$matcher->match($this->getSession()->getPage()->getContent(), $jsonString->getRaw())) {
            throw new \RuntimeException($matcher->getError());
        }
    }

    /**
     * @Then print last JSON response
     */
    public function printLastJsonResponse()
    {
        echo $this->getJson()
            ->encode();
    }

    /**
     * @Then print last response headers
     */
    public function printLastResponseHeaders()
    {
        $response = $this->getDriver()->getClient()->getResponse();
        $headers = $response->headers->all();

        $text = '';
        foreach ($headers as $name => $value) {
            $text .= $name . ': ' . $response->headers->get($name) . "\n";
        }
        echo $text;
    }

    /**
     * @Then the response should be in JSON
     */
    public function theResponseShouldBeInJson()
    {
        try {
            $this->getJson();
        } catch (\Exception $e) {
            throw new \RuntimeException('Response is not valid JSON.');
        }
    }

    /**
     * @return Json
     * @throws \Exception
     */
    protected function getJson()
    {
        return new Json($this->getSession()->getPage()->getContent());
    }

    /**
     * @param $method
     * @param $path
     * @param string $body
     * @param array $headers
     * @param array $files
     */
    private function sendRequest($method, $path, $body = '{}', $headers = [], $files = [])
    {
        $driver = $this->getDriver();

        if (empty($headers)) {
            $headers = $this->defaultHeaders;
        }
        $method = strtoupper($method);

        $driver->getClient()->request($method, $path, [], $files, $headers, $body);
    }

    /**
     * @BeforeScenario @allowRedirects
     */
    public function allowRedirects()
    {
        $this->getDriver()->getClient()->followRedirects(false);
    }

    /**
     * @return BrowserKitDriver
     */
    private function getDriver()
    {
        $driver = $this->getSession()->getDriver();
        if ( ! $driver instanceof BrowserKitDriver) {
            throw new \RuntimeException('Unsupported driver. BrowserKit driver is required.');
        }

        return $driver;
    }
}
