<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Spatie\DataTransferObject\DataTransferObject;

class RelatedDto extends DataTransferObject
{
    public array $related = [];

    public array $nested = [];

    public array $resolvedRelationships = [];

    private bool $loaded = false;

    public function getColumnsFor(string $relation): array|string
    {
        $related = collect($this->related)->first(fn ($related) => $relation === Str::before($related, '['));

        if (! (Str::contains($related, '[') && Str::contains($related, ']'))) {
            return '*';
        }

        $columns = explode(',', Str::replace('|', ',', Str::between($related, '[', ']')));

        return count($columns)
            ? $columns
            : '*';
    }

    public function getNestedFor(string $relation): ?array
    {
        // TODO: work here to support many nested levels
        return collect(
            collect($this->nested)->first(fn ($related, $key) => $relation === $key)
        )->map(fn (self $nested) => [$nested->related])->flatten()->all();
    }

    public function normalize(): self
    {
        $this->related = collect($this->related)->map(function (string $relationship) {
            if (str($relationship)->contains('.')) {
                $baseRelationship = str($relationship)->before('.')->toString();

                $this->nested[$baseRelationship][] = (new RelatedDto(
                    related: [
                        str($relationship)
                            ->after($baseRelationship)
                            ->whenStartsWith('.', fn (Stringable $string) => $string->replaceFirst('.', ''))
                            ->ltrim()
                            ->rtrim()
                            ->toString(),
                    ]
                ))
                    ->normalize();

                return $baseRelationship;
            }

            return $relationship;
        })->unique()->all();

        return $this;
    }

    public function resolved(string $relationship): self
    {
        $this->resolvedRelationships[] = $relationship;

        return $this;
    }

    public function isResolved(string $relationship): bool
    {
        return array_key_exists($relationship, $this->resolvedRelationships);
    }

    public function sync(RestifyRequest $request): self
    {
        if (! $this->loaded) {
            $this->related = collect(str_getcsv($request->input('related') ?? $request->input('include')))->mapInto(Stringable::class)->map->ltrim()->map->rtrim()->all();

            $this->normalize();

            $this->loaded = true;
        }

        return $this;
    }

    public function reset(): self
    {
        $this->loaded = false;

        $this->resolvedRelationships = [];

        return $this;
    }
}
