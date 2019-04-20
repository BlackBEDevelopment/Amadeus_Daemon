<?php


namespace Amadeus\Plugin;


use Amadeus\IO\Logger;
use Amadeus\Process;

/**
 * Class PluginManager
 * @package Amadeus\Plugin
 */
class PluginManager
{
    private $listeners;
    private $plugins;

    public function __construct()
    {
        $items = array_diff(scandir('plugins/'), array('..', '.'));
        $this->plugins = array();
        foreach ($items as $item) {
            if (is_file('plugins/' . $item) && substr($item, -5) === '.phar') {
                $info = yaml_parse(file_get_contents('phar://plugins/' . $item . '/plugin.yaml'));
                $this->plugins[] = array('name' => $info['name'], 'uri' => 'phar://' . Process::getBase() . '/plugins/' . $item, 'stub' => $info['stub'], 'namespace' => $info['namespace'], 'main' => $info['main'], 'type' => 'phar');
            } else {
                $info = yaml_parse_file('plugins/' . $item . '/plugin.yaml');
                $this->plugins[] = array('name' => $info['name'], 'uri' => Process::getBase() . '/plugins/' . $item, 'stub' => $info['stub'], 'main' => $info['main'], 'namespace' => $info['namespace'], 'type' => 'dir');
            }
            Logger::printLine('Found ' . $item, Logger::LOG_INFORM);
        }
        Logger::printLine('Successfully registered', Logger::LOG_INFORM);
    }

    public function start()
    {
        if (count($this->plugins) > 0) {
            foreach ($this->plugins as $plugin) {
                Process::getLoader()->addPsr4($plugin['namespace'], $plugin['uri'] . '/' . $plugin['stub']);
                include_once($plugin['uri'] . '/' . $plugin['stub'] . '/' . $plugin['main'] . '.php');
                $class_name = $plugin['namespace'] . $plugin['main'];
                Logger::printLine('Loading ' . $plugin['name'], Logger::LOG_INFORM);
                if (class_exists($class_name)) {
                    $reference = new $class_name();
                    $this->listeners[$reference->getName()] = $reference;
                    Logger::printLine('Registering ' . $reference->getName(), Logger::LOG_INFORM);
                    !method_exists($reference, 'onLoading') ?: $reference->onLoading();
                }
            }
        }
        Logger::printLine('All plugins loaded', Logger::LOG_INFORM);
        return true;
    }

    /* @Deprecated */
    public function register($reference, $name): bool
    {
        Logger::printLine('Registering ' . $name, Logger::LOG_INFORM);
        $this->listeners[$name] = $reference;
        return true;
    }

    public function trigger(string $event, $data = null): bool
    {
        foreach ($this->listeners as $listener) {
            if (method_exists($listener, $event)) {
                if($data===null){
                    $listener->$event();
                }else{
                    $listener->$event($data);
                }
            }
        }
        return true;
    }
}