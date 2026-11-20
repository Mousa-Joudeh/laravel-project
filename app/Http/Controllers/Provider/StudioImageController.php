<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Provider\Concerns\ScopesToProvider;
use App\Models\StudioImage;

class StudioImageController extends Controller
{
    use ScopesToProvider;

    public function destroy(StudioImage $image)
    {
        $studio = $this->ownStudio($image->studio);

        if ($studio->images()->count() <= 1) {
            return back()->withErrors(['images' => 'A space needs at least one photo.']);
        }

        $wasCover = $image->is_cover;
        $path = $image->image_path;

        $image->delete();
        $this->deleteFile($path);

        // Promote the next photo if the cover was removed.
        if ($wasCover) {
            $studio->images()->orderBy('sort_order')->first()?->update(['is_cover' => true]);
        }

        return back()->with('status', 'Photo deleted.');
    }

    public function cover(StudioImage $image)
    {
        $studio = $this->ownStudio($image->studio);

        $studio->images()->update(['is_cover' => false]);
        $image->update(['is_cover' => true]);

        return back()->with('status', 'Cover photo updated.');
    }
}
