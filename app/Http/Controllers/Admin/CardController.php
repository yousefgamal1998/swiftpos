<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CardRequest;
use App\Models\Card;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CardController extends Controller
{
    public function index(): Response
    {
        $cards = Card::query()
            ->orderBy('sort_order')
            ->get([
                'id',
                'title',
                'description',
                'icon',
                'image_path',
                'color',
                'route_name',
                'permission',
                'role',
                'sort_order',
                'is_active',
            ]);

        return Inertia::render('Cards/Index', [
            'cards' => $cards,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Cards/Create', $this->formOptions());
    }

    public function store(CardRequest $request): RedirectResponse
    {
        $data = $this->normalizeCardData($request->validated());
        $data['icon'] = $data['icon'] ?? '';
        $data = $this->handleImageUpload($request, $data);

        Card::create($data);

        return redirect()
            ->route('admin.cards.index')
            ->with('success', 'Card created successfully.');
    }

    public function edit(Card $card): Response
    {
        return Inertia::render('Cards/Edit', [
            ...$this->formOptions(),
            'card' => $card->only([
                'id',
                'title',
                'description',
                'icon',
                'image_path',
                'color',
                'route_name',
                'permission',
                'role',
                'sort_order',
                'is_active',
            ]),
        ]);
    }

    public function update(CardRequest $request, Card $card): RedirectResponse
    {
        $data = $this->normalizeCardData($request->validated(), $card);
        $data = $this->handleImageUpload($request, $data, $card);

        $card->update($data);

        return redirect()
            ->route('admin.cards.index')
            ->with('success', 'Card updated successfully.');
    }

    public function destroy(Card $card): RedirectResponse
    {
        if ($card->image_path) {
            Storage::disk('public')->delete($card->image_path);
        }

        $card->delete();

        return redirect()
            ->route('admin.cards.index')
            ->with('success', 'Card deleted successfully.');
    }

    public function removeImage(Card $card): RedirectResponse
    {
        if ($card->image_path) {
            Storage::disk('public')->delete($card->image_path);
        }

        $card->update(['image_path' => null]);

        return redirect()
            ->back()
            ->with('success', 'Card image removed.');
    }

    public function toggle(Request $request, Card $card): RedirectResponse
    {
        $card->update([
            'is_active' => ! $card->is_active,
        ]);

        return redirect()
            ->back()
            ->with('success', $card->is_active ? 'Card enabled.' : 'Card disabled.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'order' => ['required', 'array'],
            'order.*.id' => ['required', 'integer', 'exists:cards,id'],
            'order.*.sort_order' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($payload): void {
            foreach ($payload['order'] as $item) {
                Card::whereKey($item['id'])->update([
                    'sort_order' => $item['sort_order'],
                ]);
            }
        });

        return redirect()
            ->back()
            ->with('success', 'Card order updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'roles' => Role::query()->orderBy('name')->pluck('name'),
            'permissions' => Permission::query()->orderBy('name')->pluck('name'),
            'colors' => [
                'emerald',
                'sky',
                'amber',
                'violet',
                'slate',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeCardData(array $data, ?Card $card = null): array
    {
        $data['permission'] = $data['permission'] ?: null;
        $data['role'] = $data['role'] ?: null;

        if (! $card || ! $card->slug) {
            $data['slug'] = $this->makeSlug($data['title'], $card?->id);
        }

        if (array_key_exists('icon', $data)) {
            $data['icon'] = $data['icon'] ?: '';
        }

        if (empty($data['sort_order'])) {
            $data['sort_order'] = (int) (Card::max('sort_order') ?? 0) + 1;
        }

        return $data;
    }

    private function makeSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: Str::random(8);
        $slug = $base;
        $counter = 1;

        while (
            Card::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function handleImageUpload(Request $request, array $data, ?Card $card = null): array
    {
        unset($data['image']);

        if (! $request->hasFile('image')) {
            return $data;
        }

        if ($card?->image_path) {
            Storage::disk('public')->delete($card->image_path);
        }

        $data['image_path'] = $request->file('image')->store('cards', 'public');

        return $data;
    }
}
