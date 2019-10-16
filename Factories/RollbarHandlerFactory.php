<?php

namespace Rollbar\Symfony\RollbarBundle\Factories;

use Psr\Log\LogLevel;
use Rollbar\Monolog\Handler\RollbarHandler;
use Rollbar\Rollbar;
use Rollbar\Symfony\RollbarBundle\DependencyInjection\RollbarExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RollbarHandlerFactory
 *
 * @package Rollbar\Symfony\RollbarBundle\Factories
 */
class RollbarHandlerFactory
{
    /**
     * RollbarHandlerFactory constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $config = $container->getParameter(RollbarExtension::ALIAS . '.config');

        if (isset($_ENV['ROLLBAR_TEST_TOKEN']) && $_ENV['ROLLBAR_TEST_TOKEN']) {
            $config['access_token'] = $_ENV['ROLLBAR_TEST_TOKEN'];
        }

        if (!empty($config['person_fn']) && is_callable($config['person_fn'])) {
            $config['person'] = null;
        } else {
            if (empty($config['person'])) {
                $config['person_fn'] = function () use ($container) {
                    try {
                        $token = $container->get('security.token_storage')->getToken();
                        
                        if ($token) {
                            $user = $token->getUser();
                            if($container->has('serializer')) {
                                $serializer = $container->get('serializer');
                            } elseif($container->has('jms_serializer')) {
                                $serializer = $container->get('jms_serializer');
                            } else {
                                throw new ServiceNotFoundException('serializer');
                            }
                            $person = \json_decode($serializer->serialize($user, 'json'), true);
                            return $person;
                        }
                    } catch (\Exception $exception) {
                        // Ignore all except serializer
                        if($exception instanceof ServiceNotFoundException) {
                            throw $exception;
                        }
                    }
                };
            }
        }

        Rollbar::init($config, false, false, false);
    }

    /**
     * Create RollbarHandler
     *
     * @return RollbarHandler
     */
    public function createRollbarHandler()
    {
        return new RollbarHandler(Rollbar::logger(), LogLevel::ERROR);
    }
}
