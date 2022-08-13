<?php declare(strict_types=1);

namespace Sweikenb\Mdocs\Command;

use Sweikenb\ConsoleFramework\Command\CompileCommand;

class MdocsCompileCommand extends CompileCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('mdocs:app:compile');
    }
}
