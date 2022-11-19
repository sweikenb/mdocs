<?php declare(strict_types=1);

namespace Sweikenb\Mdocs\Service;

class BuildThemeService
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(string $themeDir, string $buildDir, string $linkPrefix): void
    {
        $this->runInDir($themeDir, function () use ($buildDir) {
            passthru(
                sprintf(
                    "yarn install && yarn build %s",
                    escapeshellarg(rtrim($buildDir, '/'))
                )
            );
        });
    }

    private function runInDir(string $dir, callable $callback): void
    {
        $prev = getcwd();
        chdir($dir);
        call_user_func($callback);
        $prev && chdir($prev);
    }
}
