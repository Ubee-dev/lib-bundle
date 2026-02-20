<?php

namespace UbeeDev\LibBundle\Tests\Helper;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

class HttpMock implements HttpClientInterface
{
    private string $mockPath;
    private Filesystem $fileSystem;

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly HttpClientInterface $client,
        private readonly string                $testToken,
    )
    {
        $this->mockPath = $this->parameterBag->get('kernel.project_dir').'/var/http-mock/'.$this->testToken.'/';
        $this->fileSystem = new Filesystem();
    }

    public function mockData(
        string $method,
        string $uri,
        array $expectedReturnedData,
        array $optionsSent = []
    ): string
    {
        if(!$this->fileSystem->exists($this->mockPath)) {
            $this->fileSystem->mkdir($this->mockPath);
        }
        $filePath = $this->getFilePath(
            $method,
            $uri,
            $optionsSent
        );
        $json = json_encode($expectedReturnedData);
        file_put_contents($filePath, $json);
        return $filePath;
    }

    public function request(string $method, $uri = '', array $options = []): ResponseInterface
    {
        $filePath = $this->getFilePath($method, $uri, $options);

        if(file_exists($filePath)) {
            $data = file_get_contents($filePath);

            return $this->getResponseClass()->setContent($data);
        }
        return $this->client->request($method, $uri, $options);
    }

    public function getData(string $method, $uri = '', array $options = []): array
    {
        $filePath = $this->getFilePath($method, $uri, $options);
        if(!file_exists($filePath)) {
            throw new NotFoundHttpException('Data for given uri not found');
        }

        return json_decode(file_get_contents($filePath), true);
    }

    public function getFilePath(string $method, $uri = '', array $options = []): string
    {
        ksort($options);
        // sort data in ascending order, according to the key:
        return $this->mockPath.hash('sha1',$method.' '.$uri.' '.json_encode($options)).'.json';
    }

    public function clearData(): void
    {
        if($this->fileSystem->exists($this->mockPath)) {
            $this->fileSystem->remove($this->mockPath);
        }
    }

    public function stream(iterable|ResponseInterface $responses, ?float $timeout = null): ResponseStreamInterface
    {
        // TODO: Implement stream() method.
    }

    public function withOptions(array $options): static
    {
        // TODO: Implement withOptions() method.
    }

    private function getResponseClass(): ResponseInterface
    {
        return new class implements ResponseInterface {

            private string|array $content;

            public function getContent(bool $throw = true): string
            {
                return $this->content;
            }

            public function setContent($content): self
            {
                $this->content = $content;
                return $this;
            }

            public function getStatusCode(): int
            {
                // TODO: Implement getStatusCode() method.
            }

            public function getHeaders(bool $throw = true): array
            {
                return [
                    'content-type' => ['application/json']
                ];
            }

            public function toArray(bool $throw = true): array
            {
                // TODO: Implement toArray() method.
            }

            public function cancel(): void
            {
                // TODO: Implement cancel() method.
            }

            public function getInfo(?string $type = null): mixed
            {
                // TODO: Implement getInfo() method.
            }
        };
    }
}