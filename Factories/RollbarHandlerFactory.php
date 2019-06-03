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
     * @var string|null
     */
    private $minimumLevel;

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
        } elseif (empty($config['person'])) {
            $config['person_fn'] = function () use ($container) {

                try {
                    $token = $container->get('security.token_storage')->getToken();

                    if ($token) {
                        $user = $token->getUser();
                        $serializer = $container->get('serializer');

                        return \json_decode($serializer->serialize($user, 'json'), true);
                    }
                } catch (\Exception $exception) {
                    // Ignore
                }
            };
        }

        $this->minimumLevel = isset($config['minimum_level'])
            ? $config['minimum_level']
            : \Rollbar\Defaults::get()->minimumLevel();

        Rollbar::init($config, false, false, false);
    }

    /**
     * Create RollbarHandler
     *
     * @return RollbarHandler
     */
    public function createRollbarHandler()
    {
        return new RollbarHandler(Rollbar::logger(), $this->minimumLevel);
    }
}
