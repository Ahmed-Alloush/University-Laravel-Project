<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class UserController extends Controller
{
    public function editMyProfile(Request $request, User $user)
    {
        // Validate the request inputs
        $validatedData = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'image_profile' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'address' => 'nullable|string|max:255',
        ]);

        // Check if the authenticated user is editing their own profile
        // if ($request->user()->id !== $user->id) {
        //     return response()->json(['message' => 'Unauthorized action'], 403);
        // }

        // Handle image upload to Cloudinary
        if ($request->hasFile('image_profile')) {
            // Delete the previous image if it exists (optional)
            if ($user->image_profile) {
                // Extract image ID from the URL and delete it from Cloudinary
                $this->deleteImageFromCloudinary($user->image_profile);
            }

            // Upload the new image to Cloudinary
            $uploadedImage = Cloudinary::upload($request->file('image_profile')->getRealPath(), [
                'folder' => 'Users',
            ]);

            // Store the public ID and secure URL
            $validatedData['image_profile'] = $uploadedImage->getSecurePath();
        }

        // Update user profile with validated data
        $user->update($validatedData);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ], 200);
    }

    /**
     * Extracts the image ID from Cloudinary URL and deletes the image.
     *
     * @param string $imageUrl
     * @return void
     */
    private function deleteImageFromCloudinary($imageUrl)
    {
        // Extract the public ID from the Cloudinary URL
        // Cloudinary URLs typically look like: https://res.cloudinary.com/{cloud_name}/image/upload/{public_id}
        // We need to extract the part after 'upload/' and before the extension
        $imageId = basename(parse_url($imageUrl, PHP_URL_PATH));

        // Delete the image using Cloudinary's destroy method
        Cloudinary::destroy($imageId);
    }
}
