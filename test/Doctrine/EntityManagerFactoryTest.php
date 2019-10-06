<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Doctrine;

use Doctrine\Common\Persistence\Mapping\Driver\PHPDriver;
use Doctrine\DBAL\Driver\PDOSqlite;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Doctrine\EntityManagerFactory;
use Shlinkio\Shlink\Common\Doctrine\Type\ChronosDateTimeType;
use Zend\ServiceManager\ServiceManager;

use function array_merge;

class EntityManagerFactoryTest extends TestCase
{
    /** @var EntityManagerFactory */
    private $factory;

    public function setUp(): void
    {
        if (Type::hasType(ChronosDateTimeType::CHRONOS_DATETIME)) {
            Type::overrideType(ChronosDateTimeType::CHRONOS_DATETIME, null);
        }
        $this->factory = new EntityManagerFactory();
    }

    /**
     * @test
     * @dataProvider provideConfig
     */
    public function serviceIsCreated(array $config, int $expectedAutoGenerateProxies): void
    {
        $sm = new ServiceManager(['services' => [
            'config' => $config,
        ]]);

        $this->assertFalse(Type::hasType(ChronosDateTimeType::CHRONOS_DATETIME));
        $em = ($this->factory)($sm);

        $this->assertTrue(Type::hasType(ChronosDateTimeType::CHRONOS_DATETIME));
        $this->assertEquals($expectedAutoGenerateProxies, $em->getConfiguration()->getAutoGenerateProxyClasses());
        $this->assertInstanceOf(PDOSqlite\Driver::class, $em->getConnection()->getDriver());
        $this->assertEquals(__DIR__, $em->getConfiguration()->getProxyDir());

        /** @var PHPDriver $metaDriver */
        $metaDriver = $em->getConfiguration()->getMetadataDriverImpl();
        $this->assertEquals([__FILE__], $metaDriver->getLocator()->getPaths());
    }

    public function provideConfig(): iterable
    {
        $baseConfig = [
            'entity_manager' => [
                'orm' => [
                    'types' => [
                        ChronosDateTimeType::CHRONOS_DATETIME => ChronosDateTimeType::class,
                    ],
                    'proxies_dir' => __DIR__,
                    'entities_mappings' => [__FILE__],
                ],
                'connection' => [
                    'driver' => 'pdo_sqlite',
                ],
            ],
        ];

        yield [array_merge($baseConfig, ['debug' => true]), 1];
        yield [array_merge($baseConfig, ['debug' => '1']), 1];
        yield [array_merge($baseConfig, ['debug' => 'true']), 1];
        yield [array_merge($baseConfig, ['debug' => false]), 0];
        yield [array_merge($baseConfig, ['debug' => null]), 0];
    }
}
