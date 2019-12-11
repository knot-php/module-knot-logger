<?php
declare(strict_types=1);

namespace KnotPhp\Module\KnotLogger\Adapter;

use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel as PsrLogLevel;

use KnotLib\Logger\LoggerInterface as CalgamoLoggerInterface;
use KnotLib\Logger\LogMessage;
use KnotLib\Kernel\Logger\LoggerChannelInterface;

class KnotLoggerChannelAdapter implements LoggerChannelInterface
{
    use LoggerTrait;

    private $logger;

    /**
     * CalgamoLoggerAdapter constructor.
     *
     * @param CalgamoLoggerInterface $logger
     */
    public function __construct(CalgamoLoggerInterface $logger)
    {
        $this->logger = $logger;
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
            PsrLogLevel::EMERGENCY => 'F',
            PsrLogLevel::ALERT => 'F',
            PsrLogLevel::CRITICAL => 'F',
            PsrLogLevel::ERROR => 'E',
            PsrLogLevel::WARNING => 'W',
            PsrLogLevel::NOTICE => 'I',
            PsrLogLevel::INFO => 'I',
            PsrLogLevel::DEBUG => 'D',
        ];
        $level = $_lvl[$level] ?? 'D';
        $this->logger->writeln(new LogMessage($level, $message, '', $file, $line));
    }

    /**
     * Enable log channel
     *
     * @param bool $enabled
     *
     * @return LoggerChannelInterface
     */
    public function enable(bool $enabled) : LoggerChannelInterface
    {
        $this->logger->enable($enabled);
        return $this;
    }
}