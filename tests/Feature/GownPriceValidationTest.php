<?php

use App\Models\Category;
use App\Models\Gown;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->create(['role' => 'owner']);
    $this->category = Category::create(['name' => 'Formal gowns']);
    $this->gown = Gown::create([
        'category_id' => $this->category->id,
        'gown_code' => 'GWN-PRICE-001',
        'name' => 'Price test gown',
        'size' => 'M',
        'color' => 'Black',
        'rental_price' => 2500,
        'security_deposit' => 0,
        'purchase_price' => 5000,
        'status' => 'available',
        'condition' => 'good',
    ]);
});

it('shows validation feedback instead of a database error for an oversized rental price', function () {
    $this->actingAs($this->owner)
        ->from(route('owner.gowns.create'))
        ->post(route('owner.gowns.store'), [
            'category_id' => $this->category->id,
            'name' => 'Too expensive gown',
            'size' => 'M',
            'color' => 'Black',
            'rental_price' => '100000000',
            'security_deposit' => '0',
            'purchase_price' => '0',
            'condition' => 'good',
            'status' => 'available',
        ])
        ->assertRedirect(route('owner.gowns.create'))
        ->assertSessionHasErrors('rental_price');
});

it('rejects oversized rental prices when updating a gown', function () {
    $this->actingAs($this->owner)
        ->from(route('owner.gowns.edit', $this->gown))
        ->put(route('owner.gowns.update', $this->gown), [
            'category_id' => $this->category->id,
            'name' => $this->gown->name,
            'size' => $this->gown->size,
            'color' => $this->gown->color,
            'rental_price' => '100000000',
            'security_deposit' => '0',
            'purchase_price' => '5000',
            'condition' => 'good',
            'status' => 'available',
        ])
        ->assertRedirect(route('owner.gowns.edit', $this->gown))
        ->assertSessionHasErrors('rental_price');

    expect((float) $this->gown->fresh()->rental_price)->toBe(2500.0);
});
