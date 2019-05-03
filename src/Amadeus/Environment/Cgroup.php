<?php


namespace Amadeus\Environment;

use Amadeus\Config\Config;
use Amadeus\Environment\Cgroup\{Cpu, Disk, Mem, Network};
use Amadeus\IO\Logger;

/**
 * Class Cgroup
 * @package Amadeus\Environment
 */
class Cgroup
{
    /**
     * @var mixed|string
     */
    private $cgroupBase;
    /**
     * @var int
     */
    /**
     * @var int
     */
    /**
     * @var int
     */
    /**
     * @var int
     */
    /**
     * @var int
     */
    /**
     * @var int
     */
    private
        $SID,
        $Cpu,
        $Mem,
        $DiskSpeed,
        $NetworkSpeed,
        $PID;
    /**
     * @var string
     */
    /**
     * @var string
     */
    /**
     * @var string
     */
    /**
     * @var string
     */
    private
        $c_cpu,
        $c_memory,
        $c_blkio,
        $c_net_cls;

    /**
     * Cgroup constructor.
     * @param int $SID
     * @param int $Cpu
     * @param int $Mem
     * @param int $DiskSpeed
     * @param int $NetworkSpeed
     * @param int $PID
     */
    public function __construct(int $SID, int $Cpu, int $Mem, int $DiskSpeed, int $NetworkSpeed, int $PID)
    {
        $this->SID = $SID;
        $this->Cpu = $Cpu;
        $this->Mem = $Mem;
        $this->DiskSpeed = $DiskSpeed;
        $this->NetworkSpeed = $NetworkSpeed;
        $this->PID = $PID;
        $this->cgroupBase = Config::get('cgroup_dir');
        $this->c_cpu = $this->cgroupBase . '/cpu' . DIRECTORY_SEPARATOR . 'server' . $this->SID;
        $this->c_memory = $this->cgroupBase . '/memory' . DIRECTORY_SEPARATOR . 'server' . $this->SID;
        $this->c_blkio = $this->cgroupBase . '/blkio' . DIRECTORY_SEPARATOR . 'server' . $this->SID;
        $this->c_net_cls = $this->cgroupBase . '/net_cls' . DIRECTORY_SEPARATOR . 'server' . $this->SID;
        $this->cgroupInit();
    }

    /**
     * @return bool
     */
    private function cgroupInit(): bool
    {
        is_dir($this->c_cpu) ?: Logger::printLine('creating cpu limit for' . $this->SID, Logger::LOG_INFORM);
        is_dir($this->c_memory) ?: Logger::printLine('creating memory limit for' . $this->SID, Logger::LOG_INFORM);
        is_dir($this->c_blkio) ?: Logger::printLine('creating disk limit for' . $this->SID, Logger::LOG_INFORM);
        is_dir($this->c_net_cls) ?: Logger::printLine('creating network limit for' . $this->SID, Logger::LOG_INFORM);
        Cpu::set($this->c_cpu, 100000, ($this->Cpu / 100) * 100000, $this->PID) ?: Logger::printLine('failed to set cpu for server' . $this->SID, Logger::LOG_FATAL);
        Mem::set($this->c_memory, $this->Mem * 1024 * 1024, $this->PID) ?: Logger::printLine('failed to set memory for server' . $this->SID, Logger::LOG_FATAL);
        Disk::set($this->c_blkio, Config::get('cgroup_disk_primary_id'), Config::get('cgroup_disk_secondary_id'), $this->DiskSpeed * 1024 * 1024, $this->PID) ?: Logger::printLine('failed to set disk speed for server' . $this->SID, Logger::LOG_FATAL);
        Network::set($this->c_net_cls, $this->NetworkSpeed, $this->PID) ?: Logger::printLine('failed to set network speed for server' . $this->SID, Logger::LOG_FATAL);
        return true;
    }

    /**
     * @return bool
     */
    public static function sanityCheck(): bool
    {
        $result = is_dir(Config::get('cgroup_dir'));
        if (!$result) {
            Logger::printLine('Cgroup error: path does not exist!', Logger::LOG_PANIC);
        }
        return $result;
    }

    /**
     *
     */
    public function __destruct()
    {
        Cpu::clear($this->c_cpu) ?: Logger::printLine('Failed to remove cpu limit for ' . $this->SID, Logger::LOG_PANIC);
        Mem::clear($this->c_memory) ?: Logger::printLine('Failed to remove memory limit for ' . $this->SID, Logger::LOG_PANIC);
        Disk::clear($this->c_blkio) ?: Logger::printLine('Failed to remove disk speed limit for ' . $this->SID, Logger::LOG_PANIC);
        Network::clear($this->c_net_cls) ?: Logger::printLine('Failed to remove network speed limit for ' . $this->SID, Logger::LOG_PANIC);
    }
}