<?php

namespace Rollbar\Symfony\RollbarBundle\Tests\Factories;

use Rollbar\Config;
use Rollbar\Defaults;
use Psr\Log\LogLevel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Rollbar\Symfony\RollbarBundle\DependencyInjection\RollbarExtension;
use Rollbar\Symfony\RollbarBundle\Factories\RollbarHandlerFactory;
use Rollbar\Rollbar;
use Rollbar\Payload\Level;

/**
 * Class RollbarHandlerFactoryTest
 *
 * @package Rollbar\Symfony\RollbarBundle\Tests\Factories;
 */
class RollbarHandlerFactoryTest extends KernelTestCase
{
    public static function setUpBeforeClass()
    {
        self::bootKernel();
    }
    
    public function testMinimumLevel()
    {
        $factory = new RollbarHandlerFactory(self::$container);
        
        $getAccessTokenMethod = new \ReflectionMethod('\Rollbar\RollbarLogger', 'getAccessToken');
        $getAccessTokenMethod->setAccessible(true);
        $accessToken = $getAccessTokenMethod->invoke(Rollbar::logger());
        
        $mockLogger = $this->getMockBuilder('\Rollbar\RollbarLogger')
            ->setConstructorArgs(array(array(
                'access_token' => $accessToken,
                'minimum_level' => LogLevel::ERROR
            )))
            ->getMock();
        
        $rollbarClass = new \ReflectionClass('\Rollbar\Rollbar');
        $setLoggerMethod = $rollbarClass->getMethod('setLogger');
        $setLoggerMethod->setAccessible(true);
        $setLoggerMethod->invoke(null, $mockLogger);
        
        $logger = self::$container->get('test_alias.logger');

        $mockLogger->expects($this->once())
            ->method('log')
            ->with($this->equalTo(Level::ERROR));
            
        $logger->log(Level::ERROR, "Test info from the factory test");
        
        $logger->log(Level::INFO, "Test debug from the factory test");
    }
}
