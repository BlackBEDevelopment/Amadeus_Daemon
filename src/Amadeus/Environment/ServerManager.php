<?php


namespace Amadeus\Environment;

use Amadeus\Environment\Backend\Server;
use Amadeus\IO\Logger;
use Amadeus\Process;

/**
 * Class ServerManager
 * @package Amadeus\Environment
 */
class ServerManager
{
    /**
     * @var
     */
    private $servers;

    /**
     * ServerManager constructor.
     */
    public function __construct()
    {
        Quota::sanityCheck() ? Logger::printLine('Quota sanity check succeed', Logger::LOG_SUCCESS) : Logger::printLine('Quota sanity check failed', Logger::LOG_FATAL);
        Cgroup::sanityCheck() ? Logger::printLine('Cgroup sanity check succeed', Logger::LOG_SUCCESS) : Logger::printLine('Cgroup sanity check failed', Logger::LOG_FATAL);
        Logger::PrintLine('Successfully registered', Logger::LOG_SUCCESS);
    }

    /**
     * @return bool
     */
    public function startAllServers(): bool
    {
        Logger::printLine('Starting all server',Logger::LOG_INFORM);
        $servers = Process::getMySQL()->getServers();
        foreach ($servers as $SID => $server) {
            @mkdir($server['Directory'], 0755);
            @system('chown -R server' . $SID . ':server' . $SID . ' ' . $server['Directory']);
            $this->servers[$SID] = new Server($server['SID'], $server['Key'], $server['Directory'], $server['GameControl'], $server['Cpu'], $server['Mem'], $server['Disk'], $server['DiskSpeed'], $server['NetworkSpeed'], $server['Status']);
            $this->servers[$SID]->start();
        }
        return true;
    }

    /**
     * @param string $GameType
     * @param int $Cpu
     * @param int $Mem
     * @param int $Disk
     * @param int $DiskSpeed
     * @param int $NetworkSpeed
     * @return array
     */
    public function newServer(string $GameType = 'PM', int $Cpu = 100, int $Mem = 1024, int $Disk = 5120, int $DiskSpeed = 5, int $NetworkSpeed = 1): array
    {
        Logger::printLine('Creating a new server',Logger::LOG_INFORM);
        $SID = Process::getMySQL()->countServers() + 1;
        $Directory = Process::getBase() . '/servers/server' . $SID;
        @system('useradd -m -d ' . $Directory . ' server' . $SID . ' 2>&1');
        $Key = Process::getMySQL()->newServer($Directory, $GameType, $Cpu, $Mem, $Disk, $DiskSpeed, $NetworkSpeed);
        $this->servers[$SID] = new Server($SID, $Key, $Directory, $GameType, $Cpu, $Mem, $Disk, $DiskSpeed, $NetworkSpeed, true);
        $this->servers[$SID]->start();
        return array('SID' => $SID, 'Directory' => $Directory);
    }

    /**
     * @param int $SID
     * @return bool
     */
    public function delServer(int $SID): bool
    {
        Logger::printLine('Deleting server'.$SID,Logger::LOG_WARNING);
        if (isset($this->servers[$SID])) {
            unset($this->servers[$SID]);
            $Directory = Process::getBase() . '/servers/server' . $SID;
            @system('userdel -f server >/dev/null 2>&1' . $SID);
            @system('rm -rf ' . $Directory);
            Process::getMySQL()->delServerBySID($SID);
            return true;
        } else {
            Logger::printLine('Failed to delete server: server does not exist', Logger::LOG_DANGER);
            return false;
        }
    }

    /**
     * @param int $SID
     * @return Server|null
     */
    public function getServer(int $SID): ?Server
    {
        return isset($this->servers[$SID]) ? $this->servers[$SID] : null;
    }
}