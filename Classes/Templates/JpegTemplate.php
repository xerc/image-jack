<?php
declare(strict_types=1);

namespace Sitegeist\ImageJack\Templates;

use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Utility\CommandUtility;

class JpegTemplate extends AbstractTemplate implements TemplateInterface
{
    public function isAvailable(): bool
    {
        return (in_array($this->image->getMimeType(), $this->getSupportedMimeTypes()) && $this->isActive());
    }

    public function getSupportedMimeTypes(): array
    {
        return ['image/jpeg'];
    }

    public function isActive(): bool
    {
        return (bool)$this->extensionConfiguration['jpeg']['active'];
    }

    public function processFile(): void
    {
        $binary = $this->extensionConfiguration['jpeg']['path'];
        if (!is_executable($binary)) {
            $this->logger->writeLog(
                sprintf('Binary "%s" is not executable! Please use the full path to the binary.', $binary),
                LogLevel::ERROR
            );
            return;
        }

        $buffer = CommandUtility::exec(sprintf(
            escapeshellcmd($binary . ' -o -p -P --strip-all --all-progressive %s'),
            CommandUtility::escapeShellArgument($this->imagePath)
        ) . ' >/dev/null 2>&1');

        try {
            $this->storage->addFile(
                $this->imagePath,
                $this->image->getParentFolder(),
                $this->image->getName(),
                DuplicationBehavior::REPLACE
            );
        } catch (\TypeError $e) {
            // Ignore TypeError => T3 doesn't like writing directly in a processed folder
        }

        if (!empty($buffer)) {
            $this->logger->writeLog(trim($buffer), LogLevel::INFO);
        }
    }
}
