<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Get query parameters
            $search = $request->input('search', '');
            $pageSize = (int) $request->input('pageSize', 10); // Default to 10 per page
            $pageNumber = (int) $request->input('pageNumber', 1); // Default to first page
            $sortBy = $request->input('sortBy', 'name'); // Default sort column
            $orderBy = $request->input('orderBy', 'asc'); // Default sort order
    
            // Ensure valid sort order
            if (!in_array($orderBy, ['asc', 'desc'])) {
                $orderBy = 'asc';
            }
    
            // Calculate the number of items to skip
            $skip = ($pageNumber - 1) * $pageSize;
    
            // Build the query dynamically
            $query = Product::query();
    
            // Apply search filter if provided
            if (!empty($search)) {
                $query->where('name', 'like', "%$search%");
            }
    
            // Apply sorting
            $query->orderBy($sortBy, $orderBy);
    
            // Manually implement pagination
            $total = $query->count(); // Total number of items
            $products = $query->skip($skip)->take($pageSize)->get(); // Fetch paginated data
    
            // Construct the response
            return response()->json([
                'success' => true,
                'data' => [
                    'products' => $products,
                    'pagination' => [
                        'total' => $total,
                        'pageSize' => $pageSize,
                        'pageNumber' => $pageNumber,
                        'totalPages' => ceil($total / $pageSize),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching products: ' . $e->getMessage(),
            ], 500);
        }
    }
    

    public function store(Request $request)
    {
        try {
            $validated = $this->validateProduct($request);
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $this->uploadImageToServer($request->file('image'));
            }

            $product = Product::create(array_merge($validated, ['image_url' => $imagePath]));

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully.',
                'data' => $product,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the product: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Product $product)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $product,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching the product: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, Product $product)
    {
        try {
            $validated = $this->validateProduct($request, true);

            if ($request->hasFile('image')) {

                if ($product->image_url) {
                    $this->deleteImageFromServer($product->image_url);
                }

                $product->image_url = $this->uploadImageToServer($request->file('image'));
            }

            $product->update(array_merge($validated, ['image_url' => $product->image_url]));

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully.',
                'data' => $product,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the product: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified product.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Product $product)
    {
        try {
            // Delete the product image if it exists
            if ($product->image_url) {
                $this->deleteImageFromServer($product->image_url);
            }

            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully.',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the product: ' . $e->getMessage(),
            ], 500);
        }
    }
    private function validateProduct(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'name' => $isUpdate ? 'sometimes|string|max:255' : 'required|string|max:255',
            'price' => $isUpdate ? 'sometimes|numeric|min:0' : 'required|numeric|min:0',
            'quantity' => $isUpdate ? 'sometimes|integer|min:1' : 'required|integer|min:1',
            'category_id' => $isUpdate ? 'sometimes|exists:categories,id' : 'required|exists:categories,id',
            'image' => 'sometimes|image|mimes:jpg,jpeg,png|max:2048',
        ];

        return Validator::make($request->all(), $rules)->validate();
    }

    /**
     * Upload an image to the server and return its path.
     *
     * @param \Illuminate\Http\UploadedFile $image
     * @return string
     */
    private function uploadImageToServer($image)
    {
        return $image->store('products', 'public'); // Saves to storage/app/public/products
    }

    /**
     * Delete an image from the server.
     *
     * @param string $imagePath
     * @return void
     */
    private function deleteImageFromServer($imagePath)
    {
        Storage::disk('public')->delete($imagePath);
    }
}
