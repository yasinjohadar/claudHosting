<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('client profile photo can be uploaded', function () {
    Storage::fake('public');

    $user = User::factory()->create([
        'email' => 'client-photo@example.com',
    ]);

    $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

    $response = $this
        ->actingAs($user)
        ->put(route('client.profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'photo' => $file,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('client.profile.show'));

    $user->refresh();

    expect($user->photo)->not->toBeNull();
    Storage::disk('public')->assertExists($user->photo);
});
