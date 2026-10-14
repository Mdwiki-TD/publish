<?php
namespace Publish\DTO;

use Publish\Utils\StartUtils;
/**
 * Typed, validated representation of an incoming request.
 */
final class Request
{
    private const DEFAULT_TR_TYPE = 'lead';
    public function __construct(
        public readonly string $title,
        public string $revid,
        public readonly string $text,
        public readonly string $user,
        public readonly string $summary,
        public readonly string $target,
        public readonly string $campaign,
        public readonly string $sourcetitle,
        public readonly string $wpCaptchaId,
        public readonly string $wpCaptchaWord,
        public readonly string $trType,
    ) {
    }

    /**
     * @param array<string, mixed> $request
     */
    public static function fromArray(array $request): self
    {
        $title = StartUtils::formatTitle($request['title'] ?? '');
        $user = StartUtils::formatUser($request['user'] ?? '');
        $trType = $request['tr_type'] ?? self::DEFAULT_TR_TYPE;

        return new self(
            title: $title,
            revid: (string) ($request['revid'] ?? ''),
            text: (string) ($request['text'] ?? ''),
            user: $user,
            summary: (string) ($request['summary'] ?? ''),
            target: (string) ($request['target'] ?? ''),
            campaign: (string) ($request['campaign'] ?? ''),
            sourcetitle: (string) ($request['sourcetitle'] ?? ''),
            wpCaptchaId: (string) ($request['wpCaptchaId'] ?? ''),
            wpCaptchaWord: (string) ($request['wpCaptchaWord'] ?? ''),
            trType: $trType,
        );
    }
    public function toArray()
    {
        return [
            'title'         => $this->title,
            'revid'         => $this->revid,
            'text'          => $this->text,
            'user'          => $this->user,
            'summary'       => $this->summary,
            'target'        => $this->target,
            'campaign'      => $this->campaign,
            'sourcetitle'   => $this->sourcetitle,
            'wpCaptchaId'   => $this->wpCaptchaId,
            'wpCaptchaWord' => $this->wpCaptchaWord,
        ];
    }
}
