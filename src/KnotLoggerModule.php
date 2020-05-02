<?php
declare(strict_types=1);

namespace KnotPhp\Module\KnotLogger;

use Stk2k\EventStream\Event;

use KnotLib\Kernel\EventStream\Channels;
use KnotLib\Kernel\EventStream\Events;
use KnotLib\Kernel\Exception\EventStreamException;
use KnotLib\Kernel\Exception\ModuleInstallationException;
use KnotLib\Kernel\FileSystem\Dir;
use KnotLib\Kernel\Kernel\ApplicationInterface;
use KnotLib\Kernel\Logger\LoggerChannelInterface;
use KnotLib\Kernel\Module\ModuleInterface;
use KnotLib\Kernel\Module\ComponentTypes;
use KnotLib\Logger\Logger\ConsoleLogger;
use KnotLib\Logger\Logger\FileLogger;
use KnotLib\Logger\LogManager;

use KnotPhp\Module\KnotLogger\Adapter\KnotLoggerAdapter;

class KnotLoggerModule implements ModuleInterface
{
    const CONFIG_FILE   = 'logger.config.php';

    /** @var LogManager */
    private $log_manager;

    /**
     * Declare dependency on another modules
     *
     * @return array
     */
    public static function requiredModules() : array
    {
        return [];
    }

    /**
     * Declare dependent on components
     *
     * @return array
     */
    public static function requiredComponentTypes() : array
    {
        return [
            ComponentTypes::EVENTSTREAM,
        ];
    }

    /**
     * Declare component type of this module
     *
     * @return string
     */
    public static function declareComponentType() : string
    {
        return ComponentTypes::LOGGER;
    }

    /**
     * Install module
     *
     * @param ApplicationInterface $app
     *
     * @throws ModuleInstallationException
     */
    public function install(ApplicationInterface $app)
    {
        try{
            $event_channel = $app->eventstream()->channel(Channels::SYSTEM);
            $fs = $app->filesystem();
            $config_file = $fs->getFile(Dir::CONFIG, self::CONFIG_FILE);

            if (!file_exists($config_file) || !is_readable($config_file)){
                throw new ModuleInstallationException(self::class, 'Config file does not exist or no readable: ' . $config_file);
            }

            /** @noinspection PhpIncludeInspection */
            $config = require_once $config_file;

            $this->log_manager = new LogManager($config['log_manager'] ?? []);
            $app_logger =  new KnotLoggerAdapter($this->log_manager);
            $app->logger($app_logger);

            $event_channel->push(Events::LOGGER_ATTACHED, $app_logger);

            $logs_config = $config['logs'] ?? [];
            foreach($logs_config as $name => $config)
            {
                $type = $config['type'] ?? null;
                $options = $config['options'] ?? [];
                $enabled = $config['enabled'] ?? false;
                $logger = null;
                switch($type)
                {
                    case 'file':
                        $logger = new FileLogger($options, function($keyword) use($fs){
                            switch($keyword){
                                case 'LOGS_DIR':
                                    return $fs->getDirectory(Dir::LOGS);
                            }
                            return false;
                        });
                        break;

                    case 'console':
                        $logger = new ConsoleLogger($options);
                        break;

                    default:
                        $reason = "Invalid logger type($type) for {$name} specified in log config file: $config_file";
                        throw new ModuleInstallationException(self::class, $reason);
                        break;
                }
                $this->log_manager->register($name, $logger);

                // By default, logger will be disabled. Specify 'enabled: true' in config, then the logger will be activated.
                $logger->enable($enabled);

                $event_channel->push(Events::LOGGER_CHANNEL_CREATED, [
                        'name' => $name,
                        'logger' => $logger,
                    ]);
            }

            // enable loggers by route name
            $app->eventstream()->channel(Channels::SYSTEM)->listen(
                Events::ROUTER_ROUTED,
                function(Event $e)
                {
                    $data = $e->getPayload();
                    $route_name = $data['route_name'] ?? null;
                    /** @var LoggerChannelInterface $logger */
                    if (is_string($route_name) && !empty($route_name)){
                        $logger = $this->log_manager->get($route_name);
                        if ($logger){
                            $logger->enable(true);
                        }
                    }
                }
            );
        }
        catch(EventStreamException $e)
        {
            throw new ModuleInstallationException(self::class, $e->getMessage(), 0, $e);
        }
    }
}