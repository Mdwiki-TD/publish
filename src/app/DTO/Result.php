<?php

namespace Publish\DTO;

final class Result
{
    public function __construct(
        public readonly int $status,
        public readonly string $format,
        public readonly string|array $body,
    ) {
    }

}
