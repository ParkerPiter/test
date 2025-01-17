<?php

namespace Staatic\Vendor\AsyncAws\Core\Stream;

use Traversable;
use Staatic\Vendor\AsyncAws\Core\Exception\InvalidArgument;
final class ResourceStream implements RequestStream
{
    private $content;
    private $chunkSize;
    private function __construct($content, int $chunkSize = 64 * 1024)
    {
        $this->content = $content;
        $this->chunkSize = $chunkSize;
    }
    /**
     * @param int $chunkSize
     */
    public static function create($content, $chunkSize = 64 * 1024): ResourceStream
    {
        if ($content instanceof self) {
            return $content;
        }
        if (\is_resource($content)) {
            if (!stream_get_meta_data($content)['seekable']) {
                throw new InvalidArgument('The given body is not seekable.');
            }
            return new self($content, $chunkSize);
        }
        throw new InvalidArgument(\sprintf('Expect content to be a "resource". "%s" given.', \is_object($content) ? \get_class($content) : \gettype($content)));
    }
    public function length(): ?int
    {
        return fstat($this->content)['size'] ?? null;
    }
    public function stringify(): string
    {
        if (-1 === fseek($this->content, 0)) {
            throw new InvalidArgument('Unable to seek the content.');
        }
        return stream_get_contents($this->content);
    }
    public function getIterator(): Traversable
    {
        if (-1 === fseek($this->content, 0)) {
            throw new InvalidArgument('Unable to seek the content.');
        }
        while (!feof($this->content)) {
            yield fread($this->content, $this->chunkSize);
        }
    }
    public function getResource()
    {
        return $this->content;
    }
    /**
     * @param string $algo
     * @param bool $raw
     */
    public function hash($algo = 'sha256', $raw = \false): string
    {
        $pos = ftell($this->content);
        if ($pos > 0 && -1 === fseek($this->content, 0)) {
            throw new InvalidArgument('Unable to seek the content.');
        }
        $ctx = hash_init($algo);
        hash_update_stream($ctx, $this->content);
        $out = hash_final($ctx, $raw);
        if (-1 === fseek($this->content, $pos)) {
            throw new InvalidArgument('Unable to seek the content.');
        }
        return $out;
    }
}
