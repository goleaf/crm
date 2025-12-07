<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $note->title }} - Note Print</title>
    <style>
        :root {
            color-scheme: light;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            margin: 2rem auto;
            max-width: 900px;
            line-height: 1.6;
            color: #1f2937;
            padding: 0 1.5rem;
        }
        h1 {
            margin-bottom: .2rem;
        }
        .badge {
            display: inline-block;
            padding: .25rem .6rem;
            border-radius: 999px;
            font-size: .85rem;
            font-weight: 600;
            margin-right: .35rem;
        }
        .badge.gray { background: #e5e7eb; color: #374151; }
        .badge.primary { background: #dbeafe; color: #1d4ed8; }
        .badge.info { background: #e0f2fe; color: #0ea5e9; }
        .badge.success { background: #d1fae5; color: #065f46; }
        .badge.warning { background: #fef3c7; color: #92400e; }
        .badge.danger { background: #fee2e2; color: #991b1b; }
        .badge.secondary { background: #f3f4f6; color: #374151; }
        .meta { margin: .5rem 0 1rem; color: #4b5563; }
        .section { margin: 1.5rem 0; }
        .attachments ul { list-style: none; padding: 0; }
        .attachments li { margin-bottom: .4rem; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <h1>{{ $note->title }}</h1>
    <div class="meta">
        <span class="badge {{ \App\Enums\NoteCategory::tryFrom((string) $note->category)?->color() ?? 'gray' }}">
            {{ \App\Enums\NoteCategory::tryFrom((string) $note->category)?->label() ?? 'General' }}
        </span>
        <span class="badge {{ $note->visibility->color() }}">
            {{ $note->visibility->getLabel() }}
        </span>
        @if($note->is_template)
            <span class="badge gray">Template</span>
        @endif
        <div class="muted">
            Created {{ $note->created_at?->format('Y-m-d H:i') }} by {{ $note->creator?->name ?? 'System' }} |
            Updated {{ $note->updated_at?->format('Y-m-d H:i') }}
        </div>
    </div>

    <div class="section">
        <h3>Details</h3>
        <p class="muted">
            @if($note->companies->isNotEmpty())
                Companies: {{ $note->companies->pluck('name')->join(', ') }}<br>
            @endif
            @if($note->people->isNotEmpty())
                People: {{ $note->people->pluck('name')->join(', ') }}<br>
            @endif
            @if($note->opportunities->isNotEmpty())
                Opportunities: {{ $note->opportunities->pluck('name')->join(', ') }}<br>
            @endif
            @if($note->cases->isNotEmpty())
                Cases: {{ $note->cases->pluck('subject')->join(', ') }}<br>
            @endif
            @if($note->tasks->isNotEmpty())
                Tasks: {{ $note->tasks->pluck('title')->join(', ') }}<br>
            @endif
        </p>
    </div>

    <div class="section">
        <h3>Body</h3>
        <div>{!! $body !!}</div>
    </div>

    @if($note->attachments->isNotEmpty())
        <div class="section attachments">
            <h3>Attachments</h3>
            <ul>
                @foreach($note->attachments as $file)
                    <li>
                        <a href="{{ $file->getUrl() }}">{{ $file->file_name }}</a>
                        <span class="muted">({{ $file->mime_type }}, {{ $file->created_at?->format('Y-m-d') }})</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</body>
</html>
