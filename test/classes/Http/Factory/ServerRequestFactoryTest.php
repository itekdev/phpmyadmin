<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Http\Factory;

use GuzzleHttp\Psr7\HttpFactory as GuzzleHttpFactory;
use Laminas\Diactoros\ServerRequestFactory as LaminasServerRequestFactory;
use Nyholm\Psr7\Factory\Psr17Factory as NyholmPsr17Factory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Tests\AbstractTestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Slim\Psr7\Factory\ServerRequestFactory as SlimServerRequestFactory;

use function class_exists;

/**
 * @covers \PhpMyAdmin\Http\Factory\ServerRequestFactory
 */
class ServerRequestFactoryTest extends AbstractTestCase
{
    private const IMPLEMENTATION_CLASSES = [
        'slim/psr7' => [
            SlimServerRequestFactory::class,
            'Slim PSR-7',
        ],
        'guzzlehttp/psr7' => [
            GuzzleHttpFactory::class,
            'Guzzle PSR-7',
        ],
        'nyholm/psr7' => [
            NyholmPsr17Factory::class,
            'Nyholm PSR-7',
        ],
        'laminas/laminas-diactoros' => [
            LaminasServerRequestFactory::class,
            'Laminas diactoros PSR-7',
        ],
    ];

    public function dataProviderPsr7Implementations(): array
    {
        return self::IMPLEMENTATION_CLASSES;
    }

    /**
     * @phpstan-param class-string $className
     */
    private function testOrSkip(string $className, string $humanName): void
    {
        if (! class_exists($className)) {
            $this->markTestSkipped($humanName . ' is missing');
        }

        foreach (self::IMPLEMENTATION_CLASSES as $libName => $details) {
            /** @phpstan-var class-string */
            $classImpl = $details[0];
            if ($classImpl === $className) {
                continue;
            }

            if (! class_exists($classImpl)) {
                continue;
            }

            $this->markTestSkipped($libName . ' exists and will conflict with the test results');
        }
    }

    /**
     * @phpstan-param class-string $className
     *
     * @dataProvider dataProviderPsr7Implementations
     */
    public function testPsr7Implementation(string $className, string $humanName): void
    {
        $this->testOrSkip($className, $humanName);

        $_GET['foo'] = 'bar';
        $_SERVER['QUERY_STRING'] = 'foo=bar&blob=baz';
        $_SERVER['REQUEST_URI'] = '/test-page.php';

        $request = ServerRequestFactory::createFromGlobals();
        $this->assertSame([
            'foo' => 'bar',
            'blob' => 'baz',
        ], $request->getQueryParams());
    }

    /**
     * @phpstan-param class-string $className
     *
     * @dataProvider dataProviderPsr7Implementations
     */
    public function testPsr7ImplementationCreateServerRequestFactory(string $className, string $humanName): void
    {
        $this->testOrSkip($className, $humanName);

        $serverRequestFactory = new $className();
        $this->assertInstanceOf(ServerRequestFactoryInterface::class, $serverRequestFactory);

        $factory = new ServerRequestFactory(
            $serverRequestFactory
        );
        $this->assertInstanceOf(ServerRequestFactory::class, $factory);
    }
}
