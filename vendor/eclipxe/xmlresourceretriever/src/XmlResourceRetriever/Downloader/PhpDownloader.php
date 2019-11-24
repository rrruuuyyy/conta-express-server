<?php
declare(strict_types=1);

namespace XmlResourceRetriever\Downloader;

class PhpDownloader implements DownloaderInterface
{
    /** @var resource */
    private $context;

    /**
     * PhpDownloader constructor.
     * @param resource|null $context A valid context created with stream_context_create
     */
    public function __construct($context = null)
    {
        if (null === $context) {
            $context = $this->createContext();
        }
        $this->setContext($context);
    }

    /** @return resource */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set the context (created with stream_context_create) that will be used when try to download
     * @param resource $context
     * @see https://php.net/stream-context-create
     */
    public function setContext($context)
    {
        if (! is_resource($context)) {
            throw new \InvalidArgumentException('Provided context is not a resource');
        }
        $this->context = $context;
    }

    /** @return resource */
    public function createContext()
    {
        return stream_context_get_default();
    }

    public function downloadTo(string $source, string $destination)
    {
        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        if (! @copy($source, $destination, $this->getContext())) {
            $previousException = null;
            if (null !== $lastError = error_get_last()) {
                $previousException = new \Exception($lastError['message']);
            }
            throw new \RuntimeException("Unable to download $source to $destination", 0, $previousException);
        }
    }
}
