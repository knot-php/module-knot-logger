<?php
declare(strict_types=1);

namespace KnotPhp\Module\KnotLogger\Adapter;

use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel as PsrLogLevel;

use KnotLib\Logger\LogManager;
use KnotLib\Kernel\Logger\LoggerInterface;
use KnotLib\Kernel\Logger\LoggerChannelInterface;
use KnotLib\Kernel\NullObject\NullLoggerChannel;

class KnotLoggerAdapter implements LoggerInterface
{
    use LoggerTrait;

    private $log_manager;

    /**
     * CalgamoLoggerAdapter constructor.
     *
     * @param LogManager $log_manager
     */
    public function __construct(LogManager $log_manager)
    {
        $this->log_manager = $log_manager;
    }

    /**
     * @return LogManager
     */
    public function getLogManager() : LogManager
    {
        return $this->log_manager;
    }

    /**
     * Get channel logger
     *
     * @param string $channel_id
     *
     * @return LoggerChannelInterface
     */
    public function channel(string $channel_id) : LoggerChannelInterface
    {
        $logger = $this->log_manager->get($channel_id);
        if (!$logger){
            return new NullLoggerChannel();
        }
        return new KnotLoggerChannelAdapter($logger);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function log($level, $message, array $context = array())
    {
        $file = $context['file'] ?? '';
        $line = $context['line'] ?? -1;
        $_lvl = [
            PsrLogLevel::EMERGENCY => 'F:*',
            PsrLogLevel::ALERT => 'F:*',
            PsrLogLevel::CRITICAL => 'F:*',
            PsrLogLevel::ERROR => 'E:*',
            PsrLogLevel::WARNING => 'W:*',
            PsrLogLevel::NOTICE => 'I:*',
            PsrLogLevel::INFO => 'I:*',
            PsrLogLevel::DEBUG => 'D:*',
        ];
        $target = $_lvl[$level] ?? 'D:*';
        $this->log_manager->log($file, $line, $target, $message, '');
    }
}