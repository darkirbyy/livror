<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;
    private string $projectDir;

    #[\Override]
    public function getProjectDir(): string
    {
        if (!isset($this->projectDir)) {
            $this->projectDir = realpath(__DIR__ . '/..');
        }

        return $this->projectDir;
    }
}
