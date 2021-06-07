<?php
declare(strict_types=1);

namespace knotphp\module\knotlogger\adapter;

use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel as PsrLogLevel;

use knotlib\logger\LoggerInterface as CalgamoLoggerInterface;
use knotlib\logger\LogMessage;
use knotlib\kernel\logger\LoggerChannelInterface;

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