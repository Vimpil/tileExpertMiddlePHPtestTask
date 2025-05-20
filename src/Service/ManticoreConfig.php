<?php

namespace App\Service;

class ManticoreConfig
{
    private string $configPath;

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
    }

    public function getConfigPath(): string
    {
        return $this->configPath;
    }

    public function loadConfig(): array
    {
        if (!file_exists($this->configPath)) {
            throw new \RuntimeException("Configuration file not found: {$this->configPath}");
        }

        $config = parse_ini_file($this->configPath, true);
        if ($config === false) {
            throw new \RuntimeException("Failed to parse configuration file: {$this->configPath}");
        }

        return $config;
    }
}