<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Provider\Concerns\ScopesToProvider;
use App\Models\PackageImage;

class PackageImageController extends Controller
{
    use ScopesToProvider;

    public function destroy(PackageImage $image)
    {
        $this->ownPackage($image->package);

        $path = $image->image_path;

        $image->delete();
        $this->deleteFile($path);

        return back()->with('status', 'Photo deleted.');
    }
}
