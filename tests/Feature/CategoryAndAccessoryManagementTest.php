<?php

use App\Models\Accessory;
use App\Models\Category;
use App\Models\Gown;
use App\Models\User;
use App\Services\VercelBlobStorage;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->owner = User::factory()->create(['role' => 'owner']);
});

it('lets the owner open the category form without a category image field', function () {
    $this->actingAs($this->owner)
        ->get(route('owner.categories.index'))
        ->assertOk()
        ->assertSee('Add category')
        ->assertSee('class="sb-side-drawer sb-inventory-modal" id="category-create-dialog"', false)
        ->assertSee("document.getElementById('category-create-dialog').showModal()", false)
        ->assertDontSee('sb-inventory-thumb', false)
        ->assertDontSee('Category image');
});

it('creates and updates categories without requiring an image', function () {
    $this->actingAs($this->owner)
        ->post(route('owner.categories.store'), [
            'name' => 'Evening gowns',
            'description' => 'Formal floor-length gowns',
        ])
        ->assertRedirect(route('owner.categories.index'));

    $category = Category::where('name', 'Evening gowns')->firstOrFail();
    expect($category->description)->toBe('Formal floor-length gowns');

    $this->actingAs($this->owner)
        ->get(route('owner.categories.edit', $category))
        ->assertOk()
        ->assertDontSee('Category image');

    $this->put(route('owner.categories.update', $category), [
        'name' => 'Formal gowns',
        'description' => 'Updated description',
        'is_active' => '1',
    ])->assertRedirect(route('owner.categories.index'));

    expect($category->fresh()->name)->toBe('Formal gowns');
});

it('allows inactive categories to be activated again', function () {
    $category = Category::create(['name' => 'Inactive category', 'is_active' => false]);

    $this->actingAs($this->owner)
        ->get(route('owner.categories.index'))
        ->assertOk()
        ->assertSee('Inactive category')
        ->assertSee('Activate');

    $this->patch(route('owner.categories.toggle-status', $category))
        ->assertRedirect(route('owner.categories.index'));

    expect($category->fresh()->is_active)->toBeTrue();
});

it('removes the category-level add gown shortcut', function () {
    $category = Category::create(['name' => 'Bridal']);

    $this->actingAs($this->owner)
        ->get(route('owner.categories.show', $category))
        ->assertOk()
        ->assertDontSee('Add a gown')
        ->assertDontSee(route('owner.gowns.create'), false);
});

it('shows the uploaded accessory image in inventory and item details', function () {
    Storage::fake('public');
    $image = UploadedFile::fake()->image('tiara.jpg');

    $this->actingAs($this->owner)
        ->get(route('owner.accessories.create'))
        ->assertOk()
        ->assertSee('id="accessory-preview"', false)
        ->assertSee('type="file" name="image"', false);

    $this->actingAs($this->owner)
        ->post(route('owner.accessories.store'), [
            'name' => 'Crystal tiara',
            'description' => 'Silver accessory',
            'image' => $image,
            'quantity' => 2,
            'replacement_cost' => 500,
            'status' => 'available',
        ])
        ->assertRedirect(route('owner.accessories.index'));

    $accessory = Accessory::where('name', 'Crystal tiara')->firstOrFail();
    Storage::disk('public')->assertExists($accessory->image);
    $imageUrl = asset('storage/' . $accessory->image);

    $this->actingAs($this->owner)
        ->get(route('owner.accessories.index'))
        ->assertOk()
        ->assertSee('id="accessory-create-preview"', false)
        ->assertSee('type="file" name="image"', false)
        ->assertSee($imageUrl, false)
        ->assertSee('alt="Crystal tiara"', false);

    $this->get(route('owner.accessories.show', $accessory))
        ->assertOk()
        ->assertSee($imageUrl, false)
        ->assertSee('alt="Crystal tiara"', false);
});

it('opens accessory details from the row without separate view or disable actions', function () {
    $accessory = Accessory::create([
        'name' => 'Crystal tiara',
        'description' => 'Silver accessory',
        'quantity' => 2,
        'replacement_cost' => 500,
        'status' => 'available',
    ]);

    $this->actingAs($this->owner)
        ->get(route('owner.accessories.index'))
        ->assertOk()
        ->assertSee('data-href="' . route('owner.accessories.show', $accessory) . '"', false)
        ->assertSee(route('owner.accessories.edit', $accessory), false)
        ->assertDontSee('>View</a>', false)
        ->assertDontSee('>Disable</button>', false);
});

it('shows a visual accessory stock summary without a gown-selection shortcut', function () {
    $accessory = Accessory::create([
        'name' => 'Crystal tiara',
        'description' => 'Silver accessory',
        'quantity' => 2,
        'replacement_cost' => 500,
        'status' => 'available',
    ]);

    $this->actingAs($this->owner)
        ->get(route('owner.accessories.show', $accessory))
        ->assertOk()
        ->assertSee('sb-accessory-stock-overview', false)
        ->assertSee('Stock details')
        ->assertSee('Replacement value')
        ->assertSee('Linked gowns')
        ->assertSee('No gowns are currently linked to this accessory.')
        ->assertDontSee('Edit a gown and select this item');
});

it('paginates categories and accessories with a bounded page size', function () {
    foreach (range(1, 13) as $number) {
        Category::create(['name' => "Category {$number}"]);
        Accessory::create([
            'name' => "Accessory {$number}",
            'quantity' => 1,
            'replacement_cost' => 100,
            'status' => 'available',
        ]);
    }

    $this->actingAs($this->owner)
        ->get(route('owner.categories.index', ['per_page' => 12]))
        ->assertOk()
        ->assertSee('Showing 1–12')
        ->assertSee('of 13 categories')
        ->assertViewHas('categories', fn ($categories) => $categories->count() === 12
            && $categories->total() === 13
            && $categories->perPage() === 12);

    $this->get(route('owner.accessories.index', ['per_page' => 48]))
        ->assertOk()
    ->assertSee('Showing 1–13')
    ->assertSee('of 13 accessories')
        ->assertViewHas('accessories', fn ($accessories) => $accessories->count() === 13
            && $accessories->total() === 13
            && $accessories->perPage() === 48);

    $this->get(route('owner.accessories.index', ['per_page' => 200]))
        ->assertOk()
        ->assertViewHas('accessories', fn ($accessories) => $accessories->perPage() === 12);
});

it('paginates the gown collection shown on a category detail page', function () {
    Storage::fake('public');
    $category = Category::create(['name' => 'Category collection']);
    $gown = null;
    foreach (range(1, 13) as $number) {
        $createdGown = Gown::create([
            'category_id' => $category->id,
            'gown_code' => sprintf('GWN-CATEGORY-%03d', $number),
            'name' => "Category gown {$number}",
            'rental_price' => 1500,
            'security_deposit' => 0,
            'status' => 'available',
            'image' => $number === 1 ? 'gowns/category-gown.jpg' : null,
        ]);
        if ($number === 1) {
            $gown = $createdGown;
        }
    }
    Storage::disk('public')->put('gowns/category-gown.jpg', 'image data');

    $this->actingAs($this->owner)
        ->get(route('owner.categories.show', ['category' => $category, 'per_page' => 12]))
        ->assertOk()
        ->assertSee('data-href="' . route('owner.gowns.show', $gown) . '"', false)
        ->assertSee('src="' . asset('storage/gowns/category-gown.jpg') . '"', false)
        ->assertSee('alt="Category gown 1"', false)
        ->assertSee('sb-category-detail-table', false)
        ->assertSee('sb-inventory-thumb', false)
        ->assertDontSee('View gown')
        ->assertSee('of 13 gowns')
        ->assertViewHas('gowns', fn($gowns) => $gowns->total() === 13
            && $gowns->count() === 12
            && $gowns->perPage() === 12);
});

it('paginates searchable maintenance gown options with image previews', function () {
    $category = Category::create(['name' => 'Maintenance selection']);
    foreach (range(1, 13) as $number) {
        Gown::create([
            'category_id' => $category->id,
            'gown_code' => sprintf('GWN-MAINT-%03d', $number),
            'name' => "Maintenance gown {$number}",
            'rental_price' => 1500,
            'security_deposit' => 0,
            'status' => 'available',
        ]);
    }

    $this->actingAs($this->owner)
        ->getJson(route('owner.maintenance.gowns', ['page' => 1]))
        ->assertOk()
        ->assertJsonPath('total', 13)
        ->assertJsonPath('last_page', 3)
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('data.0.image_url', asset('images/image.webp'));

    $this->getJson(route('owner.maintenance.gowns', ['page' => 3]))
        ->assertOk()
        ->assertJsonPath('total', 13)
    ->assertJsonCount(3, 'data')
    ->assertJsonPath('data.2.gown_code', 'GWN-MAINT-013');

    $this->getJson(route('owner.maintenance.gowns', ['q' => 'GWN-MAINT-013']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.gown_code', 'GWN-MAINT-013');
});

it('stores Vercel inventory images in the public Blob store', function () {
    $blobUrl = 'https://example.public.blob.vercel-storage.com/accessories/tiara.jpg';
    config([
        'services.vercel_blob.enabled' => true,
        'services.vercel_blob.bridge_url' => 'https://gown-rental.vercel.app/api/blob',
        'services.vercel_blob.bridge_secret' => str_repeat('s', 32),
    ]);
    Http::fake([
        'https://gown-rental.vercel.app/api/blob' => Http::response(['url' => $blobUrl], 201),
    ]);

    $this->actingAs($this->owner)
        ->post(route('owner.accessories.store'), [
            'name' => 'Crystal tiara',
            'description' => 'Silver accessory',
            'image' => UploadedFile::fake()->image('tiara.jpg'),
            'quantity' => 2,
            'replacement_cost' => 500,
            'status' => 'available',
        ])
        ->assertRedirect(route('owner.accessories.index'));

    $accessory = Accessory::where('name', 'Crystal tiara')->firstOrFail();
    expect($accessory->image)->toBe($blobUrl)
        ->and($accessory->image_url)->toBe($blobUrl);

    Http::assertSent(fn (ClientRequest $request) => $request->method() === 'PUT'
        && $request->url() === 'https://gown-rental.vercel.app/api/blob'
        && $request->header('X-Blob-Access')[0] === 'public'
        && $request->header('X-Blob-Bridge-Secret')[0] === str_repeat('s', 32)
        && preg_match('/^accessories\/[0-9a-f-]+\.jpg$/', $request->header('X-Blob-Path')[0]) === 1);
});

it('fetches private Blob files through the server bridge without exposing the token', function () {
    config([
        'services.vercel_blob.enabled' => true,
        'services.vercel_blob.bridge_url' => 'https://gown-rental.vercel.app/api/blob',
        'services.vercel_blob.bridge_secret' => str_repeat('s', 32),
    ]);
    Http::fake([
        'https://gown-rental.vercel.app/api/blob' => Http::response('private-id-photo', 200, [
            'Content-Type' => 'image/jpeg',
        ]),
    ]);

    $response = app(VercelBlobStorage::class)->privateFileResponse(
        'https://example.private.blob.vercel-storage.com/government-ids/id.jpg'
    );

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('Cache-Control'))->toContain('private')
        ->toContain('no-store')
        ->and($response->getContent())->toBe('private-id-photo');

    Http::assertSent(fn (ClientRequest $request) => $request->method() === 'GET'
        && $request->header('X-Blob-Access')[0] === 'private'
        && $request->header('X-Blob-URL')[0] === 'https://example.private.blob.vercel-storage.com/government-ids/id.jpg'
        && $request->header('X-Blob-Bridge-Secret')[0] === str_repeat('s', 32));
});
