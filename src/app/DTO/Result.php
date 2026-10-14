<?php

namespace Publish\DTO;
use Publish\DTO\Request;

final class Result
{
    public function __construct(
        public string $title = '',
        public string $lang = '',
        public string $user = '',
        public string $campaign = '',
        public string $sourcetitle = '',
        public array $request = [],
        public array $edit = [],
        public array $resultToCx = [],
        public string $summary = '',
        public mixed $result = '',
        public int|string $words = '',
        public int|string|null $revid = null,
        public ?string $emptyRevid = null,
        public ?string $fixRefs = null,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            title: (string) $request->title,
            lang: (string) $request->target,
            user: (string) $request->user,
            campaign: (string) $request->campaign,
            sourcetitle: (string) $request->sourcetitle,
            request: $request->toArray(),
        );
    }

    /** Legacy array shape (same keys as the old $tab) for classes not yet migrated. */
    public function toArray(): array
    {
        $tab = [
            'title'        => $this->title,
            'lang'         => $this->lang,
            'user'         => $this->user,
            'campaign'     => $this->campaign,
            'sourcetitle'  => $this->sourcetitle,
            'request'      => $this->request,
            'edit'         => $this->edit,
            'result_to_cx' => $this->resultToCx,
            'summary'      => $this->summary,
            'result'       => $this->result,
            'words'        => $this->words,
        ];

        if ($this->revid !== null) {
            $tab['revid'] = $this->revid;
        }
        if ($this->emptyRevid !== null) {
            $tab['empty revid'] = $this->emptyRevid;
        }
        if ($this->fixRefs !== null) {
            $tab['fix_refs'] = $this->fixRefs;
        }

        return $tab;
    }
}
